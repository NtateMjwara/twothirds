<?php
namespace app\core;

/**
 * Encryption at rest for the few fields that need it: bank account numbers and
 * tax reference numbers.
 *
 * AES-256-GCM via OpenSSL. Nothing else.
 *
 * ---------------------------------------------------------------------------
 * Why not libsodium
 *
 * The original version of this class used libsodium. On a server where
 * ext-sodium isn't loaded, SODIUM_CRYPTO_SECRETBOX_KEYBYTES is simply an
 * undefined constant, and in PHP 8 that is a fatal Error rather than a warning -
 * so every save touching an encrypted field died with a bare 500.
 *
 * Sodium ships with PHP 7.2+ but is frequently disabled on shared hosting.
 * OpenSSL is not: it is required by far more of the ecosystem, and any host that
 * can serve HTTPS has it. Depending on the extension that is always there is
 * worth more here than a marginally nicer API.
 *
 * AES-256-GCM is authenticated encryption, which is the property that matters:
 * a stored value that has been tampered with fails to decrypt rather than
 * decrypting to something else.
 * ---------------------------------------------------------------------------
 *
 * Stored format:
 *
 *     v2.<base64( iv | tag | ciphertext )>
 *
 * The version prefix is three bytes and buys the ability to change cipher later
 * without every existing row becoming unreadable. It is deliberately the same
 * scheme the dual-backend version used, so values written by either class can be
 * read by the other where the extensions allow it.
 */
class Crypto
{
    private const TAG = 'v2.';
    private const CIPHER = 'aes-256-gcm';
    private const TAG_LENGTH = 16;

    public static function encrypt(string $plaintext): string
    {
        self::assertAvailable();

        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            // Never return an empty string here. A caller storing "" would save
            // a record that looks encrypted and can never be read back, and the
            // failure would only surface months later on an invoice.
            throw new \RuntimeException('openssl_encrypt failed: ' . (openssl_error_string() ?: 'no detail'));
        }

        // iv and tag are both fixed length, so decrypt can split them back out
        // without storing the lengths.
        return self::TAG . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Returns null rather than throwing when a value can't be read.
     *
     * Callers already handle null - they show "unreadable" or omit a hint - and
     * one corrupt row shouldn't take a page down.
     */
    public static function decrypt(string $payload): ?string
    {
        if ($payload === '' || !self::available()) {
            return null;
        }

        if (!str_starts_with($payload, self::TAG)) {
            // Written by the old sodium-only class. This build cannot read it,
            // and saying so beats a silent null that looks like corruption.
            error_log(
                'Crypto: found a value stored by the previous libsodium version. '
                . 'This build uses AES-256-GCM and cannot read it - the affected field '
                . 'must be re-entered.'
            );
            return null;
        }

        $raw = base64_decode(substr($payload, strlen(self::TAG)), true);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);

        if ($raw === false || strlen($raw) <= $ivLength + self::TAG_LENGTH) {
            return null;
        }

        $plaintext = openssl_decrypt(
            substr($raw, $ivLength + self::TAG_LENGTH),
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            substr($raw, 0, $ivLength),
            substr($raw, $ivLength, self::TAG_LENGTH)
        );

