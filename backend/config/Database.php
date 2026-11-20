<?php

require_once __DIR__ . '/AppConfig.php';

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            AppConfig::get('host'),
            AppConfig::get('port'),
            AppConfig::get('database'),
            AppConfig::get('charset', 'utf8mb4')
        );

        self::$connection = new PDO($dsn, AppConfig::get('username'), AppConfig::get('password'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        return self::$connection;
    }
}
