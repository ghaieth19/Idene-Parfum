START TRANSACTION;

USE idene_parfum;

ALTER TABLE users
    ADD COLUMN matrice LONGTEXT NULL AFTER password_hash;

COMMIT;
