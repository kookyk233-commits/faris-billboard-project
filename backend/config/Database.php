<?php
declare(strict_types=1);

require_once __DIR__ . '/Config.php';

/**
 * اتصال PDO واحد يعاد استخدامه في كل الطلب (Singleton بسيط).
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                Config::DB_HOST,
                Config::DB_NAME,
                Config::DB_CHARSET
            );

            self::$instance = new PDO($dsn, Config::DB_USER, Config::DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // استعلامات مُجهّزة حقيقية = حماية أقوى من SQL Injection
            ]);
        }

        return self::$instance;
    }
}
