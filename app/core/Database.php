<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor (private for singleton)
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Connect to database
     */
    private function connect(): void
    {
        try {
            $host = Config::get('DB_HOST', 'localhost');
            $port = Config::get('DB_PORT', '3306');
            $user = Config::get('DB_USER', 'root');
            $pass = Config::get('DB_PASS', '');
            $name = Config::get('DB_NAME', 'myapi');

            $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            Logger::info("Database connected successfully", [
                'host' => $host,
                'database' => $name
            ]);
        } catch (PDOException $e) {
            Logger::error("Database connection failed", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Execute query without returning results (INSERT, UPDATE, DELETE)
     */
    public function execute(string $query, array $params = []): int
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Logger::error("Query execution failed", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch single row
     */
    public function fetch(string $query, array $params = []): ?array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            Logger::error("Query fetch failed", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch multiple rows
     */
    public function fetchAll(string $query, array $params = []): array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            Logger::error("Query fetchAll failed", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Prevent serialization
     */
    public function __sleep(): array
    {
        throw new \Exception("Cannot serialize Database");
    }
}
