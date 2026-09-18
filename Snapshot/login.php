<?php
/**
 * User Login Page
 * 
 * Authenticates users via email and password using PDO prepared statements
 * and password_verify(). Regenerates session ID on success.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_helpers.php';

// Redirect already logged-in users directly to dashboard
redirect_if_logged_in();

$errors = [];
$successMessage = '';
$email = '';

// Check for redirect notifications
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $successMessage = 'Registration successful! You can now log in with your credentials.';
} elseif (isset($_GET['status']) && $_GET['status'] === 'logged_out') {
    $successMessage = 'You have been successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Invalid or expired security token. Please refresh and try again.';
    }

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // 2. Validate input presence
    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both your email address and password.';
    }

    // 3. Authenticate User
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();

            // Prepared statement to fetch user by email
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password, created_at FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verify password hash
            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                // Populate session variables
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_created_at'] = $user['created_at'];
                $_SESSION['logged_in'] = true;

                // Redirect to protected dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                // Generic error message prevents user enumeration attacks
                $errors[] = 'Invalid email or password.';
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
    <title>Sign In | User Authentication</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon-badge">
                    <!-- Lock SVG Icon -->
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1>Welcome Back</h1>
                <p>Enter your credentials to access your account</p>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= e($successMessage) ?></span>
                </div>
            <?php endif; ?>

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

            <form action="login.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

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
                        placeholder="Enter your password" 
                        required 
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Sign In
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account yet? <a href="registration.php">Create Account</a>
            </div>
        </div>
    </div>

    <footer class="app-footer">
        &copy; <?= date('Y') ?> Production Auth App &bull; Secure PHP &amp; MySQL
    </footer>
</body>
</html>

