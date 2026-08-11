<?php
namespace app\core;

class Database
{
    private static ?\PDO $instance = null;

    public static function connection(): \PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/config.php';
            $db = $config['db'];
            $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";

            self::$instance = new \PDO($dsn, $db['user'], $db['pass'], [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
            ]);
        }

        return self::$instance;
    }
}
