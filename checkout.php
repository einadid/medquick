<?php
// FILE: checkout.php (Updated for single-shop place_order.php - FIX FOR SAVED ADDRESSES)
require_once 'src/session.php';
require_once 'config/database.php';

// Ensure user is logged in and has the customer role
if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) {
    redirect('login.php?redirect=checkout.php');
}

$user_id = $_SESSION['user_id'];
$saved_addresses = [];
$current_user = null;

// Fetch user's data for the address modal. This is the only PHP data fetch needed initially.
try {
    $addresses_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $addresses_stmt->execute([$user_id]);
    $saved_addresses = $addresses_stmt->fetchAll();

    $user_stmt = $pdo->prepare("SELECT full_name, phone, points_balance FROM users WHERE id = ?"); // Fetch points_balance here
    $user_stmt->execute([$user_id]);
    $current_user = $user_stmt->fetch();
} catch (PDOException $e) {
    error_log("Checkout page pre-fetch error: " . $e->getMessage());
}

// Generate a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = "Secure Checkout";
include 'templates/header.php'; // Make sure this includes your CSRF meta tag
?>

<!-- Add this meta tag in your header.php or directly here for CSRF token -->
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">

<div class="fade-in bg-slate-50 py-12" x-data="checkoutApp(<?= htmlspecialchars(json_encode($saved_addresses)) ?>, <?= htmlspecialchars(json_encode($current_user)) ?>)">
    <div class="container mx-auto px-4 sm:px-6 max-w-4xl">
        <h1 class="text-3xl font-bold text-slate-800 mb-8">Secure Checkout</h1>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="text-center py-20 bg-white rounded-lg shadow-md border">
                <i class="fas fa-spinner fa-spin text-4xl text-teal-500"></i>
                <p class="mt-4 text-gray-600">Analyzing your cart for best delivery options...</p>
            </div>
        </template>
        
        <!-- Error/Empty State -->
        <template x-if="!loading && (shipments.length === 0 || !selectedShop) && !errorMessage">
            <div class="text-center py-20 bg-white rounded-lg shadow-md border">
                <i class="fas fa-shopping-cart text-6xl text-gray-300"></i>
                <h3 class="mt-4 text-xl font-semibold">Your Cart is Empty or No Shop Selected</h3>
                <a href="catalog.php" class="mt-6 btn-primary">Start Shopping</a>
            </div>
        </template>
        <template x-if="!loading && (shipments.length === 0 || !selectedShop) && errorMessage">
            <div class="text-center py-20 bg-white rounded-lg shadow-md border">
                <i class="fas fa-exclamation-circle text-5xl text-red-500"></i>
                <h3 class="mt-4 text-xl font-semibold text-gray-800" x-text="errorMessage"></h3>
                <a href="cart.php" class="mt-6 btn-primary">Return to Cart</a>
            </div>
        </template>
        
        <!-- Main Checkout Form (visible only after successful analysis) -->
        <form @submit.prevent="placeOrder" x-show="!loading && selectedShop" class="space-y-8" style="display: none;">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Delivery & Shipments -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Delivery Details -->
                    <div class="bg-white p-6 rounded-lg shadow-md border">
                        <h2 class="text-xl font-bold mb-4">1. Delivery Details</h2>
                        <div>
                            <div class="mb-4" x-show="savedAddresses.length > 0">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Delivery Address</label>
                                <div class="flex flex-col space-y-2">
                                    <template x-for="address in savedAddresses" :key="address.id">
                                        <label :for="'address_' + address.id" class="flex items-start p-3 border rounded-md cursor-pointer hover:bg-gray-50 has-[:checked]:border-teal-500 has-[:checked]:ring-1 has-[:checked]:ring-teal-500">
                                            <input type="radio" :id="'address_' + address.id" name="address_option" :value="address.id" x-model="selectedAddressId" class="mt-1 mr-2 focus:ring-teal-500 h-4 w-4 text-teal-600 border-gray-300">
                                            <div class="flex-grow">
                                                <p class="font-medium text-sm text-gray-900" x-text="address.full_name"></p>
                                                <p class="text-xs text-gray-600" x-text="address.address_line"></p>
                                                <p class="text-xs text-gray-500" x-text="address.phone"></p>
                                            </div>
                                            <span x-if="address.is_default == 1" class="ml-auto px-2 py-0.5 bg-teal-100 text-teal-800 text-xs font-medium rounded-full self-start">Default</span>
                                        </label>
                                    </template>
                                    <label for="address_new" class="flex items-start p-3 border rounded-md cursor-pointer hover:bg-gray-50 has-[:checked]:border-teal-500 has-[:checked]:ring-1 has-[:checked]:ring-teal-500">
                                        <input type="radio" id="address_new" name="address_option" value="new" x-model="selectedAddressId" class="mt-1 mr-2 focus:ring-teal-500 h-4 w-4 text-teal-600 border-gray-300">
                                        <div class="flex-grow">
                                            <p class="font-medium text-sm text-gray-900">Add New Address</p>
                                            <p class="text-xs text-gray-600">Enter details for a new delivery location.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div x-show="selectedAddressId === 'new'" class="space-y-4 mt-6 p-4 bg-gray-50 rounded-md border">
                                <h3 class="font-semibold text-md text-gray-800">New Address Details</h3>
                                <div>
                                    <label for="new_address_name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                                    <input type="text" id="new_address_name" name="full_name" x-model="newAddress.name" placeholder="Enter recipient's full name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
                                </div>
                                <div>
                                    <label for="new_address_phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                                    <input type="tel" id="new_address_phone" name="phone" x-model="newAddress.phone" placeholder="e.g., +8801XXXXXXXXX" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required>
                                </div>
                                <div>
                                    <label for="new_address_line" class="block text-sm font-medium text-gray-700">Full Address <span class="text-red-500">*</span></label>
                                    <textarea id="new_address_line" name="address" x-model="newAddress.address" rows="3" placeholder="Street, City, Area, Zip Code (e.g., House #123, Road #4, Gulshan, Dhaka 1212)" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" required></textarea>
                                </div>
                            </div>
                            <!-- IMPORTANT FIX: Hidden inputs to pass data for selected existing address -->
                            <template x-if="selectedAddressId !== 'new' && selectedAddressData">
                                <input type="hidden" name="full_name" :value="selectedAddressData.full_name">
                                <input type="hidden" name="phone" :value="selectedAddressData.phone">
                                <input type="hidden" name="address" :value="selectedAddressData.address_line">
                            </template>
                        </div>
                    </div>
                    
                    <!-- Order Review -->
                    <div class="bg-white p-6 rounded-lg shadow-md border">
                        <h2 class="text-xl font-bold mb-4">2. Order Review</h2>
                        <div x-show="shipments.length > 1" class="p-3 bg-yellow-50 border-l-4 border-yellow-400 text-sm text-yellow-800 mb-4">
                            <p>Multiple shops can fulfill your order. Please select one below.</p>
                        </div>

                        <!-- Shop Selection (If multiple options exist) -->
                        <div x-show="shipments.length > 0" class="mb-6">
                            <label for="shop_selector" class="block text-sm font-medium text-gray-700 mb-2">Select Shop for Fulfillment:</label>
                            <select id="shop_selector" x-model="selectedShop" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm rounded-md">
                                <template x-for="shipment in shipments" :key="shipment.shop_id">
                                    <option :value="shipment.shop_id" x-text="`${shipment.shop_name} (Total: ৳${shipment.subtotal.toFixed(2)})`"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Display selected shop's items -->
                        <template x-if="selectedShop">
                            <div class="border rounded-md p-4">
                                <h3 class="font-semibold mb-2">Order from <span class="text-teal-600" x-text="currentShipment.shop_name"></span></h3>
                                <div class="divide-y">
                                    <template x-for="item in currentShipment.items" :key="item.id">
                                        <div class="flex items-center gap-4 py-2">
                                            <img :src="item.image" class="w-12 h-12 border rounded object-cover" alt="Medicine image">
                                            <div class="flex-grow">
                                                <p class="font-medium text-sm" x-text="item.name"></p>
                                                <p class="text-xs text-gray-500" x-text="'Qty: ' + item.qty"></p>
                                            </div>
                                            <p class="font-semibold text-sm" x-text="'৳' + (item.price * item.qty).toFixed(2)"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Display unavailable items if any -->
                        <template x-if="unavailableItems.length > 0">
                            <div class="border border-red-300 bg-red-50 p-4 rounded-md mt-4">
                                <h3 class="font-semibold text-red-700 mb-2">Unavailable Items (will not be ordered)</h3>
                                <ul class="list-disc pl-5 text-sm text-red-600">
                                    <template x-for="item in unavailableItems" :key="item.id">
                                        <li x-text="item.name + ' (Out of stock)'"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
                <!-- Right Column: Final Bill -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-lg shadow-md border sticky top-24">
                        <h2 class="text-xl font-bold border-b pb-4 mb-4">Final Bill</h2>
                        <div class="space-y-2">
                            <div class="flex justify-between"><span>Products Subtotal</span><span class="font-medium" x-text="'৳' + productsSubtotal.toFixed(2)"></span></div>
                            <div class="flex justify-between"><span>Delivery Fee</span><span class="font-medium" x-text="'৳' + deliveryFee.toFixed(2)"></span></div>
                            <!-- Point redemption input -->
                            <div class="flex justify-between items-center">
                                <span>Use Loyalty Points (Available: <span x-text="userPoints"></span>)</span>
                                <input type="number" name="points_to_use" x-model.number="pointsToUse" @input="validatePointsInput" :max="userPoints" min="0" step="1" class="w-24 text-right border border-gray-300 rounded-md shadow-sm py-1 px-2 text-sm">
                            </div>
                            <div class="flex justify-between"><span>Points Redemption</span><span class="font-medium text-red-600" x-text="'-৳' + redeemedPointsAmount.toFixed(2)"></span></div>
                            <div class="border-t my-2"></div>
                            <div class="flex justify-between font-bold text-2xl"><span class="text-slate-800">Grand Total</span><span class="text-teal-600" x-text="'৳' + grandTotal.toFixed(2)"></span></div>
                        </div>
                        <button type="submit" :disabled="placingOrder || !selectedShop || productsSubtotal === 0 || (!selectedAddressId || (selectedAddressId === 'new' && (!newAddress.name || !newAddress.phone || !newAddress.address)) || (selectedAddressId !== 'new' && !selectedAddressData))" class="w-full btn-primary text-lg mt-6 py-3 disabled:bg-gray-400">
                            <span x-show="!placingOrder">Confirm & Place Order</span>
                            <span x-show="placingOrder"><i class="fas fa-spinner fa-spin"></i> Placing Order...</span>
                        </button>
                         <template x-if="errorMessage"><p class="text-red-500 text-center text-sm mt-3" x-text="errorMessage"></p></template>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
