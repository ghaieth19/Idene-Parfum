START TRANSACTION;

USE idene_parfum;

ALTER TABLE password_reset_tokens
    ADD COLUMN reset_code_hash CHAR(64) NULL AFTER token_hash,
    ADD COLUMN code_expires_at DATETIME NULL AFTER expires_at;

CREATE INDEX idx_password_reset_code_expires ON password_reset_tokens (code_expires_at);

COMMIT;
