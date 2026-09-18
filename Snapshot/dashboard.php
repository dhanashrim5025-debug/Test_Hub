<?php
/**
 * Authenticated User Dashboard
 * 
 * Enforces strict authentication verification, sets cache control headers,
 * and presents secure user account details and session state.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_helpers.php';

// Enforce authentication guard
require_login();

// Prevent browser from caching authenticated content
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$userId = (int)$_SESSION['user_id'];
$userName = (string)($_SESSION['user_name'] ?? 'User');
$userEmail = (string)($_SESSION['user_email'] ?? '');
$createdAt = (string)($_SESSION['user_created_at'] ?? 'N/A');

// Format the registration timestamp if valid
$formattedCreatedAt = 'N/A';
if (!empty($createdAt) && $createdAt !== 'N/A') {
    try {
        $date = new DateTimeImmutable($createdAt);
        $formattedCreatedAt = $date->format('F j, Y, g:i A');
    } catch (Exception) {
        $formattedCreatedAt = $createdAt;
    }
}

// Compute initials for the avatar
$words = explode(' ', trim($userName));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    if (!empty($w)) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
}
if ($initials === '') {
    $initials = 'U';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | User Account</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="dashboard-navbar">
        <a href="dashboard.php" class="navbar-brand">
            <svg width="24" height="24" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span>SecurePortal</span>
        </a>
        <div class="navbar-user">
            <span style="font-weight: 500; font-size: 0.95rem; color: var(--text-muted);">
                Welcome, <strong style="color: var(--text-main);"><?= e($userName) ?></strong>
            </span>
            <a href="logout.php" class="btn btn-outline" style="width: auto; padding: 0.45rem 1rem; font-size: 0.85rem;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sign Out
            </a>
        </div>
    </header>

    <main class="dashboard-container">
        <!-- Welcome Hero Card -->
        <div class="dashboard-welcome-card">
            <div class="welcome-text">
                <h1>Hello, <?= e($userName) ?>!</h1>
                <p>Welcome to your personal account dashboard. Your session is active and secured.</p>
            </div>
            <div>
                <span class="status-badge">
                    <span class="status-dot"></span>
                    Authenticated Session Active
                </span>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Account Details Card -->
            <div class="info-card">
                <div class="avatar-circle">
                    <?= e($initials) ?>
                </div>
                <div class="info-card-header">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profile Overview
                </div>

                <div class="info-item">
                    <span class="info-label">User ID</span>
                    <span class="info-value">#<?= e((string)$userId) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?= e($userName) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?= e($userEmail) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Account Created</span>
                    <span class="info-value"><?= e($formattedCreatedAt) ?></span>
                </div>
            </div>

            <!-- Security & Session Card -->
            <div class="info-card">
                <div class="info-card-header">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Security &amp; Session Controls
                </div>

                <div class="info-item">
                    <span class="info-label">Password Storage</span>
                    <span class="info-value" style="color: #16a34a;">Hashed (Bcrypt / Default)</span>
                </div>

                <div class="info-item">
                    <span class="info-label">Database Driver</span>
                    <span class="info-value">PHP Data Objects (PDO)</span>
                </div>

                <div class="info-item">
                    <span class="info-label">Session Protection</span>
                    <span class="info-value" style="color: #16a34a;">HttpOnly &bull; SameSite=Lax</span>
                </div>

                <div class="info-item">
                    <span class="info-label">Session ID Regeneration</span>
                    <span class="info-value" style="color: #16a34a;">Enabled on Login</span>
                </div>

                <div style="margin-top: 1.5rem;">
                    <a href="logout.php" class="btn btn-danger">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        &copy; <?= date('Y') ?> Production Auth App &bull; Secure PHP &amp; MySQL
    </footer>
</body>
</html>

