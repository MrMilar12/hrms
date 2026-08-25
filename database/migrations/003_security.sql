-- Migration: 2FA (TOTP) + login rate-limiting/lockout columns on `users`.
-- Safe to run once against an existing installed database.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(64) NULL AFTER password_hash,
    ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER two_factor_secret,
    ADD COLUMN IF NOT EXISTS failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL AFTER failed_login_attempts;
