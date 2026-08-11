-- Migration 001: Email verification for investor registration
-- Run against a database that already has the Phase 1-9 schema applied.
-- Do not edit schema.sql for this - schema.sql is now frozen as the Phase 1-9 baseline.

ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL AFTER status,
    ADD COLUMN verification_token VARCHAR(64) NULL AFTER email_verified_at,
    ADD COLUMN verification_token_expires_at DATETIME NULL AFTER verification_token;
