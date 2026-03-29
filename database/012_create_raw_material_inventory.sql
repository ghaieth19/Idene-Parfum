USE idene_parfum;

CREATE TABLE IF NOT EXISTS raw_material_inventory (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    material_category VARCHAR(40) NOT NULL COMMENT 'BASE|ALCOOL|COLORANT|BOUTEILLE|TICKET|BOUCHON|AUTRE',
    item_name VARCHAR(160) NOT NULL,
    unit_label VARCHAR(30) NOT NULL DEFAULT 'piece',
    quantity_in_stock DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    min_alert_quantity DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    unit_cost_dzd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    purchase_date DATE NOT NULL,
    supplier_name VARCHAR(160) DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_raw_material_inventory_date (purchase_date),
    KEY idx_raw_material_inventory_category (material_category),
    KEY idx_raw_material_inventory_name (item_name),
    CONSTRAINT fk_raw_material_inventory_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
