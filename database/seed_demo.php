<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    $name = 'Demo User';
    $email = 'demo@example.com';
    $password = 'Password123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if user already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $pdo->prepare('UPDATE users SET password = ?, name = ? WHERE id = ?');
        $update->execute([$hash, $name, $existing['id']]);
        echo "Demo user updated successfully." . PHP_EOL;
    } else {
        $insert = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $insert->execute([$name, $email, $hash]);
        echo "Demo user created successfully." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

