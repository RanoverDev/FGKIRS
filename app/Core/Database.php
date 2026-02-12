<?php

namespace Core;

use PDO;
use PDOException;

/**
 * Database Class - Singleton Pattern
 * Manages PDO database connection for the FGKIRS system
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    /**
     * Private constructor to prevent direct instantiation
     * Establishes database connection using credentials from config.php
     */
    private function __construct()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_NAME
            );

            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new PDOException('Database connection error. Please check configuration.');
        }
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Get the singleton instance
     *
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the PDO connection object
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Execute a prepared statement with parameters
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the last inserted ID
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }
}
