<?php
require_once 'src/session.php';
require_once 'config/constants.php';

// শুধুমাত্র Salesman এই পেজ অ্যাক্সেস করতে পারবে
if (!has_role(ROLE_SALESMAN)) {
    redirect('dashboard.php');
}

$pageTitle = "New Sale (POS)";
include 'templates/header.php';
?>

<div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-4">Point of Sale (POS)</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Side: Search and Product List -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Find Medicine</h2>
            <div class="relative">
                <input type="text" id="pos-search" class="w-full p-3 border rounded-md" placeholder="Type to search medicine by name or category...">
                <div id="pos-suggestions" class="absolute top-full left-0 right-0 bg-white border mt-1 rounded-md shadow-lg z-20 hidden"></div>
            </div>
            
            <!-- Barcode Scanner Placeholder -->
            <div class="text-center my-4 text-gray-500">
                <i class="fas fa-barcode"></i> or use a barcode scanner
            </div>
        </div>

        <!-- Right Side: Bill / Cart -->
        <div class="lg:col-span-1 bg-gray-50 p-6 rounded-lg shadow-md" id="bill-section">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Current Bill</h2>
            
            <div id="bill-items" class="space-y-3 mb-4">
                <!-- Bill items will be added here by JS -->
                <p id="bill-empty-msg" class="text-gray-500 text-center py-8">No items added yet.</p>
            </div>

            <div class="border-t pt-4 space-y-2">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span id="bill-subtotal">৳ 0.00</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Discount (%)</span>
                    <input type="number" id="bill-discount" class="w-20 text-right border rounded px-1" value="0" min="0" max="100">
                </div>
                <div class="flex justify-between font-bold text-xl">
                    <span>TOTAL</span>
                    <span id="bill-total">৳ 0.00</span>
                </div>
            </div>

            <button id="complete-sale-btn" class="w-full bg-green-600 text-white py-3 mt-6 rounded-lg font-bold text-lg hover:bg-green-700 disabled:bg-gray-400" disabled>
                Complete Sale
            </button>
        </div>
    </div>
</div>

<!-- Template for bill item -->
<template id="bill-item-template">
    <div class="bill-item flex items-center" data-id="{id}">
        <div class="flex-grow">
            <p class="font-semibold">{name}</p>
            <p class="text-xs text-gray-600">৳{price} x <input type="number" class="item-qty w-12 text-center border rounded" value="1" min="1" max="{stock}"></p>
        </div>
        <p class="item-total-price font-semibold w-24 text-right">৳{price}</p>
        <button class="remove-item-btn text-red-500 hover:text-red-700 ml-3 text-lg">&times;</button>
    </div>
</template>

<script src="assets/js/pos.js"></script>
<?php include 'templates/footer.php'; ?>