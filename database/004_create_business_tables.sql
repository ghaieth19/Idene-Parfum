START TRANSACTION;

CREATE DATABASE IF NOT EXISTS idene_parfum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE idene_parfum;

CREATE TABLE IF NOT EXISTS roles (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_name VARCHAR(40) NOT NULL,
    role_description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_roles_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (role_name, role_description) VALUES
('CLIENT', 'Customer account'),
('EMPLOYE', 'Employee account'),
('MANAGER', 'Manager account'),
('DIRECTEUR', 'Director account'),
('ADMIN', 'Administrator account')
ON DUPLICATE KEY UPDATE
    role_description = VALUES(role_description),
    updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id TINYINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (user_id, role_id),
    KEY idx_user_roles_role (role_id),
    KEY idx_user_roles_assigned_by (assigned_by),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_user_roles_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS addresses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(80) NOT NULL DEFAULT 'principale',
    line1 VARCHAR(255) NOT NULL,
    line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(120) NOT NULL,
    region VARCHAR(120) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    country VARCHAR(80) NOT NULL DEFAULT 'Algerie',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_addresses_user (user_id),
    KEY idx_addresses_default (user_id, is_default),
    CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    perfume_catalog_id INT UNSIGNED NOT NULL,
    sku VARCHAR(60) DEFAULT NULL,
    barcode VARCHAR(100) DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_products_perfume_catalog_id (perfume_catalog_id),
    UNIQUE KEY uniq_products_sku (sku),
    UNIQUE KEY uniq_products_barcode (barcode),
    KEY idx_products_is_active (is_active),
    CONSTRAINT fk_products_perfume_catalog FOREIGN KEY (perfume_catalog_id) REFERENCES perfume_catalog(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (perfume_catalog_id, sku)
SELECT pc.id,
       CONCAT(
           pc.catalog_group, '-',
           pc.segment, '-',
           LPAD(pc.id, 5, '0')
       ) AS sku
FROM perfume_catalog pc
LEFT JOIN products p ON p.perfume_catalog_id = pc.id
WHERE p.id IS NULL;

CREATE TABLE IF NOT EXISTS product_prices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    sale_type VARCHAR(20) NOT NULL COMMENT 'GROS|DETAIL',
    price_dzd DECIMAL(10,2) NOT NULL,
    starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ends_at DATETIME DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_product_prices_product (product_id),
    KEY idx_product_prices_sale_type (sale_type),
    KEY idx_product_prices_period (starts_at, ends_at),
    CONSTRAINT fk_product_prices_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_product_prices_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_prices (product_id, sale_type, price_dzd)
SELECT p.id, 'DETAIL', pc.price_dzd
FROM products p
INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
LEFT JOIN product_prices pp ON pp.product_id = p.id AND pp.sale_type = 'DETAIL' AND pp.ends_at IS NULL
WHERE pp.id IS NULL;

CREATE TABLE IF NOT EXISTS stock (
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_ml DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    min_alert_ml DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id),
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO stock (product_id, quantity_ml, min_alert_ml)
SELECT p.id, 0.00, 0.00
FROM products p
LEFT JOIN stock s ON s.product_id = p.id
WHERE s.product_id IS NULL;

CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    movement_type VARCHAR(20) NOT NULL COMMENT 'IN|OUT|ADJUSTMENT|RETURN',
    quantity_ml DECIMAL(14,2) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    reference_type VARCHAR(40) DEFAULT NULL COMMENT 'ORDER|INVOICE|MANUAL|PURCHASE',
    reference_id BIGINT UNSIGNED DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_movements_product (product_id),
    KEY idx_stock_movements_reference (reference_type, reference_id),
    KEY idx_stock_movements_created_at (created_at),
    CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_stock_movements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(40) NOT NULL,
    customer_user_id BIGINT UNSIGNED NOT NULL,
    billing_address_id BIGINT UNSIGNED DEFAULT NULL,
    shipping_address_id BIGINT UNSIGNED DEFAULT NULL,
    sale_type VARCHAR(20) NOT NULL DEFAULT 'DETAIL' COMMENT 'GROS|DETAIL',
    status VARCHAR(30) NOT NULL DEFAULT 'BROUILLON' COMMENT 'BROUILLON|CONFIRMEE|EN_PREPARATION|EXPEDIEE|LIVREE|ANNULEE',
    notes TEXT DEFAULT NULL,
    subtotal_dzd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_dzd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_dzd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_orders_order_number (order_number),
    KEY idx_orders_customer (customer_user_id),
    KEY idx_orders_status (status),
    KEY idx_orders_created_at (created_at),
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_orders_billing_address FOREIGN KEY (billing_address_id) REFERENCES addresses(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_orders_shipping_address FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_orders_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_ml DECIMAL(14,2) NOT NULL,
    unit_price_dzd DECIMAL(10,2) NOT NULL,
    line_total_dzd DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'NON_PAYE' COMMENT 'NON_PAYE|PARTIEL|PAYE|ANNULE',
    subtotal_dzd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_dzd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_dzd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    due_date DATE DEFAULT NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_invoices_invoice_number (invoice_number),
    UNIQUE KEY uniq_invoices_order (order_id),
    KEY idx_invoices_status (status),
    KEY idx_invoices_issued_at (issued_at),
    CONSTRAINT fk_invoices_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id BIGINT UNSIGNED NOT NULL,
    payment_reference VARCHAR(100) DEFAULT NULL,
    method VARCHAR(30) NOT NULL COMMENT 'ESPECES|CARTE|VIREMENT|CHEQUE|AUTRE',
    amount_dzd DECIMAL(12,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'VALIDE' COMMENT 'VALIDE|EN_ATTENTE|ANNULE',
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_invoice (invoice_id),
    KEY idx_payments_status (status),
    KEY idx_payments_paid_at (paid_at),
    CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payments_received_by FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(30) DEFAULT NULL,
    new_status VARCHAR(30) NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    changed_by BIGINT UNSIGNED DEFAULT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_status_history_order (order_id),
    KEY idx_order_status_history_changed_at (changed_at),
    CONSTRAINT fk_order_status_history_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_status_history_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employees (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    employee_code VARCHAR(50) NOT NULL,
    job_title VARCHAR(120) NOT NULL,
    salary_dzd DECIMAL(12,2) DEFAULT NULL,
    hire_date DATE DEFAULT NULL,
    employment_status VARCHAR(20) NOT NULL DEFAULT 'ACTIF' COMMENT 'ACTIF|INACTIF|SUSPENDU',
    manager_user_id BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_employees_user (user_id),
    UNIQUE KEY uniq_employees_code (employee_code),
    KEY idx_employees_status (employment_status),
    CONSTRAINT fk_employees_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_employees_manager FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id BIGINT UNSIGNED DEFAULT NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id VARCHAR(80) DEFAULT NULL,
    payload_json JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_actor (actor_user_id),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_created_at (created_at),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE VIEW daily_cash_summary AS
SELECT
    DATE(p.paid_at) AS revenue_day,
    COUNT(*) AS payments_count,
    SUM(CASE WHEN p.status = 'VALIDE' THEN p.amount_dzd ELSE 0 END) AS validated_amount_dzd,
    SUM(CASE WHEN p.status = 'EN_ATTENTE' THEN p.amount_dzd ELSE 0 END) AS pending_amount_dzd,
    SUM(CASE WHEN p.status = 'ANNULE' THEN p.amount_dzd ELSE 0 END) AS canceled_amount_dzd
FROM payments p
GROUP BY DATE(p.paid_at);

COMMIT;
