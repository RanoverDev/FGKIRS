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

            // 1451 = exclusão bloqueada de propósito por ON DELETE RESTRICT; não é migração
            $driverCode = (int) ($e->errorInfo[1] ?? 0);

            // Auto-migrate if a child row points to a missing parent (1452)
            if ($driverCode === 1452) {
                error_log('Query failed with FK 1452, running column fixes: ' . $e->getMessage());
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
            "ALTER TABLE dojos ADD COLUMN phone_whatsapp VARCHAR(20) NULL",
            "ALTER TABLE dojos ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE graduations ADD COLUMN style_id INT NOT NULL DEFAULT 1",
            "ALTER TABLE graduations ADD COLUMN belt_name VARCHAR(100)",
            "ALTER TABLE graduations ADD COLUMN belt_color VARCHAR(50)",
            "ALTER TABLE graduations ADD COLUMN order_rank INT NOT NULL DEFAULT 0",
            "ALTER TABLE graduations ADD COLUMN requirements TEXT",
            "ALTER TABLE graduations ADD COLUMN minimum_time_months INT DEFAULT 0",
            "ALTER TABLE graduations ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE graduations ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE martial_arts_styles ADD COLUMN symbol VARCHAR(50)",
            "ALTER TABLE martial_arts_styles ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE martial_arts_styles ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE posts ADD COLUMN status ENUM('draft','published') DEFAULT 'published'",
            "ALTER TABLE student_profiles ADD COLUMN registration_number VARCHAR(50)",
            "ALTER TABLE student_profiles ADD COLUMN birth_date DATE",
            "ALTER TABLE student_profiles ADD COLUMN status ENUM('active', 'inactive', 'absent') DEFAULT 'active'",
            "ALTER TABLE student_profiles ADD COLUMN notes TEXT",
            "ALTER TABLE student_profiles ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE student_profiles ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE athlete_profiles ADD COLUMN fgkirs_registration INT NULL",
            "ALTER TABLE athlete_profiles ADD COLUMN cbki_registration VARCHAR(30) NULL",
            "ALTER TABLE athlete_profiles ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE athlete_profiles ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE posts ADD COLUMN video_url VARCHAR(500) NULL",
            "ALTER TABLE posts MODIFY COLUMN type ENUM('news','event','video','live') DEFAULT 'news'",
            "ALTER TABLE posts ADD COLUMN slug VARCHAR(255) NULL UNIQUE",
            "ALTER TABLE posts ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE posts ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE galleries ADD COLUMN slug VARCHAR(255) NULL UNIQUE",
            "ALTER TABLE galleries ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE galleries ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(255) NULL AFTER password",
            "ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL AFTER password_reset_token",
            "ALTER TABLE graduation_history ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE payments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE payments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE graduations DROP FOREIGN KEY graduations_ibfk_1",
            "ALTER TABLE graduations ADD CONSTRAINT fk_graduations_style FOREIGN KEY (style_id) REFERENCES martial_arts_styles(id) ON DELETE CASCADE",
            "ALTER TABLE student_profiles DROP FOREIGN KEY student_profiles_ibfk_2",
            "ALTER TABLE student_profiles ADD CONSTRAINT fk_student_profiles_style FOREIGN KEY (style_id) REFERENCES martial_arts_styles(id) ON DELETE RESTRICT",
            "ALTER TABLE athlete_profiles DROP FOREIGN KEY athlete_profiles_ibfk_2",
            "ALTER TABLE athlete_profiles ADD CONSTRAINT fk_athlete_profiles_style FOREIGN KEY (style_id) REFERENCES martial_arts_styles(id) ON DELETE SET NULL",
            "ALTER TABLE dojos ADD COLUMN instagram VARCHAR(255) NULL",
            "ALTER TABLE dojos ADD COLUMN facebook VARCHAR(255) NULL",
            "ALTER TABLE athlete_profiles ADD COLUMN is_para_karate TINYINT(1) NOT NULL DEFAULT 0 AFTER gender",
            "ALTER TABLE federation_profile ADD COLUMN legal_name VARCHAR(180) NULL",
            "ALTER TABLE federation_profile ADD COLUMN cnpj VARCHAR(20) NULL",
            "ALTER TABLE federation_profile ADD COLUMN website VARCHAR(180) NULL",
            "ALTER TABLE championship_athletes ADD COLUMN height SMALLINT UNSIGNED NULL AFTER weight"
        ];

        foreach ($fixes as $fixSql) {
            try {
                $this->connection->exec($fixSql);
            } catch (\PDOException $ex) {
                // Ignore if column already exists
            }
        }

        $this->ensureBlackBeltFlag();
    }

    /**
     * A flag nasce zerada, entao o backfill so pode rodar junto do ALTER que
     * cria a coluna - rodar sempre desfaria as correcoes manuais feitas depois
     * na tela de Graduacoes.
     */
    private function ensureBlackBeltFlag(): void
    {
        try {
            $this->connection->exec(
                "ALTER TABLE graduations ADD COLUMN is_black_belt TINYINT(1) NOT NULL DEFAULT 0"
            );
        } catch (\PDOException $e) {
            return;
        }

        try {
            $this->connection->exec(
                "UPDATE graduations
                 SET is_black_belt = 1
                 WHERE belt_color LIKE '%preta%'
                    OR belt_color LIKE '%black%'
                    OR belt_name LIKE '%preta%'
                    OR belt_name LIKE '%dan%'"
            );
        } catch (\PDOException $e) {
        }
    }

    /**
     * Auto-run pending database migrations
     */
    private function runMigrations(): void
    {
        $needsCategoryBackfill = !$this->tableExists('championship_categories');

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS post_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                is_featured TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_post_id (post_id),
                INDEX idx_featured (is_featured),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS federation_profile (
                id TINYINT UNSIGNED NOT NULL DEFAULT 1,
                legal_name VARCHAR(180),
                cnpj VARCHAR(20),
                website VARCHAR(180),
                whatsapp VARCHAR(20),
                phone VARCHAR(20),
                email VARCHAR(100),
                address VARCHAR(500),
                city VARCHAR(100),
                state VARCHAR(2),
                zip_code VARCHAR(9),
                facebook VARCHAR(255),
                instagram VARCHAR(255),
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS athlete_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                birth_date DATE,
                email VARCHAR(100),
                phone_whatsapp VARCHAR(20),
                gender ENUM('M','F','O') NULL,
                is_para_karate TINYINT(1) NOT NULL DEFAULT 0,
                weight DECIMAL(5,2) NULL COMMENT 'kg',
                height SMALLINT NULL COMMENT 'cm',
                style_id INT NULL,
                graduation_id INT NULL,
                fgkirs_registration INT NULL,
                cbki_registration VARCHAR(30) NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user (user_id),
                INDEX idx_style (style_id),
                INDEX idx_graduation (graduation_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (style_id) REFERENCES martial_arts_styles(id) ON DELETE SET NULL,
                FOREIGN KEY (graduation_id) REFERENCES graduations(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS galleries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                event_date DATE NOT NULL,
                cover_image VARCHAR(500),
                author_id INT NOT NULL,
                status ENUM('draft','published') DEFAULT 'published',
                published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_author_id (author_id),
                INDEX idx_status (status),
                INDEX idx_event_date (event_date),
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS gallery_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gallery_id INT NOT NULL,
                filename VARCHAR(500) NOT NULL,
                is_cover TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_gallery_id (gallery_id),
                INDEX idx_cover (is_cover),
                FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                email VARCHAR(100) NOT NULL,
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_time (ip_address, attempted_at),
                INDEX idx_email_time (email, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS board_positions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(100) NOT NULL,
                tier ENUM('primary','secondary','list') NOT NULL DEFAULT 'secondary',
                section VARCHAR(100) NULL,
                color VARCHAR(30) NOT NULL DEFAULT 'slate',
                allow_multiple TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS board_assignments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                position_id INT NOT NULL,
                user_id INT NULL,
                custom_name VARCHAR(100) NULL,
                custom_info VARCHAR(150) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                INDEX idx_position (position_id),
                FOREIGN KEY (position_id) REFERENCES board_positions(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS help_videos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                url VARCHAR(500) NOT NULL,
                author_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_author_id (author_id),
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS site_popup (
                id TINYINT UNSIGNED NOT NULL DEFAULT 1,
                is_active TINYINT(1) NOT NULL DEFAULT 0,
                image VARCHAR(255) NULL,
                image_format ENUM('square','portrait','story') NOT NULL DEFAULT 'portrait',
                image_alt VARCHAR(255) NULL,
                pix_enabled TINYINT(1) NOT NULL DEFAULT 1,
                pix_label VARCHAR(100) NULL,
                pix_key_type ENUM('cnpj','cpf','email','phone','random') NOT NULL DEFAULT 'cnpj',
                pix_key VARCHAR(255) NULL,
                whatsapp_enabled TINYINT(1) NOT NULL DEFAULT 1,
                whatsapp_label VARCHAR(100) NULL,
                whatsapp_phone VARCHAR(20) NULL,
                whatsapp_message VARCHAR(500) NULL,
                frequency_hours SMALLINT UNSIGNED NOT NULL DEFAULT 24,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS competition_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(180) NOT NULL,
                modality ENUM('kata','kumite') NOT NULL,
                entry_type ENUM('individual','team') NOT NULL DEFAULT 'individual',
                gender ENUM('M','F','X') NOT NULL DEFAULT 'X',
                age_min TINYINT UNSIGNED NULL,
                age_max TINYINT UNSIGNED NULL,
                age_label VARCHAR(60) NULL,
                belt_group ENUM('black','colored','any') NOT NULL DEFAULT 'any',
                weight_min DECIMAL(5,2) NULL,
                weight_max DECIMAL(5,2) NULL,
                team_size TINYINT UNSIGNED NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_lookup (entry_type, modality, gender, is_active),
                INDEX idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS championships (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NULL UNIQUE,
                description TEXT,
                location VARCHAR(255) NULL,
                event_date DATE NOT NULL,
                registration_start DATETIME NOT NULL,
                registration_end DATETIME NOT NULL,
                status ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
                post_id INT NULL,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_event_date (event_date),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS championship_categories (
                championship_id INT NOT NULL,
                category_id INT NOT NULL,
                PRIMARY KEY (championship_id, category_id),
                INDEX idx_category (category_id),
                FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE CASCADE,
                CONSTRAINT fk_champcat_category FOREIGN KEY (category_id) REFERENCES competition_categories(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS championship_athletes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                championship_id INT NOT NULL,
                dojo_id INT NOT NULL,
                user_id INT NULL,
                name VARCHAR(255) NOT NULL,
                gender ENUM('M','F') NOT NULL,
                birth_date DATE NOT NULL,
                style_id INT NULL,
                graduation_id INT NULL,
                belt_group ENUM('black','colored') NOT NULL DEFAULT 'colored',
                weight DECIMAL(5,2) NULL,
                height SMALLINT UNSIGNED NULL COMMENT 'cm',
                is_guest TINYINT(1) NOT NULL DEFAULT 0,
                is_para_karate TINYINT(1) NOT NULL DEFAULT 0,
                registered_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_championship_dojo (championship_id, dojo_id),
                INDEX idx_belt_group (belt_group),
                UNIQUE KEY unique_championship_user (championship_id, user_id),
                CONSTRAINT fk_champathletes_championship FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE RESTRICT,
                CONSTRAINT fk_champathletes_dojo FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE RESTRICT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (style_id) REFERENCES martial_arts_styles(id) ON DELETE SET NULL,
                FOREIGN KEY (graduation_id) REFERENCES graduations(id) ON DELETE SET NULL,
                FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS championship_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                championship_athlete_id INT NOT NULL,
                category_id INT NOT NULL,
                weight DECIMAL(5,2) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_athlete_category (championship_athlete_id, category_id),
                INDEX idx_category (category_id),
                FOREIGN KEY (championship_athlete_id) REFERENCES championship_athletes(id) ON DELETE CASCADE,
                CONSTRAINT fk_entries_category FOREIGN KEY (category_id) REFERENCES competition_categories(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS championship_teams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                championship_id INT NOT NULL,
                dojo_id INT NOT NULL,
                category_id INT NOT NULL,
                name VARCHAR(120) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_championship_dojo (championship_id, dojo_id),
                INDEX idx_category (category_id),
                CONSTRAINT fk_teams_championship FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE RESTRICT,
                CONSTRAINT fk_teams_dojo FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE RESTRICT,
                CONSTRAINT fk_teams_category FOREIGN KEY (category_id) REFERENCES competition_categories(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS championship_team_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                team_id INT NOT NULL,
                championship_athlete_id INT NOT NULL,
                position TINYINT UNSIGNED NOT NULL DEFAULT 1,
                UNIQUE KEY unique_team_athlete (team_id, championship_athlete_id),
                INDEX idx_athlete (championship_athlete_id),
                FOREIGN KEY (team_id) REFERENCES championship_teams(id) ON DELETE CASCADE,
                FOREIGN KEY (championship_athlete_id) REFERENCES championship_athletes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS championship_referees (
                id INT AUTO_INCREMENT PRIMARY KEY,
                championship_id INT NOT NULL,
                dojo_id INT NOT NULL,
                user_id INT NULL,
                name VARCHAR(255) NOT NULL,
                role ENUM('referee','table_judge','timekeeper','scorer') NOT NULL DEFAULT 'referee',
                qualification VARCHAR(100) NULL,
                notes VARCHAR(255) NULL,
                registered_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_championship_dojo (championship_id, dojo_id),
                CONSTRAINT fk_referees_championship FOREIGN KEY (championship_id) REFERENCES championships(id) ON DELETE RESTRICT,
                CONSTRAINT fk_referees_dojo FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE RESTRICT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE RESTRICT
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

        // Seed board positions if empty
        try {
            $count = $this->connection->query("SELECT COUNT(*) FROM board_positions")->fetchColumn();
            if ((int) $count === 0) {
                $this->connection->exec("INSERT INTO board_positions (title, tier, section, color, allow_multiple, sort_order) VALUES
                    ('Presidente', 'primary', NULL, 'red', 0, 1),
                    ('Vice-Presidente', 'primary', NULL, 'yellow', 0, 2),
                    ('Secretária', 'secondary', NULL, 'slate', 0, 3),
                    ('Diretora Financeira', 'secondary', NULL, 'slate', 0, 4),
                    ('Diretor Técnico', 'secondary', NULL, 'green', 0, 5),
                    ('Diretor de Arbitragem', 'secondary', NULL, 'slate', 0, 6),
                    ('Diretor Jurídico', 'secondary', NULL, 'slate', 0, 7),
                    ('Relações Públicas, Marketing e Eventos', 'secondary', NULL, 'red', 1, 8),
                    ('Conselho Fiscal', 'list', 'Conselho Fiscal', 'slate', 1, 9),
                    ('Suplentes do Conselho', 'list', 'Suplentes do Conselho', 'slate', 1, 10),
                    ('Comissão de Ética', 'list', 'Comissão de Ética', 'slate', 1, 11),
                    ('Departamento de Kobudo', 'list', 'Departamento de Kobudo', 'slate', 1, 12)
                ");
            }
        } catch (\PDOException $e) {
        }

        $this->ensureBlackBeltFlag();
        $this->seedCompetitionCategories();

        if ($needsCategoryBackfill) {
            $this->backfillChampionshipCategories();
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return (bool) $this->connection->query("SHOW TABLES LIKE " . $this->connection->quote($table))->fetchColumn();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Eventos criados antes da selecao de categorias por evento continuam com
     * todas as categorias ativas. Roda so na criacao da tabela: rodar de novo
     * devolveria categorias que o Presidente ja tirou.
     */
    private function backfillChampionshipCategories(): void
    {
        try {
            $this->connection->exec(
                "INSERT IGNORE INTO championship_categories (championship_id, category_id)
                 SELECT c.id, k.id FROM championships c
                 CROSS JOIN competition_categories k WHERE k.is_active = 1"
            );
        } catch (\PDOException $e) {
            error_log('backfillChampionshipCategories error: ' . $e->getMessage());
        }
    }

    /**
     * Catalogo inicial de categorias. As regras vivem no model; aqui so
     * persistimos, e apenas quando a tabela ainda esta vazia.
     */
    private function seedCompetitionCategories(): void
    {
        try {
            $count = $this->connection->query("SELECT COUNT(*) FROM competition_categories")->fetchColumn();
            if ((int) $count > 0) {
                return;
            }
        } catch (\PDOException $e) {
            return;
        }

        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO competition_categories
                    (name, modality, entry_type, gender, age_min, age_max, age_label,
                     belt_group, weight_min, weight_max, team_size, sort_order)
                 VALUES
                    (:name, :modality, :entry_type, :gender, :age_min, :age_max, :age_label,
                     :belt_group, :weight_min, :weight_max, :team_size, :sort_order)"
            );

            foreach (\Models\CompetitionCategory::defaultCatalog() as $row) {
                $stmt->execute($row);
            }
        } catch (\PDOException $e) {
            error_log('seedCompetitionCategories error: ' . $e->getMessage());
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
