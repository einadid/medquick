<?php
// FILE: pos.php (Final, Complete, and Professional Version)
require_once 'src/session.php';
require_once 'config/database.php';

// Security check
if (!has_role(ROLE_SALESMAN) && !has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

// Fetch necessary data for the page
try {
    $categories = $pdo->query("SELECT DISTINCT category FROM medicines WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
    $shop_info_stmt = $pdo->prepare("SELECT name, address FROM shops WHERE id = ?");
    $shop_info_stmt->execute([$_SESSION['shop_id']]);
    $shop = $shop_info_stmt->fetch();
} catch(PDOException $e) {
    error_log("POS page data fetch error: " . $e->getMessage());
    $categories = [];
    $shop = ['name' => 'Your Shop', 'address' => ''];
}

// Category icons for a better UI
$category_icons = [
    'Painkiller & Fever' => 'fa-tablets',
    'Antacid & Anti-ulcerant' => 'fa-stomach',
    'Antibiotic' => 'fa-bacterium',
    'Antihistamine' => 'fa-allergies',
    'Vitamin & Supplement' => 'fa-pills',
    'Cardiovascular' => 'fa-heartbeat',
    'Respiratory' => 'fa-lungs',
    'Anti-diabetic' => 'fa-syringe',
    'Dermatological' => 'fa-hand-sparkles',
    'Default' => 'fa-capsules'
];

$pageTitle = "Point of Sale";
// We include the main header to get the Salesman layout (sidebar, etc.)
include 'templates/header.php';
?>

<!-- Alpine.js component for the entire POS interface -->
<div x-data="posApp()" x-init="init()" class="h-full flex flex-col md:flex-row overflow-hidden p-0 md:p-6 md:gap-6">
    
    <!-- Left/Center Panel: Product Selection (Main panel on mobile) -->
    <div class="flex-grow h-full flex flex-col bg-white rounded-lg shadow-md" :class="{ 'hidden md:flex': activeTab === 'bill' }">
        <div class="p-4 border-b">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                <input type="text" x-model.debounce.300ms="searchQuery" placeholder="Search by name or manufacturer..." class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
            </div>
            <!-- Categories for Mobile -->
            <div class="md:hidden mt-3 flex-wrap gap-2 flex overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide">
                 <button @click="selectCategory('')" :class="{ 'bg-teal-600 text-white': activeCategory === '', 'bg-gray-200 text-gray-700': activeCategory !== '' }" class="flex-shrink-0 px-3 py-1 text-sm font-medium rounded-full">All</button>
                 <?php foreach($categories as $cat): ?><button @click="selectCategory('<?= e($cat) ?>')" :class="{ 'bg-teal-600 text-white': activeCategory === '<?= e($cat) ?>', 'bg-gray-200 text-gray-700': activeCategory !== '<?= e($cat) ?>' }" class="flex-shrink-0 px-3 py-1 text-sm font-medium rounded-full"><?= e($cat) ?></button><?php endforeach; ?>
            </div>
        </div>
        <div class="flex-grow p-4 overflow-y-auto bg-slate-50">
            <template x-if="loading"><div class="flex justify-center items-center h-full"><i class="fas fa-spinner fa-spin text-4xl text-teal-500"></i></div></template>
            <template x-if="!loading && medicines.length === 0"><div class="text-center text-gray-500 pt-16"><p>No products found for this filter.</p></div></template>
            <div x-show="!loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <template x-for="med in medicines" :key="med.id">
                    <div @click="addToBill(med)" class="bg-white border rounded-lg p-3 text-center cursor-pointer hover:border-teal-500 hover:shadow-lg transition-all relative">
                        <div class="absolute top-1 right-1 bg-green-100 text-green-800 text-xs font-bold px-2 py-0.5 rounded-full" x-text="'Qty: ' + med.stock"></div>
                        <img :src="med.image_path || 'assets/images/default_med.png'" :alt="med.name" class="w-full h-24 object-contain mb-2">
                        <h4 class="text-xs font-semibold" x-text="med.name"></h4><p class="text-sm font-bold text-teal-600" x-text="'৳' + parseFloat(med.price).toFixed(2)"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Right Panel: Bill -->
    <div class="w-full md:w-2/5 lg:w-1/3 bg-white rounded-lg shadow-md flex flex-col h-full" :class="{ 'hidden md:flex': activeTab === 'browse' }">
        <div class="p-4 border-b">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-slate-800">Current Bill</h2>
                <button x-show="billItems.length > 0" @click="clearBill" class="text-sm text-red-500 hover:underline">Clear All</button>
            </div>
            <div class="mt-4" x-data="{ search: '', customers: [], showList: false }">
                <label class="text-sm font-medium">Assign Customer (Optional)</label>
                <div class="relative">
                    <input type="text" x-model="search" @input.debounce.300ms="$parent.searchCustomers($event.target.value, customers => this.customers = customers)" @focus="showList = true" placeholder="Search by Name/Email/Member ID" class="w-full p-2 border rounded-md mt-1">
                    <div x-show="showList && customers.length > 0" @click.away="showList = false" class="absolute bg-white border shadow-lg rounded-md mt-1 w-full z-10 max-h-40 overflow-y-auto">
                        <template x-for="customer in customers"><div @click="$parent.selectCustomer(customer); showList = false;" class="p-2 hover:bg-gray-100 cursor-pointer" x-text="customer.full_name + ' (' + customer.email + ')'"></div></template>
                    </div>
                </div>
                <div x-show="$parent.selectedCustomer" class="mt-2 p-2 bg-teal-50 rounded-md text-sm flex justify-between items-center">
                    <span>Billing to: <strong x-text="$parent.selectedCustomer.full_name"></strong></span>
                    <button @click="$parent.resetCustomer()" class="text-red-500 hover:text-red-700 text-xs font-bold">&times; Remove</button>
                </div>
            </div>
        </div>
        <div class="flex-grow p-4 space-y-3 overflow-y-auto">
            <template x-if="billItems.length === 0"><p class="text-center text-gray-500 py-16">Click a product to start a bill.</p></template>
            <template x-for="(item, index) in billItems" :key="item.id"><div class="flex items-center gap-3"><div class="flex-grow"><p class="font-semibold text-sm" x-text="item.name"></p><p class="text-xs text-gray-500" x-text="'৳' + parseFloat(item.price).toFixed(2)"></p></div><div class="flex items-center border rounded-md"><button @click="updateQty(item.id, -1)" class="w-7 h-7 text-gray-600">-</button><input type="number" x-model.number="item.qty" @change="validateQty(item)" class="w-10 h-7 text-center border-l border-r"><button @click="updateQty(item.id, 1)" class="w-7 h-7 text-gray-600">+</button></div><p class="font-semibold w-20 text-right" x-text="'৳' + (item.price * item.qty).toFixed(2)"></p><button @click="removeFromBill(index)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash-alt"></i></button></div></template>
        </div>
        <div class="flex-shrink-0 p-4 border-t bg-slate-50 space-y-2">
            <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span class="font-semibold" x-text="'৳' + subtotal.toFixed(2)"></span></div>
            <div class="flex justify-between items-center"><span class="text-gray-600">Discount (%)</span><input type="number" x-model.number="discount" min="0" max="100" class="w-24 text-right border rounded-md px-2 py-1"></div>
            <div class="flex justify-between items-center"><span class="text-gray-600">VAT (%)</span><input type="number" x-model.number="vatRate" min="0" max="100" class="w-24 text-right border rounded-md px-2 py-1"></div>
            <div class="border-t my-2"></div>
            <div class="text-sm space-y-1 text-gray-500"><div class="flex justify-between"><span>Amount after Discount:</span><span x-text="'৳' + amountAfterDiscount.toFixed(2)"></span></div><div class="flex justify-between"><span>VAT Amount:</span><span x-text="'+ ৳' + vatAmount.toFixed(2)"></span></div></div>
            <div class="border-t my-2"></div>
            <div class="flex justify-between font-bold text-2xl text-teal-700"><span>Grand Total</span><span x-text="'৳' + total.toFixed(2)"></span></div>
            <button @click="completeSale" :disabled="billItems.length === 0 || processingSale" class="w-full btn-primary text-lg mt-4 py-3 disabled:bg-gray-400 disabled:cursor-not-allowed"><span x-show="!processingSale">Complete Sale & Print</span><span x-show="processingSale"><i class="fas fa-spinner fa-spin"></i> Processing...</span></button>
        </div>
    </div>
    
    <!-- Mobile Tab Navigation -->
    <div class="md:hidden fixed bottom-[70px] left-0 right-0 h-14 bg-white border-t flex z-30"><button @click="activeTab = 'browse'" :class="{ 'text-teal-600 border-t-2 border-teal-600': activeTab === 'browse' }" class="flex-1 font-semibold text-gray-600">Browse</button><button @click="activeTab = 'bill'" :class="{ 'text-teal-600 border-t-2 border-teal-600': activeTab === 'bill' }" class="flex-1 font-semibold text-gray-600 relative">Bill <span x-show="billItems.length > 0" x-transition class="absolute top-2 right-4 bg-red-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" x-text="billItems.length"></span></button></div>
    
    <!-- Receipt Modal -->
    <div x-show="receipt" @keydown.escape.window="receipt = null" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 p-4" style="display:none;"><div @click.away="receipt = null" class="bg-white rounded-lg shadow-xl max-w-sm w-full"><div id="receipt-container" class="p-6 sm:p-8"><div class="text-center mb-6"><h2 class="text-2xl font-bold">QuickMed</h2><p class="text-sm text-gray-500"><?= e($shop['name']) ?></p><p class="text-xs text-gray-500"><?= e($shop['address']) ?></p><p class="text-xs text-gray-500 mt-2">Date: <span x-text="receipt ? new Date(receipt.date).toLocaleString() : ''"></span></p><p class="text-sm font-semibold mt-1">Receipt #: <span x-text="receipt ? receipt.order_id : ''"></span></p><p class="text-sm mt-1">Customer: <strong x-text="receipt.customerName"></strong></p></div><div class="border-t border-dashed"></div><div class="space-y-2 py-4"><div class="flex justify-between font-bold text-sm"><span class="w-1/2">Item</span><span class="w-1/4 text-center">Qty</span><span class="w-1/4 text-right">Total</span></div><template x-for="item in receipt.items" :key="item.id"><div class="flex justify-between text-sm"><span class="w-1/2 truncate" x-text="item.name"></span><span class="w-1/4 text-center" x-text="item.qty"></span><span class="w-1/4 text-right" x-text="'৳' + (item.price * item.qty).toFixed(2)"></span></div></template></div><div class="border-t border-dashed pt-4 mt-2 space-y-1 text-sm"><div class="flex justify-between"><span class="text-gray-600">Subtotal:</span><span x-text="'৳' + receipt.subtotal.toFixed(2)"></span></div><div class="flex justify-between"><span class="text-gray-600">Discount:</span><span x-text="'- ৳' + receipt.discountAmount.toFixed(2)"></span></div><div class="flex justify-between"><span class="text-gray-600">VAT:</span><span x-text="'+ ৳' + receipt.vatAmount.toFixed(2)"></span></div><div class="flex justify-between font-bold text-lg border-t pt-2 mt-2"><span>Grand Total:</span><span x-text="'৳' + receipt.total.toFixed(2)"></span></div></div><div class="mt-8 text-center text-xs text-gray-500">Thank you for your purchase!</div></div><div class="bg-gray-50 px-6 py-4 flex gap-4 print:hidden rounded-b-lg"><button @click="printReceipt()" class="w-full bg-gray-200 py-2 rounded-md hover:bg-gray-300 font-semibold">Print</button><button @click="receipt = null" class="w-full btn-primary">New Sale</button></div></div></div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
function posApp() {
    return {
        // Properties
        searchQuery: '', activeCategory: '', activeTab: 'browse', medicines: [], loading: true,
        billItems: [], discount: 0, vatRate: 5, processingSale: false, receipt: null,
        customerId: 1, selectedCustomer: null,
        
        // Initialization
        init() {
            this.fetchMedicines();
            this.$watch('searchQuery', () => this.fetchMedicines());
            this.$watch('activeCategory', () => this.fetchMedicines());
        },

        // Methods
        async fetchMedicines() {
            this.loading = true;
            try {
                const url = `api_pos_search.php?q=${encodeURIComponent(this.searchQuery)}&category=${encodeURIComponent(this.activeCategory)}`;
                const response = await fetch(url);
                if (!response.ok) throw new Error('Network error');
                const result = await response.json();
                this.medicines = Array.isArray(result) ? result : (result.medicines || []);
            } catch (error) {
                if (typeof showToast === 'function') showToast('Could not load products.', 'error');
                console.error("Error fetching medicines:", error);
            } 
            finally {
                this.loading = false;
            }
        },
        selectCategory(category) {
            this.activeCategory = category;
            this.searchQuery = ''; // Clear search when category changes
        },
        addToBill(medicine) {
            if (medicine.stock <= 0) {
                showToast('This item is out of stock!', 'error');
                return;
            }
            const existing = this.billItems.find(i => i.id === medicine.id);
            if (existing) {
                if (existing.qty < medicine.stock) {
                    existing.qty++;
                } else {
                    showToast('Maximum stock reached for this item.', 'info');
                }
            } else {
                this.billItems.push({
                    id: medicine.id,
                    name: medicine.name,
                    price: parseFloat(medicine.price),
                    qty: 1,
                    stock: parseInt(medicine.stock)
                });
            }
          //  this.activeTab = 'bill'; // Switch to bill view on mobile
        },
        removeFromBill(index) {
            this.billItems.splice(index, 1);
        },
        updateQty(id, amount) {
            const item = this.billItems.find(i => i.id === id);
            if (item) {
                const newQty = item.qty + amount;
                if (newQty >= 1 && newQty <= item.stock) {
                    item.qty = newQty;
                } else if (newQty > item.stock) {
                    showToast('Quantity cannot exceed stock.', 'error');
                    item.qty = item.stock; // Cap at max stock
                }
            }
        },
        validateQty(item) {
            if (item.qty > item.stock) {
                showToast('Quantity cannot exceed stock.', 'error');
                item.qty = item.stock;
            }
            if (item.qty < 1 || isNaN(item.qty)) {
                item.qty = 1; // Default to 1 if invalid
            }
        },
        clearBill() {
            if (confirm('Are you sure you want to clear the entire bill?')) {
                this.billItems = [];
                this.discount = 0;
                this.vatRate = 5;
                this.resetCustomer();
            }
        },
        
        // Customer Search Methods
        async searchCustomers(query, callback) {
            if (query.length < 2) {
                callback([]);
                return;
            }
            try {
                const response = await fetch(`customer_search_api.php?q=${encodeURIComponent(query)}`);
                if (!response.ok) throw new Error('Network error during customer search');
                const customers = await response.json();
                callback(customers);
            } catch (error) {
                console.error("Error searching customers:", error);
                callback([]);
            }
        },
        selectCustomer(customer) {
            this.selectedCustomer = customer;
            this.customerId = customer.id;
            // Optionally clear the search input if using a local search variable in the x-data scope
            // For example, if you had `this.customerSearchInput = '';`
        },
        resetCustomer() {
            this.selectedCustomer = null;
            this.customerId = 1; // Assuming 1 is the ID for a 'Walk-in Customer' or default
        },

        // Calculated Properties
        get subtotal() {
            return this.billItems.reduce((total, item) => total + (item.price * item.qty), 0);
        },
        get amountAfterDiscount() {
            let discountedSubtotal = this.subtotal - (this.subtotal * (this.discount / 100));
            return Math.max(0, discountedSubtotal); // Ensure it's not negative
        },
        get vatAmount() {
            return this.amountAfterDiscount * (this.vatRate / 100);
        },
        get total() {
            return this.amountAfterDiscount + this.vatAmount;
        },
        
        // Finalize Sale
        async completeSale() {
            if (this.billItems.length === 0) {
                showToast('The bill is empty. Please add items first.', 'info');
                return;
            }
            this.processingSale = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const saleData = {
                    items: this.billItems.map(i => ({id: i.id, qty: i.qty})),
                    discount: this.discount,
                    vat_rate: this.vatRate,
                    total_amount: this.total,
                    customer_id: this.customerId,
                    csrf_token: csrfToken
                };

                const response = await fetch('pos_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Sale completed successfully!', 'success');
                    const receiptItems = JSON.parse(JSON.stringify(this.billItems)); // Deep copy for receipt display
                    this.receipt = {
                        order_id: result.order_id,
                        items: receiptItems,
                        subtotal: this.subtotal,
                        discountAmount: (this.subtotal - this.amountAfterDiscount),
                        vatAmount: this.vatAmount,
                        total: this.total,
                        date: new Date().toISOString(), // Use ISO string for reliable date parsing
                        customerName: this.selectedCustomer ? this.selectedCustomer.full_name : 'Walk-in Customer'
                    };
                    // Reset for new sale
                    this.billItems = [];
                    this.discount = 0;
                    this.vatRate = 5;
                    this.resetCustomer();
                    this.fetchMedicines(); // Refresh product list to reflect stock changes
                } else {
                    showToast(result.message || 'An error occurred during sale processing.', 'error');
                }
            } catch (error) {
                showToast('A network or server error occurred. Please try again.', 'error');
                console.error("Error completing sale:", error);
            } 
            finally {
                this.processingSale = false;
            }
        },

        printReceipt() {
            const printContent = document.getElementById('receipt-container').innerHTML;
            const originalContents = document.body.innerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContents; // Restore original content
            // Reload the page or re-initialize Alpine if necessary after print to ensure reactivity
            // For this specific setup, closing the modal might be enough:
            this.receipt = null; 
            location.reload(); // A simple way to fully reset the UI after printing
        }
    };
}
</script>