<?php
// FILE: src/helpers.php
// PURPOSE: Contains all global utility and helper functions.

// We need constants.php for the BASE_URL constant.
require_once __DIR__ . '/../config/constants.php';

/**
 * Escapes a string for safe output in HTML to prevent XSS attacks.
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects the user to a new URL and stops script execution.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Verifies the CSRF token to prevent cross-site request forgery attacks.
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        error_log('CSRF token validation failed. IP: ' . $_SERVER['REMOTE_ADDR']);
        die('CSRF token validation failed. Please go back and try submitting the form again.');
    }
}

/**
 * Checks if a user is currently logged in.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user has a specific role.
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Logs a critical action to the audit_log table.
 */
function log_audit($pdo, $action, $details = null) {
    try {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip_address]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Creates a full, absolute URL by prepending the base URL.
 * This is the corrected version.
 * @param string $path The path to append (e.g., 'login.php' or '/login.php').
 * @return string The full URL.
 */
function base_url($path = '') {
    // Remove any leading slash from the path to prevent double slashes (e.g., http://site.com//login.php)
    // Then, prepend a single slash before joining with BASE_URL.
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Formats a number as currency (Taka).
 * @param float $amount The amount to format.
 * @return string The formatted currency string (e.g., '৳1,250.50').
 */
function money($amount) {
    // Ensure the input is a numeric type before formatting.
    return '৳' . number_format((float)$amount, 2);
}