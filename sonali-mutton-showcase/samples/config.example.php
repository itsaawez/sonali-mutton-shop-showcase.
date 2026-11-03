<?php
/**
 * ============================================================================
 * Configuration Template (config.example.php)
 * Project: Sonali Mutton Shop
 * Author:  Aawez Irfan Sayyed
 * Notice:  Portfolio sample containing placeholder environment values.
 *          Never commit production database credentials to version control.
 * ============================================================================
 */

// ── 1. DATABASE CONFIGURATION PLACEHOLDERS ─────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'db_user_placeholder');
define('DB_PASS', getenv('DB_PASS') ?: 'db_password_placeholder');
define('DB_NAME', getenv('DB_NAME') ?: 'sonali_mutton_db');

// ── 2. APPLICATION CONSTANTS ───────────────────────────────────────────────
define('SITE_NAME',   'Sonali Mutton Shop');
define('ADMIN_EMAIL', 'admin@example.com');
define('BASE_PATH',   dirname(__DIR__));

// ── 3. DYNAMIC URL DETECTION ───────────────────────────────────────────────
// Seamlessly operates on Localhost, ngrok reverse tunnels, and production VPS
if (!defined('SITE_URL')) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    $protocol = $isHttps ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Calculate subfolder relative to document root
    $projectRoot = rtrim(str_replace('\\', '/', realpath(dirname(__DIR__))), '/');
    $docRoot     = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');

    $subFolder = '';
    if ($docRoot && strpos($projectRoot, $docRoot) === 0) {
        $subFolder = substr($projectRoot, strlen($docRoot));
    }
    $subFolder = '/' . trim($subFolder, '/');
    if ($subFolder === '/') {
        $subFolder = '';
    }

    define('SITE_URL', $protocol . '://' . $host . $subFolder);
}

// ── 4. SECURE SESSION INITIALIZATION ───────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// ── 5. DATABASE CONNECTION BOOTSTRAP ───────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    // In production, log error privately; do not expose internal database error details to users
    error_log('Database connection error: ' . $conn->connect_error);
    http_response_code(500);
    exit('Service temporarily unavailable. Please try again shortly.');
}

// Enforce full UTF-8 Unicode charset for Marathi and English text support
$conn->set_charset('utf8mb4');
