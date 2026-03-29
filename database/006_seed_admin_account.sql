USE idene_parfum;

INSERT INTO users (
    first_name,
    last_name,
    perfume_shop_name,
    phone,
    location,
    email,
    password_hash,
    is_active
)
SELECT
    'Admin',
    'Idene',
    'IDENE PARFUM',
    '+21690000000',
    'Tunis',
    'admin@idene.tn',
    '$2y$10$uyz1ltfwqirgR0crCPMWguovIH7/hiLQqlC0VIeeX3dpWAIHXTs1i',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@idene.tn'
);

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
INNER JOIN roles r ON r.role_name = 'ADMIN'
WHERE u.email = 'admin@idene.tn';
