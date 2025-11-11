<?php
session_start();
require '../../includes/db_connect.php';

// bKash API credentials
$bkash_app_key = 'your_app_key';
$bkash_app_secret = 'your_app_secret';
$bkash_username = 'your_username';
$bkash_password = 'your_password';
$bkash_base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';

// Get payment ID from session
$payment_id = $_SESSION['bkash_payment_id'] ?? '';
$order_id = $_SESSION['current_order_id'] ?? 0;

if (!$payment_id || !$order_id) {
    die("ইনভ্যালিড রিকোয়েস্ট");
}

// Execute bKash payment
function executeBkashPayment($payment_id) {
    global $bkash_app_key, $bkash_base_url;
    
    $token = getBkashToken();
    if (!$token) return false;
    
    $url = "$bkash_base_url/tokenized/checkout/execute";
    $data = array('paymentID' => $payment_id);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: ' . $token,
        'X-APP-Key: ' . $bkash_app_key
    ));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Get bKash token
function getBkashToken() {
    global $bkash_app_key, $bkash_app_secret, $bkash_username, $bkash_password, $bkash_base_url;
    
    $url = "$bkash_base_url/tokenized/checkout/token/grant";
    $data = array(
        'app_key' => $bkash_app_key,
        'app_secret' => $bkash_app_secret
    );
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'password: ' . $bkash_password,
        'username: ' . $bkash_username
    ));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['id_token'] ?? '';
}

// Execute the payment
$payment = executeBkashPayment($payment_id);

if ($payment && $payment['statusCode'] == '0000') {
    // Payment successful
    try {
        $pdo->beginTransaction();
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'paid', 
                payment_status = 'completed',
                payment_method = 'bkash',
                transaction_id = ?,
                updated_at = NOW()
            WHERE id = ? AND customer_id = ?
        ");
        
        $stmt->execute([
            $payment['paymentID'],
            $order_id,
            $_SESSION['user_id']
        ]);
        
        // Insert payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments (order_id, amount, payment_method, transaction_id, status)
            VALUES (?, ?, 'bkash', ?, 'completed')
        ");
        
        $stmt->execute([
            $order_id,
            $payment['amount'],
            $payment['paymentID']
        ]);
        
        $pdo->commit();
        
        // Clear session data
        unset($_SESSION['bkash_payment_id']);
        unset($_SESSION['current_order_id']);
        
        // Redirect to success page
        header("Location: ../order_success.php?order_id=$order_id");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "পেমেন্ট ভেরিফাই করতে সমস্যা: " . $e->getMessage();
    }
} else {
    $error = "পেমেন্ট ব্যর্থ হয়েছে: " . ($payment['statusMessage'] ?? 'অজানা ত্রুটি');
}

// If we reach here, there was an error
$_SESSION['payment_error'] = $error;
header("Location: ../checkout.php?order_id=$order_id&error=1");
exit();
?>