ALTER TABLE users
    ADD COLUMN admin_only_client TINYINT(1) NOT NULL DEFAULT 0 AFTER fiscal_code;
