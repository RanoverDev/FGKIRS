-- FGKIRS Phase 2: Graduation System & Enhanced Features
-- Martial Arts Styles, Belt Progression, Student Profiles, Payments

-- Martial Arts Styles Table
CREATE TABLE IF NOT EXISTS martial_arts_styles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    origin_country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Graduations/Belts Table
CREATE TABLE IF NOT EXISTS graduations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Profiles Table
CREATE TABLE IF NOT EXISTS student_profiles (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Graduation History Table
CREATE TABLE IF NOT EXISTS graduation_history (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts/News Table
CREATE TABLE IF NOT EXISTS posts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert Martial Arts Styles
INSERT INTO martial_arts_styles (name, description, origin_country) VALUES
('Shotokan', 'Estilo tradicional japonês focado em base forte e técnicas lineares', 'Japão'),
('Goju-ryu', 'Estilo que combina técnicas duras e suaves (Go-Ju)', 'Japão'),
('Wado-ryu', 'Estilo que enfatiza movimentos fluidos e evasão', 'Japão'),
('Shito-ryu', 'Estilo que combina elementos de Shuri-te e Naha-te', 'Japão'),
('Kyokushin', 'Estilo de contato total conhecido pela resistência física', 'Japão');

-- Insert Graduations for Shotokan (style_id = 1)
INSERT INTO graduations (style_id, belt_name, belt_color, order_rank, minimum_time_months) VALUES
(1, '10º Kyu - Branca', '#FFFFFF', 0, 0),
(1, '9º Kyu - Amarela', '#FFD700', 1, 3),
(1, '8º Kyu - Vermelha', '#FF0000', 2, 3),
(1, '7º Kyu - Laranja', '#FF8C00', 3, 3),
(1, '6º Kyu - Verde', '#008000', 4, 4),
(1, '5º Kyu - Roxa', '#800080', 5, 4),
(1, '4º Kyu - Marrom Claro', '#8B4513', 6, 6),
(1, '3º Kyu - Marrom Médio', '#654321', 7, 6),
(1, '2º Kyu - Marrom Escuro', '#3E2723', 8, 6),
(1, '1º Kyu - Marrom Listrada', '#3E2723', 9, 8),
(1, '1º Dan - Preta', '#000000', 10, 12),
(1, '2º Dan - Preta', '#000000', 11, 24),
(1, '3º Dan - Preta', '#000000', 12, 36);

-- Insert Graduations for Goju-ryu (style_id = 2)
INSERT INTO graduations (style_id, belt_name, belt_color, order_rank, minimum_time_months) VALUES
(2, '10º Kyu - Branca', '#FFFFFF', 0, 0),
(2, '9º Kyu - Amarela', '#FFD700', 1, 3),
(2, '8º Kyu - Laranja', '#FF8C00', 2, 3),
(2, '7º Kyu - Verde', '#008000', 3, 4),
(2, '6º Kyu - Azul', '#0000FF', 4, 4),
(2, '5º Kyu - Roxa', '#800080', 5, 6),
(2, '4º Kyu - Marrom', '#8B4513', 6, 6),
(2, '3º Kyu - Marrom', '#654321', 7, 6),
(2, '2º Kyu - Marrom', '#3E2723', 8, 8),
(2, '1º Kyu - Marrom Listrada', '#3E2723', 9, 8),
(2, '1º Dan - Preta', '#000000', 10, 12);

-- Create trigger to auto-update overdue payments
DELIMITER //
CREATE TRIGGER update_overdue_payments
BEFORE UPDATE ON payments
FOR EACH ROW
BEGIN
    IF NEW.status = 'pending' AND NEW.due_date < CURDATE() THEN
        SET NEW.status = 'overdue';
    END IF;
END//
DELIMITER ;

-- Add status column to users table if not exists
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'absent') DEFAULT 'active' AFTER dojo_id;

-- Sample Student Profiles (linking existing users to styles and graduations)
-- Assuming user_id 2 exists and is a student
INSERT IGNORE INTO student_profiles (user_id, style_id, current_graduation_id, registration_number, status) VALUES
(2, 1, 1, 'FGKI-2024-001', 'active');

-- Sample Payments
INSERT IGNORE INTO payments (user_id, amount, status, payment_type, due_date) VALUES
(2, 150.00, 'pending', 'monthly', DATE_ADD(CURDATE(), INTERVAL 15 DAY)),
(2, 150.00, 'paid', 'monthly', DATE_SUB(CURDATE(), INTERVAL 30 DAY));
