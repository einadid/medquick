<?php
// QuickMed Configuration File

// ==========================================
// DATABASE CONFIGURATION
// ==========================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quickmed');

// ==========================================
// SITE CONFIGURATION
// ==========================================
define('SITE_URL', 'http://localhost/final');
define('SITE_NAME', 'QuickMed');

define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// ==========================================
// LOYALTY POINTS CONFIGURATION
// ==========================================
define('SIGNUP_BONUS_POINTS', 100);
define('POINTS_PER_1000_BDT', 100);
define('POINT_VALUE_BDT', 1);

// ==========================================
// DELIVERY CHARGES
// ==========================================
define('HOME_DELIVERY_CHARGE', 100);
define('STORE_PICKUP_CHARGE', 0);

// ==========================================
// EMAIL CONFIGURATION
// ==========================================
define('ENABLE_EMAIL', false);
define('FROM_EMAIL', 'noreply@quickmed.com');

// ==========================================
// SECURITY CONFIGURATION
// ==========================================
define('CSRF_TOKEN_NAME', 'csrf_token');  // ← এটা important!

// ==========================================
// TIMEZONE
// ==========================================
date_default_timezone_set('Asia/Dhaka');

// ==========================================
// ERROR REPORTING
// ==========================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================
// SESSION CONFIGURATION
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.gc_maxlifetime', 3600);
    session_start();
}

// ==========================================
// AUTO-LOAD REQUIRED FILES
// ==========================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';