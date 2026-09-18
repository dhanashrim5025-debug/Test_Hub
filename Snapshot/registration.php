<?php
/**
 * User Registration Page
 * 
 * Handles user sign-up with strict input validation,
 * duplicate check, password hashing, and CSRF protection.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_helpers.php';

// Redirect authenticated users directly to dashboard
redirect_if_logged_in();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Invalid or expired security token. Please refresh and try again.';
    }

    // 2. Retrieve and sanitize input fields
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    // 3. Validation Rules
    if ($name === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Full name must be between 2 and 100 characters.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one letter and one number.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // 4. Database Check & Insertion
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();

            // Check for duplicate email using prepared statement
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address already exists.';
            } else {
                // Securely hash the password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new user
                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
                );
                $insertStmt->execute([$name, $email, $passwordHash]);

                // Redirect to login with success flash parameter
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException | RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account | User Authentication</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon-badge">
                    <!-- User Plus SVG Icon -->
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h1>Create Account</h1>
                <p>Register with your details to get started</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <?php if (count($errors) === 1): ?>
                            <span><?= e($errors[0]) ?></span>
                        <?php else: ?>
                            <ul class="alert-list">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form action="registration.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        value="<?= e($name) ?>" 
                        placeholder="John Doe" 
                        required 
                        autocomplete="name"
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        value="<?= e($email) ?>" 
                        placeholder="john@example.com" 
                        required 
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="At least 8 characters" 
                        required 
                        autocomplete="new-password"
                    >
                    <p class="form-text">Minimum 8 characters with at least 1 letter and 1 number.</p>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        placeholder="Re-enter your password" 
                        required 
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Create Account
                </button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>

    <footer class="app-footer">
        &copy; <?= date('Y') ?> Production Auth App &bull; Secure PHP &amp; MySQL
    </footer>
</body>
</html>

