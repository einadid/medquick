<?php
// FILE: src/session.php (Final & Robust Version)
// PURPOSE: Manages secure session startup and ensures essential user data is in the session.

/**
 * Starts a secure PHP session with recommended settings.
 */
function secure_session_start() {
    $session_name = 'quickmed_session';
    $secure = false; // Set to true if using HTTPS in production
    $httponly = true;
    $samesite = 'Strict';

    if (ini_set('session.use_only_cookies', 1) === false) {
        error_log('FATAL: Could not force sessions to use only cookies.');
        exit('A critical security error occurred.');
    }

    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path'     => $cookieParams['path'],
        'domain'   => $cookieParams['domain'],
        'secure'   => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);

    session_name($session_name);
    session_start();
}

// Start the secure session for any script that includes this file.
secure_session_start();

// CRITICAL: Include the helper functions, which are needed globally.
require_once __DIR__ . '/helpers.php';

// Automatically generate a CSRF token if it doesn't exist.
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        error_log('FATAL: Could not generate CSRF token: ' . $e->getMessage());
        exit('A critical security error occurred.');
    }
}

/**
 * **NEW: Function to ensure user data is fresh in the session.**
 * This function will be called from main PHP files AFTER the database connection is established.
 */
function ensure_user_session_data() {
    // Only run if the user is logged in.
    if (isset($_SESSION['user_id'])) {
        // We need the $pdo object.
        global $pdo;

        // Check if $pdo is available.
        if (isset($pdo)) {
            // If user's image path is not set in the session, fetch it from the database.
            if (!isset($_SESSION['user_image'])) {
                try {
                    $stmt = $pdo->prepare("SELECT profile_image_path FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    // fetchColumn() returns the value or false if not found.
                    $image_path = $stmt->fetchColumn(); 
                    $_SESSION['user_image'] = $image_path ? $image_path : null;
                } catch (PDOException $e) {
                    error_log("Session setup error - couldn't fetch user image: " . $e->getMessage());
                    $_SESSION['user_image'] = null; // Set to null on error
                }
            }
        }
    }
}
?>