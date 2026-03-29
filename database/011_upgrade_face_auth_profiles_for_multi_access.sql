START TRANSACTION;

USE idene_parfum;

CREATE TABLE IF NOT EXISTS face_auth_profiles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    profile_label VARCHAR(120) NULL,
    face_matrix_json LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_face_auth_profiles_user_id (user_id),
    CONSTRAINT fk_face_auth_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_unique_index := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'face_auth_profiles'
      AND INDEX_NAME = 'uniq_face_auth_user_id'
);
SET @drop_unique_sql := IF(
    @has_unique_index > 0,
    'ALTER TABLE face_auth_profiles DROP INDEX uniq_face_auth_user_id',
    'SELECT 1'
);
PREPARE drop_unique_stmt FROM @drop_unique_sql;
EXECUTE drop_unique_stmt;
DEALLOCATE PREPARE drop_unique_stmt;

SET @has_profile_label := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'face_auth_profiles'
      AND COLUMN_NAME = 'profile_label'
);
SET @add_label_sql := IF(
    @has_profile_label = 0,
    'ALTER TABLE face_auth_profiles ADD COLUMN profile_label VARCHAR(120) NULL AFTER user_id',
    'SELECT 1'
);
PREPARE add_label_stmt FROM @add_label_sql;
EXECUTE add_label_stmt;
DEALLOCATE PREPARE add_label_stmt;

SET @has_user_index := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'face_auth_profiles'
      AND INDEX_NAME = 'idx_face_auth_profiles_user_id'
);
SET @add_index_sql := IF(
    @has_user_index = 0,
    'ALTER TABLE face_auth_profiles ADD INDEX idx_face_auth_profiles_user_id (user_id)',
    'SELECT 1'
);
PREPARE add_index_stmt FROM @add_index_sql;
EXECUTE add_index_stmt;
DEALLOCATE PREPARE add_index_stmt;

COMMIT;
