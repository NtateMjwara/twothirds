-- Platform database schema for MariaDB
-- All tables use InnoDB for foreign key and transaction support.
-- Table order matters: each table only references tables already created above it.

CREATE TABLE companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(20) UNIQUE,        -- e.g. 'SPV-00842', generated after insert
    name VARCHAR(255) NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    incorporation_date DATE,
    registered_address VARCHAR(255),
    moi_reference VARCHAR(100),
    corporate_secretary VARCHAR(255),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    shares_issued INT UNSIGNED NOT NULL DEFAULT 0,
    nav_per_share DECIMAL(10,4) NOT NULL DEFAULT 0,
    arf_target DECIMAL(14,2) DEFAULT 0,
    arf_balance DECIMAL(14,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    make VARCHAR(100),
    model VARCHAR(100),
    year SMALLINT UNSIGNED,
    vin VARCHAR(50) UNIQUE,
    registration_number VARCHAR(50),
    purchase_price DECIMAL(14,2),
    purchase_date DATE,
    current_valuation DECIMAL(14,2),
    valuation_date DATE,
    valuation_method VARCHAR(100),
    mileage INT UNSIGNED,
    insurance_provider VARCHAR(100),
    insurance_status VARCHAR(50),
    roadworthy_status VARCHAR(50),
    asset_status ENUM('active', 'inactive', 'sold') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company (company_id)
) ENGINE=InnoDB;

CREATE TABLE commercial_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    operator VARCHAR(150),
    location VARCHAR(150),
    start_date DATE NOT NULL,
    end_date DATE NULL,
    utilisation_rate DECIMAL(5,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company (company_id)
) ENGINE=InnoDB;

CREATE TABLE financial_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    gross_revenue DECIMAL(14,2) NOT NULL DEFAULT 0,
    operating_costs DECIMAL(14,2) NOT NULL DEFAULT 0,
    net_operating_income DECIMAL(14,2) NOT NULL DEFAULT 0,
    arf_allocation DECIMAL(14,2) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_period (company_id, period_start)
) ENGINE=InnoDB;

-- Auth only. Everything identifying the person lives in the profile block below.
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ops', 'finance', 'super_admin') NOT NULL DEFAULT 'ops',
    reset_token VARCHAR(64) NULL,
    reset_token_expires_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    doc_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Profile block =====

-- 1:1 with users - enforced by the UNIQUE constraint on user_id
CREATE TABLE user_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30),
    date_of_birth DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 1:many - a user can have a residential and a postal address
CREATE TABLE user_addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    address_type ENUM('residential', 'postal') NOT NULL DEFAULT 'residential',
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) NOT NULL DEFAULT 'South Africa',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- 1:1 with users - the uploaded ID/passport scan itself lives in `documents`,
-- referenced here rather than duplicated
CREATE TABLE user_kyc (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    id_type ENUM('sa_id', 'passport') NOT NULL,
    id_number VARCHAR(50) NOT NULL,
    document_id INT UNSIGNED NULL,
    status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    rejection_reason VARCHAR(255) NULL,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id),
    FOREIGN KEY (verified_by) REFERENCES admin_users(id)
) ENGINE=InnoDB;

-- 1:many - is_primary marks which account settlements are paid out to
CREATE TABLE user_bank_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    account_holder_name VARCHAR(150) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(255) NOT NULL,
    branch_code VARCHAR(20),
    account_type ENUM('cheque', 'savings') NOT NULL DEFAULT 'cheque',
    is_primary TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ===== End profile block =====

CREATE TABLE commitments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(30) UNIQUE,        -- e.g. 'SPV-00842-C-1183', generated after insert
    user_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    shares_requested INT UNSIGNED NOT NULL,
    status ENUM('pending', 'confirmed', 'expired', 'withdrawn') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_status (company_id, status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Append-only ledger. Never UPDATE or DELETE a row here; corrections are new offsetting rows.
CREATE TABLE shareholdings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    shares INT NOT NULL,
    commitment_id INT UNSIGNED NULL,
    settled_by INT UNSIGNED NOT NULL,
    settled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (commitment_id) REFERENCES commitments(id),
    FOREIGN KEY (settled_by) REFERENCES admin_users(id),
    INDEX idx_company (company_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE watchlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_watch (user_id, company_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(100) NOT NULL,
    payload TEXT,
    read_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, read_at)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('admin', 'system') NOT NULL,
    actor_id INT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;
