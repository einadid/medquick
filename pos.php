<?php
// FILE: pos.php (The Ultimate, Final, and Working Version)
require_once 'src/session.php';
require_once 'config/database.php';

if (!has_role(ROLE_SALESMAN) && !has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

try {
    $categories = $pdo->query("SELECT DISTINCT category FROM medicines WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
    $shop_info_stmt = $pdo->prepare("SELECT name, address FROM shops WHERE id = ?");
    $shop_info_stmt->execute([$_SESSION['shop_id']]);
    $shop = $shop_info_stmt->fetch();
} catch(PDOException $e) {
    $categories = []; $shop = ['name' => 'Your Shop', 'address' => 'N/A'];
}

$category_icons = [ 'Painkiller & Fever' => 'fa-tablets', 'Antacid' => 'fa-stomach', 'Antibiotic' => 'fa-bacterium', 'Antihistamine' => 'fa-allergies', 'Vitamin' => 'fa-pills', 'Cardiovascular' => 'fa-heartbeat', 'Respiratory' => 'fa-lungs', 'Default' => 'fa-capsules' ];
$pageTitle = "Point of Sale";
include 'templates/header.php';
?>

<!-- Main container for the POS app, controlled by a single Alpine.js component -->
<div x-data="posApp()" x-init="init()" class="h-full flex flex-col md:flex-row overflow-hidden p-0 md:p-6 md:gap-6">
    
    <!-- Left Panel: Categories (Desktop Only) -->
    <div class="hidden md:block w-72 bg-white border-r h-full flex-col">
        <div class="p-4 border-b"><h2 class="font-bold text-lg">Categories</h2></div>
        <nav class="flex-grow overflow-y-auto">
            <a @click.prevent="selectCategory('')" href="#" class="flex items-center gap-3 px-4 py-3 text-sm font-medium" :class="activeCategory === '' ? 'bg-teal-50 text-teal-700 border-r-4 border-teal-500' : 'text-gray-600 hover:bg-gray-50'"><i class="fas fa-list-ul w-5 text-center"></i> All Products</a>
            <?php foreach($categories as $cat): ?>
                <a @click.prevent="selectCategory('<?= e($cat) ?>')" href="#" class="flex items-center gap-3 px-4 py-3 text-sm font-medium" :class="activeCategory === '<?= e($cat) ?>' ? 'bg-teal-50 text-teal-700 border-r-4 border-teal-500' : 'text-gray-600 hover:bg-gray-50'"><i class="fas <?= e($category_icons[$cat] ?? $category_icons['Default']) ?> w-5 text-center"></i> <span><?= e($cat) ?></span></a>
            <?php endforeach; ?>
        </nav>
    </div>
            
    <!-- Center Panel: Products (Main content on mobile) -->
    <div class="flex-grow h-full flex flex-col bg-white rounded-lg shadow-md" :class="{ 'hidden md:flex': activeTab === 'bill' }">
        <div class="p-4 border-b">
            <div class="relative"><input type="text" x-model.debounce.300ms="searchQuery" placeholder="Search by name or manufacturer..." class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div></div>
            <div class="md:hidden mt-3 flex-wrap gap-2 flex overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide"><button @click="selectCategory('')" :class="{'bg-teal-600 text-white': activeCategory === '', 'bg-gray-200': activeCategory !== ''}" class="flex-shrink-0 px-3 py-1 text-sm rounded-full">All</button><?php foreach($categories as $cat): ?><button @click="selectCategory('<?= e($cat) ?>')" :class="{'bg-teal-600 text-white': activeCategory === '<?= e($cat) ?>', 'bg-gray-200': activeCategory !== ''}" class="flex-shrink-0 px-3 py-1 text-sm rounded-full"><?= e($cat) ?></button><?php endforeach; ?></div>
        </div>
        <div class="flex-grow p-4 overflow-y-auto bg-slate-50">
            <template x-if="loading"><div class="flex justify-center items-center h-full"><i class="fas fa-spinner fa-spin text-4xl text-teal-500"></i></div></template>
            <template x-if="!loading && medicines.length === 0"><div class="text-center text-gray-500 pt-16">No products found.</div></template>
            <div x-show="!loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <template x-for="med in medicines" :key="med.id"><div @click="addToBill(med)" class="bg-white border rounded-lg p-3 text-center cursor-pointer hover:border-teal-500 hover:shadow-lg transition-all relative"><div class="absolute top-1 right-1 bg-green-100 text-green-800 text-xs font-bold px-2 py-0.5 rounded-full" x-text="'Qty: ' + med.stock"></div><img :src="med.image_path || 'assets/images/default_med.png'" :alt="med.name" class="w-full h-24 object-contain mb-2"><h4 class="text-xs font-semibold" x-text="med.name"></h4><p class="text-sm font-bold text-teal-600" x-text="'৳' + parseFloat(med.price).toFixed(2)"></p></div></template>
            </div>
        </div>
    </div>

    <!-- Right Panel: Bill -->
    <div class="w-full md:w-2/5 lg:w-1/3 bg-white rounded-lg shadow-md flex flex-col h-full" :class="{ 'hidden md:flex': activeTab === 'browse' }">
        <div class="p-4 border-b">
            <h2 class="text-xl font-bold text-slate-800">Current Bill</h2>
            <div class="mt-4">
                <label class="text-sm font-medium">Assign Customer (Optional)</label>
                <div class="relative">
                    <input type="text" x-model.lazy="customerSearchQuery" @input.debounce.300ms="searchCustomers()" placeholder="Search by Name/Email/Member ID" class="w-full p-2 border rounded-md mt-1">
                    <div x-show="customerSearchResults.length > 0" @click.away="customerSearchResults = []" class="absolute bg-white border shadow-lg rounded-md mt-1 w-full z-10 max-h-40 overflow-y-auto">
                        <template x-for="customer in customerSearchResults"><div @click="selectCustomer(customer)" class="p-2 hover:bg-gray-100 cursor-pointer"><p class="font-semibold" x-text="customer.full_name"></p><p class="text-xs text-gray-500" x-text="customer.email.split('@')[0]"></p></div></template>
                    </div>
                </div>
                <div x-show="selectedCustomer" x-transition class="mt-2 p-3 bg-teal-50 rounded-lg text-sm"><div class="flex justify-between items-center"><div><p>Billing to: <strong x-text="selectedCustomer.full_name"></strong></p><p class="text-xs">Points: <strong x-text="selectedCustomer.points_balance"></strong></p></div><button @click="resetCustomer()" class="text-red-500 hover:text-red-700 font-bold">&times; Remove</button></div></div>
            </div>
        </div>
        <div class="flex-grow p-4 space-y-3 overflow-y-auto"><template x-if="billItems.length === 0"><p class="text-center text-gray-500 py-16">Click a product to start a bill.</p></template><template x-for="(item, index) in billItems" :key="item.id"><div class="flex items-center gap-3"><div class="flex-grow"><p class="font-semibold text-sm" x-text="item.name"></p><p class="text-xs text-gray-500" x-text="'৳' + parseFloat(item.price).toFixed(2)"></p></div><div class="flex items-center border rounded-md"><button @click="updateQty(item.id, -1)" class="w-7 h-7 text-gray-600">-</button><input type="number" x-model.number="item.qty" @change="validateQty(item)" class="w-10 h-7 text-center border-l border-r"><button @click="updateQty(item.id, 1)" class="w-7 h-7 text-gray-600">+</button></div><p class="font-semibold w-20 text-right" x-text="'৳' + (item.price * item.qty).toFixed(2)"></p><button @click="removeFromBill(index)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash-alt"></i></button></div></template></div>
        <div class="flex-shrink-0 p-4 border-t bg-slate-50 space-y-2"><div class="flex justify-between"><span>Subtotal</span><span class="font-semibold" x-text="'৳' + subtotal.toFixed(2)"></span></div><div class="flex justify-between items-center"><span>Discount (%)</span><input type="number" x-model.number="discount" min="0" max="100" class="w-24 text-right border rounded-md px-2 py-1"></div><div class="flex justify-between items-center"><span>VAT (%)</span><input type="number" x-model.number="vatRate" min="0" max="100" class="w-24 text-right border rounded-md px-2 py-1"></div><div class="border-t my-2"></div><div class="text-sm space-y-1 text-gray-500"><div class="flex justify-between"><span>After Discount:</span><span x-text="'৳' + amountAfterDiscount.toFixed(2)"></span></div><div class="flex justify-between"><span>VAT Amount:</span><span x-text="'+ ৳' + vatAmount.toFixed(2)"></span></div></div><div class="border-t my-2"></div><div class="flex justify-between font-bold text-2xl text-teal-700"><span>Grand Total</span><span x-text="'৳' + total.toFixed(2)"></span></div><button @click="completeSale" :disabled="billItems.length === 0 || processingSale" class="w-full btn-primary text-lg mt-4 py-3 disabled:bg-gray-400"><span x-show="!processingSale">Complete Sale & Print</span><span x-show="processingSale"><i class="fas fa-spinner fa-spin"></i> Processing...</span></button></div>
    </div>
    
    <!-- Mobile Tab Navigation -->
    <div class="md:hidden fixed bottom-[70px] left-0 right-0 h-14 bg-white border-t flex z-30"><button @click="activeTab = 'browse'" :class="{ 'text-teal-600 border-t-2 border-teal-600': activeTab === 'browse' }" class="flex-1 font-semibold text-gray-600">Browse</button><button @click="activeTab = 'bill'" :class="{ 'text-teal-600 border-t-2 border-teal-600': activeTab === 'bill' }" class="flex-1 font-semibold text-gray-600 relative">Bill <span x-show="billItems.length > 0" x-transition class="absolute top-2 right-4 bg-red-600 text-white text-xs rounded-full w-5 h-5" x-text="billItems.length"></span></button></div>
    
    <!-- Receipt Modal -->
    <div x-show="receipt" @keydown.escape.window="receipt = null" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 p-4" style="display:none;"><div @click.away="receipt = null" class="bg-white rounded-lg shadow-xl max-w-sm w-full"><div id="receipt-container" class="p-6 sm:p-8"><!-- Receipt content --></div><div class="bg-gray-50 px-6 py-4 flex gap-4 print:hidden rounded-b-lg"><button @click="window.print()" class="w-full bg-gray-200 py-2 rounded-md">Print</button><button @click="receipt = null" class="w-full btn-primary">New Sale</button></div></div></div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
function posApp() {
    return {
        // Properties for product browsing
        searchQuery: '', activeCategory: '', medicines: [], loading: true,
        // Properties for billing
        billItems: [], discount: 0, vatRate: 5,
        // Properties for customer assignment
        customerSearchQuery: '', customerSearchResults: [], selectedCustomer: null, customerId: 1,
        // State management
        activeTab: 'browse', processingSale: false, receipt: null,
        
        init() {
            console.log('POS App Initializing...');
            this.fetchMedicines();
            this.$watch('searchQuery', () => this.fetchMedicines());
            this.$watch('activeCategory', () => this.fetchMedicines());
        },

        async fetchMedicines() {
            this.loading = true;
            try {
                const url = `api_pos_search.php?q=${encodeURIComponent(this.searchQuery)}&category=${encodeURIComponent(this.activeCategory)}`;
                const response = await fetch(url);
                if (!response.ok) throw new Error('Network error while fetching products.');
                const result = await response.json();
                this.medicines = Array.isArray(result) ? result : [];
            } catch (error) {
                console.error(error);
                if(typeof showToast === 'function') showToast(error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        selectCategory(category) { this.activeCategory = category; this.searchQuery = ''; },
        
        addToBill(medicine) {
            if (medicine.stock <= 0) { showToast('This item is out of stock!', 'error'); return; }
            const existing = this.billItems.find(i => i.id === medicine.id);
            if(existing) {
                if(existing.qty < medicine.stock) { existing.qty++; showToast(`${medicine.name} quantity increased.`, 'info'); } 
                else { showToast('Maximum stock reached for this item.', 'info'); }
            } else {
                this.billItems.push({ id: medicine.id, name: medicine.name, price: parseFloat(medicine.price), qty: 1, stock: parseInt(medicine.stock) });
                showToast(`${medicine.name} added to bill.`, 'success');
            }
            this.activeTab = 'bill';
        },
        
        removeFromBill(index) { this.billItems.splice(index, 1); },
        updateQty(id, amount) { const item = this.billItems.find(i => i.id === id); if(item) { const newQty = item.qty + amount; if (newQty >= 1 && newQty <= item.stock) item.qty = newQty; } },
        clearBill() { if(confirm('Are you sure?')) { this.billItems = []; this.discount = 0; this.vatRate = 5; this.resetCustomer(); } },

        async searchCustomers() {
            if (this.customerSearchQuery.length < 2) { this.customerSearchResults = []; return; }
            try {
                const response = await fetch(`customer_search_api.php?q=${encodeURIComponent(this.customerSearchQuery)}`);
                this.customerSearchResults = await response.json();
            } catch (e) { console.error(e); }
        },
        selectCustomer(customer) { this.selectedCustomer = customer; this.customerId = customer.id; this.customerSearchQuery = ''; this.customerSearchResults = []; },
        resetCustomer() { this.selectedCustomer = null; this.customerId = 1; },

        get subtotal() { return this.billItems.reduce((t, i) => t + (i.price * i.qty), 0); },
        get amountAfterDiscount() { return this.subtotal - (this.subtotal * (this.discount / 100)); },
        get vatAmount() { return this.amountAfterDiscount * (this.vatRate / 100); },
        get total() { return this.amountAfterDiscount + this.vatAmount; },

        async completeSale() {
            if (this.billItems.length === 0) return;
            this.processingSale = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const saleData = { items: this.billItems.map(i=>({id:i.id, qty:i.qty})), discount: this.discount, vat_rate: this.vatRate, total_amount: this.total, customer_id: this.customerId, csrf_token: csrfToken };
                const response = await fetch('pos_process.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(saleData) });
                const result = await response.json();
                if (result.success) {
                    const receiptItems = JSON.parse(JSON.stringify(this.billItems));
                    this.receipt = { order_id: result.order_id, items: receiptItems, subtotal: this.subtotal, discountAmount: (this.subtotal-this.amountAfterDiscount), vatAmount: this.vatAmount, total: this.total, date: new Date(), customerName: this.selectedCustomer ? this.selectedCustomer.full_name : 'Walk-in Customer', pointsEarned: result.points_earned||0, newTotalPoints: result.new_total_points||this.selectedCustomer?.points_balance||0 };
                    this.billItems = []; this.discount = 0; this.vatRate = 5; this.resetCustomer();
                    this.fetchMedicines(); // Refresh products
                } else { showToast(result.message || 'An error occurred.', 'error'); }
            } catch (e) { showToast('A network error occurred.', 'error'); } 
            finally { this.processingSale = false; }
        }
    };
}
</script>