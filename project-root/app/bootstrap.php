<?php
session_start();

$config = require __DIR__ . '/config/config.php';

// Autoloads app\core\Foo -> app/core/Foo.php, app\controllers\Bar -> app/controllers/Bar.php, etc.
spl_autoload_register(function ($class) {
    $prefix = 'app\\';
    if (strpos($class, $prefix) === 0) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = __DIR__ . "/{$relative}.php";
        if (file_exists($file)) {
            require $file;
        }
    }
});

require __DIR__ . '/core/helpers.php';

if (($config['app']['env'] ?? 'production') === 'local') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
