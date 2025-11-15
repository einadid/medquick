<?php
// FILE: returns.php (Final & Professional Version)
// PURPOSE: Interface for processing sales returns.
require_once 'src/session.php';
require_once 'config/database.php';

// Security check
if (!has_role(ROLE_SALESMAN) && !has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

$pageTitle = "Process Return";
// This will correctly load the salesman layout (sidebar, etc.)
include 'templates/header.php';
?>

<!-- This main div is now part of the layout loaded by header.php -->
<div class="fade-in p-4 sm:p-6" x-data="returnApp()">
    <h1 class="text-3xl font-bold text-slate-800 mb-6">Process a Sales Return</h1>
    
    <!-- Step 1: Find Order by ID -->
    <div class="bg-white p-6 rounded-lg shadow-md border mb-8">
        <h2 class="text-xl font-bold text-slate-700 mb-4">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-full inline-flex items-center justify-center mr-3">1</span>
            Find Original Sale
        </h2>
        <div class="flex flex-col sm:flex-row gap-4">
            <input type="number" x-model="orderId" @keydown.enter.prevent="findOrder" placeholder="Enter Order ID or Receipt #" class="flex-grow w-full p-3 border rounded-lg focus:ring-2 focus:ring-teal-500">
            <button @click="findOrder" class="btn-primary w-full sm:w-auto" :disabled="loading">
                <span x-show="!loading">Find Order</span>
                <span x-show="loading"><i class="fas fa-spinner fa-spin"></i> Searching...</span>
            </button>
        </div>
    </div>
    
    <!-- Step 2: Process Return (shown after order is found) -->
    <template x-if="order">
        <div class="bg-white p-6 rounded-lg shadow-md border" x-transition>
            <div class="flex justify-between items-start mb-4 pb-4 border-b">
                <div>
                    <h2 class="text-xl font-bold text-slate-700 flex items-center">
                        <span class="w-8 h-8 bg-teal-600 text-white rounded-full inline-flex items-center justify-center mr-3">2</span>
                        Select Items to Return
                    </h2>
                    <p class="text-sm text-gray-500 ml-11">Order #<span x-text="order.id"></span> | Customer: <span x-text="order.customer_name"></span></p>
                </div>
                <button @click="resetForm" class="text-sm text-gray-500 hover:text-red-600">&times; Start Over</button>
            </div>
            
            <form @submit.prevent="processReturn">
                <!-- Items to Return List -->
                <div class="space-y-4 mb-6">
                    <p class="text-sm text-gray-600">Select the items and quantities being returned.</p>
                    <template x-for="item in order.items" :key="item.id">
                        <div class="flex flex-wrap items-center gap-4 p-3 border rounded-md has-[:checked]:bg-slate-50">
                            <div class="flex items-center flex-grow">
                                <input type="checkbox" :id="'item_'+item.id" x-model="item.is_returning" class="h-5 w-5 rounded text-teal-600 focus:ring-teal-500">
                                <label :for="'item_'+item.id" class="ml-3 font-semibold text-slate-800" x-text="item.medicine_name"></label>
                            </div>
                            <div class="flex items-center gap-2">
                                <label :for="'qty_'+item.id" class="text-sm">Qty:</label>
                                <input type="number" :id="'qty_'+item.id" x-model.number="item.return_qty" min="0" :max="item.quantity" class="w-16 p-1 border rounded-md text-center" :disabled="!item.is_returning">
                                <span class="text-sm text-gray-500">/ <span x-text="item.quantity"></span></span>
                            </div>
                            <span class="w-24 text-right text-sm text-gray-600" x-text="'@ ৳' + parseFloat(item.price_per_unit).toFixed(2)"></span>
                        </div>
                    </template>
                </div>
                
                <!-- Return Reason -->
                <div class="mb-6">
                    <label for="return_reason" class="block text-sm font-medium text-gray-700">Reason for Return (Optional)</label>
                    <input type="text" id="return_reason" x-model="returnReason" class="mt-1 w-full p-2 border rounded-md" placeholder="e.g., Damaged product, wrong item...">
                </div>
                
                <!-- Summary and Submit -->
                <div class="p-4 bg-slate-50 rounded-lg border flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Total Refund Amount</p>
                        <p class="text-2xl font-bold text-red-600" x-text="'৳' + calculateRefund().toFixed(2)"></p>
                    </div>
                    <button type="submit" class="btn-primary py-3 px-6 text-lg w-full sm:w-auto" :disabled="!isReturnable() || processing">
                        <span x-show="!processing">Confirm & Process Return</span>
                        <span x-show="processing"><i class="fas fa-spinner fa-spin"></i> Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    </template>
    
    <!-- Message display area -->
    <template x-if="message">
        <div class="mt-4 p-4 rounded-md" :class="messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'" x-text="message"></div>
    </template>
</div>

<?php include 'templates/footer.php'; ?>

<!-- Page-specific JavaScript with self-contained Toast function -->
<script>
// This Toast function is included here to make the page self-contained and avoid dependency issues.
function showToast(message, type = 'info', duration = 3500) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    const toastId = 'toast-' + Date.now(); toast.id = toastId; toast.className = `toast toast-${type}`;
    let icon = type === 'success' ? '<i class="fas fa-check-circle mr-3"></i>' : (type === 'error' ? '<i class="fas fa-times-circle mr-3"></i>' : '<i class="fas fa-info-circle mr-3"></i>');
    toast.innerHTML = `${icon} <span class="font-medium flex-1">${message}</span>`;
    container.prepend(toast);
    toast.style.animation = `toast-in 0.5s ease-out, toast-out 0.5s ease-in ${duration / 1000}s forwards`;
    setTimeout(() => { const el = document.getElementById(toastId); if (el) el.remove(); }, duration + 500);
}

