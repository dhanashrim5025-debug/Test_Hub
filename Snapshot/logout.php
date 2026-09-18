<?php
/**
 * User Logout Handler
 * 
 * Securely terminates the user session, clears server session data,
 * expires client session cookies, and redirects back to the login page.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_helpers.php';

init_secure_session();

// Clear all session variables
$_SESSION = [];

// Remove session cookie from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session data on server
session_destroy();

// Redirect to login page with logged-out feedback
header('Location: login.php?status=logged_out');
exit;

