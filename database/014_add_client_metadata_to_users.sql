START TRANSACTION;

USE idene_parfum;

ALTER TABLE users
    ADD COLUMN client_code VARCHAR(80) NULL AFTER email,
    ADD COLUMN fiscal_code VARCHAR(120) NULL AFTER client_code;

COMMIT;
