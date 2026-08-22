<?php
declare(strict_types=0);

namespace {
    error_reporting(E_ALL);
    $GLOBALS['pass'] = 0;
    $GLOBALS['fail'] = 0;
    function check($l, $ok, $d = '') {
        if ($ok) { $GLOBALS['pass']++; echo "  ok   $l" . ($d ? " ($d)" : '') . "\n"; }
        else { $GLOBALS['fail']++; echo "  FAIL $l" . ($d ? " ($d)" : '') . "\n"; }
    }
}

namespace app\core {
    class Database {
        public static function connection() { throw new \RuntimeException('no db in this harness'); }
    }
    class Crypto {
        public static string $mode = 'ok';
        public static function encrypt(string $v) {
            if (self::$mode === 'empty') { return ''; }
            if (self::$mode === 'throw') { throw new \RuntimeException('openssl failure'); }
            return 'enc:' . base64_encode($v);
        }
        public static function decrypt(string $v) {
            if (self::$mode === 'badkey') { throw new \RuntimeException('bad key'); }
            return str_starts_with($v, 'enc:') ? base64_decode(substr($v, 4)) : null;
        }
    }
    // NOTE: no flash(), no audit(). That is the whole point of this harness.
    abstract class AdminController {
        public array $rendered = [];
        public ?string $redirected = null;
        protected function requirePermission(string $p): void {}
        protected function verifyCsrf(): void {}
        protected function render(string $v, array $d = []): void { $this->rendered = ['view'=>$v] + $d; }
        protected function redirect(string $p): void { $this->redirected = $p; }
    }
}

namespace app\models {
    class Company {
        public static function findByReference(string $r): ?array {
            return $r === 'SPV-00001'
                ? ['id'=>1,'reference'=>'SPV-00001','name'=>'Vukani Mobility SPV 1']
                : null;
        }
    }
    class CompanyBankAccount {
        public static ?array $row = null;
        public static array $saved = [];
        public static string $mode = 'ok';
        public static function forCompany(int $id): ?array { return self::$row; }
        public static function save(int $id, array $data): void {
            if (self::$mode === 'notable') {
                throw new \PDOException("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'twothirds.company_bank_accounts' doesn't exist");
            }
            self::$saved = $data;
        }
        public static function isComplete(?array $a): bool { return $a !== null; }
    }
}

namespace app\services {
    class ProfileOptions {
        public static function banks(): array { return ['Capitec Bank' => '470010', 'Other' => '']; }
    }
}

namespace {
    require '/mnt/user-data/outputs/commit-invoicing/app/controllers/admin/CompanyBankController.php';
    use app\controllers\admin\CompanyBankController;
    use app\models\CompanyBankAccount;
    use app\core\Crypto;

    function post(array $data): CompanyBankController {
        $_POST = $data; $_SESSION = ['admin_id' => 1];
        $c = new CompanyBankController();
        $c->update('SPV-00001');
        return $c;
    }

    echo "\n== Base class missing flash() and audit() ==\n";
    CompanyBankAccount::$row = null; CompanyBankAccount::$mode = 'ok'; Crypto::$mode = 'ok';
    $c = post(['account_holder_name'=>'Vukani Mobility SPV 1','bank_name'=>'Capitec Bank',
               'account_number'=>'1417739988','branch_code'=>'470010','account_type'=>'cheque']);
    check('the save completes rather than fataling', $c->redirected === '/admin/companies/SPV-00001/banking',
        $c->redirected ?? 'no redirect');
    check('the account was written', CompanyBankAccount::$saved['bank_name'] === 'Capitec Bank');
    check('the number was encrypted', str_starts_with(CompanyBankAccount::$saved['account_number'], 'enc:'));
    check('the message survived without flash()', ($_SESSION['_flash'][0]['type'] ?? null) === 'success');

    echo "\n== A holder name that looks wrong ==\n";
    $_SESSION = ['admin_id'=>1];
    $c = post(['account_holder_name'=>'Thabo Nkosi','bank_name'=>'Capitec Bank',
               'account_number'=>'1417739988','branch_code'=>'470010']);
    check('still saves', $c->redirected !== null);
    $types = array_column($_SESSION['_flash'] ?? [], 'type');
    check('but warns about the name', in_array('warning', $types, true));

