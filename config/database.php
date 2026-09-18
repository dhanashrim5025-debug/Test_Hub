<?php
/**
 * Database Connection Configuration using PDO
 * 
 * Configured for XAMPP default settings.
 * Adjust credentials if your MySQL server uses a custom password or port.
 */

declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'user_auth');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 *
 * @return PDO
 * @throws RuntimeException if database connection fails
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log detailed error internally
        error_log('Database Connection Error: ' . $e->getMessage());

        // Throw generic safe exception for presentation layer
        throw new RuntimeException(
            'Unable to connect to the database. Please ensure MySQL is running in XAMPP and the "user_auth" database has been created.'
        );
    }
}

