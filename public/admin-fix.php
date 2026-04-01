<?php
$host = '127.0.0.1';
$dbname = 'idene_parfum';
$user = 'root';
$pass = '';
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $email = 'admin@idene.tn';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Ensure ADMIN role
    $db->exec("INSERT IGNORE INTO roles (role_name) VALUES ('ADMIN')");
    $roleId = $db->query("SELECT id FROM roles WHERE role_name = 'ADMIN'")->fetchColumn();

    $userId = $db->query("SELECT id FROM users WHERE email = '$email'")->fetchColumn();
    if ($userId) {
        $db->exec("UPDATE users SET password_hash = '$hash', is_active = 1 WHERE id = $userId");
        echo "Admin updated. ID: $userId<br>";
    } else {
        $db->exec("INSERT INTO users (first_name, last_name, perfume_shop_name, phone, location, email, password_hash, is_active) VALUES ('Admin', 'Idene', 'IDENE PARFUM', '+21690000000', 'Tunis', '$email', '$hash', 1)");
        $userId = $db->lastInsertId();
        echo "Admin created. ID: $userId<br>";
    }

    $db->exec("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES ($userId, $roleId)");
    echo "Done! You can login with $email and admin123";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
