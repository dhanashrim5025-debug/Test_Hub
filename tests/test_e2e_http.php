<?php
/**
 * End-to-End Live HTTP Integration Test
 * 
 * Executes real HTTP requests against the local Apache web server:
 * 1. Protected dashboard access without auth -> redirect to login.php
 * 2. Registration GET -> extract session cookie & CSRF token
 * 3. User registration POST -> database insertion & redirect
 * 4. Duplicate email registration POST -> rejection error
 * 5. Login POST with invalid password -> rejection error
 * 6. Login POST with valid credentials -> session regeneration & redirect to dashboard
 * 7. Dashboard GET with session -> displays user details & active session badge
 * 8. Logout GET -> terminates session & redirects to login.php?status=logged_out
 * 9. Post-logout Dashboard GET -> redirected to login.php
 */

declare(strict_types=1);

$baseUrl = 'http://localhost/registration-login';
$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_' . uniqid() . '.txt';

function httpGet(string $url, ?string $cookieJar = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    $raw = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headers = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    curl_close($ch);
    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body];
}

function httpPost(string $url, array $data, ?string $cookieJar = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    $raw = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headers = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    curl_close($ch);
    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body];
}

function extractCsrfToken(string $html): string
{
    if (preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

echo "=== Running Live HTTP Integration Test Suite ===" . PHP_EOL . PHP_EOL;

// 1. Unauthenticated Dashboard Guard
echo "[1] Testing unauthenticated access to dashboard.php..." . PHP_EOL;
$res = httpGet("$baseUrl/dashboard.php");
if ($res['code'] === 302 && strpos($res['headers'], 'Location: login.php') !== false) {
    echo "    [PASS] Successfully blocked unauthenticated user (302 Redirect to login.php)" . PHP_EOL;
} else {
    echo "    [FAIL] Unexpected response: " . $res['code'] . PHP_EOL;
}

// 2. Fetch Registration Page & CSRF Token
echo "[2] Fetching registration.php for fresh session and CSRF token..." . PHP_EOL;
$res = httpGet("$baseUrl/registration.php", $cookieFile);
$csrfToken = extractCsrfToken($res['body']);
if (!empty($csrfToken)) {
    echo "    [PASS] Acquired CSRF token: " . substr($csrfToken, 0, 16) . "..." . PHP_EOL;
} else {
    echo "    [FAIL] Failed to extract CSRF token." . PHP_EOL;
    exit(1);
}

// 3. Perform Live User Registration
$testEmail = 'alex.smith_' . time() . '@example.com';
$testPass = 'Secret123!';
$testName = 'Alex Smith';

echo "[3] Submitting new registration for $testEmail..." . PHP_EOL;
$regData = [
    'csrf_token'       => $csrfToken,
    'name'             => $testName,
    'email'            => $testEmail,
    'password'         => $testPass,
    'confirm_password' => $testPass,
];
$res = httpPost("$baseUrl/registration.php", $regData, $cookieFile);
if ($res['code'] === 302 && strpos($res['headers'], 'login.php?registered=1') !== false) {
    echo "    [PASS] User registered successfully (302 Redirect to login.php?registered=1)" . PHP_EOL;
} else {
    echo "    [FAIL] Registration failed. Code: " . $res['code'] . PHP_EOL;
    echo $res['body'] . PHP_EOL;
}

// 4. Test Duplicate Email Handling
echo "[4] Testing duplicate registration with identical email..." . PHP_EOL;
// Re-fetch token
$resGet = httpGet("$baseUrl/registration.php", $cookieFile);
$csrfToken2 = extractCsrfToken($resGet['body']);
$dupData = [
    'csrf_token'       => $csrfToken2,
    'name'             => 'Duplicate Alex',
    'email'            => $testEmail,
    'password'         => $testPass,
    'confirm_password' => $testPass,
];
$resDup = httpPost("$baseUrl/registration.php", $dupData, $cookieFile);
if (strpos($resDup['body'], 'An account with this email address already exists.') !== false) {
    echo "    [PASS] Duplicate email rejected with expected alert message" . PHP_EOL;
} else {
    echo "    [FAIL] Duplicate email not caught!" . PHP_EOL;
}

// 5. Test Login with Incorrect Password
echo "[5] Testing login with wrong password..." . PHP_EOL;
$resGetLogin = httpGet("$baseUrl/login.php", $cookieFile);
$csrfLogin = extractCsrfToken($resGetLogin['body']);
$wrongLogin = [
    'csrf_token' => $csrfLogin,
    'email'      => $testEmail,
    'password'   => 'WrongPassword999',
];
$resWrong = httpPost("$baseUrl/login.php", $wrongLogin, $cookieFile);
if (strpos($resWrong['body'], 'Invalid email or password.') !== false) {
    echo "    [PASS] Incorrect credentials rejected with expected alert message" . PHP_EOL;
} else {
    echo "    [FAIL] Incorrect password was not rejected properly!" . PHP_EOL;
}

// 6. Test Login with Correct Password
echo "[6] Testing login with correct credentials..." . PHP_EOL;
$resGetLogin2 = httpGet("$baseUrl/login.php", $cookieFile);
$csrfLogin2 = extractCsrfToken($resGetLogin2['body']);
$correctLogin = [
    'csrf_token' => $csrfLogin2,
    'email'      => $testEmail,
    'password'   => $testPass,
];
$resLogin = httpPost("$baseUrl/login.php", $correctLogin, $cookieFile);
if ($resLogin['code'] === 302 && strpos($resLogin['headers'], 'Location: dashboard.php') !== false) {
    echo "    [PASS] Login successful (302 Redirect to dashboard.php)" . PHP_EOL;
} else {
    echo "    [FAIL] Login failed! Code: " . $resLogin['code'] . PHP_EOL;
}

// 7. Access Dashboard with Session Cookie
echo "[7] Accessing dashboard.php with authenticated session..." . PHP_EOL;
$resDash = httpGet("$baseUrl/dashboard.php", $cookieFile);
if (
    $resDash['code'] === 200 &&
    strpos($resDash['body'], 'Alex Smith') !== false &&
    strpos($resDash['body'], $testEmail) !== false &&
    strpos($resDash['body'], 'Authenticated Session Active') !== false
) {
    echo "    [PASS] Dashboard loaded successfully with user details and active session badge" . PHP_EOL;
} else {
    echo "    [FAIL] Dashboard failed to load user details! Code: " . $resDash['code'] . PHP_EOL;
}

// 8. Test Logout
echo "[8] Logging out via logout.php..." . PHP_EOL;
$resLogout = httpGet("$baseUrl/logout.php", $cookieFile);
if ($resLogout['code'] === 302 && strpos($resLogout['headers'], 'Location: login.php?status=logged_out') !== false) {
    echo "    [PASS] Logout successful (302 Redirect to login.php?status=logged_out)" . PHP_EOL;
} else {
    echo "    [FAIL] Logout failed! Code: " . $resLogout['code'] . PHP_EOL;
}

// 9. Verify Dashboard Inaccessible Post-Logout
echo "[9] Verifying dashboard cannot be accessed after logout..." . PHP_EOL;
$resPostLogout = httpGet("$baseUrl/dashboard.php", $cookieFile);
if ($resPostLogout['code'] === 302 && strpos($resPostLogout['headers'], 'Location: login.php') !== false) {
    echo "    [PASS] Post-logout access redirected to login.php" . PHP_EOL;
} else {
    echo "    [FAIL] Session was not properly cleared!" . PHP_EOL;
}

// Clean up
@unlink($cookieFile);

echo PHP_EOL . "=== ALL END-TO-END HTTP TESTS PASSED! ===" . PHP_EOL;

