# Production-Ready PHP & MySQL Registration and Login System

A secure, responsive, production-style user registration and authentication web application built using **PHP 8+**, **MySQL**, **PDO**, **HTML5**, and modern **CSS3** tailored for XAMPP on Windows.

---

## Features & Security Architecture

- **PHP Data Objects (PDO)**: Clean, secure database layer with exceptions enabled (`PDO::ERRMODE_EXCEPTION`) and emulated prepares disabled.
- **Prepared Statements**: Parameterized SQL queries on all input preventing SQL Injection attacks.
- **Modern Password Hashing**: Utilizes PHP's native `password_hash($password, PASSWORD_DEFAULT)` using strong Bcrypt/Argon2 hashing. Never stores plain text.
- **Secure Verification**: Verifies credentials via `password_verify()`.
- **Session Security**:
  - `session_regenerate_id(true)` prevents session fixation upon login.
  - Hardened cookie flags: `HttpOnly`, `SameSite=Lax`, and `use_only_cookies`.
  - Complete session and cookie destruction on logout.
- **Authentication Guards**:
  - `dashboard.php` verifies active session; unauthorized users are automatically redirected to `login.php`.
  - Cache-control headers prevent browser back-button caching of protected content.
  - Logged-in users visiting `login.php` or `registration.php` are redirected directly to `dashboard.php`.
- **Input Validation & Sanitization**:
  - Strict full-name, email format (`filter_var`), and password complexity checks.
  - Output escaping with `htmlspecialchars()` (`ENT_QUOTES | ENT_HTML5`) preventing Cross-Site Scripting (XSS).
  - CSRF protection via cryptographically secure random session tokens.
- **Responsive UI**:
  - Clean, mobile-friendly design with centered cards, input states, and responsive dashboard.
  - Native inline SVG icons — zero external dependencies, 100% functional offline on local XAMPP.

---

## Project Structure

```
c:/xampp/htdocs/registration-login/
├── config/
│   └── database.php         # PDO database configuration and connection handler
├── css/
│   └── style.css            # Responsive CSS3 styling
├── database/
│   └── schema.sql           # Database creation & table schema script
├── includes/
│   └── auth_helpers.php     # Session, CSRF, escaping, and authentication helper functions
├── tests/
│   └── test_auth.php        # CLI verification and automated test script
├── dashboard.php            # Authenticated user dashboard
├── index.php                # Entry router (redirects to dashboard or login)
├── login.php                # User login page
├── logout.php               # Secure logout and session teardown
├── registration.php         # User registration page
└── README.md                # Documentation and setup instructions
```

---

## Step-by-Step Setup Guide

### Step 1: Start Apache and MySQL in XAMPP
1. Open the **XAMPP Control Panel** on Windows (e.g. from the Start Menu or `C:\xampp\xampp-control.exe`).
2. Beside **Apache**, click the **Start** button. The module name will highlight green when running.
3. Beside **MySQL**, click the **Start** button. The module name will highlight green when running.

---

### Step 2: Create and Import the MySQL Database

You can import the database using either **phpMyAdmin (Web GUI)** or the **Command Line**:

#### Option A: Using phpMyAdmin (Recommended)
1. Open your browser and navigate to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click on the **Import** tab at the top.
3. Under **File to import**, click **Choose File**.
4. Browse to your project directory and select:
   ```
   C:\xampp\htdocs\registration-login\database\schema.sql
   ```
5. Scroll to the bottom and click **Import**.
6. A success message will appear and the `user_auth` database with the `users` table will be created.

#### Option B: Using the Command Line
1. Open PowerShell or Command Prompt.
2. Run:
   ```cmd
   C:\xampp\mysql\bin\mysql.exe -u root -p < C:\xampp\htdocs\registration-login\database\schema.sql
   ```
   *(Press Enter if prompted for password and your root user has no password).*

---

### Step 3: Configure the PHP Database Connection

Open `config/database.php` in your editor. By default, it is configured for standard XAMPP settings:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'user_auth');
define('DB_USER', 'root');
define('DB_PASS', '');       // Default XAMPP root password is empty
define('DB_CHARSET', 'utf8mb4');
```

If your MySQL service uses a custom port or root password, update `DB_PASS` or `DB_PORT` accordingly.

---

### Step 4: Run the Application in Your Browser

With Apache and MySQL running:

1. Open your browser and visit:
   ```
   http://localhost/registration-login/
   ```
2. You will be greeted with the **Sign In** screen.
3. Click **Create Account** or visit:
   ```
   http://localhost/registration-login/registration.php
   ```
4. Register a new user account (e.g. Name: `Jane Doe`, Email: `jane@example.com`, Password: `Password123`).
5. After successful registration, you will be redirected to the login screen with a success alert.
6. Enter your credentials to log in.
7. You will be redirected to `dashboard.php`, displaying your profile details, user ID, member registration timestamp, and session status.
8. Click **Sign Out** to terminate your session and return safely to the login screen.

---

## Running the Automated Test Suite

You can verify the codebase syntax and run the authentication test suite using PHP CLI:

1. **Verify PHP Syntax (Linting)**:
   ```powershell
   C:\xampp\php\php.exe -l registration.php
   C:\xampp\php\php.exe -l login.php
   C:\xampp\php\php.exe -l dashboard.php
   C:\xampp\php\php.exe -l logout.php
   C:\xampp\php\php.exe -l config/database.php
   C:\xampp\php\php.exe -l includes/auth_helpers.php
   ```

2. **Run Authentication & Security Unit Tests**:
   ```powershell
   C:\xampp\php\php.exe tests/test_auth.php
   ```

---

## Troubleshooting

- **"Unable to connect to the database" error**:
  - Ensure the **MySQL** module is started in the XAMPP Control Panel.
  - Verify that you imported `database/schema.sql` into MySQL.
- **Port 80 or 443 already in use (Apache doesn't start)**:
  - In XAMPP Control Panel, click **Config** next to Apache -> `httpd.conf` and change `Listen 80` to `Listen 8080`.
  - Then access the application at `http://localhost:8080/registration-login/`.
- **Database password error**:
  - If you set a password for MySQL `root`, update `DB_PASS` in `config/database.php`.

