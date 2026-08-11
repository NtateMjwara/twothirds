-- Migration 002: Company directors
-- Run against a database that already has schema.sql + migration 001 applied.

CREATE TABLE company_directors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role VARCHAR(100) NOT NULL DEFAULT 'Director',
    appointed_date DATE NULL,
    status ENUM('active', 'resigned') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company (company_id)
) ENGINE=InnoDB;
