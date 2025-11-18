<?php
require_once __DIR__ . '/../includes/db.php';

class Medicine {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Upload medicine image
     */
    public function uploadImage($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No file uploaded or upload error'];
        }
        
        // Validate file type
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'];
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File too large. Maximum 5MB'];
        }
        
        // Create unique filename
        $newName = 'medicine_' . uniqid() . '.' . $ext;
        $uploadPath = UPLOAD_PATH . 'medicines/';
        
        // Create directory if not exists
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath . $newName)) {
            return ['success' => true, 'filename' => 'medicines/' . $newName];
        }
        
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    /**
     * Delete medicine image
     */
    public function deleteImage($imagePath) {
        if ($imagePath && $imagePath !== 'medicines/default-medicine.png') {
            $fullPath = UPLOAD_PATH . $imagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get medicine image URL
     */
    public function getImageUrl($imagePath) {
        if ($imagePath && file_exists(UPLOAD_PATH . $imagePath)) {
            return UPLOAD_URL . $imagePath;
        }
        return UPLOAD_URL . 'medicines/default-medicine.png';
    }
    
    /**
     * Create new medicine with image
     */
    public function createMedicine($data, $imageFile = null) {
        try {
            $imagePath = 'medicines/default-medicine.png';
            
            // Upload image if provided
            if ($imageFile && isset($imageFile['tmp_name']) && $imageFile['tmp_name']) {
                $uploadResult = $this->uploadImage($imageFile);
                if ($uploadResult['success']) {
                    $imagePath = $uploadResult['filename'];
                } else {
                    return ['success' => false, 'message' => $uploadResult['message']];
                }
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO medicines (category_id, name, generic_name, description, 
                                      manufacturer, dosage_form, strength, image, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $data['category_id'],
                $data['name'],
                $data['generic_name'],
                $data['description'] ?? null,
                $data['manufacturer'],
                $data['dosage_form'],
                $data['strength'],
                $imagePath
            ]);
            
            $medicineId = $this->db->lastInsertId();
            return ['success' => true, 'id' => $medicineId, 'image' => $imagePath];
            
        } catch (PDOException $e) {
            error_log("Medicine creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update medicine with optional new image
     */
    public function updateMedicine($id, $data, $imageFile = null) {
        try {
            // Get current medicine data
            $currentMedicine = $this->getMedicineById($id);
            if (!$currentMedicine) {
                return ['success' => false, 'message' => 'Medicine not found'];
            }
            
            $imagePath = $currentMedicine['image'];
            
            // Upload new image if provided
            if ($imageFile && isset($imageFile['tmp_name']) && $imageFile['tmp_name']) {
                $uploadResult = $this->uploadImage($imageFile);
                if ($uploadResult['success']) {
                    // Delete old image if not default
                    $this->deleteImage($currentMedicine['image']);
                    $imagePath = $uploadResult['filename'];
                } else {
                    return ['success' => false, 'message' => $uploadResult['message']];
                }
            }
            
            $fields = [];
            $params = [];
            
            $allowedFields = ['category_id', 'name', 'generic_name', 'description', 
                             'manufacturer', 'dosage_form', 'strength', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            // Always update image path
            $fields[] = "image = ?";
            $params[] = $imagePath;
            
            if (empty($fields)) {
                return ['success' => false, 'message' => 'No data to update'];
            }
            
            $params[] = $id;
            $sql = "UPDATE medicines SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'image' => $imagePath];
            
        } catch (PDOException $e) {
            error_log("Medicine update failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // ... (keep all other methods from previous Medicine.php)
    
    public function getAllMedicines($filters = []) {
        $sql = "SELECT m.*, c.name as category_name,
                MIN(sm.selling_price) as min_price,
                MAX(sm.selling_price) as max_price,
                SUM(sm.stock) as total_stock
                FROM medicines m
                JOIN categories c ON m.category_id = c.id
                LEFT JOIN shop_medicines sm ON m.id = sm.medicine_id
                WHERE m.status = 'active'";
        
        $params = [];
        
        if (isset($filters['search']) && $filters['search']) {
            $sql .= " AND (m.name LIKE ? OR m.generic_name LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        if (isset($filters['category_id']) && $filters['category_id']) {
            $sql .= " AND m.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        $sql .= " GROUP BY m.id";
        
        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_low':
                    $sql .= " ORDER BY min_price ASC";
                    break;
                case 'price_high':
                    $sql .= " ORDER BY min_price DESC";
                    break;
                case 'name':
                default:
                    $sql .= " ORDER BY m.name ASC";
                    break;
            }
        } else {
            $sql .= " ORDER BY m.name ASC";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getMedicineById($id) {
        $stmt = $this->db->prepare("
            SELECT m.*, c.name as category_name
            FROM medicines m
            JOIN categories c ON m.category_id = c.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getMedicineWithShops($medicineId) {
        $medicine = $this->getMedicineById($medicineId);
        
        if (!$medicine) {
            return null;
        }
        
        $stmt = $this->db->prepare("
            SELECT sm.*, s.name as shop_name, s.city, s.address, s.phone,
                   DATEDIFF(sm.expiry_date, CURDATE()) as days_until_expiry
            FROM shop_medicines sm
            JOIN shops s ON sm.shop_id = s.id
            WHERE sm.medicine_id = ? AND sm.stock > 0 AND s.status = 'active'
            ORDER BY sm.selling_price ASC
        ");
        $stmt->execute([$medicineId]);
        $medicine['shops'] = $stmt->fetchAll();
        
        return $medicine;
    }
}