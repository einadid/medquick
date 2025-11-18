<?php
require_once __DIR__ . '/../includes/db.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generate unique member ID from email
     */
    private function generateMemberId($email) {
        // Extract username from email (part before @)
        $username = strtolower(substr($email, 0, strpos($email, '@')));
        
        // Remove special characters, keep only alphanumeric and dots/underscores
        $username = preg_replace('/[^a-z0-9._-]/', '', $username);
        
        // Check if this member ID already exists
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE member_id = ?");
        $stmt->execute([$username]);
        $exists = $stmt->fetch()['count'];
        
        if ($exists > 0) {
            // If exists, add number suffix
            $counter = 1;
            $newUsername = $username;
            
            while ($exists > 0) {
                $newUsername = $username . $counter;
                $stmt->execute([$newUsername]);
                $exists = $stmt->fetch()['count'];
                $counter++;
                
                // Safety limit
                if ($counter > 100) {
                    // Fallback to random
                    $newUsername = $username . '_' . substr(uniqid(), -4);
                    break;
                }
            }
            
            return $newUsername;
        }
        
        return $username;
    }
    
    public function register($data, $roleCode = null) {
        // Validate email
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Determine role
        if ($roleCode) {
            // Check verification code
            $stmt = $this->db->prepare("SELECT * FROM signup_codes WHERE code = ? AND used = 0");
            $stmt->execute([$roleCode]);
            $code = $stmt->fetch();
            
            if (!$code) {
                return ['success' => false, 'message' => 'Invalid or used verification code'];
            }
            
            $roleId = $code['role_id'];
            $shopId = $code['shop_id'];
            
            // Mark code as used
            $stmt = $this->db->prepare("UPDATE signup_codes SET used = 1, used_by = ?, used_at = NOW() WHERE id = ?");
            $stmt->execute([$data['email'], $code['id']]);
            
        } else {
            // Customer registration
            $stmt = $this->db->prepare("SELECT id FROM roles WHERE role_name = 'customer'");
            $stmt->execute();
            $roleId = $stmt->fetch()['id'];
            $shopId = null;
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Generate Member ID from email (only for customers)
        $memberId = null;
        if (!$roleCode) { // Customer
            $memberId = $this->generateMemberId($data['email']);
        }
        
        // Insert user
        $stmt = $this->db->prepare("INSERT INTO users (role_id, shop_id, email, password, full_name, phone, address, member_id, status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([
            $roleId,
            $shopId,
            $data['email'],
            $hashedPassword,
            $data['full_name'],
            $data['phone'],
            $data['address'] ?? null,
            $memberId
        ]);
        
        $userId = $this->db->lastInsertId();
        
        // Give signup bonus for customers
        if (!$roleCode) {
            $stmt = $this->db->prepare("INSERT INTO loyalty_transactions (user_id, points, type, description) 
                                       VALUES (?, ?, 'earned', 'Signup Bonus')");
            $stmt->execute([$userId, SIGNUP_BONUS_POINTS]);
        }
        
        // Log
        logAudit($userId, 'user_registered', 'New user registration with member ID: ' . $memberId);
        
        return ['success' => true, 'message' => 'Registration successful', 'member_id' => $memberId];
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT u.*, r.role_name FROM users u 
                                     JOIN roles r ON u.role_id = r.id 
                                     WHERE u.email = ? AND u.status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role_name'];
            
            // Update last login
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            logAudit($user['id'], 'user_login', 'User logged in');
            
            return ['success' => true, 'role' => $user['role_name']];
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    public function logout() {
        if (isLoggedIn()) {
            logAudit($_SESSION['user_id'], 'user_logout', 'User logged out');
        }
        session_destroy();
    }
    
    private function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
}