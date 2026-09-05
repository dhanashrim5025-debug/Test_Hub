<?php

function getDatabaseConnection(): PDO
{
    $host = '127.0.0.1';
    $user = 'root';
    $password = '';
    $database = 'contact_app';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Create the database if it does not exist.
    $serverConnection = new PDO("mysql:host={$host};charset=utf8mb4", $user, $password, $options);
    $serverConnection->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Connect to the database and create the table if needed.
    $connection = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $user, $password, $options);
    $connection->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS enquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    SQL);

    return $connection;
}
