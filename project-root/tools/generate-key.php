<?php
/**
 * Generate an application encryption key.
 *
 *   php tools/generate-key.php
 *
 * Prints a 64-character hex key — 32 random bytes — to paste into
 * config/config.php. It writes nothing itself, deliberately: overwriting a live
 * key would make every encrypted value in the database unreadable, and that
 * should never be one keystroke away.
 */

if (PHP_SAPI !== 'cli') {
    // A key generator reachable over the web is a key generator someone else
    // can watch.
    http_response_code(404);
    exit('Not found');
}

$key = bin2hex(random_bytes(32));

echo "\nApplication encryption key\n" . str_repeat('=', 62) . "\n\n";
echo "  {$key}\n\n";
echo "Put it in config/config.php:\n\n";
echo "    'app_key' => '{$key}',\n\n";
echo str_repeat('-', 62) . "\n";
echo "Once anything has been encrypted with this key, changing it makes every\n";
echo "stored bank account and tax number unreadable. There is no recovery from\n";
echo "that - the values would have to be re-entered by hand.\n\n";
echo "Keep it out of version control, and keep a copy somewhere you would still\n";
echo "have it if the server were lost.\n\n";
