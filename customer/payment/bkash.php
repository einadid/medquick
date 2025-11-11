<?php
session_start();
require '../../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../../login.php');
    exit();
}

// Get order ID from session or URL
$order_id = $_GET['order_id'] ?? $_SESSION['current_order_id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    die("অর্ডার খুঁজে পাওয়া যায়নি!");
}

// bKash API credentials (use sandbox credentials for testing)
$bkash_app_key = 'your_app_key';
$bkash_app_secret = 'your_app_secret';
$bkash_username = 'your_username';
$bkash_password = 'your_password';
$bkash_base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'; // Sandbox URL

// Get bKash token
function getBkashToken() {
    global $bkash_app_key, $bkash_app_secret, $bkash_base_url;
    
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

// Create bKash payment
function createBkashPayment($order_id, $amount, $callback_url) {
    global $bkash_app_key, $bkash_base_url;
    
    $token = getBkashToken();
    if (!$token) return false;
    
    $url = "$bkash_base_url/tokenized/checkout/create";
    $data = array(
        'mode' => '0011',
        'payerReference' => 'customer_' . $_SESSION['user_id'],
        'callbackURL' => $callback_url,
        'amount' => $amount,
        'currency' => 'BDT',
        'intent' => 'sale',
        'merchantInvoiceNumber' => 'ORD' . $order_id
    );
    
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

// Handle payment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $callback_url = 'https://yourwebsite.com/customer/payment/bkash_callback.php';
    $payment = createBkashPayment($order_id, $order['final_amount'], $callback_url);
    
    if ($payment && isset($payment['paymentID'])) {
        // Save payment ID in session
        $_SESSION['bkash_payment_id'] = $payment['paymentID'];
        $_SESSION['current_order_id'] = $order_id;
        
        // Redirect to bKash payment page
        header('Location: ' . $payment['bkashURL']);
        exit();
    } else {
        $error = "পেমেন্ট তৈরি করতে সমস্যা হয়েছে। দয়া করে আবার চেষ্টা করুন।";
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6 text-center">bKash পেমেন্ট</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="mb-6 text-center">
            <img src="../../assets/images/payment/bkash.png" alt="bKash" class="h-16 mx-auto mb-4">
            <p class="text-gray-600 mb-2">অর্ডার নম্বর: <span class="font-semibold">#<?php echo $order_id; ?></span></p>
            <p class="text-gray-600 mb-2">পরিশোধের পরিমাণ: <span class="font-semibold">৳<?php echo number_format($order['final_amount'], 2); ?></span></p>
        </div>
        
        <form method="POST" action="">
            <button type="submit" class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 font-bold">
                bKash এ পেমেন্ট করুন
            </button>
        </form>
        
        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <p class="text-yellow-700 text-sm">
                <i class="fas fa-info-circle mr-1"></i> 
                পেমেন্ট সম্পন্ন করতে bKash অ্যাপে লগইন করতে হবে। আপনার bKash অ্যাকাউন্টে পর্যাপ্ত ব্যালেন্স আছে কিনা নিশ্চিত করুন।
            </p>
        </div>
        
        <div class="mt-6 text-center">
            <a href="../checkout.php?order_id=<?php echo $order_id; ?>" class="text-blue-600 hover:underline">
                <i class="fas fa-arrow-left mr-1"></i> পেমেন্ট মেথড পরিবর্তন করুন
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>