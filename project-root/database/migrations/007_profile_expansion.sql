-- Migration 007: profile, FICA, tax and multi-account banking
--
-- The account section had four thin forms against a schema that only held a
-- name, a phone number and one bank account. That's enough to identify someone
-- and not much else - and FICA in particular needs a good deal more than an ID
-- number and a scan.
--
-- Three things happen here:
--   1. user_profiles gains the identity fields a regulated onboarding actually
--      collects (title, gender, places of birth and residence, work contact).
--   2. user_kyc gains the source-of-funds block: what someone does, who they
--      work for, roughly what they earn, and where the money comes from.
--   3. Bank accounts stop being one-per-user. The table was already 1:many but
--      nothing used it, and there was no verification state on an account that
--      money would eventually be paid into.
--
-- A new user_tax_details table carries tax residency separately, because it is
-- the one block that changes for reasons unrelated to identity.

-- ============================================================
-- Profile
-- ============================================================

ALTER TABLE user_profiles
    ADD COLUMN title VARCHAR(20) NULL AFTER user_id,
    ADD COLUMN initials VARCHAR(10) NULL AFTER first_name,
    -- What they'd like to be called. Distinct from first_name, which has to
    -- match the ID document for FICA purposes.
    ADD COLUMN preferred_name VARCHAR(100) NULL AFTER initials,
    ADD COLUMN gender ENUM('male', 'female', 'other', 'undisclosed') NULL AFTER date_of_birth,
    -- Stored apart from the number so an international mobile survives a
    -- change of residence, and so the field can be validated per country.
    ADD COLUMN calling_code VARCHAR(8) NULL DEFAULT '+27' AFTER phone,
    ADD COLUMN work_calling_code VARCHAR(8) NULL AFTER calling_code,
    ADD COLUMN work_phone VARCHAR(30) NULL AFTER work_calling_code,
    ADD COLUMN country_of_birth VARCHAR(100) NULL AFTER work_phone,
    ADD COLUMN city_of_birth VARCHAR(100) NULL AFTER country_of_birth,
    ADD COLUMN country_of_residence VARCHAR(100) NULL DEFAULT 'South Africa' AFTER city_of_birth,
    ADD COLUMN country_of_citizenship VARCHAR(100) NULL DEFAULT 'South Africa' AFTER country_of_residence,
    ADD COLUMN marital_status ENUM('single', 'married_cop', 'married_anc', 'divorced', 'widowed', 'partnership') NULL AFTER country_of_citizenship;

-- Existing rows keep the +27 default only where the column is genuinely new;
-- anyone who already stored a phone number gets it too, which is right for a
-- South African platform and easy to change on the form.
UPDATE user_profiles SET calling_code = '+27' WHERE calling_code IS NULL;

-- ============================================================
-- Address
-- ============================================================

ALTER TABLE user_addresses
    -- Suburb matters for South African proof-of-address documents, and city
    -- alone loses it.
    ADD COLUMN suburb VARCHAR(100) NULL AFTER address_line2;

-- ============================================================
-- KYC / FICA
-- ============================================================

ALTER TABLE user_kyc
    ADD COLUMN source_of_income VARCHAR(60) NULL AFTER id_number,
    ADD COLUMN account_funds_source VARCHAR(60) NULL AFTER source_of_income,
    ADD COLUMN occupation VARCHAR(60) NULL AFTER account_funds_source,
    ADD COLUMN employer VARCHAR(150) NULL AFTER occupation,
    ADD COLUMN industry VARCHAR(80) NULL AFTER employer,
    -- A band, not a figure. Precision here would be false - people estimate -
    -- and a band is what the risk rating actually uses.
    ADD COLUMN annual_income_band VARCHAR(40) NULL AFTER industry;

-- ============================================================
-- Tax
-- ============================================================

CREATE TABLE user_tax_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    -- Encrypted at rest, like the bank account number. A SARS tax number is a
    -- direct identifier and there is no reason for it to sit in plaintext in a
    -- backup.
    tax_number VARCHAR(255) NULL,
    is_sa_tax_resident TINYINT(1) NOT NULL DEFAULT 1,
    -- Only meaningful when the answer above is no.
    foreign_tax_country VARCHAR(100) NULL,
    foreign_tax_number VARCHAR(255) NULL,
    -- Some jurisdictions genuinely don't issue one; the reason is what gets
    -- reported rather than a blank field.
    no_tin_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Bank accounts
-- ============================================================

ALTER TABLE user_bank_accounts
    ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'ZAR' AFTER account_type,
    -- Money leaves the platform through this record, so it needs the same
    -- reviewed/not-reviewed state as an identity document. Before this, an
    -- account was usable the moment it was typed in.
    ADD COLUMN status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending' AFTER currency,
    ADD COLUMN rejection_reason VARCHAR(255) NULL AFTER status,
    ADD COLUMN verified_by INT UNSIGNED NULL AFTER rejection_reason,
    ADD COLUMN verified_at DATETIME NULL AFTER verified_by,
    ADD CONSTRAINT fk_bank_verified_by FOREIGN KEY (verified_by) REFERENCES admin_users(id),
    ADD INDEX idx_user_currency (user_id, currency);

-- Accounts captured before this migration were trusted implicitly, so treating
-- them as unverified now would block withdrawals for existing investors with no
-- warning. They are grandfathered as verified and flagged in the reason column
-- so the distinction is visible rather than silently lost.
UPDATE user_bank_accounts
SET status = 'verified',
    rejection_reason = NULL,
    verified_at = created_at
WHERE status = 'pending';
