<?php
$pageTitle = 'Shopping Cart';
require_once '../includes/header.php';
requireRole('customer');

require_once '../classes/Cart.php';
$cart = new Cart();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $cart->updateQuantity($_POST['cart_id'], $_POST['quantity']);
                setFlash('success', 'Cart updated');
                break;
            case 'remove':
                $cart->removeItem($_POST['cart_id']);
                setFlash('success', 'Item removed from cart');
                break;
        }
        redirect('/customer/cart.php');
    }
}

$cartItems = $cart->getItemsGroupedByShop($_SESSION['user_id']);
$cartTotal = $cart->getTotal($_SESSION['user_id']);
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Shopping Cart</h2>
</div>

<?php if (empty($cartItems)): ?>
    <div class="bg-white border-2 border-gray-300 p-6 text-center">
        <p class="text-gray-600 mb-4">Your cart is empty</p>
        <a href="medicines.php" class="inline-block px-6 py-3 bg-blue-600 text-white font-bold">
            BROWSE MEDICINES
        </a>
    </div>
<?php else: ?>
    <!-- Cart Items by Shop -->
    <?php foreach ($cartItems as $shopId => $shopData): ?>
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <h3 class="text-xl font-bold mb-4 bg-gray-100 p-2">
            📦 <?php echo clean($shopData['shop_name']); ?>
        </h3>
        
        <table class="w-full border-2 border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left border">Medicine</th>
                    <th class="p-2 text-left border">Generic Name</th>
                    <th class="p-2 text-right border">Price</th>
                    <th class="p-2 text-center border">Quantity</th>
                    <th class="p-2 text-right border">Subtotal</th>
                    <th class="p-2 text-center border">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $shopSubtotal = 0;
                foreach ($shopData['items'] as $item): 
                    $itemTotal = $item['quantity'] * $item['selling_price'];
                    $shopSubtotal += $itemTotal;
                ?>
                <tr>
                    <td class="p-2 border">
                        <div class="font-bold"><?php echo clean($item['medicine_name']); ?></div>
                    </td>
                    <td class="p-2 border text-sm text-gray-600">
                        <?php echo clean($item['generic_name']); ?>
                    </td>
                    <td class="p-2 border text-right">
                        <?php echo formatPrice($item['selling_price']); ?>
                    </td>
                    <td class="p-2 border text-center">
                        <form method="POST" class="inline">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                   min="1" max="<?php echo $item['stock']; ?>" 
                                   class="w-16 p-1 border text-center"
                                   onchange="this.form.submit()">
                        </form>
                        <div class="text-xs text-gray-500">Max: <?php echo $item['stock']; ?></div>
                    </td>
                    <td class="p-2 border text-right font-bold">
                        <?php echo formatPrice($itemTotal); ?>
                    </td>
                    <td class="p-2 border text-center">
                        <form method="POST" onsubmit="return confirm('Remove this item?')">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="px-3 py-1 bg-red-600 text-white">REMOVE</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="bg-gray-50">
                    <td colspan="4" class="p-2 border text-right font-bold">Shop Subtotal:</td>
                    <td class="p-2 border text-right font-bold"><?php echo formatPrice($shopSubtotal); ?></td>
                    <td class="p-2 border"></td>
                </tr>
            </tbody>
        </table>
        
        <div class="mt-2 p-2 bg-yellow-50 border border-yellow-300 text-sm">
            ℹ️ This parcel will be shipped from <strong><?php echo clean($shopData['shop_name']); ?></strong>
        </div>
    </div>
    <?php endforeach; ?>
    
    <!-- Cart Summary -->
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <div class="text-sm text-gray-600">Total Parcels: <?php echo count($cartItems); ?></div>
                <div class="text-2xl font-bold">Cart Total: <?php echo formatPrice($cartTotal); ?></div>
            </div>
            <div>
                <a href="checkout.php" class="inline-block px-8 py-3 bg-green-600 text-white font-bold text-lg">
                    PROCEED TO CHECKOUT →
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>