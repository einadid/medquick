<?php
require_once __DIR__ . '/../includes/db.php';

class Shop {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all shops
     */
    public function getAllShops($activeOnly = false) {
        $sql = "SELECT * FROM shops";
        
        if ($activeOnly) {
            $sql .= " WHERE status = 'active'";
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get shop by ID
     */
    public function getShopById($id) {
        $stmt = $this->db->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get shop with statistics
     */
    public function getShopWithStats($id) {
        $shop = $this->getShopById($id);
        
        if (!$shop) {
            return null;
        }
        
        // Get total stock items
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT medicine_id) as total_medicines,
                   SUM(stock) as total_units,
                   SUM(stock * buying_price) as inventory_value
            FROM shop_medicines
            WHERE shop_id = ?
        ");
        $stmt->execute([$id]);
        $shop['inventory'] = $stmt->fetch();
        
        // Get total revenue
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_parcels,
                   SUM(total_amount) as total_revenue
            FROM parcels
            WHERE shop_id = ?
        ");
        $stmt->execute([$id]);
        $shop['sales'] = $stmt->fetch();
        
        // Get pending orders
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as pending_count
            FROM parcels
            WHERE shop_id = ? AND status IN ('pending', 'confirmed', 'packed')
        ");
        $stmt->execute([$id]);
        $shop['pending_orders'] = $stmt->fetch()['pending_count'];
        
        // Get low stock count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as low_stock_count
            FROM shop_medicines
            WHERE shop_id = ? AND stock < 20
        ");
        $stmt->execute([$id]);
        $shop['low_stock_count'] = $stmt->fetch()['low_stock_count'];
        
        return $shop;
    }
    
    /**
     * Create new shop
     */
    public function createShop($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO shops (name, address, city, phone, email, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $data['name'],
                $data['address'],
                $data['city'],
                $data['phone'],
                $data['email']
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Shop creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update shop
     */
    public function updateShop($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['name', 'address', 'city', 'phone', 'email', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $id;
            $sql = "UPDATE shops SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Shop update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deactivate shop
     */
    public function deactivateShop($id) {
        $stmt = $this->db->prepare("UPDATE shops SET status = 'inactive' WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Activate shop
     */
    public function activateShop($id) {
        $stmt = $this->db->prepare("UPDATE shops SET status = 'active' WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get shop inventory
     */
    public function getInventory($shopId, $filters = []) {
        $sql = "SELECT sm.*, m.name, m.generic_name, m.dosage_form, m.strength,
                c.name as category_name,
                DATEDIFF(sm.expiry_date, CURDATE()) as days_until_expiry
                FROM shop_medicines sm
                JOIN medicines m ON sm.medicine_id = m.id
                JOIN categories c ON m.category_id = c.id
                WHERE sm.shop_id = ?";
        
        $params = [$shopId];
        
        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $sql .= " AND (m.name LIKE ? OR m.generic_name LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $sql .= " AND sm.stock < 20";
        }
        
        if (isset($filters['expiring_soon']) && $filters['expiring_soon']) {
            $sql .= " AND sm.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
            $sql .= " AND sm.expiry_date >= CURDATE()";
        }
        
        $sql .= " ORDER BY m.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get shop orders/parcels
     */
    public function getOrders($shopId, $status = null, $dateRange = null) {
        $sql = "SELECT p.*, o.delivery_type, o.delivery_address, o.delivery_phone,
                u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
                FROM parcels p
                JOIN orders o ON p.order_id = o.id
                JOIN users u ON o.user_id = u.id
                WHERE p.shop_id = ?";
        
        $params = [$shopId];
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        if ($dateRange && isset($dateRange['start']) && isset($dateRange['end'])) {
            $sql .= " AND DATE(p.created_at) BETWEEN ? AND ?";
            $params[] = $dateRange['start'];
            $params[] = $dateRange['end'];
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get shop performance report
     */
    public function getPerformanceReport($shopId, $startDate = null, $endDate = null) {
        $params = [$shopId];
        $dateFilter = "";
        
        if ($startDate && $endDate) {
            $dateFilter = " AND DATE(p.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $report = [];
        
        // Total sales
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_orders,
                   SUM(p.total_amount) as total_revenue,
                   AVG(p.total_amount) as avg_order_value
            FROM parcels p
            WHERE p.shop_id = ? $dateFilter
        ");
        $stmt->execute($params);
        $report['sales'] = $stmt->fetch();
        
        // Sales by status
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count, SUM(total_amount) as amount
            FROM parcels
            WHERE shop_id = ? $dateFilter
            GROUP BY status
        ");
        $stmt->execute($params);
        $report['by_status'] = $stmt->fetchAll();
        
        // Top selling medicines
        $stmt = $this->db->prepare("
            SELECT m.name, SUM(oi.quantity) as total_sold,
                   SUM(oi.quantity * oi.price) as revenue
            FROM order_items oi
            JOIN medicines m ON oi.medicine_id = m.id
            JOIN parcels p ON oi.parcel_id = p.id
            WHERE oi.shop_id = ? $dateFilter
            GROUP BY m.id
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        $stmt->execute($params);
        $report['top_medicines'] = $stmt->fetchAll();
        
        // Daily sales trend
        $stmt = $this->db->prepare("
            SELECT DATE(created_at) as date,
                   COUNT(*) as orders,
                   SUM(total_amount) as revenue
            FROM parcels
            WHERE shop_id = ? $dateFilter
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ");
        $stmt->execute($params);
        $report['daily_trend'] = $stmt->fetchAll();
        
        return $report;
    }
    
    /**
     * Add medicine to shop inventory
     */
    public function addInventoryItem($shopId, $data) {
        try {
            // Check if already exists
            $stmt = $this->db->prepare("
                SELECT id FROM shop_medicines
                WHERE shop_id = ? AND medicine_id = ? AND batch_number = ?
            ");
            $stmt->execute([$shopId, $data['medicine_id'], $data['batch_number']]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'This batch already exists'];
            }
            
            // Insert new stock
            $stmt = $this->db->prepare("
                INSERT INTO shop_medicines 
                (shop_id, medicine_id, stock, buying_price, selling_price, expiry_date, batch_number)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $shopId,
                $data['medicine_id'],
                $data['stock'],
                $data['buying_price'],
                $data['selling_price'],
                $data['expiry_date'] ?? null,
                $data['batch_number']
            ]);
            
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            error_log("Inventory add failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update shop inventory item
     */
    public function updateInventoryItem($shopMedicineId, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = ['stock', 'buying_price', 'selling_price', 'expiry_date', 'batch_number'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $params[] = $shopMedicineId;
            $sql = "UPDATE shop_medicines SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Inventory update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Transfer stock between shops
     */
    public function transferStock($fromShopId, $toShopId, $medicineId, $quantity) {
        $this->db->beginTransaction();
        
        try {
            // Get source stock
            $stmt = $this->db->prepare("
                SELECT * FROM shop_medicines
                WHERE shop_id = ? AND medicine_id = ?
                ORDER BY expiry_date ASC
                LIMIT 1
            ");
            $stmt->execute([$fromShopId, $medicineId]);
            $sourceStock = $stmt->fetch();
            
            if (!$sourceStock || $sourceStock['stock'] < $quantity) {
                throw new Exception("Insufficient stock in source shop");
            }
            
            // Reduce from source
            $stmt = $this->db->prepare("
                UPDATE shop_medicines
                SET stock = stock - ?
                WHERE shop_id = ? AND medicine_id = ?
            ");
            $stmt->execute([$quantity, $fromShopId, $medicineId]);
            
            // Check if exists in destination
            $stmt = $this->db->prepare("
                SELECT * FROM shop_medicines
                WHERE shop_id = ? AND medicine_id = ? AND batch_number = ?
            ");
            $stmt->execute([$toShopId, $medicineId, $sourceStock['batch_number']]);
            $destStock = $stmt->fetch();
            
            if ($destStock) {
                // Update existing
                $stmt = $this->db->prepare("
                    UPDATE shop_medicines
                    SET stock = stock + ?
                    WHERE id = ?
                ");
                $stmt->execute([$quantity, $destStock['id']]);
            } else {
                // Insert new
                $stmt = $this->db->prepare("
                    INSERT INTO shop_medicines 
                    (shop_id, medicine_id, stock, buying_price, selling_price, expiry_date, batch_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $toShopId,
                    $medicineId,
                    $quantity,
                    $sourceStock['buying_price'],
                    $sourceStock['selling_price'],
                    $sourceStock['expiry_date'],
                    $sourceStock['batch_number']
                ]);
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Stock transferred successfully'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get shop staff members
     */
    public function getStaff($shopId) {
        $stmt = $this->db->prepare("
            SELECT u.*, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.shop_id = ? AND u.status = 'active'
            ORDER BY r.role_name, u.full_name
        ");
        $stmt->execute([$shopId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get shops by city
     */
    public function getShopsByCity($city) {
        $stmt = $this->db->prepare("
            SELECT * FROM shops
            WHERE city = ? AND status = 'active'
            ORDER BY name
        ");
        $stmt->execute([$city]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check medicine availability in shop
     */
    public function checkMedicineAvailability($shopId, $medicineId) {
        $stmt = $this->db->prepare("
            SELECT stock, selling_price, expiry_date, batch_number
            FROM shop_medicines
            WHERE shop_id = ? AND medicine_id = ? AND stock > 0
            ORDER BY expiry_date ASC
        ");
        $stmt->execute([$shopId, $medicineId]);
        return $stmt->fetch();
    }
    
    /**
     * Get shop comparison for a medicine
     */
    public function compareMedicinePrices($medicineId) {
        $stmt = $this->db->prepare("
            SELECT s.id, s.name, s.city, s.address,
                   sm.stock, sm.selling_price, sm.expiry_date
            FROM shops s
            JOIN shop_medicines sm ON s.id = sm.shop_id
            WHERE sm.medicine_id = ? AND sm.stock > 0 AND s.status = 'active'
            ORDER BY sm.selling_price ASC
        ");
        $stmt->execute([$medicineId]);
        return $stmt->fetchAll();
    }
}