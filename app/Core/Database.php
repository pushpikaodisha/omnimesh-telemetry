<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Singleton Database wrapper for SQLite with automatic schema migration.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $configPath = dirname(__DIR__, 2) . '/config/database.php';
        if (!file_exists($configPath)) {
            throw new RuntimeException("Database configuration file not found at: {$configPath}");
        }

        /** @var array $config */
        $config = require $configPath;
        $dbPath = $config['database'];
        $schemaPath = $config['schema'];
        $autoMigrate = $config['auto_migrate'] ?? true;

        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $needsMigration = !file_exists($dbPath) || filesize($dbPath) === 0;

        try {
            $this->pdo = new PDO("sqlite:{$dbPath}", null, null, $config['options'] ?? []);
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
        } catch (PDOException $e) {
            throw new RuntimeException("SQLite connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }

        if ($autoMigrate) {
            // Check if tables exist
            $checkStmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='devices'");
            if ($checkStmt->fetchColumn() === false || $needsMigration) {
                $this->runMigration($schemaPath);
            }
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Executes the SQL schema to initialize tables and initial seeds.
     */
    public function runMigration(string $schemaPath): void
    {
        if (!file_exists($schemaPath)) {
            throw new RuntimeException("Schema file not found at: {$schemaPath}");
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException("Schema file is empty or unreadable: {$schemaPath}");
        }

        $this->pdo->exec($sql);
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
