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

    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Auto-migrate if table is missing (1146)
            if ($e->getCode() === '42S02' || str_contains($e->getMessage(), 'Base table or view not found')) {
                $this->runMigrations();
                // Retry once
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }

            // Auto-migrate if column is missing (1054)
            if ($e->getCode() === '42S22' || str_contains($e->getMessage(), 'Unknown column')) {
                $this->runColumnFixes();
                // Retry once
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }

            error_log('Query failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Auto-run pending database migrations for missing columns
     */
    private function runColumnFixes(): void
    {
        $fixes = [
            "ALTER TABLE dojos ADD COLUMN address VARCHAR(500)",
            "ALTER TABLE dojos ADD COLUMN city VARCHAR(100)",
            "ALTER TABLE dojos ADD COLUMN state VARCHAR(2)",
            "ALTER TABLE dojos ADD COLUMN sensei_id INT NULL",
            "ALTER TABLE dojos ADD COLUMN logo VARCHAR(255)",
            "ALTER TABLE users ADD COLUMN dojo_id INT NULL",
            "ALTER TABLE users ADD COLUMN photo VARCHAR(255)",
            "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive', 'absent') DEFAULT 'active'",
            "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE dojos ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE graduations ADD COLUMN style_id INT NOT NULL DEFAULT 1",
            "ALTER TABLE graduations ADD COLUMN belt_name VARCHAR(100)",
            "ALTER TABLE graduations ADD COLUMN belt_color VARCHAR(50)",
            "ALTER TABLE graduations ADD COLUMN order_rank INT NOT NULL DEFAULT 0",
            "ALTER TABLE graduations ADD COLUMN requirements TEXT",
            "ALTER TABLE graduations ADD COLUMN minimum_time_months INT DEFAULT 0",
            "ALTER TABLE martial_arts_styles ADD COLUMN symbol VARCHAR(50)",
            "ALTER TABLE student_profiles ADD COLUMN registration_number VARCHAR(50)",
            "ALTER TABLE student_profiles ADD COLUMN birth_date DATE",
            "ALTER TABLE student_profiles ADD COLUMN status ENUM('active', 'inactive', 'absent') DEFAULT 'active'",
            "ALTER TABLE student_profiles ADD COLUMN notes TEXT"
        ];

        foreach ($fixes as $fixSql) {
            try {
                $this->connection->exec($fixSql);
            } catch (\PDOException $ex) {
                // Ignore if column already exists
            }
        }
    }

    /**
     * Auto-run pending database migrations
     */
    private function runMigrations(): void
    {
        $tables = [
            "CREATE TABLE IF NOT EXISTS martial_arts_styles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                symbol VARCHAR(50),
                origin_country VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS graduations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                style_id INT NOT NULL,
                belt_name VARCHAR(100) NOT NULL,
                belt_color VARCHAR(50) NOT NULL,
                order_rank INT NOT NULL,
                requirements TEXT,
                minimum_time_months INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_style_id (style_id),
                INDEX idx_order_rank (order_rank),
                FOREIGN KEY (style_id) REFERENCES martial_arts_styles(id) ON DELETE CASCADE,
                UNIQUE KEY unique_style_rank (style_id, order_rank)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS student_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                style_id INT NOT NULL,
                current_graduation_id INT NOT NULL,
                registration_number VARCHAR(50) UNIQUE,
                birth_date DATE,
                status ENUM('active', 'inactive', 'absent') DEFAULT 'active',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_style_id (style_id),
                INDEX idx_current_graduation (current_graduation_id),
                INDEX idx_status (status),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (style_id) REFERENCES martial_arts_styles(id) ON DELETE RESTRICT,
                FOREIGN KEY (current_graduation_id) REFERENCES graduations(id) ON DELETE RESTRICT,
                UNIQUE KEY unique_user_style (user_id, style_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS graduation_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_profile_id INT NOT NULL,
                graduation_id INT NOT NULL,
                promoted_by_sensei_id INT NOT NULL,
                promotion_date DATE NOT NULL,
                exam_score DECIMAL(5,2),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_student_profile (student_profile_id),
                INDEX idx_graduation (graduation_id),
                INDEX idx_promoted_by (promoted_by_sensei_id),
                INDEX idx_promotion_date (promotion_date),
                FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE,
                FOREIGN KEY (graduation_id) REFERENCES graduations(id) ON DELETE RESTRICT,
                FOREIGN KEY (promoted_by_sensei_id) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
                payment_type ENUM('monthly', 'exam', 'registration', 'other') DEFAULT 'monthly',
                due_date DATE NOT NULL,
                paid_date DATE,
                payment_method VARCHAR(50),
                reference_number VARCHAR(100),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_due_date (due_date),
                INDEX idx_paid_date (paid_date),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                featured_image VARCHAR(255),
                type ENUM('news', 'event') DEFAULT 'news',
                author_id INT NOT NULL,
                event_date DATETIME,
                event_location VARCHAR(255),
                published_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_author_id (author_id),
                INDEX idx_type (type),
                INDEX idx_published_at (published_at),
                INDEX idx_event_date (event_date),
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($tables as $sql) {
            try {
                $this->connection->exec($sql);
            } catch (\PDOException $e) {
                // Ignore if table exists
            }
        }

        try {
            $this->connection->exec("INSERT IGNORE INTO martial_arts_styles (name, description, origin_country) VALUES
            ('Shotokan', 'Estilo tradicional japonês', 'Japão'),
            ('Goju-ryu', 'Combina técnicas duras e suaves', 'Japão'),
            ('Wado-ryu', 'Enfatiza movimentos fluidos', 'Japão')");
        } catch (\PDOException $e) {
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
