-- Migration 005: Investor password reset tokens
-- Run against a database that already has schema.sql + migrations 001-004 applied.
-- Mirrors the reset_token / reset_token_expires_at columns admin_users already has.

ALTER TABLE users
    ADD COLUMN reset_token VARCHAR(64) NULL AFTER verification_token_expires_at,
    ADD COLUMN reset_token_expires_at DATETIME NULL AFTER reset_token;
