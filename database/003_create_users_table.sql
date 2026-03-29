START TRANSACTION;

CREATE DATABASE IF NOT EXISTS idene_parfum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE idene_parfum;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    perfume_shop_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    location VARCHAR(255) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    matrice LONGTEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_email (email),
    UNIQUE KEY uniq_users_phone (phone),
    KEY idx_users_name (last_name, first_name),
    KEY idx_users_shop (perfume_shop_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
