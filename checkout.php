<?php
// FILE: checkout.php (Final & Fixed Version)
require_once 'src/session.php';
require_once 'config/database.php';

if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) { redirect('login.php?redirect=checkout.php'); }

$user_id = $_SESSION['user_id'];
try {
    $user_stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $current_user = $user_stmt->fetch();
    
    $addresses_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $addresses_stmt->execute([$user_id]);
    $saved_addresses = $addresses_stmt->fetchAll();

    $shops = $pdo->query("SELECT DISTINCT s.id, s.name, s.address FROM shops s JOIN inventory_batches ib ON s.id = ib.shop_id WHERE ib.quantity > 0 AND ib.expiry_date > CURDATE() ORDER BY s.name ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Checkout page fetch error: " . $e->getMessage());
    $current_user = null; $saved_addresses = []; $shops = [];
}

$pageTitle = "Checkout";
include 'templates/header.php';
?>

<div class="fade-in bg-slate-50 py-12" x-data="{ useNewAddress: <?= empty($saved_addresses) ? 'true' : 'false' ?> }">
    <div class="container mx-auto px-4 sm:px-6 max-w-4xl">
        <h1 class="text-3xl font-bold text-slate-800 mb-8">Checkout</h1>
        
        <div class="bg-white p-8 rounded-lg shadow-md border">
            <form id="checkout-form" class="space-y-8">
                <!-- Step 1: Delivery Details -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-700 mb-4 border-b pb-2 flex items-center gap-3"><span class="w-8 h-8 bg-teal-600 text-white rounded-full flex items-center justify-center">1</span> Delivery Details</h2>
                    <?php if (!empty($saved_addresses)): ?>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600">Select a saved address or add a new one.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach($saved_addresses as $index => $addr): ?>
                            <label class="block p-4 border rounded-lg cursor-pointer hover:border-teal-500 has-[:checked]:bg-teal-50 has-[:checked]:border-teal-500">
                                <input type="radio" name="address_option" value="<?= e($addr['id']) ?>" @click="useNewAddress = false" class="sr-only" <?= $index === 0 ? 'checked' : '' ?>>
                                <div><p class="font-bold"><?= e($addr['full_name']) ?> <?php if($addr['is_default']) echo '<span class="text-xs bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full ml-2">Default</span>' ?></p><p class="text-sm text-gray-600 mt-2"><?= nl2br(e($addr['address_line'])) ?></p><p class="text-sm text-gray-600 mt-1">Phone: <?= e($addr['phone']) ?></p></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <label class="block p-4 border rounded-lg cursor-pointer hover:border-teal-500 has-[:checked]:bg-teal-50 has-[:checked]:border-teal-500">
                             <input type="radio" name="address_option" value="new" @click="useNewAddress = true" class="sr-only">
                             <p class="font-bold flex items-center gap-2"><i class="fas fa-plus-circle"></i> Add a New Address</p>
                        </label>
                    </div>
                    <?php endif; ?>
                    <div x-show="useNewAddress" x-transition class="mt-6 space-y-4 <?= !empty($saved_addresses) ? 'border-t pt-6' : '' ?>">
                        <p x-show="<?= !empty($saved_addresses) ? 'true' : 'false' ?>" class="text-md font-semibold text-gray-800">New Address Details:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label for="full_name">Full Name</label><input type="text" name="full_name" value="<?= e($current_user['full_name']) ?>" :required="useNewAddress" class="mt-1 w-full p-2 border rounded-md"></div>
                            <div><label for="phone">Contact Phone</label><input type="tel" name="phone" value="<?= e($current_user['phone']) ?>" :required="useNewAddress" class="mt-1 w-full p-2 border rounded-md"></div>
                        </div>
                        <div><label for="address">Full Delivery Address</label><textarea name="address" rows="3" :required="useNewAddress" class="mt-1 w-full p-2 border rounded-md"></textarea></div>
                    </div>
                </div>

                <!-- Step 2: Fulfilling Pharmacy -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-700 mb-4 border-b pb-2 flex items-center gap-3"><span class="w-8 h-8 bg-teal-600 text-white rounded-full flex items-center justify-center">2</span> Fulfilling Pharmacy</h2>
                    <select name="shop_id" id="shop_id" required class="w-full p-3 border rounded-md bg-white">
                        <option value="">-- Select a Pharmacy --</option>
                        <?php foreach($shops as $shop): ?><option value="<?= e($shop['id']) ?>"><?= e($shop['name']) ?> - (<?= e($shop['address']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Step 3: Payment & Confirmation -->
                <div>
                    <h2 class="text-xl font-semibold text-slate-700 mb-4 border-b pb-2 flex items-center gap-3"><span class="w-8 h-8 bg-teal-600 text-white rounded-full flex items-center justify-center">3</span> Payment & Confirmation</h2>
                    <div class="p-6 bg-slate-50 rounded-lg border">
                        <div class="mt-2 p-4 border-2 border-teal-500 rounded-lg bg-teal-50 flex items-center gap-3">
                            <i class="fas fa-money-bill-wave text-teal-600 text-2xl"></i>
                            <div><p class="font-bold text-teal-800">Cash on Delivery</p><p class="text-xs text-gray-600">Pay with cash when your order is delivered.</p></div>
                        </div>
                        <div class="mt-6 text-right">
                            <button type="submit" id="confirm-order-btn" class="btn-primary text-lg w-full md:w-auto disabled:bg-gray-400 disabled:cursor-not-allowed">
                                <span class="btn-text"><i class="fas fa-lock mr-2"></i> Confirm & Place Order</span>
                                <span class="btn-loader hidden"><i class="fas fa-spinner fa-spin"></i> Placing Order...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// **CRITICAL FIX:** We must include the footer, which loads main.js where showToast() is defined.
include 'templates/footer.php'; 
?>

<!-- We move the page-specific script AFTER the footer so main.js is loaded first. -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Check if showToast is available. If not, provide a fallback.
    if (typeof showToast === 'undefined') {
        window.showToast = function(message, type) {
            alert(`[${type.toUpperCase()}] ${message}`);
        }
    }

    const checkoutForm = document.getElementById('checkout-form');
    const confirmBtn = document.getElementById('confirm-order-btn');
    if (!checkoutForm || !confirmBtn) return;

    const btnText = confirmBtn.querySelector('.btn-text');
    const btnLoader = confirmBtn.querySelector('.btn-loader');

    checkoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const cart = JSON.parse(localStorage.getItem('quickmed_cart')) || {};
        if (Object.keys(cart).length === 0) {
            showToast('Your cart is empty. Please add items before checking out.', 'error');
            return;
        }
        if (!checkoutForm.checkValidity()) {
            showToast('Please fill all required fields correctly.', 'error');
            checkoutForm.reportValidity(); // This will show native browser validation errors
            return;
        }

        confirmBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoader.classList.remove('hidden');

        const formData = new FormData(checkoutForm);
        formData.append('cart_data', JSON.stringify(cart));
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        try {
            const response = await fetch('place_order.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                localStorage.removeItem('quickmed_cart');
                showToast(result.message, 'success');
                setTimeout(() => { window.location.href = `order_details.php?id=${result.order_id}`; }, 2000);
            } else {
                showToast(result.message || 'An unknown error occurred.', 'error', 5000);
                confirmBtn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoader.classList.add('hidden');
            }
        } catch (error) {
            showToast('An unexpected network error occurred.', 'error');
            confirmBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoader.classList.add('hidden');
        }
    });
});
</script>