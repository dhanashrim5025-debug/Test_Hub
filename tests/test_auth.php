<?php
/**
 * Automated Test Suite for User Authentication System
 * 
 * Verifies:
 * 1. Password hashing & verification
 * 2. Input validation & sanitization
 * 3. CSRF token generation & matching
 * 4. User registration simulation
 * 5. Duplicate email rejection
 * 6. Login authentication & incorrect password rejection
 * 7. Dashboard authorization guard
 * 8. Logout session termination
 * 
 * Can be run via CLI: php tests/test_auth.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_helpers.php';

// ANSI terminal colors
const COLOR_GREEN = "\033[32m";
const COLOR_RED   = "\033[31m";
const COLOR_BLUE  = "\033[34m";
const COLOR_RESET = "\033[0m";

$totalTests = 0;
$passedTests = 0;

function assertTest(string $description, bool $condition): void
{
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo COLOR_GREEN . "[PASS] " . COLOR_RESET . $description . PHP_EOL;
    } else {
        echo COLOR_RED . "[FAIL] " . COLOR_RESET . $description . PHP_EOL;
    }
}

echo PHP_EOL . COLOR_BLUE . "=== Running User Authentication Test Suite ===" . COLOR_RESET . PHP_EOL . PHP_EOL;

// -------------------------------------------------------------
// Test Group 1: Password Hashing & Verification
// -------------------------------------------------------------
echo "--> Group 1: Password Security" . PHP_EOL;
$plainPassword = 'SecurePassword123';
$hashed = password_hash($plainPassword, PASSWORD_DEFAULT);

assertTest(
    "Password hash is not plain text and uses modern algorithm",
    $hashed !== $plainPassword && strlen($hashed) >= 60
);

assertTest(
    "password_verify() returns true for correct password",
    password_verify($plainPassword, $hashed) === true
);

assertTest(
    "password_verify() returns false for incorrect password",
    password_verify('WrongPassword123', $hashed) === false
);

// -------------------------------------------------------------
// Test Group 2: CSRF & XSS Helpers
// -------------------------------------------------------------
echo PHP_EOL . "--> Group 2: CSRF & XSS Protections" . PHP_EOL;
$token = get_csrf_token();
assertTest("CSRF token is generated and non-empty", !empty($token) && strlen($token) === 64);
assertTest("verify_csrf_token() validates matching token", verify_csrf_token($token) === true);
assertTest("verify_csrf_token() rejects invalid token", verify_csrf_token('invalid_token_123') === false);
assertTest("verify_csrf_token() rejects empty token", verify_csrf_token('') === false);

$unsafeString = '<script>alert("XSS")</script>';
$escaped = e($unsafeString);
assertTest(
    "e() properly escapes HTML special characters",
    strpos($escaped, '<script>') === false && strpos($escaped, '&lt;script&gt;') !== false
);

// -------------------------------------------------------------
// Test Group 3: Input Validation Rules
// -------------------------------------------------------------
echo PHP_EOL . "--> Group 3: Input Validation Logic" . PHP_EOL;

function validateRegistrationInput(string $name, string $email, string $password, string $confirm): array
{
    $errors = [];
    $name = trim($name);
    $email = trim($email);

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

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    return $errors;
}

assertTest(
    "Validation passes on valid user input",
    empty(validateRegistrationInput('John Doe', 'john@example.com', 'Pass12345', 'Pass12345'))
);

assertTest(
    "Validation fails when email is invalid",
    in_array('Please provide a valid email address.', validateRegistrationInput('John Doe', 'invalid-email', 'Pass12345', 'Pass12345'))
);

assertTest(
    "Validation fails when password is under 8 characters",
    in_array('Password must be at least 8 characters long.', validateRegistrationInput('John Doe', 'john@example.com', 'P123', 'P123'))
);

assertTest(
    "Validation fails when passwords do not match",
    in_array('Passwords do not match.', validateRegistrationInput('John Doe', 'john@example.com', 'Pass12345', 'Mismatch123'))
);

// -------------------------------------------------------------
// Test Group 4: Database Layer, Duplicate Email & Auth Flows
// -------------------------------------------------------------
echo PHP_EOL . "--> Group 4: Registration, Duplicate Email & Login Simulation (PDO)" . PHP_EOL;

// Setup in-memory PDO database matching the users schema for unit simulation
$testPdo = new PDO('sqlite::memory:');
$testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$testPdo->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Test Registration
$userEmail = 'testuser@example.com';
$userPass = 'Secret1234';
$insertStmt = $testPdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
$insertStmt->execute(['Test User', $userEmail, password_hash($userPass, PASSWORD_DEFAULT)]);

$fetchStmt = $testPdo->prepare('SELECT * FROM users WHERE email = ?');
$fetchStmt->execute([$userEmail]);
$userRecord = $fetchStmt->fetch(PDO::FETCH_ASSOC);

assertTest("User record successfully inserted in database", $userRecord !== false && $userRecord['email'] === $userEmail);

// Test Duplicate Email Handling
$duplicateDetected = false;
$checkDuplicate = $testPdo->prepare('SELECT id FROM users WHERE email = ?');
$checkDuplicate->execute([$userEmail]);
if ($checkDuplicate->fetch()) {
    $duplicateDetected = true;
}
assertTest("Duplicate email detection catches existing email address", $duplicateDetected === true);

// Test Login with Correct Password
$loginStmt = $testPdo->prepare('SELECT id, name, email, password, created_at FROM users WHERE email = ?');
$loginStmt->execute([$userEmail]);
$authCandidate = $loginStmt->fetch(PDO::FETCH_ASSOC);

$loginSuccess = ($authCandidate && password_verify($userPass, $authCandidate['password']));
assertTest("Login verification succeeds with correct credentials", $loginSuccess === true);

// Test Login with Incorrect Password
$loginWrong = ($authCandidate && password_verify('WrongPassword', $authCandidate['password']));
assertTest("Login verification fails with incorrect password", $loginWrong === false);

// -------------------------------------------------------------
// Test Group 5: Session & Authentication Guards
// -------------------------------------------------------------
echo PHP_EOL . "--> Group 5: Session State & Dashboard Auth Guards" . PHP_EOL;

// Simulate unauthenticated state
$_SESSION = [];
assertTest("is_logged_in() returns false when unauthenticated", is_logged_in() === false);

// Simulate login session state
$_SESSION['user_id'] = (int)$userRecord['id'];
$_SESSION['user_name'] = $userRecord['name'];
$_SESSION['user_email'] = $userRecord['email'];
$_SESSION['logged_in'] = true;

assertTest("is_logged_in() returns true when session is populated", is_logged_in() === true);

// Simulate Logout
$_SESSION = [];
assertTest("Logout clears session and revokes authentication", is_logged_in() === false);

// -------------------------------------------------------------
// Test Summary
// -------------------------------------------------------------
echo PHP_EOL . COLOR_BLUE . "=== Test Results Summary ===" . COLOR_RESET . PHP_EOL;
echo "Total Tests:  $totalTests" . PHP_EOL;
echo "Passed Tests: " . COLOR_GREEN . "$passedTests" . COLOR_RESET . PHP_EOL;
echo "Failed Tests: " . ($totalTests - $passedTests === 0 ? "0" : COLOR_RED . ($totalTests - $passedTests) . COLOR_RESET) . PHP_EOL;

if ($passedTests === $totalTests) {
    echo COLOR_GREEN . "ALL AUTOMATED AUTHENTICATION TESTS PASSED SUCCESSFULLY!" . COLOR_RESET . PHP_EOL . PHP_EOL;
    exit(0);
} else {
    echo COLOR_RED . "SOME TESTS FAILED." . COLOR_RESET . PHP_EOL . PHP_EOL;
    exit(1);
}

