<?php
require_once __DIR__ . '/../includes/db.php';

class News {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Upload news image
     */
    public function uploadImage($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No file uploaded or upload error'];
        }
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File too large. Maximum 5MB'];
        }
        
        $newName = 'news_' . uniqid() . '.' . $ext;
        $uploadPath = UPLOAD_PATH . 'news/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath . $newName)) {
            return ['success' => true, 'filename' => 'news/' . $newName];
        }
        
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    /**
     * Get news image URL
     */
    public function getImageUrl($imagePath) {
        if ($imagePath && file_exists(UPLOAD_PATH . $imagePath)) {
            return UPLOAD_URL . $imagePath;
        }
        return UPLOAD_URL . 'news/default-news.png';
    }
    
    /**
     * Create news with image
     */
    public function createNews($data, $imageFile = null) {
        try {
            $imagePath = null;
            
            if ($imageFile && isset($imageFile['tmp_name']) && $imageFile['tmp_name']) {
                $uploadResult = $this->uploadImage($imageFile);
                if ($uploadResult['success']) {
                    $imagePath = $uploadResult['filename'];
                }
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO news (title, content, image, published_by, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['title'],
                $data['content'],
                $imagePath,
                $data['published_by'],
                $data['status']
            ]);
            
            return ['success' => true, 'id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            error_log("News creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get all news - FIXED VERSION
     */
    public function getAllNews($status = 'published', $limit = null) {
        $sql = "SELECT n.*, u.full_name as author_name
                FROM news n
                JOIN users u ON n.published_by = u.id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE n.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY n.created_at DESC";
        
        // LIMIT directly add - no placeholder
        if ($limit) {
            $limit = (int)$limit; // Sanitize to integer
            $sql .= " LIMIT " . $limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get news by ID
     */
    public function getNewsById($id) {
        $stmt = $this->db->prepare("
            SELECT n.*, u.full_name as author_name
            FROM news n
            JOIN users u ON n.published_by = u.id
            WHERE n.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Update news
     */
    public function updateNews($id, $data, $imageFile = null) {
        try {
            // Get current news
            $currentNews = $this->getNewsById($id);
            if (!$currentNews) {
                return ['success' => false, 'message' => 'News not found'];
            }
            
            $imagePath = $currentNews['image'];
            
            // Upload new image if provided
            if ($imageFile && isset($imageFile['tmp_name']) && $imageFile['tmp_name']) {
                $uploadResult = $this->uploadImage($imageFile);
                if ($uploadResult['success']) {
                    // Delete old image
                    if ($imagePath) {
                        $fullPath = UPLOAD_PATH . $imagePath;
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                    $imagePath = $uploadResult['filename'];
                }
            }
            
            $stmt = $this->db->prepare("
                UPDATE news 
                SET title = ?, content = ?, image = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['title'],
                $data['content'],
                $imagePath,
                $data['status'],
                $id
            ]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("News update failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Delete news
     */
    public function deleteNews($id) {
        try {
            // Get news to delete image
            $news = $this->getNewsById($id);
            if ($news && $news['image']) {
                $fullPath = UPLOAD_PATH . $news['image'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            
            $stmt = $this->db->prepare("DELETE FROM news WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("News deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}