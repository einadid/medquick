<?php
// FILE: src/helpers.php
// PURPOSE: Contains all global utility and helper functions for the application.

/**
 * Escapes a string for safe output in HTML to prevent XSS attacks.
 * @param string|null $string The string to escape.
 * @return string The escaped string.
 */
function e($string) {
    // Ensure we don't try to escape a null value
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects the user to a new URL and stops script execution.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Verifies the CSRF token to prevent cross-site request forgery attacks.
 * @param string $token The token received from the submitted form.
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // This is a security failure. Stop execution.
        error_log('CSRF token validation failed. IP: ' . $_SERVER['REMOTE_ADDR']);
        die('CSRF token validation failed. Please go back and try submitting the form again.');
    }
}

/**
 * Checks if a user is currently logged in by checking the session.
 * @return bool True if the user is logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user has a specific role.
 * @param string $role The role to check for (e.g., 'admin', 'customer').
 * @return bool True if the user has the specified role, false otherwise.
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Logs a critical action to the audit_log table.
 * This version is compatible with older PHP versions.
 *
 * @param object $pdo The PDO database connection object.
 * @param string $action A description of the action (e.g., 'USER_LOGIN').
 * @param string|null $details Additional details about the action.
 */
function log_audit($pdo, $action, $details = null) {
    try {
        // Use older ternary syntax for maximum compatibility with PHP < 7
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $stmt = $pdo->prepare(
            "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $action, $details, $ip_address]);
    } catch (PDOException $e) {
        // In a real production environment, you should log this error to a file
        // and not halt execution for the user.
        error_log("Audit log failed: " . $e->getMessage());
    }
}