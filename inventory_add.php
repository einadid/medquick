<?php
// FILE: inventory_add.php (Final Professional & Responsive Version)
// PURPOSE: Allows Shop Admins to add stock with an intelligent, user-friendly, and responsive interface.

require_once 'src/session.php';
require_once 'config/database.php';

// Security check for role.
if (!has_role(ROLE_SHOP_ADMIN) && !has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

// CRITICAL: Get Shop ID from session. This ensures a Shop Admin can ONLY add to their own shop.
$shop_id = $_SESSION['shop_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $medicine_id = (int)$_POST['medicine_id'];
    $batch_number = trim($_POST['batch_number']);
    $quantity = (int)$_POST['quantity'];
    $purchase_price = (float)$_POST['purchase_price'];
    $selling_price = (float)$_POST['selling_price'];
    $expiry_date = trim($_POST['expiry_date']);

    // Validation
    if (empty($medicine_id)) { $errors[] = 'A medicine must be selected first.'; }
    if (empty($batch_number)) { $errors[] = 'Batch number is required.'; }
    if ($quantity <= 0) { $errors[] = 'Quantity must be a positive number.'; }
    if ($purchase_price <= 0) { $errors[] = 'Purchase price must be a positive number.'; }
    if ($selling_price <= $purchase_price) { $errors[] = 'Selling price should be greater than the purchase price.'; }
    if (empty($expiry_date)) { $errors[] = 'Expiry date is required.'; }

    if (empty($errors)) {
        try {
            // Server-side double-check for duplicate batch number.
            $check_stmt = $pdo->prepare("SELECT id FROM inventory_batches WHERE medicine_id = ? AND shop_id = ? AND batch_number = ?");
            $check_stmt->execute([$medicine_id, $shop_id, $batch_number]);
            if ($check_stmt->fetch()) {
                $errors[] = "This batch number already exists for the selected medicine in your shop.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO inventory_batches (medicine_id, shop_id, batch_number, quantity, purchase_price, price, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$medicine_id, $shop_id, $batch_number, $quantity, $purchase_price, $selling_price, $expiry_date]);
                
                $batch_id = $pdo->lastInsertId();
                log_audit($pdo, 'STOCK_ADDED', "Batch ID: $batch_id, Med ID: $medicine_id, Qty: $quantity");
                
                $_SESSION['success_message'] = "Stock for batch #$batch_number added successfully! You can now add another.";
                redirect('inventory_add.php');
            }
        } catch (PDOException $e) {
            error_log("Inventory Add DB Error: " . $e->getMessage());
            $errors[] = 'A database error occurred. Please try again.';
        }
    }
}

// Retrieve and then clear session messages for display.
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
if (isset($_SESSION['error_message'])) { $errors[] = $_SESSION['error_message']; unset($_SESSION['error_message']); }

$pageTitle = "Add New Stock";
include 'templates/header.php';
?>

<div class="fade-in p-4 sm:p-6 lg:p-8" x-data="inventoryAddForm()">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 mb-6 sm:mb-8">Add New Stock to Inventory</h1>
    
    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <p><?= e($success_message); ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <ul class="list-disc pl-5"><?php foreach ($errors as $error): ?><li><?= e($error); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form action="inventory_add.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="medicine_id" :value="selectedMedicine ? selectedMedicine.id : ''">

        <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg border space-y-8">
            <!-- Step 1: Select Medicine -->
            <div>
                <h2 class="text-lg sm:text-xl font-semibold text-slate-700 flex items-center mb-4">
                    <span class="w-7 h-7 sm:w-8 sm:h-8 bg-teal-600 text-white rounded-full flex items-center justify-center mr-3 flex-shrink-0 text-sm sm:text-base">1</span>
                    Select Medicine
                </h2>
                <div class="relative">
                    <input type="text" id="medicine_search" placeholder="Type to search for a medicine..." @input.debounce.300ms="searchMedicines" 
                           class="w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-teal-500 text-sm sm:text-base">
                    <div id="medicine_suggestions" class="absolute top-full left-0 right-0 bg-white border mt-1 rounded-md shadow-lg z-30 hidden"></div>
                </div>
                <div x-show="selectedMedicine" x-transition class="mt-4 p-4 bg-slate-50 border border-teal-200 rounded-lg">
                    <div class="flex items-center gap-4">
                        <img :src="selectedMedicine.image_path || 'assets/images/default_med.png'" alt="Medicine" 
                             class="w-12 h-12 sm:w-16 sm:h-16 object-contain rounded-md bg-white p-1 border flex-shrink-0">
                        <div>
                            <p class="font-bold text-slate-800 text-base sm:text-lg" x-text="selectedMedicine.name"></p>
                            <p class="text-xs sm:text-sm text-gray-500" x-text="selectedMedicine.manufacturer"></p>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Can't find the medicine? 
                    <a href="medicine_add.php" target="_blank" class="text-teal-600 hover:underline">Add it to the main catalog first.</a>
                </p>
            </div>

            <!-- Step 2: Batch Details & Quantity -->
            <fieldset id="batch-details-fs" class="space-y-6 pt-6 sm:pt-8 border-t" :class="{ 'opacity-50 pointer-events-none': !selectedMedicine }">
                 <h2 class="text-lg sm:text-xl font-semibold text-slate-700 flex items-center mb-4">
                    <span class="w-7 h-7 sm:w-8 sm:h-8 bg-teal-600 text-white rounded-full flex items-center justify-center mr-3 flex-shrink-0 text-sm sm:text-base">2</span>
                    Enter Batch Details
                </h2>
                <div>
                    <label for="batch_number" class="block text-sm font-medium text-gray-700">Batch Number</label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mt-1">
                        <input type="text" name="batch_number" id="batch_number" required placeholder="Enter Batch Number" 
                               x-model="batchNumber" @input.debounce.500ms="checkBatch" 
                               class="flex-grow p-3 border rounded-md shadow-sm text-sm sm:text-base" 
                               :class="duplicateBatch ? 'border-red-500 ring-red-500' : 'border-gray-300 focus:ring-teal-500'">
                        <button type="button" @click="generateBatch" 
                                class="px-4 py-2 sm:py-3 bg-gray-200 text-sm font-medium rounded-md hover:bg-gray-300 w-full sm:w-auto">
                            Generate
                        </button>
                    </div>
                    <p x-show="duplicateBatch" class="text-xs text-red-600 mt-1" x-transition>Warning: This batch number already exists!</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="purchase_price" class="block text-sm font-medium text-gray-700">Purchase Price (per unit)</label>
                        <input type="number" name="purchase_price" id="purchase_price" required step="0.01" min="0.01" 
                               placeholder="e.g., 12.00" class="mt-1 w-full p-3 border rounded-md text-sm sm:text-base">
                    </div>
                    <div>
                        <label for="selling_price" class="block text-sm font-medium text-gray-700">Selling Price (per unit)</label>
                        <input type="number" name="selling_price" id="selling_price" required step="0.01" min="0.01" 
                               placeholder="e.g., 15.50" class="mt-1 w-full p-3 border rounded-md text-sm sm:text-base">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                        <input type="number" name="quantity" id="quantity" required min="1" 
                               placeholder="e.g., 100" class="mt-1 w-full p-3 border rounded-md text-sm sm:text-base">
                    </div>
                    <div>
                        <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                        <input type="date" name="expiry_date" id="expiry_date" required 
                               class="mt-1 w-full p-3 border rounded-md text-sm sm:text-base" min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </fieldset>
            
            <div class="pt-6 sm:pt-8 border-t">
                <button type="submit" id="submit-btn" 
                        class="w-full btn-primary text-base sm:text-lg py-3 disabled:bg-gray-400 disabled:cursor-not-allowed" 
                        :disabled="!selectedMedicine || duplicateBatch || !batchNumber">
                    <i class="fas fa-check-circle mr-2"></i> Confirm and Add Stock
                </button>
            </div>
        </div>
    </form>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('inventoryAddForm', () => ({
        selectedMedicine: null,
        batchNumber: '',
        duplicateBatch: false,
        
        async searchMedicines(event) {
            const query = event.target.value.trim();
            const suggestionsEl = document.getElementById('medicine_suggestions');
            if (query.length < 2) { 
                suggestionsEl.innerHTML = ''; 
                suggestionsEl.classList.add('hidden'); 
                return; 
            }
            try {
                const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
                const suggestions = await response.json();
                if (suggestions.length > 0) {
                    suggestionsEl.innerHTML = suggestions.map(s => `
                        <div class="p-3 hover:bg-gray-100 cursor-pointer text-sm sm:text-base" 
                             @click='selectMedicine(${JSON.stringify(s)})'>
                            ${s.name} <span class="text-xs sm:text-sm text-gray-500">by ${s.manufacturer}</span>
                        </div>`).join('');
                    suggestionsEl.classList.remove('hidden');
                } else { 
                    suggestionsEl.innerHTML = '<div class="p-3 text-gray-500 text-sm sm:text-base">No medicines found.</div>'; 
                }
            } catch (error) { 
                console.error('Medicine search error:', error); 
            }
        },
        
        selectMedicine(medicine) {
            this.selectedMedicine = medicine;
            document.getElementById('medicine_search').value = ''; // Clear search input
            document.getElementById('medicine_suggestions').classList.add('hidden');
            this.checkBatch();
        },

        async checkBatch() {
            if (!this.selectedMedicine || this.batchNumber.length === 0) { 
                this.duplicateBatch = false; 
                // Enable submit button if no medicine selected or batch number is empty
                document.getElementById('submit-btn').disabled = !this.selectedMedicine || !this.batchNumber.length;
                return; 
            }
            try {
                // Ensure correct shop_id is passed for the check
                const shopId = <?php echo json_encode($shop_id); ?>;
                const response = await fetch(`api_check_batch.php?medicine_id=${this.selectedMedicine.id}&shop_id=${shopId}&batch_number=${encodeURIComponent(this.batchNumber)}`);
                const result = await response.json();
                this.duplicateBatch = result.exists;
                // Disable submit button if duplicate batch exists or other conditions are not met
                document.getElementById('submit-btn').disabled = result.exists || !this.selectedMedicine || !this.batchNumber.length;
            } catch (error) { 
                console.error('Batch check error:', error); 
                document.getElementById('submit-btn').disabled = !this.selectedMedicine || !this.batchNumber.length; // Re-enable if API fails
            }
        },

        generateBatch() {
            const date = new Date();
            const dateStr = `${date.getFullYear()}${(date.getMonth() + 1).toString().padStart(2, '0')}${date.getDate().toString().padStart(2, '0')}`;
            const randomStr = Math.random().toString(36).substring(2, 6).toUpperCase();
            this.batchNumber = `BN-${dateStr}-${randomStr}`;
            this.checkBatch();
        }
    }));
});
</script>