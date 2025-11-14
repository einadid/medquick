<?php
// src/session.php
function secure_session_start() {
    $session_name = 'quickmed_session';
    $secure = false; // Set to true if using HTTPS in production
    $httponly = true;
    $samesite = 'Strict';

    if (ini_set('session.use_only_cookies', 1) === false) {
        // Log error, critical for security
        exit('Error: Could not initiate a safe session.');
    }

    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);

    session_name($session_name);
    session_start();
}

secure_session_start();

require_once __DIR__ . '/helpers.php';

// Generate a new CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- src/helpers.php ---

function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function verify_csrf_token($token) {
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        // Log this attempt
        die('CSRF token validation failed.');
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}