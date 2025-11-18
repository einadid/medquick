<?php
require_once __DIR__ . '/db.php';

// ==========================================
// DEFINE CONSTANTS IF NOT ALREADY DEFINED
// ==========================================
if (!defined('CSRF_TOKEN_NAME')) {
    define('CSRF_TOKEN_NAME', 'csrf_token');
}

if (!defined('SIGNUP_BONUS_POINTS')) {
    define('SIGNUP_BONUS_POINTS', 100);
}

if (!defined('POINTS_PER_1000_BDT')) {
    define('POINTS_PER_1000_BDT', 100);
}

// Don't start session here - config.php already handles it

// ==========================================
// CSRF TOKEN FUNCTIONS
// ==========================================
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// ==========================================
// AUTHENTICATION HELPERS
// ==========================================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT u.*, r.role_name FROM users u 
                              JOIN roles r ON u.role_id = r.id 
                              WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("getCurrentUser error: " . $e->getMessage());
        return null;
    }
}

function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role_name'] === $role;
}

function requireRole($roles) {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
    
    $user = getCurrentUser();
    $rolesArray = is_array($roles) ? $roles : [$roles];
    
    if (!in_array($user['role_name'], $rolesArray)) {
        die('Access Denied. Required role: ' . implode(' or ', $rolesArray));
    }
}

// ==========================================
// REDIRECT FUNCTION
// ==========================================
function redirect($path) {
    header('Location: ' . SITE_URL . $path);
    exit;
}

// ==========================================
// SANITIZATION
// ==========================================
function clean($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ==========================================
// FLASH MESSAGES
// ==========================================
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ==========================================
// AUDIT LOG
// ==========================================
function logAudit($userId, $action, $details = '') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

// ==========================================
// EMAIL FUNCTION
// ==========================================
function sendEmail($to, $subject, $body) {
    $enableEmail = defined('ENABLE_EMAIL') ? ENABLE_EMAIL : false;
    
    if (!$enableEmail) {
        error_log("EMAIL: To=$to, Subject=$subject, Body=$body");
        return true;
    }
    
    $fromEmail = defined('FROM_EMAIL') ? FROM_EMAIL : 'noreply@quickmed.com';
    $headers = "From: " . $fromEmail . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $body, $headers);
}

// ==========================================
// FILE UPLOAD HANDLER
// ==========================================
function uploadImage($file, $folder = 'medicines') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    $newName = uniqid() . '.' . $ext;
    $uploadPath = UPLOAD_PATH . $folder . '/';
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath . $newName)) {
        return $folder . '/' . $newName;
    }
    
    return false;
}

// ==========================================
// FORMAT CURRENCY
// ==========================================
function formatPrice($amount) {
    return '৳' . number_format($amount, 2);
}

// ==========================================
// GENERATE VERIFICATION CODE
// ==========================================
function generateVerificationCode($prefix, $role) {
    return 'qm-' . $prefix . '-' . strtoupper(substr(uniqid(), -6));
}

// ==========================================
// CALCULATE LOYALTY POINTS
// ==========================================
function calculatePointsEarned($orderAmount) {
    $pointsPerThousand = defined('POINTS_PER_1000_BDT') ? POINTS_PER_1000_BDT : 100;
    return floor($orderAmount / 1000) * $pointsPerThousand;
}

// ==========================================
// GET STATS FOR HOMEPAGE
// ==========================================
function getStats() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stats = [];
        
        // Total Medicines
        $stmt = $db->query("SELECT COUNT(*) as count FROM medicines WHERE status = 'active'");
        $stats['medicines'] = $stmt->fetch()['count'] ?? 0;
        
        // Active Shops
        $stmt = $db->query("SELECT COUNT(*) as count FROM shops WHERE status = 'active'");
        $stats['shops'] = $stmt->fetch()['count'] ?? 0;
        
        // Total Customers
        $stmt = $db->query("SELECT COUNT(*) as count FROM users u 
                           JOIN roles r ON u.role_id = r.id 
                           WHERE r.role_name = 'customer'");
        $stats['customers'] = $stmt->fetch()['count'] ?? 0;
        
        // Total Orders
        $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
        $stats['orders'] = $stmt->fetch()['count'] ?? 0;
        
        // Delivery Success Rate
        $stmt = $db->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered
                            FROM parcels");
        $data = $stmt->fetch();
        $stats['delivery_rate'] = $data['total'] > 0 
            ? round(($data['delivered'] / $data['total']) * 100, 1) 
            : 100;
        
        return $stats;
    } catch (Exception $e) {
        error_log("getStats error: " . $e->getMessage());
        return [
            'medicines' => 0,
            'shops' => 0,
            'customers' => 0,
            'orders' => 0,
            'delivery_rate' => 100
        ];
    }
}

// ==========================================
// GET CART COUNT
// ==========================================
function getCartCount() {
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        error_log("getCartCount error: " . $e->getMessage());
        return 0;
    }
}