        return $plaintext === false ? null : $plaintext;
    }

    public static function available(): bool
    {
        return extension_loaded('openssl')
            && in_array(self::CIPHER, openssl_get_cipher_methods(), true);
    }

    /**
     * Which class wrote a stored value. Used by tools/crypto-check.php to report
     * what is in the database without decrypting anything.
     */
    public static function backendOf(string $payload): string
    {
        return str_starts_with($payload, self::TAG) ? 'openssl' : 'legacy-sodium';
    }

    /**
     * A 32-byte key.
     *
     * The configured key is usually a readable string rather than raw bytes, so
     * it is hashed to length. A 64-character hex key is decoded instead, so a
     * key generated as hex stays exactly the key it was - hashing it would
     * quietly throw away the entropy someone deliberately generated.
     */
    private static function key(): string
    {
        static $key = null;

        if ($key !== null) {
            return $key;
        }

        $configured = self::configuredKey();

        if ($configured === '') {
            throw new \RuntimeException(
                'No application encryption key found. Looked for '
                . implode(', ', self::KEY_NAMES)
                . ' in config/config.php (at any depth), in a $config global, in the environment, '
                . 'and as a defined constant. Run tools/find-key.php to see what your config '
                . 'actually contains, or tools/generate-key.php to create one.'
            );
        }

        if (strlen($configured) === 64 && ctype_xdigit($configured)) {
            return $key = hex2bin($configured);
        }

        if (strlen($configured) === 32) {
            return $key = $configured;
        }

        return $key = hash('sha256', $configured, true);
    }

    /** Names that could plausibly hold the key, checked at any nesting depth. */
    private const KEY_NAMES = [
        'app_key', 'appkey', 'encryption_key', 'crypto_key', 'secret_key',
        'app_secret', 'cipher_key', 'key', 'secret',
    ];

    /**
     * Finds the application key wherever it happens to live.
     *
     * Deliberately thorough, because config files differ more than they should:
     * some return an array, some define constants, some set a $config global and
     * return nothing at all - in which case `require` returns int(1) and a
     * lookup like $config['app_key'] quietly yields null.
     *
     * Order matters. An explicitly configured value beats an environment
     * variable, which beats a constant, so a deployment can override without
     * editing code.
     */
    private static function configuredKey(): string
    {
        static $resolved = null;

        if ($resolved !== null) {
            return $resolved;
        }

        // 1. config/config.php, searched at any depth.
        $path = __DIR__ . '/../../config/config.php';

        if (is_file($path)) {
            $config = require $path;

            if (is_array($config)) {
                $found = self::searchArray($config);
                if ($found !== null) {
                    return $resolved = $found;
                }
            }
        }

        // 2. A $config global, for config files that set one and return nothing.
        foreach (['config', 'CONFIG', 'settings'] as $name) {
            if (isset($GLOBALS[$name]) && is_array($GLOBALS[$name])) {
                $found = self::searchArray($GLOBALS[$name]);
                if ($found !== null) {
                    return $resolved = $found;
                }
            }
        }

        // 3. The environment.
        foreach (self::KEY_NAMES as $name) {
            $value = getenv(strtoupper($name));
            if (is_string($value) && $value !== '') {
                return $resolved = $value;
            }
        }

        // 4. Constants, which is how a good many older config files do it.
        foreach (self::KEY_NAMES as $name) {
            $constant = strtoupper($name);
            if (defined($constant) && is_string(constant($constant)) && constant($constant) !== '') {
                return $resolved = (string) constant($constant);
            }
        }

        return $resolved = '';
    }

    /**
     * Depth-limited search for a key-shaped setting.
     *
     * Depth is capped so a deeply nested or self-referential config can't turn
     * a missing key into a hang.
     */
    private static function searchArray(array $config, int $depth = 0): ?string
    {
        if ($depth > 3) {
            return null;
        }

        // Exact names first, at this level, before descending - so a top-level
        // app_key wins over something nested that merely matches.
        foreach (self::KEY_NAMES as $name) {
            foreach ($config as $configKey => $value) {
                if (is_string($value) && $value !== '' && strcasecmp((string) $configKey, $name) === 0) {
                    return $value;
                }
            }
        }

        foreach ($config as $value) {
            if (is_array($value)) {
                $found = self::searchArray($value, $depth + 1);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private static function assertAvailable(): void
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException(
                'The openssl extension is not loaded, so sensitive fields cannot be encrypted. '
                . 'Nothing will be stored.'
            );
        }

        if (!in_array(self::CIPHER, openssl_get_cipher_methods(), true)) {
            throw new \RuntimeException(
                'This build of OpenSSL does not support ' . self::CIPHER . '.'
            );
        }
    }
}
