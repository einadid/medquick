<?php
$pageTitle = 'Checkout';
require_once '../includes/header.php';
requireRole('customer');

require_once '../classes/Cart.php';
require_once '../classes/Order.php';

$cart = new Cart();
$cartItems = $cart->getItemsGroupedByShop($_SESSION['user_id']);
$cartTotal = $cart->getTotal($_SESSION['user_id']);

if (empty($cartItems)) {
    setFlash('error', 'Your cart is empty');
    redirect('/customer/cart.php');
}

// Get user's loyalty points
$userPoints = Database::getInstance()->fetchOne("
    SELECT COALESCE(SUM(points), 0) as total
    FROM loyalty_transactions
    WHERE user_id = ?
", [$_SESSION['user_id']])['total'];

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $deliveryType = $_POST['delivery_type'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $pointsToUse = min((int)$_POST['points_to_use'], $userPoints, $cartTotal);
    
    $order = new Order();
    $result = $order->createOrder($_SESSION['user_id'], $deliveryType, $address, $phone, $pointsToUse);
    
    if ($result['success']) {
        setFlash('success', 'Order placed successfully! Order ID: #' . $result['order_id']);
        redirect('/customer/orders.php');
    } else {
        $error = $result['message'];
    }
}

// Calculate delivery charge
$deliveryCharge = HOME_DELIVERY_CHARGE; // Will be updated by JS
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Checkout</h2>
</div>

<?php if (isset($error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 p-3 mb-4">
    <?php echo clean($error); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Checkout Form -->
    <div class="md:col-span-2">
        <form method="POST" id="checkoutForm">
            <?php echo csrfField(); ?>
            
            <!-- Delivery Information -->
            <div class="bg-white border-2 border-gray-300 p-6 mb-4">
                <h3 class="text-xl font-bold mb-4">Delivery Information</h3>
                
                <div class="mb-4">
                    <label class="block mb-2 font-bold">Full Name *</label>
                    <input type="text" name="full_name" required 
                           value="<?php echo clean(getCurrentUser()['full_name']); ?>"
                           class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div class="mb-4">
                    <label class="block mb-2 font-bold">Phone Number *</label>
                    <input type="text" name="phone" required 
                           value="<?php echo clean(getCurrentUser()['phone']); ?>"
                           class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div class="mb-4">
                    <label class="block mb-2 font-bold">Delivery Type *</label>
                    <select name="delivery_type" id="deliveryType" required 
                            class="w-full p-2 border-2 border-gray-400">
                        <option value="home">Home Delivery (+<?php echo formatPrice(HOME_DELIVERY_CHARGE); ?>)</option>
                        <option value="pickup">Pick from Store (Free)</option>
                    </select>
                </div>
                
                <div class="mb-4" id="addressField">
                    <label class="block mb-2 font-bold">Delivery Address *</label>
                    <textarea name="address" required rows="3" 
                              class="w-full p-2 border-2 border-gray-400"><?php echo clean(getCurrentUser()['address']); ?></textarea>
                </div>
            </div>
            
            <!-- Loyalty Points -->
            <div class="bg-white border-2 border-gray-300 p-6 mb-4">
                <h3 class="text-xl font-bold mb-4">Loyalty Points</h3>
                
                <div class="mb-4">
                    <div class="text-lg">Available Points: <strong><?php echo $userPoints; ?></strong></div>
                    <div class="text-sm text-gray-600">1 Point = 1 BDT discount</div>
                </div>
                
                <?php if ($userPoints > 0): ?>
                <div class="mb-4">
                    <label class="block mb-2 font-bold">Points to Use (Max: <?php echo min($userPoints, $cartTotal); ?>)</label>
                    <input type="number" name="points_to_use" id="pointsToUse" 
                           min="0" max="<?php echo min($userPoints, $cartTotal); ?>" 
                           value="0" class="w-full p-2 border-2 border-gray-400">
                </div>
                <?php else: ?>
                <input type="hidden" name="points_to_use" value="0">
                <div class="text-gray-600">You don't have any points yet. Earn 100 points for every 1000 BDT spent!</div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Method -->
            <div class="bg-white border-2 border-gray-300 p-6 mb-4">
                <h3 class="text-xl font-bold mb-4">Payment Method</h3>
                <div class="p-4 bg-yellow-50 border border-yellow-300">
                    <strong>Cash on Delivery (COD)</strong>
                    <p class="text-sm text-gray-600">Pay when you receive your order</p>
                </div>
            </div>
            
            <button type="submit" class="w-full p-4 bg-green-600 text-white font-bold text-lg">
                PLACE ORDER
            </button>
        </form>
    </div>
    
    <!-- Order Summary -->
    <div>
        <div class="bg-white border-2 border-gray-300 p-6 sticky top-4">
            <h3 class="text-xl font-bold mb-4">Order Summary</h3>
            
            <table class="w-full mb-4">
                <tr>
                    <td class="py-2">Subtotal:</td>
                    <td class="py-2 text-right font-bold" id="subtotal"><?php echo formatPrice($cartTotal); ?></td>
                </tr>
                <tr>
                    <td class="py-2">Delivery Charge:</td>
                    <td class="py-2 text-right font-bold" id="deliveryCharge"><?php echo formatPrice(HOME_DELIVERY_CHARGE); ?></td>
                </tr>
                <tr id="discountRow" style="display:none;">
                    <td class="py-2 text-green-600">Point Discount:</td>
                    <td class="py-2 text-right font-bold text-green-600" id="discount">0.00 BDT</td>
                </tr>
                <tr class="border-t-2 border-gray-300">
                    <td class="py-2 text-lg font-bold">Total:</td>
                    <td class="py-2 text-right text-lg font-bold text-green-600" id="grandTotal">
                        <?php echo formatPrice($cartTotal + HOME_DELIVERY_CHARGE); ?>
                    </td>
                </tr>
            </table>
            
            <div class="border-t pt-4">
                <div class="text-sm mb-2"><strong>Order contains:</strong></div>
                <?php foreach ($cartItems as $shopData): ?>
                <div class="text-sm mb-1">
                    📦 <?php echo clean($shopData['shop_name']); ?>: <?php echo count($shopData['items']); ?> item(s)
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4 p-3 bg-blue-50 border border-blue-300 text-sm">
                <strong>Note:</strong> Multiple parcels will be created if items are from different shops.
            </div>
        </div>
    </div>
</div>

<script>
const subtotal = <?php echo $cartTotal; ?>;
const homeDeliveryCharge = <?php echo HOME_DELIVERY_CHARGE; ?>;
const pickupCharge = <?php echo STORE_PICKUP_CHARGE; ?>;

document.getElementById('deliveryType').addEventListener('change', updateTotal);
document.getElementById('pointsToUse').addEventListener('input', updateTotal);

function updateTotal() {
    const deliveryType = document.getElementById('deliveryType').value;
    const pointsUsed = parseInt(document.getElementById('pointsToUse').value) || 0;
    
    // Update delivery charge
    const deliveryCharge = deliveryType === 'home' ? homeDeliveryCharge : pickupCharge;
    document.getElementById('deliveryCharge').textContent = formatPrice(deliveryCharge);
    
    // Update discount
    const discount = pointsUsed;
    if (discount > 0) {
        document.getElementById('discountRow').style.display = 'table-row';
        document.getElementById('discount').textContent = '-' + formatPrice(discount);
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
    
    // Update grand total
    const grandTotal = subtotal + deliveryCharge - discount;
    document.getElementById('grandTotal').textContent = formatPrice(grandTotal);
    
    // Toggle address field
    document.getElementById('addressField').style.display = deliveryType === 'home' ? 'block' : 'none';
}

function formatPrice(amount) {
    return amount.toFixed(2) + ' BDT';
}
</script>

<?php require_once '../includes/footer.php'; ?>