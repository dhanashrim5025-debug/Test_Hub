<?php
/**
 * Authentication and Security Helper Functions
 */

declare(strict_types=1);

/**
 * Initialize a secure session with modern cookie parameters
 */
function init_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            $cookieParams = [
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ];

            session_set_cookie_params($cookieParams);
            session_start();
        } else {
            // If running in CLI or output already flushed, start session cleanly without headers warning
            @session_start();
        }
    }
}

/**
 * Check if the current user is authenticated
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Ensure user is logged in; redirect to login page if unauthenticated
 */
function require_login(): void
{
    init_secure_session();
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect logged-in users away from guest pages (login/registration)
 */
function redirect_if_logged_in(): void
{
    init_secure_session();
    if (is_logged_in()) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Generate or retrieve the CSRF token for the session
 */
function get_csrf_token(): string
{
    init_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify submitted CSRF token
 */
function verify_csrf_token(?string $token): bool
{
    init_secure_session();
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape HTML output to prevent Cross-Site Scripting (XSS)
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