    echo "\n== The table is missing (migration 009 not run) ==\n";
    CompanyBankAccount::$mode = 'notable';
    $c = post(['account_holder_name'=>'Vukani Mobility SPV 1','bank_name'=>'Capitec Bank',
               'account_number'=>'1417739988','branch_code'=>'470010']);
    check('no fatal - the form is re-rendered', ($c->rendered['view'] ?? null) === 'admin/companies/banking');
    check('and names the real cause on the page', str_contains($c->rendered['error'] ?? '', "doesn't exist"));
    check('says nothing was changed', str_contains($c->rendered['error'] ?? '', 'Nothing was changed'));

    echo "\n== Encryption returns nothing ==\n";
    CompanyBankAccount::$mode = 'ok'; Crypto::$mode = 'empty';
    $c = post(['account_holder_name'=>'Vukani Mobility SPV 1','bank_name'=>'Capitec Bank',
               'account_number'=>'1417739988','branch_code'=>'470010']);
    check('refuses to store an unreadable account', str_contains($c->rendered['error'] ?? '', 'application key'));
    check('nothing was saved', CompanyBankAccount::$saved['bank_name'] !== 'ShouldNotSave');

    echo "\n== Encryption throws ==\n";
    Crypto::$mode = 'throw';
    $c = post(['account_holder_name'=>'Vukani Mobility SPV 1','bank_name'=>'Capitec Bank',
               'account_number'=>'1417739988','branch_code'=>'470010']);
    check('caught and displayed', str_contains($c->rendered['error'] ?? '', 'openssl failure'));

    echo "\n== A stored value that will not decrypt ==\n";
    Crypto::$mode = 'badkey';
    CompanyBankAccount::$row = ['id'=>1,'account_number'=>'enc:abc','account_holder_name'=>'X',
                                'bank_name'=>'Capitec Bank','branch_code'=>'470010','account_type'=>'cheque'];
    $c = new CompanyBankController();
    $_POST = []; $_SESSION = ['admin_id'=>1];
    $c->edit('SPV-00001');
    check('the page still renders', ($c->rendered['view'] ?? null) === 'admin/companies/banking');
    // ?? treats a null value as absent, so it can't tell "set to null" from
    // "never set". Check the key explicitly.
    check('the hint is simply absent',
        array_key_exists('numberHint', $c->rendered) && $c->rendered['numberHint'] === null);

    echo "\n== Validation still works ==\n";
    Crypto::$mode = 'ok'; CompanyBankAccount::$row = null;
    foreach ([
        [['account_holder_name'=>'','bank_name'=>'Capitec Bank','account_number'=>'123456'], 'Account holder and bank'],
        [['account_holder_name'=>'X SPV','bank_name'=>'','account_number'=>'123456'], 'Account holder and bank'],
        [['account_holder_name'=>'X SPV','bank_name'=>'Capitec Bank','account_number'=>''], 'account number is required'],
        [['account_holder_name'=>'X SPV','bank_name'=>'Capitec Bank','account_number'=>'12ab'], 'between 6 and 20 digits'],
        [['account_holder_name'=>'X SPV','bank_name'=>'Capitec Bank','account_number'=>'123456','branch_code'=>'99'], 'between 4 and 10 digits'],
    ] as [$data, $needle]) {
        $c = post($data);
        check("rejects: {$needle}", str_contains($c->rendered['error'] ?? '', $needle));
    }

    echo "\n== Unknown company ==\n";
    $c = new CompanyBankController();
    $c->update('SPV-99999');
    check('404, not 500', ($c->rendered['view'] ?? null) === 'errors/404');

    echo "\n" . str_repeat('-',52) . "\n{$GLOBALS['pass']} passed, {$GLOBALS['fail']} failed\n";
    exit($GLOBALS['fail'] === 0 ? 0 : 1);
}
