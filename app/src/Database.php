<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('MYSQL_HOST') ?: '127.0.0.1';
            $db = getenv('MYSQL_DATABASE') ?: 'developmentdb';
            $user = getenv('MYSQL_USER') ?: 'developer';
            $pass = getenv('MYSQL_PASSWORD') ?: 'secret123';
            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$pdo;
    }
}
