START TRANSACTION;

USE idene_parfum;

INSERT INTO users (
    id,
    first_name,
    last_name,
    perfume_shop_name,
    phone,
    location,
    email,
    password_hash,
    is_active
) VALUES (
    1,
    'Client',
    'Idene',
    'Parfumerie Atlas',
    '+21600000000',
    'Tunis',
    'client@idene.tn',
    '$2y$10$demo_hash_placeholder',
    1
)
ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    perfume_shop_name = VALUES(perfume_shop_name),
    phone = VALUES(phone),
    location = VALUES(location),
    email = VALUES(email),
    is_active = VALUES(is_active);

INSERT INTO orders (
    id,
    order_number,
    customer_user_id,
    sale_type,
    status,
    notes,
    subtotal_dzd,
    total_dzd,
    created_at,
    updated_at
)
SELECT
    1,
    'CMD-2026-1001',
    1,
    'DETAIL',
    'CONFIRMEE',
    '{"last_name":"Client","first_name":"Idene","phone":"+21600000000","shop":"Parfumerie Atlas"}',
    54.00,
    54.00,
    '2026-03-24 10:00:00',
    '2026-03-24 10:00:00'
WHERE NOT EXISTS (SELECT 1 FROM orders WHERE id = 1);

INSERT INTO orders (
    id,
    order_number,
    customer_user_id,
    sale_type,
    status,
    notes,
    subtotal_dzd,
    total_dzd,
    created_at,
    updated_at
)
SELECT
    2,
    'CMD-2026-1002',
    1,
    'DETAIL',
    'CONFIRMEE',
    '{"last_name":"Client","first_name":"Idene","phone":"+21600000000","shop":"Parfumerie Atlas"}',
    30.00,
    30.00,
    '2026-03-25 15:30:00',
    '2026-03-25 15:30:00'
WHERE NOT EXISTS (SELECT 1 FROM orders WHERE id = 2);

INSERT INTO order_items (order_id, product_id, quantity_ml, unit_price_dzd, line_total_dzd)
SELECT 1, 1, 1, 27.00, 27.00
WHERE NOT EXISTS (SELECT 1 FROM order_items WHERE order_id = 1 AND product_id = 1);

INSERT INTO order_items (order_id, product_id, quantity_ml, unit_price_dzd, line_total_dzd)
SELECT 1, 2, 1, 27.00, 27.00
WHERE NOT EXISTS (SELECT 1 FROM order_items WHERE order_id = 1 AND product_id = 2);

INSERT INTO order_items (order_id, product_id, quantity_ml, unit_price_dzd, line_total_dzd)
SELECT 2, 3, 2, 15.00, 30.00
WHERE NOT EXISTS (SELECT 1 FROM order_items WHERE order_id = 2 AND product_id = 3);

INSERT INTO invoices (
    id,
    invoice_number,
    order_id,
    status,
    subtotal_dzd,
    total_dzd,
    issued_at,
    created_at,
    updated_at
)
SELECT
    1,
    'FAC-2026-2001',
    1,
    'PAYE',
    54.00,
    54.00,
    '2026-03-24 10:05:00',
    '2026-03-24 10:05:00',
    '2026-03-24 10:05:00'
WHERE NOT EXISTS (SELECT 1 FROM invoices WHERE id = 1);

INSERT INTO invoices (
    id,
    invoice_number,
    order_id,
    status,
    subtotal_dzd,
    total_dzd,
    issued_at,
    created_at,
    updated_at
)
SELECT
    2,
    'FAC-2026-2002',
    2,
    'NON_PAYE',
    30.00,
    30.00,
    '2026-03-25 15:40:00',
    '2026-03-25 15:40:00',
    '2026-03-25 15:40:00'
WHERE NOT EXISTS (SELECT 1 FROM invoices WHERE id = 2);

INSERT INTO payments (invoice_id, method, amount_dzd, status, paid_at, created_at)
SELECT 1, 'ESPECES', 54.00, 'VALIDE', '2026-03-24 10:10:00', '2026-03-24 10:10:00'
WHERE NOT EXISTS (SELECT 1 FROM payments WHERE invoice_id = 1);

COMMIT;
