-- ==========================================
-- DIVINESHIELD DATABASE SCHEMA
-- MAINPI Cloud System
-- ==========================================

CREATE DATABASE IF NOT EXISTS divineshield_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE divineshield_db;

-- ------------------------------------------
-- 1. USERS TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'church_leader') NOT NULL,
    position_title VARCHAR(100) NULL, -- Leader position/title (e.g., Pastor)
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    admin_pin VARCHAR(4) NULL, -- 4-digit PIN for Admin MFA (e.g., '1234')
    profile_picture VARCHAR(255) NULL DEFAULT NULL, -- Path to uploaded profile picture
    admin_message TEXT NULL, -- Optional message to administrator
    status ENUM('pending', 'active', 'inactive') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 2. CHURCH SITES TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS church_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    church_leader_id INT NOT NULL,
    church_name VARCHAR(150) NOT NULL,
    address TEXT NOT NULL,
    region VARCHAR(100) NOT NULL, -- e.g., 'Metro Manila', 'Visayas', 'Mindanao'
    province VARCHAR(100) NOT NULL,
    city_municipality VARCHAR(100) NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (church_leader_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_region (region)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 3. CHILDREN SUBMISSIONS TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS children_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    church_site_id INT NOT NULL,
    church_leader_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    gender ENUM('male', 'female') NOT NULL,
    birthdate DATE NOT NULL,
    guardian_name VARCHAR(100) NOT NULL,
    guardian_relationship VARCHAR(50) NOT NULL,
    initial_weight DECIMAL(5,2) NOT NULL, -- in kg
    initial_height DECIMAL(5,2) NOT NULL, -- in cm
    initial_bmi DECIMAL(4,2) NOT NULL,
    initial_bmi_status VARCHAR(50) NOT NULL,
    suggested_status ENUM('qualified', 'disqualified') NOT NULL,
    submission_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    review_notes TEXT NULL, -- Reason for disqualification/rejection
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (church_site_id) REFERENCES church_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (church_leader_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_submission_status (submission_status)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 4. CHILDREN (OFFICIAL REGISTRY) TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS children (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT UNIQUE NULL, -- References source submission
    church_site_id INT NOT NULL,
    rfid_tag VARCHAR(50) UNIQUE NULL, -- RFID attendance identifier
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    gender ENUM('male', 'female') NOT NULL,
    birthdate DATE NOT NULL,
    guardian_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'graduated', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES children_submissions(id) ON DELETE SET NULL,
    FOREIGN KEY (church_site_id) REFERENCES church_sites(id) ON DELETE CASCADE,
    INDEX idx_rfid_tag (rfid_tag),
    INDEX idx_child_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 5. NUTRITIONAL ASSESSMENTS TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS nutritional_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    child_id INT NOT NULL,
    encoder_id INT NOT NULL, -- Encoder/Staff recording assessment
    weight DECIMAL(5,2) NOT NULL,
    height DECIMAL(5,2) NOT NULL,
    bmi DECIMAL(4,2) NOT NULL,
    bmi_status VARCHAR(50) NOT NULL,
    assessment_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    FOREIGN KEY (encoder_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_assessment_date (assessment_date)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 6. FEEDING PROGRAMS TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS feeding_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    church_site_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (church_site_id) REFERENCES church_sites(id) ON DELETE CASCADE,
    INDEX idx_scheduled_date (scheduled_date)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 7. ATTENDANCE TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feeding_program_id INT NOT NULL,
    child_id INT NOT NULL,
    status ENUM('present', 'absent', 'excused') NOT NULL,
    logged_via ENUM('manual', 'rfid') DEFAULT 'manual',
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (feeding_program_id) REFERENCES feeding_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    INDEX idx_feeding_attendance (feeding_program_id, child_id)
) ENGINE=InnoDB;

-- ------------------------------------------
-- 8. ANNOUNCEMENTS TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    target_role ENUM('all', 'staff', 'church_leader') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------
-- 9. AUDIT LOGS TABLE
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- ==========================================
-- SEED DATA (Default Accounts)
-- ==========================================

-- Seed default accounts with password 'admin123' (bcrypt hash: $2y$10$kw00kdWe258GJMJwCiUeZu5DRaUn7bCAAMpV8Ziio/m0NXBC47TTy)
INSERT INTO users (username, password_hash, role, first_name, middle_name, last_name, email, phone, admin_pin, status) VALUES
-- Admin account
('admin', '$2y$10$kw00kdWe258GJMJwCiUeZu5DRaUn7bCAAMpV8Ziio/m0NXBC47TTy', 'admin', 'DivineShield', NULL, 'Admin', 'admin@mainpi.org', '09171234567', '1234', 'active'),
-- Staff/Encoder accounts
('encoder1', '$2y$10$kw00kdWe258GJMJwCiUeZu5DRaUn7bCAAMpV8Ziio/m0NXBC47TTy', 'staff', 'Maria', NULL, 'Santos', 'maria.encoder@mainpi.org', '09187654321', NULL, 'active'),
-- Church Leader accounts (Juan: Active, Pedro: Inactive/Pending approval)
('pastor_juan', '$2y$10$kw00kdWe258GJMJwCiUeZu5DRaUn7bCAAMpV8Ziio/m0NXBC47TTy', 'church_leader', 'Juan', NULL, 'Dela Cruz', 'juan.delacruz@church.org', '09191112222', NULL, 'active'),
('pastor_pedro', '$2y$10$kw00kdWe258GJMJwCiUeZu5DRaUn7bCAAMpV8Ziio/m0NXBC47TTy', 'church_leader', 'Pedro', NULL, 'Penduko', 'pedro.penduko@church.org', '09193334444', NULL, 'pending');

-- Seed a sample church site for Pastor Juan
INSERT INTO church_sites (church_leader_id, church_name, address, region, province, city_municipality, barangay, contact_number) VALUES
((SELECT id FROM users WHERE username = 'pastor_juan'), 'Grace Born-Again Church', '123 Salvation St.', 'Metro Manila', 'Metro Manila', 'Quezon City', 'Batasan Hills', '09191112222');
