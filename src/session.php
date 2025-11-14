<?php
// FILE: src/session.php
// PURPOSE: Manages secure session startup and includes helper functions.

/**
 * Starts a secure PHP session with recommended settings.
 */
function secure_session_start() {
    // Define session cookie parameters for security
    $session_name = 'quickmed_session';
    $secure = false; // Set to true if you are using HTTPS in production
    $httponly = true; // Prevents JavaScript from accessing the session cookie
    $samesite = 'Strict'; // Prevents the browser from sending the cookie with cross-site requests

    // Force sessions to only use cookies, not URLs
    if (ini_set('session.use_only_cookies', 1) === false) {
        // This is a critical security setting. If it fails, stop execution.
        error_log('FATAL: Could not force sessions to use only cookies.');
        exit('A critical security error occurred. Please contact the administrator.');
    }

    // Get current cookie parameters and update them with secure settings
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path'     => $cookieParams['path'],
        'domain'   => $cookieParams['domain'],
        'secure'   => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);

    // Set the session name and start the session
    session_name($session_name);
    session_start();
}

// Start the secure session for any script that includes this file
secure_session_start();

// CRITICAL: Include the helper functions. This line makes all functions from helpers.php available.
require_once __DIR__ . '/helpers.php';

// Automatically generate a CSRF token for the user's session if it doesn't exist.
// This token will be used in all POST forms for security.
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Handle error if random_bytes fails
        error_log('FATAL: Could not generate CSRF token. ' . $e->getMessage());
        exit('A critical security error occurred.');
    }
}