function returnApp() {
    return {
        orderId: '', order: null, returnReason: '', loading: false, processing: false, message: '', messageType: 'info',
        async findOrder() {
            if (!this.orderId) { showToast('Please enter an Order ID.', 'error'); return; }
            this.loading = true; this.message = ''; this.order = null;
            try {
                const response = await fetch(`order_search_api.php?id=${this.orderId}`);
                if (!response.ok) throw new Error('Order not found or server error.');
                const result = await response.json();
                if (result.success && result.order) {
                    this.order = result.order;
                    this.order.items.forEach(item => { item.is_returning = false; item.return_qty = item.quantity; });
                    this.message = 'Order found. Please select items to return.'; this.messageType = 'info';
                } else { this.message = result.message || 'Order not found in your shop.'; this.messageType = 'error'; }
            } catch (e) { this.message = 'An error occurred while searching.'; this.messageType = 'error'; console.error(e); } 
            finally { this.loading = false; }
        },
        calculateRefund() { if (!this.order) return 0; return this.order.items.reduce((total, item) => total + (item.is_returning ? (Number(item.return_qty) * Number(item.price_per_unit)) : 0), 0); },
        isReturnable() { if (!this.order) return false; return this.order.items.some(item => item.is_returning && Number(item.return_qty) > 0); },
        async processReturn() {
            if (!this.isReturnable()) { showToast('Please select at least one item and quantity to return.', 'error'); return; }
            if (!confirm('Process this return? Stock will be added back to inventory. This action cannot be easily undone.')) return;
            this.processing = true; this.message = '';
            const itemsToReturn = this.order.items.filter(item => item.is_returning && Number(item.return_qty) > 0);
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('return_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: this.order.id, items: itemsToReturn, reason: this.returnReason, csrf_token: csrfToken })
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success', 5000);
                    this.resetForm();
                } else { showToast(result.message || 'Return processing failed.', 'error', 5000); }
            } catch(e) { showToast('A network error occurred. Please try again.', 'error'); console.error(e); } 
            finally { this.processing = false; }
        },
        resetForm() { this.orderId = ''; this.order = null; this.returnReason = ''; this.message = ''; }
    };
}
</script>