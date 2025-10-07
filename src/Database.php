<?php
declare(strict_types=1);

namespace Wartollex;

use DateTimeImmutable;
use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function initialize(string $path): void
    {
        if (self::$connection !== null) {
            return;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $dsn = 'sqlite:' . $path;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        self::$connection = new PDO($dsn, options: $options);
        self::migrate();
    }

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            throw new PDOException('Database not initialised. Call Database::initialize() first.');
        }

        return self::$connection;
    }

    private static function migrate(): void
    {
        $schema = [
            'CREATE TABLE IF NOT EXISTS users (
                user_id INTEGER PRIMARY KEY,
                username TEXT NOT NULL,
                language TEXT DEFAULT "en",
                monthly_points INTEGER DEFAULT 0,
                total_points INTEGER DEFAULT 0,
                monthly_period TEXT NOT NULL,
                invited_by INTEGER,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY(invited_by) REFERENCES users(user_id)
            )',
            'CREATE TABLE IF NOT EXISTS invites (
                user_id INTEGER NOT NULL,
                invited_user_id INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY(user_id, invited_user_id),
                FOREIGN KEY(user_id) REFERENCES users(user_id),
                FOREIGN KEY(invited_user_id) REFERENCES users(user_id)
            )',
            'CREATE TABLE IF NOT EXISTS task_progress (
                user_id INTEGER NOT NULL,
                task_id TEXT NOT NULL,
                task_date TEXT NOT NULL,
                completed INTEGER NOT NULL DEFAULT 0,
                completed_at TEXT,
                PRIMARY KEY(user_id, task_id, task_date),
                FOREIGN KEY(user_id) REFERENCES users(user_id)
            )'
        ];

        $pdo = self::connection();
        foreach ($schema as $sql) {
            $pdo->exec($sql);
        }
    }

    public static function now(): string
    {
        return (new DateTimeImmutable('now'))->format(DateTimeImmutable::ATOM);
    }
}
