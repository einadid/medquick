<?php
function validateCoupon($code, $user_id, $subtotal) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM user_coupons uc 
                WHERE uc.coupon_id = c.id AND uc.user_id = ?) as user_used
        FROM coupons c
        WHERE c.code = ? 
        AND c.is_active = 1 
        AND c.start_date <= NOW() 
        AND c.end_date >= NOW()
        AND (c.usage_limit IS NULL OR c.used_count < c.usage_limit)
    ");
    
    $stmt->execute([$user_id, $code]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        return ['valid' => false, 'message' => 'অবৈধ কুপন কোড'];
    }
    
    // Check if user has already used this coupon
    if ($coupon['user_used'] > 0) {
        return ['valid' => false, 'message' => 'আপনি ইতিমধ্যেই এই কুপনটি ব্যবহার করেছেন'];
    }
    
    // Check minimum order amount
    if ($subtotal < $coupon['min_order_amount']) {
        return ['valid' => false, 'message' => 'ন্যূনতম অর্ডার মূল্য ৳' . $coupon['min_order_amount']];
    }
    
    // Calculate discount
    if ($coupon['discount_type'] == 'percentage') {
        $discount = $subtotal * ($coupon['discount_value'] / 100);
        
        // Apply max discount if set
        if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = $coupon['discount_value'];
        
        // Don't allow discount more than subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
    }
    
    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount' => $discount,
        'discount_display' => $coupon['discount_type'] == 'percentage' ? 
            $coupon['discount_value'] . '%' : '৳' . $coupon['discount_value']
    ];
}

function applyCouponToUser($coupon_id, $user_id, $order_id = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Record coupon usage
        $stmt = $pdo->prepare("
            INSERT INTO user_coupons (user_id, coupon_id, order_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $coupon_id, $order_id]);
        
        // Update coupon usage count
        $pdo->prepare("
            UPDATE coupons 
            SET used_count = used_count + 1 
            WHERE id = ?
        ")->execute([$coupon_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
?>