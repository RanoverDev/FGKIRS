-- FGKIRS Database Schema
-- Created for PHP 8.3+ MVC System

-- Create database (if needed)
-- CREATE DATABASE IF NOT EXISTS fgkirs01 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE fgkirs01;

-- Dojos Table
CREATE TABLE IF NOT EXISTS dojos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(500),
    city VARCHAR(100),
    state VARCHAR(2),
    sensei_id INT NULL,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sensei_id (sensei_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'sensei', 'aluno-colaborador', 'aluno') NOT NULL DEFAULT 'aluno',
    dojo_id INT NULL,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_dojo_id (dojo_id),
    FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for sensei_id in dojos (after users table exists)
ALTER TABLE dojos
ADD CONSTRAINT fk_dojos_sensei
FOREIGN KEY (sensei_id) REFERENCES users(id) ON DELETE SET NULL;

-- Sample Admin User (password: admin123)
INSERT INTO users (name, email, password, role, dojo_id, photo)
VALUES (
    'Administrador',
    'presidente@fgkirs.com.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    NULL,
    NULL
);

-- Sample Dojo
INSERT INTO dojos (name, address, city, state, logo)
VALUES (
    'Dojo Central Porto Alegre',
    'Rua Exemplo, 123',
    'Porto Alegre',
    'RS',
    NULL
);
