<?php
require_once __DIR__ . '/../includes/db.php';

class Loyalty {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get user's total loyalty points
     */
    public function getUserPoints($userId) {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(points), 0) as total
            FROM loyalty_transactions
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return (int)$result['total'];
    }
    
    /**
     * Get user's points breakdown
     */
    public function getPointsBreakdown($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN points > 0 THEN points ELSE 0 END), 0) as earned,
                COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) as redeemed,
                COALESCE(SUM(points), 0) as balance
            FROM loyalty_transactions
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Award signup bonus
     */
    public function awardSignupBonus($userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO loyalty_transactions (user_id, points, type, description)
                VALUES (?, ?, 'earned', 'Signup Bonus')
            ");
            $stmt->execute([$userId, SIGNUP_BONUS_POINTS]);
            
            logAudit($userId, 'loyalty_signup_bonus', SIGNUP_BONUS_POINTS . ' points awarded');
            return ['success' => true, 'points' => SIGNUP_BONUS_POINTS];
        } catch (PDOException $e) {
            error_log("Signup bonus failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Award points for purchase
     */
    public function awardPurchasePoints($userId, $orderAmount, $orderId) {
        $pointsEarned = calculatePointsEarned($orderAmount);
        
        if ($pointsEarned <= 0) {
            return ['success' => false, 'points' => 0];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO loyalty_transactions (user_id, points, type, description, order_id)
                VALUES (?, ?, 'earned', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $pointsEarned,
                "Points earned from order #$orderId",
                $orderId
            ]);
            
            logAudit($userId, 'loyalty_points_earned', "$pointsEarned points from order #$orderId");
            return ['success' => true, 'points' => $pointsEarned];
        } catch (PDOException $e) {
            error_log("Purchase points award failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Redeem points for discount
     */
    public function redeemPoints($userId, $points, $orderId) {
        // Validate points
        $availablePoints = $this->getUserPoints($userId);
        
        if ($points > $availablePoints) {
            return ['success' => false, 'message' => 'Insufficient points'];
        }
        
        if ($points <= 0) {
            return ['success' => false, 'message' => 'Invalid points amount'];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO loyalty_transactions (user_id, points, type, description, order_id)
                VALUES (?, ?, 'redeemed', ?, ?)
            ");
            $stmt->execute([
                $userId,
                -$points,
                "Points redeemed for order #$orderId",
                $orderId
            ]);
            
            logAudit($userId, 'loyalty_points_redeemed', "$points points for order #$orderId");
            return ['success' => true, 'points' => $points, 'discount' => $points * POINT_VALUE_BDT];
        } catch (PDOException $e) {
            error_log("Points redemption failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get transaction history
     */
    public function getTransactionHistory($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT lt.*, o.total_amount as order_amount
            FROM loyalty_transactions lt
            LEFT JOIN orders o ON lt.order_id = o.id
            WHERE lt.user_id = ?
            ORDER BY lt.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Award bonus points (admin action)
     */
    public function awardBonusPoints($userId, $points, $reason) {
        if ($points <= 0) {
            return ['success' => false, 'message' => 'Points must be positive'];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO loyalty_transactions (user_id, points, type, description)
                VALUES (?, ?, 'earned', ?)
            ");
            $stmt->execute([$userId, $points, "Bonus: $reason"]);
            
            logAudit($_SESSION['user_id'] ?? 0, 'loyalty_bonus_awarded', "$points points to user #$userId: $reason");
            return ['success' => true, 'points' => $points];
        } catch (PDOException $e) {
            error_log("Bonus points award failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Deduct points (admin penalty)
     */
    public function deductPoints($userId, $points, $reason) {
        if ($points <= 0) {
            return ['success' => false, 'message' => 'Points must be positive'];
        }
        
        $availablePoints = $this->getUserPoints($userId);
        
        if ($points > $availablePoints) {
            return ['success' => false, 'message' => 'User does not have enough points'];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO loyalty_transactions (user_id, points, type, description)
                VALUES (?, ?, 'redeemed', ?)
            ");
            $stmt->execute([$userId, -$points, "Deduction: $reason"]);
            
            logAudit($_SESSION['user_id'] ?? 0, 'loyalty_points_deducted', "$points points from user #$userId: $reason");
            return ['success' => true, 'points' => $points];
        } catch (PDOException $e) {
            error_log("Points deduction failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Calculate maximum redeemable points for an order
     */
    public function calculateMaxRedeemablePoints($userId, $orderAmount) {
        $availablePoints = $this->getUserPoints($userId);
        
        // Cannot redeem more than order amount (1 point = 1 BDT)
        $maxByAmount = floor($orderAmount / POINT_VALUE_BDT);
        
        return min($availablePoints, $maxByAmount);
    }
    
    /**
     * Get loyalty tier (optional feature)
     */
    public function getUserTier($userId) {
        $totalEarned = $this->db->prepare("
            SELECT COALESCE(SUM(points), 0) as total
            FROM loyalty_transactions
            WHERE user_id = ? AND type = 'earned'
        ");
        $totalEarned->execute([$userId]);
        $earned = $totalEarned->fetch()['total'];
        
        // Define tiers
        if ($earned >= 5000) {
            return ['tier' => 'Platinum', 'level' => 4, 'benefits' => '10% extra points'];
        } elseif ($earned >= 2000) {
            return ['tier' => 'Gold', 'level' => 3, 'benefits' => '5% extra points'];
        } elseif ($earned >= 500) {
            return ['tier' => 'Silver', 'level' => 2, 'benefits' => '2% extra points'];
        } else {
            return ['tier' => 'Bronze', 'level' => 1, 'benefits' => 'Standard points'];
        }
    }
    
    /**
     * Get leaderboard (top users by points)
     */
    public function getLeaderboard($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.full_name, 
                   COALESCE(SUM(lt.points), 0) as total_points
            FROM users u
            LEFT JOIN loyalty_transactions lt ON u.id = lt.user_id
            JOIN roles r ON u.role_id = r.id
            WHERE r.role_name = 'customer' AND u.status = 'active'
            GROUP BY u.id
            ORDER BY total_points DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get loyalty statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total points issued
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(points), 0) as total
            FROM loyalty_transactions
            WHERE type = 'earned'
        ");
        $stats['total_issued'] = $stmt->fetch()['total'];
        
        // Total points redeemed
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(ABS(points)), 0) as total
            FROM loyalty_transactions
            WHERE type = 'redeemed'
        ");
        $stats['total_redeemed'] = $stmt->fetch()['total'];
        
        // Active points (in circulation)
        $stats['active_points'] = $stats['total_issued'] - $stats['total_redeemed'];
        
        // Total users with points
        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT user_id) as count
            FROM loyalty_transactions
            WHERE points > 0
        ");
        $stats['users_with_points'] = $stmt->fetch()['count'];
        
        // Average points per user
        if ($stats['users_with_points'] > 0) {
            $stats['avg_points_per_user'] = round($stats['active_points'] / $stats['users_with_points'], 2);
        } else {
            $stats['avg_points_per_user'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Check if user has received signup bonus
     */
    public function hasReceivedSignupBonus($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM loyalty_transactions
            WHERE user_id = ? AND description = 'Signup Bonus'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'] > 0;
    }
    
    /**
     * Get points expiring soon (if implementing expiry)
     */
    public function getExpiringPoints($userId, $daysThreshold = 30) {
        // This assumes you add an expiry_date column to loyalty_transactions
        $stmt = $this->db->prepare("
            SELECT SUM(points) as expiring_points
            FROM loyalty_transactions
            WHERE user_id = ? 
            AND type = 'earned'
            AND expiry_date IS NOT NULL
            AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ");
        $stmt->execute([$userId, $daysThreshold]);
        $result = $stmt->fetch();
        return (int)($result['expiring_points'] ?? 0);
    }
    
    /**
     * Validate points transaction
     */
    private function validateTransaction($userId, $points, $type) {
        if ($points == 0) {
            return ['valid' => false, 'message' => 'Points cannot be zero'];
        }
        
        if ($type === 'redeemed' && $points > 0) {
            $points = -$points; // Ensure negative for redemption
        }
        
        if ($type === 'earned' && $points < 0) {
            return ['valid' => false, 'message' => 'Earned points must be positive'];
        }
        
        return ['valid' => true, 'points' => $points];
    }
}