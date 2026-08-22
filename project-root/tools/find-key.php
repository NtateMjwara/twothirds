<?php
/**
 * Find the application encryption key.
 *
 *   php tools/find-key.php
 *
 * Reports what your config actually contains and where Crypto will look, so a
 * missing key becomes a fact rather than a guess.
 *
 * Values are never printed in full — only a length and the first four
 * characters, which is enough to recognise a key you already have without
 * putting it on a screen or in a scrollback buffer.
 */

$root = dirname(__DIR__);

// Same list Crypto uses.
const KEY_NAMES = [
    'app_key', 'appkey', 'encryption_key', 'crypto_key', 'secret_key',
    'app_secret', 'cipher_key', 'key', 'secret',
];

function preview(string $value): string
{
    return strlen($value) . ' chars, starts "' . substr($value, 0, 4) . '…"';
}

function walk(array $config, string $prefix = '', int $depth = 0): array
{
    if ($depth > 4) {
        return [];
    }

    $found = [];

    foreach ($config as $key => $value) {
        $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $found = array_merge($found, walk($value, $path, $depth + 1));
            continue;
        }

        if (!is_string($value) || $value === '') {
            continue;
        }

        foreach (KEY_NAMES as $name) {
            if (strcasecmp((string) $key, $name) === 0) {
                $found[$path] = $value;
                break;
            }
        }
    }

    return $found;
}

echo "\nLooking for an application key\n" . str_repeat('=', 62) . "\n";

// ------------------------------------------------------------
echo "\nconfig/config.php\n";

$path = "{$root}/config/config.php";

if (!is_file($path)) {
    echo "  \033[31mnot found\033[0m at {$path}\n";
} else {
    $config = require $path;

    if (!is_array($config)) {
        // The most common quiet failure: a config that sets a global or defines
        // constants and never returns anything. `require` then yields int(1),
        // and every array lookup against it is null.
        echo "  \033[33mIt does not return an array.\033[0m require() gave "
            . gettype($config) . ".\n";
        echo "  That means \$config['app_key'] is always null, which is very likely\n";
        echo "  why the key wasn't found. It probably sets a global or defines\n";
        echo "  constants instead - both are checked below.\n";
    } else {
        $found = walk($config);

        if ($found) {
            foreach ($found as $where => $value) {
                echo "  \033[32mfound\033[0m  {$where} — " . preview($value) . "\n";
            }
        } else {
            echo "  no key-shaped setting found. Top-level keys present:\n";
            echo '    ' . implode(', ', array_map('strval', array_keys($config))) . "\n";
        }
    }
}

// ------------------------------------------------------------
echo "\n\$config global\n";

$globalFound = false;
foreach (['config', 'CONFIG', 'settings'] as $name) {
    if (isset($GLOBALS[$name]) && is_array($GLOBALS[$name])) {
        foreach (walk($GLOBALS[$name], "\${$name}") as $where => $value) {
            echo "  \033[32mfound\033[0m  {$where} — " . preview($value) . "\n";
            $globalFound = true;
        }
    }
}
if (!$globalFound) {
    echo "  nothing\n";
}

// ------------------------------------------------------------
echo "\nEnvironment\n";

$envFound = false;
foreach (KEY_NAMES as $name) {
    $value = getenv(strtoupper($name));
    if (is_string($value) && $value !== '') {
        echo "  \033[32mfound\033[0m  " . strtoupper($name) . ' — ' . preview($value) . "\n";
        $envFound = true;
    }
}
if (!$envFound) {
    echo "  nothing\n";
}

// ------------------------------------------------------------
echo "\nDefined constants\n";

$constFound = false;
foreach (KEY_NAMES as $name) {
    $constant = strtoupper($name);
    if (defined($constant) && is_string(constant($constant)) && constant($constant) !== '') {
        echo "  \033[32mfound\033[0m  {$constant} — " . preview((string) constant($constant)) . "\n";
        $constFound = true;
    }
}
if (!$constFound) {
    echo "  nothing\n";
}

// ------------------------------------------------------------
// The old Crypto may well have carried the key itself.
echo "\nAny backup of the previous Crypto.php\n";

$candidates = array_merge(
    glob("{$root}/app/core/Crypto.php.*") ?: [],
    glob("{$root}/app/core/*Crypto*.bak") ?: [],
    glob("{$root}/**/Crypto.php.orig") ?: []
);

if (!$candidates) {
    echo "  none found alongside the current one.\n";
    echo "  \033[33mWorth checking anyway:\033[0m the previous class may have held the key\n";
    echo "  as a literal rather than reading it from config. Look in your backup, or\n";
    echo "  run: git show HEAD~1:app/core/Crypto.php | grep -i key\n";
} else {
    foreach ($candidates as $file) {
        echo "  {$file}\n";
        $source = file_get_contents($file);
        if (preg_match_all("/['\\\"]([A-Za-z0-9+\\/=_-]{16,})['\\\"]/", $source, $m)) {
            foreach (array_unique($m[1]) as $literal) {
                echo "      long string literal — " . preview($literal) . "\n";
            }
        }
    }
}

// ------------------------------------------------------------
echo "\n" . str_repeat('=', 62) . "\n";
echo "If nothing was found anywhere, and tools/crypto-check.php reports no\n";
echo "existing encrypted rows, you can safely generate a fresh key:\n\n";
echo "  php tools/generate-key.php\n\n";
echo "If encrypted rows DO exist, find the original key first. A new one will\n";
echo "not decrypt them, and there is no way back from that.\n\n";
