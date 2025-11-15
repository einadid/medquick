<?php
// FILE: cart.php (The Ultimate Cart Experience with Alpine.js)
require_once 'src/session.php';
require_once 'config/database.php';
ensure_user_session_data(); // Ensure session is fresh

if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) {
    redirect('login.php?redirect=cart.php');
}

$pageTitle = "My Shopping Cart";
include 'templates/header.php';
?>

<div class="fade-in bg-slate-50 py-12" x-data="shoppingCart()">
    <div class="container mx-auto px-4 sm:px-6">
        <h1 class="text-3xl font-bold text-slate-800 mb-8">Shopping Cart</h1>
        
        <template x-if="loading">
            <div class="text-center py-16"><i class="fas fa-spinner fa-spin text-4xl text-teal-500"></i><p class="mt-4">Loading Cart...</p></div>
        </template>
        
        <template x-if="!loading && items.length === 0">
            <div class="text-center py-20 bg-white rounded-lg shadow-md border"><i class="fas fa-shopping-cart text-6xl text-gray-300"></i><h3 class="mt-4 text-xl font-semibold">Your Cart is Empty</h3><a href="catalog.php" class="mt-6 btn-primary">Start Shopping</a></div>
        </template>

        <div x-show="!loading && items.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items List -->
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md border">
                <div class="divide-y divide-gray-200">
                    <template x-for="item in items" :key="item.id">
                        <div class="flex items-center gap-4 py-4">
                            <img :src="item.image" :alt="item.name" class="w-20 h-20 object-contain rounded-md border p-1">
                            <div class="flex-grow">
                                <h3 class="font-semibold text-slate-800" x-text="item.name"></h3>
                                <p class="text-sm text-gray-500" x-text="'৳' + item.price.toFixed(2)"></p>
                                <button @click="removeItem(item.id)" class="text-xs text-red-500 hover:underline mt-1">Remove</button>
                            </div>
                            <div class="flex items-center border rounded-md">
                                <button @click="updateQty(item.id, -1)" class="w-8 h-8 text-gray-600">-</button>
                                <input type="number" :value="item.qty" @change="setQty(item.id, $event.target.value)" min="1" class="w-12 h-8 text-center border-l border-r focus:outline-none">
                                <button @click="updateQty(item.id, 1)" class="w-8 h-8 text-gray-600">+</button>
                            </div>
                            <p class="font-semibold w-24 text-right text-slate-800" x-text="'৳' + (item.price * item.qty).toFixed(2)"></p>
                        </div>
                    </template>
                </div>
            </div>
            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md border sticky top-24">
                    <h2 class="text-xl font-bold border-b pb-4 mb-4">Order Summary</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between"><span>Subtotal</span><span class="font-medium" x-text="'৳' + subtotal.toFixed(2)"></span></div>
                        <div class="border-t my-2"></div>
                        <div class="flex justify-between font-bold text-lg"><span>Total</span><span class="text-teal-600" x-text="'৳' + subtotal.toFixed(2)"></span></div>
                    </div>
                    <a href="checkout.php" class="mt-6 w-full btn-primary text-center text-lg py-3 block">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('shoppingCart', () => ({
        items: [],
        loading: true,
        
        init() {
            console.log('Shopping Cart Initializing...');
            this.loadCart();
        },
        
        async loadCart() {
            this.loading = true;
            const localCart = JSON.parse(localStorage.getItem('quickmed_cart')) || {};
            const ids = Object.keys(localCart);

            if (ids.length === 0) {
                this.items = [];
                this.loading = false;
                this.updateHeaderCount();
                return;
            }

            try {
                const response = await fetch('api_cart.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ ids }) });
                const result = await response.json();
                
                if (!result.success) throw new Error(result.message);

                const serverData = result.data;
                const freshCart = {};
                let loadedItems = [];

                for (const id in localCart) {
                    if (serverData[id] && serverData[id].price) {
                        freshCart[id] = localCart[id]; // Keep valid item
                        loadedItems.push({
                            id: id,
                            name: serverData[id].name,
                            image: serverData[id].image,
                            price: serverData[id].price,
                            qty: localCart[id].qty
                        });
                    }
                }
                this.items = loadedItems;
                localStorage.setItem('quickmed_cart', JSON.stringify(freshCart)); // Sync local storage

            } catch (error) {
                console.error("Error loading cart:", error);
                if (typeof showToast === 'function') showToast('Could not load cart items.', 'error');
            } finally {
                this.loading = false;
                this.updateHeaderCount();
            }
        },

        updateQty(id, change) {
            const item = this.items.find(i => i.id == id);
            if (item) {
                const newQty = item.qty + change;
                if (newQty > 0) {
                    item.qty = newQty;
                    this.syncToLocalStorage();
                }
            }
        },

        setQty(id, newQty) {
            const item = this.items.find(i => i.id == id);
            const qty = parseInt(newQty);
            if (item && qty > 0) {
                item.qty = qty;
                this.syncToLocalStorage();
            } else if (item) {
                // Revert if invalid value is typed
                event.target.value = item.qty;
            }
        },
        
        removeItem(id) {
            if (confirm('Are you sure you want to remove this item?')) {
                this.items = this.items.filter(i => i.id != id);
                this.syncToLocalStorage();
            }
        },

        syncToLocalStorage() {
            const newCart = {};
            this.items.forEach(item => {
                newCart[item.id] = { qty: item.qty, name: item.name }; // Store minimal info
            });
            localStorage.setItem('quickmed_cart', JSON.stringify(newCart));
            this.updateHeaderCount();
        },

        updateHeaderCount() {
            const count = this.items.reduce((sum, item) => sum + item.qty, 0);
            ['cart-count', 'cart-count-mobile'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.textContent = count; el.classList.toggle('hidden', count === 0); }
            });
        },

        get subtotal() {
            return this.items.reduce((total, item) => total + (item.price * item.qty), 0);
        }
    }));
});
</script>