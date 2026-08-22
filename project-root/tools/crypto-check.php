<?php
/**
 * Encryption check.
 *
 *   php tools/crypto-check.php
 *
 * Reports which backend is in use, round-trips a value, and then tries to
 * decrypt every encrypted field already in the database — reporting which
 * backend wrote each and whether it can still be read.
 *
 * That last part is the point. Swapping the Crypto class is only safe if
 * existing values remain readable, and the only way to know is to try them. It
 * reads and prints nothing sensitive: values are reported as readable or not,
 * never shown.
 */

$root = dirname(__DIR__);

require_once "{$root}/app/core/Crypto.php";

use app\core\Crypto;

$problems = 0;

echo "\nEncryption check\n" . str_repeat('=', 62) . "\n\n";

// ------------------------------------------------------------
echo "Backend\n";

$available = Crypto::available();

printf("  %-22s %s\n", 'openssl', extension_loaded('openssl') ? "\033[32mloaded\033[0m" : "\033[31mNOT LOADED\033[0m");
printf("  %-22s %s\n", 'aes-256-gcm', $available ? "\033[32msupported\033[0m" : "\033[31mNOT SUPPORTED\033[0m");

if (!$available) {
    echo "\n\033[31mAES-256-GCM is unavailable, so nothing can be encrypted.\033[0m\n";
    echo "Enable the openssl extension. Any host that serves HTTPS has it.\n\n";
    exit(1);
}

// Stated plainly, because the previous class used it and the swap is the whole
// reason this tool exists.
echo "\n  This build uses AES-256-GCM and does not need ext-sodium.\n";
echo "  sodium is " . (extension_loaded('sodium') ? 'present but unused.' : 'absent, which is fine.') . "\n";

// ------------------------------------------------------------
echo "\nRound trip\n";

try {
    $sample = '1417739988';
    $encrypted = Crypto::encrypt($sample);
    $back = Crypto::decrypt($encrypted);

    if ($back === $sample) {
        echo "  \033[32mok\033[0m    encrypt then decrypt returns the original\n";
        echo "        stored as: " . Crypto::backendOf($encrypted) . ", " . strlen($encrypted) . " characters\n";
    } else {
        $problems++;
        echo "  \033[31mFAIL\033[0m  round trip did not return the original value\n";
    }
} catch (Throwable $e) {
    $problems++;
    echo "  \033[31mFAIL\033[0m  " . get_class($e) . ': ' . $e->getMessage() . "\n";
    echo "        Usually a missing or empty app_key in config/config.php.\n";
}

// ------------------------------------------------------------
echo "\nExisting encrypted data\n";

$config = is_file("{$root}/config/config.php") ? require "{$root}/config/config.php" : null;

if (!$config) {
    echo "  \033[33mwarn\033[0m  config/config.php not found — skipping.\n";
} else {
    try {
        $db = $config['db'] ?? $config;
        $pdo = new PDO(
            "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
            $db['user'],
            $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // table => [column => label]. Only fields this platform encrypts.
        $encryptedFields = [
            'user_bank_accounts'    => ['account_number' => 'investor bank accounts'],
            'company_bank_accounts' => ['account_number' => 'company bank accounts'],
            'user_tax_details'      => ['tax_number' => 'tax numbers', 'foreign_tax_number' => 'foreign tax numbers'],
        ];

        $anyRows = false;

        foreach ($encryptedFields as $table => $columns) {
            if (!$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetch()) {
                echo "  \033[90mskip\033[0m  {$table} — table not present\n";
                continue;
            }

            foreach ($columns as $column => $label) {
                $rows = $pdo->query(
                    "SELECT id, `{$column}` AS value FROM `{$table}` WHERE `{$column}` IS NOT NULL AND `{$column}` <> ''"
                )->fetchAll(PDO::FETCH_ASSOC);

                if (!$rows) {
                    echo "  \033[90m—\033[0m     {$label}: no rows\n";
                    continue;
                }

                $anyRows = true;
                $readable = 0;
                $unreadable = [];
                $backends = [];

                foreach ($rows as $row) {
                    $backend = Crypto::backendOf($row['value']);
                    $backends[$backend] = ($backends[$backend] ?? 0) + 1;

                    if (Crypto::decrypt($row['value']) !== null) {
                        $readable++;
                    } else {
                        $unreadable[] = (int) $row['id'];
                    }
                }

                $summary = [];
                foreach ($backends as $name => $count) {
                    $summary[] = "{$count} {$name}";
                }

                if ($unreadable === []) {
                    printf("  \033[32mok\033[0m    %s: %d readable (%s)\n", $label, $readable, implode(', ', $summary));
                } else {
                    $problems++;
                    printf("  \033[31mFAIL\033[0m  %s: %d readable, %d NOT (%s)\n",
                        $label, $readable, count($unreadable), implode(', ', $summary));
                    printf("        unreadable ids: %s\n", implode(', ', array_slice($unreadable, 0, 20)));
                    echo "        Either the app key changed, or these were written by a backend\n";
                    echo "        that is no longer available. They must be re-entered.\n";
                }
            }
        }

        if (!$anyRows) {
            echo "\n  Nothing encrypted is stored yet, so there is no migration risk:\n";
            echo "  whichever backend writes from now on will also be able to read.\n";
        }
    } catch (Throwable $e) {
        $problems++;
        echo "  \033[31mFAIL\033[0m  " . $e->getMessage() . "\n";
    }
}

// ------------------------------------------------------------
echo "\n" . str_repeat('=', 62) . "\n";

if ($problems) {
    echo "{$problems} problem(s). Fix these before relying on encrypted fields.\n\n";
    exit(1);
}

echo "Encryption is working and all stored values are readable.\n\n";
exit(0);
