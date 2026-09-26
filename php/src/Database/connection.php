<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    DB_HOST,
                    DB_PORT,
                    DB_DATABASE
                );

                self::$connection = new PDO(
                    $dsn,
                    DB_USER,
                    DB_PASSWORD,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                throw new PDOException("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$connection;
    }

    public static function close(): void
    {
        self::$connection = null;
    }
}
