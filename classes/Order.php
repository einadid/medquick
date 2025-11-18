<?php
require_once __DIR__ . '/../includes/db.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function createOrder($userId, $deliveryType, $address, $phone, $pointsUsed = 0) {
        $this->db->beginTransaction();
        
        try {
            $cart = new Cart();
            $cartItems = $cart->getItemsGroupedByShop($userId);
            
            if (empty($cartItems)) {
                throw new Exception("Cart is empty");
            }
            
            // Calculate totals
            $subtotal = $cart->getTotal($userId);
            $deliveryCharge = $deliveryType === 'home' ? HOME_DELIVERY_CHARGE : STORE_PICKUP_CHARGE;
            $discount = $pointsUsed * POINT_VALUE_BDT;
            $total = $subtotal + $deliveryCharge - $discount;
            
            // Create main order
            $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_amount, delivery_type, delivery_address, delivery_phone, points_used, status) 
                                        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$userId, $total, $deliveryType, $address, $phone, $pointsUsed]);
            $orderId = $this->db->lastInsertId();
            
            // Create parcels for each shop
            foreach ($cartItems as $shopId => $shopData) {
                // Calculate parcel total
                $parcelTotal = 0;
                foreach ($shopData['items'] as $item) {
                    $parcelTotal += $item['quantity'] * $item['selling_price'];
                }
                
                // Create parcel
                $stmt = $this->db->prepare("INSERT INTO parcels (order_id, shop_id, total_amount, status) 
                                            VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$orderId, $shopId, $parcelTotal]);
                $parcelId = $this->db->lastInsertId();
                
                // Log parcel status
                $stmt = $this->db->prepare("INSERT INTO parcel_status_log (parcel_id, status, notes) 
                                            VALUES (?, 'pending', 'Parcel created')");
                $stmt->execute([$parcelId]);
                
                // Create order items
                foreach ($shopData['items'] as $item) {
                    // Get the actual medicine_id from shop_medicines table
                    $medicineInfo = $this->db->prepare("SELECT medicine_id FROM shop_medicines WHERE id = ?");
                    $medicineInfo->execute([$item['id']]);
                    $medicineData = $medicineInfo->fetch();
                    
                    if (!$medicineData) {
                        throw new Exception("Medicine not found for shop_medicine_id: " . $item['id']);
                    }
                    
                    $actualMedicineId = $medicineData['medicine_id'];
                    
                    // Insert order item with correct medicine_id
                    $stmt = $this->db->prepare("INSERT INTO order_items (order_id, parcel_id, medicine_id, shop_id, quantity, price) 
                                                VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $orderId,
                        $parcelId,
                        $actualMedicineId,  // This is the correct medicine_id from medicines table
                        $shopId,
                        $item['quantity'],
                        $item['selling_price']
                    ]);
                    
                    // Reduce stock using shop_medicine_id
                    $stmt = $this->db->prepare("UPDATE shop_medicines SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['id']]);
                }
            }
            
            // Deduct points if used
            if ($pointsUsed > 0) {
                $stmt = $this->db->prepare("INSERT INTO loyalty_transactions (user_id, points, type, description, order_id) 
                                           VALUES (?, ?, 'redeemed', 'Points redeemed for order #$orderId', ?)");
                $stmt->execute([$userId, -$pointsUsed, $orderId]);
            }
            
            // Award points for this purchase
            $pointsEarned = calculatePointsEarned($total);
            if ($pointsEarned > 0) {
                $stmt = $this->db->prepare("INSERT INTO loyalty_transactions (user_id, points, type, description, order_id) 
                                           VALUES (?, ?, 'earned', 'Points earned from order #$orderId', ?)");
                $stmt->execute([$userId, $pointsEarned, $orderId]);
            }
            
            // Clear cart
            $cart->clearCart($userId);
            
            // Log
            logAudit($userId, 'order_created', "Order #$orderId created");
            
            $this->db->commit();
            return ['success' => true, 'order_id' => $orderId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Order creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getOrder($orderId) {
        $stmt = $this->db->prepare("SELECT o.*, u.full_name, u.email FROM orders o 
                                     JOIN users u ON o.user_id = u.id 
                                     WHERE o.id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }
    
    public function getOrderParcels($orderId) {
        $stmt = $this->db->prepare("SELECT p.*, s.name as shop_name FROM parcels p 
                                     JOIN shops s ON p.shop_id = s.id 
                                     WHERE p.order_id = ? ORDER BY p.id");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    public function getParcelItems($parcelId) {
        $stmt = $this->db->prepare("SELECT oi.*, m.name as medicine_name, m.generic_name 
                                     FROM order_items oi 
                                     JOIN medicines m ON oi.medicine_id = m.id 
                                     WHERE oi.parcel_id = ?");
        $stmt->execute([$parcelId]);
        return $stmt->fetchAll();
    }
    
    public function updateParcelStatus($parcelId, $status, $notes = '') {
        $stmt = $this->db->prepare("UPDATE parcels SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $parcelId]);
        
        // Log status change
        $stmt = $this->db->prepare("INSERT INTO parcel_status_log (parcel_id, status, notes) VALUES (?, ?, ?)");
        $stmt->execute([$parcelId, $status, $notes]);
        
        return true;
    }
}