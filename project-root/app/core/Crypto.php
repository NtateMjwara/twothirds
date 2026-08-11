<?php
namespace app\core;

// Encrypts/decrypts individual sensitive fields (currently: bank account numbers)
// before they touch the database. This protects the data if the database itself
// is ever exposed - a leaked backup or an SQL injection dump reads as ciphertext,
// not a real account number. It does NOT protect against someone who has both
// app-level access and the encryption key - that's the inherent limit of
// "encryption at rest," not a bug in this implementation.
class Crypto
{
    private static function key(): string
    {
        $config = require __DIR__ . '/../config/config.php';
        $encoded = $config['app']['encryption_key'] ?? '';

        if ($encoded === '') {
            throw new \RuntimeException(
                'encryption_key is not set in config.php. Generate one with: ' .
                'base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES))'
            );
        }

        $key = base64_decode($encoded, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \RuntimeException('encryption_key in config.php is not a valid base64-encoded 32-byte key.');
        }

        return $key;
    }

    public static function encrypt(string $plaintext): string
    {
        $key = self::key();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);

        // Nonce and ciphertext travel together so decrypt() only needs the one column
        return base64_encode($nonce . $ciphertext);
    }

    // Returns null rather than throwing on bad input - callers decide how to
    // handle a field that can't be decrypted (wrong key, corrupted data, etc.)
    public static function decrypt(string $encoded): ?string
    {
        $key = self::key();
        $decoded = base64_decode($encoded, true);

        if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        return $plaintext === false ? null : $plaintext;
    }
}
