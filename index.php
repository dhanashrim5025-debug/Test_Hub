<?php
/**
 * Application Entry Point / Root Router
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_helpers.php';

init_secure_session();

if (is_logged_in()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;

