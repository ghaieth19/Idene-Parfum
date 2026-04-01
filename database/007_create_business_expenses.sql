USE idene_parfum;

CREATE TABLE IF NOT EXISTS business_expenses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    expense_type VARCHAR(30) NOT NULL COMMENT 'RAW_MATERIAL|SALARY|TRANSPORT|RENT|OTHER',
    label VARCHAR(160) NOT NULL,
    amount_dzd DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_business_expenses_date (expense_date),
    KEY idx_business_expenses_type (expense_type),
    CONSTRAINT fk_business_expenses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
