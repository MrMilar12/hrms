<?php
// PDO connection singleton.

class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Prevent direct instantiation.
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require CONFIG_PATH . '/app.php';
            $db = $config['db'];

            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                if ($config['debug']) {
                    die('Database connection failed: ' . $e->getMessage());
                }
                die('Database connection failed.');
            }
        }

        return self::$instance;
    }

    private function __clone()
    {
    }
}
