<?php
require_once __DIR__ . '/../includes/db.php';

class Cart {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function addItem($userId, $shopMedicineId, $quantity = 1) {
        // Check if item already exists
        $stmt = $this->db->prepare("SELECT * FROM cart WHERE user_id = ? AND shop_medicine_id = ?");
        $stmt->execute([$userId, $shopMedicineId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $newQty = $existing['quantity'] + $quantity;
            $stmt = $this->db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQty, $existing['id']]);
        } else {
            // Insert new
            $stmt = $this->db->prepare("INSERT INTO cart (user_id, shop_medicine_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $shopMedicineId, $quantity]);
        }
        
        return true;
    }
    
    public function getItems($userId) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   sm.id as shop_medicine_id,
                   sm.selling_price, 
                   sm.stock, 
                   sm.shop_id,
                   sm.medicine_id,
                   m.name as medicine_name, 
                   m.generic_name, 
                   m.image,
                   s.name as shop_name
            FROM cart c
            JOIN shop_medicines sm ON c.shop_medicine_id = sm.id
            JOIN medicines m ON sm.medicine_id = m.id
            JOIN shops s ON sm.shop_id = s.id
            WHERE c.user_id = ?
            ORDER BY s.id, m.name
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getItemsGroupedByShop($userId) {
        $items = $this->getItems($userId);
        $grouped = [];
        
        foreach ($items as $item) {
            $shopId = $item['shop_id'];
            if (!isset($grouped[$shopId])) {
                $grouped[$shopId] = [
                    'shop_name' => $item['shop_name'],
                    'items' => []
                ];
            }
            $grouped[$shopId]['items'][] = $item;
        }
        
        return $grouped;
    }
    
    public function updateQuantity($cartId, $quantity) {
        $stmt = $this->db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        return $stmt->execute([$quantity, $cartId]);
    }
    
    public function removeItem($cartId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE id = ?");
        return $stmt->execute([$cartId]);
    }
    
    public function clearCart($userId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function getTotal($userId) {
        $stmt = $this->db->prepare("
            SELECT SUM(c.quantity * sm.selling_price) as total
            FROM cart c
            JOIN shop_medicines sm ON c.shop_medicine_id = sm.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}