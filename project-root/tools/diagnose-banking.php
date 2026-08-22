<?php
/**
 * Banking save diagnostic.
 *
 *   php tools/diagnose-banking.php SPV-00001
 *
 * Runs the same steps the POST to /admin/companies/{reference}/banking runs,
 * one at a time, with errors turned on — and prints the real exception instead
 * of a 500.
 *
 * A 500 in production tells you nothing: display_errors is off (correctly), so
 * the fatal goes to the error log and the browser gets a blank page. This walks
 * the same path in the open.
 *
 * It writes nothing. The final save step is rolled back.
 */

$root = dirname(__DIR__);
$reference = $argv[1] ?? null;

if (!$reference) {
    fwrite(STDERR, "Usage: php tools/diagnose-banking.php SPV-00001\n");
    exit(2);
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

$step = 0;
$failed = false;

function step(string $label, callable $fn): mixed
{
    global $step, $failed;
    $step++;

    if ($failed) {
        echo "  \033[90mskip\033[0m  {$label}\n";
        return null;
    }

    try {
        $result = $fn();
        echo "  \033[32mok\033[0m    {$label}\n";
        return $result;
    } catch (Throwable $e) {
        $failed = true;
        echo "  \033[31mFAIL\033[0m  {$label}\n";
        echo "        " . get_class($e) . ": " . $e->getMessage() . "\n";
        echo "        at " . str_replace(dirname(__DIR__) . '/', '', $e->getFile()) . ':' . $e->getLine() . "\n";
        return null;
    }
}

echo "\nBanking save diagnostic — {$reference}\n" . str_repeat('=', 62) . "\n\n";

// ------------------------------------------------------------
echo "Boot\n";

step('config/config.php loads', function () use ($root) {
    if (!is_file("{$root}/config/config.php")) {
        throw new RuntimeException('Not found.');
    }
    return require "{$root}/config/config.php";
});

step('autoloader and helpers', function () use ($root) {
    foreach (['app/core/helpers.php', 'app/core/url-helpers.php'] as $file) {
        if (is_file("{$root}/{$file}")) {
            require_once "{$root}/{$file}";
        }
    }
    spl_autoload_register(static function (string $class) use ($root) {
        $path = $root . '/' . str_replace('\\', '/', $class) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    });
    return true;
});

// ------------------------------------------------------------
echo "\nClasses this page needs\n";

foreach ([
    'app\core\Crypto'                => 'encrypts the account number',
    'app\core\Database'              => 'the connection',
    'app\core\AdminController'       => 'the base class, and audit()',
    'app\models\Company'             => 'looks up the SPV',
    'app\models\CompanyBankAccount'  => 'reads and writes the account',
    'app\services\ProfileOptions'    => 'the bank list on the form',
    'app\controllers\admin\CompanyBankController' => 'the controller itself',
] as $class => $why) {
    step("{$class} — {$why}", static function () use ($class) {
        if (!class_exists($class)) {
            throw new RuntimeException('Class not found. Is the file deployed, and does its path match the namespace?');
        }
        return true;
    });
}

step('AdminController::audit() exists', static function () {
    if (!method_exists('app\core\AdminController', 'audit')) {
        throw new RuntimeException(
            'Missing. Your AdminController predates the admin refresh; the save calls $this->audit() and will fatal.'
        );
    }
    return true;
});

// ------------------------------------------------------------
echo "\nExtensions the write path uses\n";

step('openssl — Crypto::encrypt needs it', static function () {
    if (!extension_loaded('openssl')) {
        throw new RuntimeException('Not loaded. Encrypting the account number will fatal.');
    }
    return true;
});

step('pcre with UTF-8 support — used to truncate names', static function () {
    if (@preg_match('/^.{0,10}/us', 'Café') === false) {
        throw new RuntimeException('PCRE has no UTF-8 support on this build.');
    }
    return true;
});

// Not required — there should be no mb_* left on this path — but worth knowing.
echo '  ' . (extension_loaded('mbstring') ? "\033[32mok\033[0m    " : "\033[33mwarn\033[0m  ")
   . "mbstring (not required; the code has fallbacks)\n";

// ------------------------------------------------------------
echo "\nDatabase\n";

$pdo = step('connect', static function () use ($root) {
    $settings = require "{$root}/config/config.php";
    $db = $settings['db'] ?? $settings;
    return new PDO(
        "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
        $db['user'],
        $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
});

step('migration 009 has run — company_bank_accounts exists', static function () use ($pdo) {
    if (!$pdo->query("SHOW TABLES LIKE 'company_bank_accounts'")->fetch()) {
        throw new RuntimeException(
            'Table missing. Run database/migrations/009_agreements_and_invoicing.sql. '
            . 'This is the most common cause of a 500 here.'
        );
    }
    return true;
});

step('legal_documents exists', static function () use ($pdo) {
    if (!$pdo->query("SHOW TABLES LIKE 'legal_documents'")->fetch()) {
        throw new RuntimeException('Table missing — migration 009 only partly applied.');
    }
    return true;
});

$company = step("company {$reference} exists", static function () use ($pdo, $reference) {
    $stmt = $pdo->prepare("SELECT id, reference, name FROM companies WHERE reference = ?");
    $stmt->execute([$reference]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('No company with that reference.');
    }
    return $row;
});

// ------------------------------------------------------------
echo "\nThe write itself\n";

step('read the existing account (the GET path)', static function () use ($company) {
    return \app\models\CompanyBankAccount::forCompany((int) $company['id']);
});

$encrypted = step('encrypt a test account number', static function () {
    $out = \app\core\Crypto::encrypt('1234567890');
    if (!$out) {
        throw new RuntimeException('Crypto::encrypt returned nothing. Check the app key in config.');
    }
    return $out;
});

step('decrypt it again', static function () use ($encrypted) {
    $back = \app\core\Crypto::decrypt($encrypted);
    if ($back !== '1234567890') {
        throw new RuntimeException(
            'Round trip failed. The app key may have changed since existing records were written.'
        );
    }
    return true;
});

step('insert and roll back', static function () use ($pdo, $company, $encrypted) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "INSERT INTO company_bank_accounts
                (company_id, account_holder_name, bank_name, account_number, branch_code, account_type)
             VALUES (?, ?, ?, ?, ?, 'cheque')
             ON DUPLICATE KEY UPDATE bank_name = VALUES(bank_name)"
        )->execute([(int) $company['id'], $company['name'], 'Diagnostic Bank', $encrypted, '000000']);
    } finally {
        // Never leaves anything behind, whether it worked or not.
        $pdo->rollBack();
    }
    return true;
});

step('write an audit entry and roll back', static function () use ($pdo, $company) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
             VALUES ('admin', 1, 'diagnostic', 'companies', ?, 'diagnostic run')"
        )->execute([(int) $company['id']]);
    } finally {
        $pdo->rollBack();
    }
    return true;
});

// ------------------------------------------------------------
echo "\n" . str_repeat('=', 62) . "\n";

if ($failed) {
    echo "Stopped at step {$step}. The message above is the real cause of the 500.\n\n";
    exit(1);
}

echo "Every step passed. If the page still 500s, the failure is outside this path -\n";
echo "check the web server error log for the exact line:\n\n";
echo "  tail -50 /var/log/apache2/error.log\n";
echo "  tail -50 ~/logs/error_log            # cPanel and similar\n\n";
exit(0);
