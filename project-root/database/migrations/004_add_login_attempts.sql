-- Migration 004: Login attempt tracking for rate limiting
-- Run against a database that already has schema.sql + migrations 001-003 applied.

CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(160) NOT NULL,   -- e.g. 'investor:jane@example.com' or 'admin:jane@example.com'
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    successful TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_identifier_time (identifier, attempted_at)
) ENGINE=InnoDB;