<script>
// Mock showToast for demonstration if not already defined globally
if (typeof showToast !== 'function') {
    window.showToast = function(message, type = 'info', duration = 3000) {
        console.log(`Toast (${type}): ${message}`);
        // Basic visible toast for debug, replace with your actual toast component
        let toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 p-3 rounded-md shadow-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        toast.innerText = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('checkoutApp', (savedAddresses, currentUser) => ({
        shipments: [], 
        unavailableItems: [], 
        loading: true, 
        placingOrder: false, 
        errorMessage: '',
        deliveryFee: 99, 
        userPoints: 0, 
        pointsToUse: 0,
        redeemedPointsAmount: 0,
        
        savedAddresses: savedAddresses,
        selectedAddressId: savedAddresses.find(a => a.is_default == 1)?.id || (savedAddresses.length > 0 ? savedAddresses[0].id : 'new'),
        newAddress: { 
            name: currentUser?.full_name || '', 
            phone: currentUser?.phone || '', 
            address: '' 
        },
        selectedShop: null, 

        init() {
            this.analyzeCart();
            this.userPoints = currentUser?.points_balance || 0; // Ensure points_balance is fetched
            this.$watch('pointsToUse', () => this.calculateGrandTotal()); 
            this.$watch('selectedShop', () => this.calculateGrandTotal()); 
            this.$watch('selectedAddressId', (value) => {
                // When address selection changes, recalculate grand total if needed (for points)
                this.calculateGrandTotal();
                // If a saved address is selected, ensure we have its data readily available
                if (value !== 'new') {
                    // This will trigger the hidden inputs to update
                    this.selectedAddressData; // Accessing the getter makes it reactive
                }
            });
        },
        
        async analyzeCart() {
            this.loading = true;
            this.errorMessage = ''; 
            const cart = JSON.parse(localStorage.getItem('quickmed_cart')) || {};

            if (Object.keys(cart).length === 0) {
                this.errorMessage = 'Your cart is empty. Please add items to proceed.';
                this.loading = false;
                return;
            }

            try {
                const response = await fetch('api_analyze_cart.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ cart: cart }) 
                });
                const result = await response.json();

                if (result.success) {
                    this.shipments = result.shipments;
                    this.unavailableItems = result.unavailable_items;
                    
                    if (this.shipments.length > 0) {
                        this.selectedShop = this.shipments[0].shop_id; // Default to first available shop
                        this.calculateGrandTotal(); 
                    } else if (this.unavailableItems.length > 0) {
                        this.errorMessage = 'All items in your cart are currently out of stock or expired.';
                    } else {
                        this.errorMessage = 'Your cart is empty or could not be processed for the selected shop.';
                    }

                } else { 
                    this.errorMessage = result.message || 'Could not analyze your cart. Please try again.'; 
                    console.error('API Error:', result.message);
                }
            } catch (e) { 
                this.errorMessage = 'A network error occurred while analyzing your cart.'; 
                console.error('Network Error:', e); 
            } finally { 
                this.loading = false; 
            }
        },

        get currentShipment() {
            return this.shipments.find(s => s.shop_id === this.selectedShop);
        },

        get productsSubtotal() { 
            return this.currentShipment ? this.currentShipment.subtotal : 0; 
        },

        // New getter to easily access selected address data for hidden inputs
        get selectedAddressData() {
            if (this.selectedAddressId !== 'new') {
                return this.savedAddresses.find(a => a.id == this.selectedAddressId);
            }
            return null; // No saved address selected
        },

        calculateGrandTotal() {
            let subtotal = this.productsSubtotal;
            this.deliveryFee = (subtotal > 0) ? 99 : 0;
            let total = subtotal + this.deliveryFee;

            this.redeemedPointsAmount = 0;
            if (this.pointsToUse > 0 && this.userPoints > 0) {
                let actualPointsToUse = Math.min(this.pointsToUse, this.userPoints);
                this.redeemedPointsAmount = Math.min(actualPointsToUse, total); // Can't redeem more than total
                total -= this.redeemedPointsAmount;
            }
            // Ensure total doesn't go below zero due to points (though redeemedPointsAmount should handle this)
            return Math.max(0, total);
        },

        get grandTotal() {
            return this.calculateGrandTotal();
        },

        validatePointsInput() {
            // Ensure points are non-negative
            if (this.pointsToUse < 0) this.pointsToUse = 0;
            // Ensure points don't exceed available points
            if (this.pointsToUse > this.userPoints) this.pointsToUse = this.userPoints;
            // Ensure points don't exceed the total order amount
            const maxRedeemableFromTotal = Math.floor(this.productsSubtotal + this.deliveryFee);
            if (this.pointsToUse > maxRedeemableFromTotal) {
                this.pointsToUse = maxRedeemableFromTotal;
            }
        },
        
        async placeOrder(event) {
            this.placingOrder = true;
            this.errorMessage = ''; 

            // Client-side validation for address
            if (!this.selectedAddressId) {
                this.errorMessage = 'Please select or add a delivery address.';
                this.placingOrder = false;
                return;
            }
            if (this.selectedAddressId === 'new' && (!this.newAddress.name || !this.newAddress.phone || !this.newAddress.address)) {
                this.errorMessage = 'Please fill in all required new address details.';
                this.placingOrder = false;
                return;
            }
            if (this.selectedAddressId !== 'new' && !this.selectedAddressData) {
                 this.errorMessage = 'Selected saved address data is missing. Please select another or add new.';
                 this.placingOrder = false;
                 return;
            }


            if (!this.selectedShop) {
                this.errorMessage = 'No shop is selected for order fulfillment.';
                this.placingOrder = false;
                return;
            }
            if (this.productsSubtotal === 0) {
                this.errorMessage = 'Your cart is empty or contains no available items from the selected shop to order.';
                this.placingOrder = false;
                return;
            }

            const form = event.target;
            const formData = new FormData(form);

            // Add cart data to the form data
            const cart = JSON.parse(localStorage.getItem('quickmed_cart')) || {};
            formData.append('cart_data', JSON.stringify(cart));
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('shop_id', this.selectedShop);
            
            // The `address_option`, `full_name`, `phone`, `address` fields are now correctly populated
            // by the HTML form (either direct inputs or hidden inputs).
            
            // Add points to use
            formData.append('points_to_use', this.pointsToUse);

            try {
                const response = await fetch('place_order.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    localStorage.removeItem('quickmed_cart');
                    showToast('Order placed successfully!', 'success');
                    setTimeout(() => window.location.href = `order_details.php?id=${result.order_id}`, 2000);
                } else {
                    this.errorMessage = result.message; 
                    showToast(result.message, 'error', 5000);
                }
            } catch (e) {
                this.errorMessage = 'A network error occurred. Please check your connection.';
                showToast('A network error occurred.', 'error');
                console.error('Network error during order placement:', e);
            } finally {
                this.placingOrder = false;
            }
        }
    }));
});
</script>