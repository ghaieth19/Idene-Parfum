<?php
require __DIR__ . '/../vendor/autoload.php';

try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=idene_parfum', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $email = 'admin@idene.tn';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Ensure role exists
    $db->exec("INSERT IGNORE INTO roles (role_name) VALUES ('ADMIN')");
    $stmt = $db->query("SELECT id FROM roles WHERE role_name = 'ADMIN'");
    $roleId = $stmt->fetchColumn();

    // Check user
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, is_active = 1 WHERE id = ?");
        $stmt->execute([$hash, $userId]);
        echo "Admin password updated to 'admin123'.\n";
    } else {
        $stmt = $db->prepare("INSERT INTO users (first_name, last_name, perfume_shop_name, phone, location, email, password_hash, is_active) VALUES ('Admin', 'Idene', 'IDENE PARFUM', '+21690000000', 'Tunis', ?, ?, 1)");
        $stmt->execute([$email, $hash]);
        $userId = $db->lastInsertId();
        echo "Admin user created with email 'admin@idene.tn' and password 'admin123'.\n";
    }

    // Assign role
    $stmt = $db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->execute([$userId, $roleId]);
    echo "Admin role assigned.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
