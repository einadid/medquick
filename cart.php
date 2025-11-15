<?php
// FILE: cart.php (Simple, Reliable, Back-to-Basics Version)
require_once 'src/session.php';

if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) {
    redirect('login.php?redirect=cart.php');
}

$pageTitle = "My Shopping Cart";
include 'templates/header.php';
?>

<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6">
        <h1 class="text-3xl font-bold text-slate-800 mb-8">Shopping Cart</h1>
        
        <div id="cart-container">
            <!-- JavaScript will render the cart here -->
            <div class="text-center py-16"><i class="fas fa-spinner fa-spin text-4xl text-teal-500"></i><p class="mt-4">Loading Cart...</p></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cartContainer = document.getElementById('cart-container');

    // Helper functions to interact with localStorage
    const getCart = () => JSON.parse(localStorage.getItem('quickmed_cart')) || {};
    const saveCart = (cart) => localStorage.setItem('quickmed_cart', JSON.stringify(cart));
    
    // Main function to build and display the cart
    const renderCart = async () => {
        const cart = getCart();
        const medicineIds = Object.keys(cart);

        // --- 1. Handle Empty Cart ---
        if (medicineIds.length === 0) {
            cartContainer.innerHTML = `
                <div class="text-center py-20 bg-white rounded-lg shadow-md border">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300"></i>
                    <h3 class="mt-4 text-xl font-semibold text-gray-700">Your Cart is Empty</h3>
                    <p class="mt-1 text-gray-500">Add items from the catalog to get started.</p>
                    <a href="catalog.php" class="mt-6 btn-primary">Browse Medicines</a>
                </div>`;
            return;
        }

        // --- 2. Fetch Real-time Data from Server ---
        try {
            const response = await fetch('api_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: medicineIds })
            });

            if (!response.ok) throw new Error('Failed to fetch cart data from server.');
            
            const result = await response.json();
            if (!result.success && result.message) throw new Error(result.message);

            const serverData = result.data;
            
            // --- 3. Build HTML from Data ---
            let itemsHTML = '';
            let subtotal = 0;
            let cartWasModified = false;

            for (const id in cart) {
                if (serverData[id] && serverData[id].price) {
                    const item = cart[id];
                    const serverItem = serverData[id];
                    const itemTotal = item.qty * serverItem.price;
                    subtotal += itemTotal;

                    itemsHTML += `
                        <tr class="cart-item-row">
                            <td class="p-4">
                                <div class="flex items-center gap-4">
                                    <img src="${serverItem.image}" alt="${serverItem.name}" class="w-16 h-16 object-contain rounded-md border p-1">
                                    <div>
                                        <p class="font-semibold">${serverItem.name}</p>
                                        <button class="remove-item-btn text-xs text-red-500 hover:underline" data-id="${id}">Remove</button>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-center">৳${parseFloat(serverItem.price).toFixed(2)}</td>
                            <td class="p-4">
                                <div class="flex items-center justify-center border rounded-md w-28 mx-auto">
                                    <button class="qty-btn w-8 h-8" data-id="${id}" data-change="-1">-</button>
                                    <span class="w-12 text-center">${item.qty}</span>
                                    <button class="qty-btn w-8 h-8" data-id="${id}" data-change="1">+</button>
                                </div>
                            </td>
                            <td class="p-4 text-right font-semibold">৳${itemTotal.toFixed(2)}</td>
                        </tr>`;
                } else {
                    // Item is out of stock or doesn't exist, remove from cart
                    delete cart[id];
                    cartWasModified = true;
                }
            }

            if (cartWasModified) {
                saveCart(cart);
            }

            // If all items were out of stock, show empty message
            if (itemsHTML === '') {
                renderCart(); // Re-render to show the empty message
                return;
            }

            // --- 4. Display the Final HTML ---
            cartContainer.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-white rounded-lg shadow-md border overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="p-4 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="p-4 text-center text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="p-4 text-center text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th class="p-4 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">${itemsHTML}</tbody>
                        </table>
                    </div>
                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-lg shadow-md border sticky top-24">
                            <h2 class="text-xl font-bold border-b pb-4 mb-4">Order Summary</h2>
                            <div class="space-y-2">
                                <div class="flex justify-between"><span>Subtotal</span><span class="font-medium">৳${subtotal.toFixed(2)}</span></div>
                                <div class="border-t my-2"></div>
                                <div class="flex justify-between font-bold text-lg"><span>Total</span><span class="text-teal-600">৳${subtotal.toFixed(2)}</span></div>
                            </div>
                            <a href="checkout.php" class="mt-6 w-full btn-primary text-center text-lg py-3">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>`;
            
            // --- 5. Attach Event Listeners ---
            attachEventListeners();
        } catch (error) {
            console.error("Cart render failed:", error);
            cartContainer.innerHTML = `<div class="bg-red-100 text-red-700 p-4 rounded-lg text-center"><strong>Error:</strong> Could not load your cart. Please try again.</div>`;
        }
    };

    const attachEventListeners = () => {
        cartContainer.addEventListener('click', e => {
            const cart = getCart();
            if (e.target.matches('.qty-btn')) {
                const id = e.target.dataset.id;
                const change = parseInt(e.target.dataset.change);
                if (cart[id]) {
                    cart[id].qty = Math.max(1, cart[id].qty + change);
                    saveCart(cart);
                    renderCart(); // Re-render the whole cart
                }
            } else if (e.target.matches('.remove-item-btn')) {
                const id = e.target.dataset.id;
                if (confirm('Are you sure you want to remove this item?')) {
                    delete cart[id];
                    saveCart(cart);
                    renderCart();
                }
            }
        });
    };

    renderCart(); // Initial call
});
</script>

<?php include 'templates/footer.php'; ?>