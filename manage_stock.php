<?php
// FILE: manage_stock.php (Final Professional & Responsive Version)
// PURPOSE: A comprehensive inventory management dashboard for Shop Admins.

require_once 'src/session.php';
require_once 'config/database.php';

// Security check for role
if (!has_role(ROLE_SHOP_ADMIN) && !has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

$shop_id = $_SESSION['shop_id'];
$pageTitle = "Manage Stock";

try {
    // Fetch all inventory items for the current shop
    $inventory_stmt = $pdo->prepare("
        SELECT 
            m.id as medicine_id, 
            m.name, 
            m.manufacturer, 
            ib.id as batch_id, 
            ib.batch_number, 
            ib.quantity, 
            ib.price, 
            ib.expiry_date, 
            m.reorder_level
        FROM inventory_batches ib
        JOIN medicines m ON ib.medicine_id = m.id
        WHERE ib.shop_id = ?
        ORDER BY m.name, ib.expiry_date ASC
    ");
    $inventory_stmt->execute([$shop_id]);
    $shop_inventory = $inventory_stmt->fetchAll();

    // Calculate summary stats
    $total_unique_medicines = count(array_unique(array_column($shop_inventory, 'medicine_id')));
    $total_stock_units = array_sum(array_column($shop_inventory, 'quantity'));

} catch (PDOException $e) {
    error_log("Manage Stock page error: " . $e->getMessage());
    $shop_inventory = [];
    $total_unique_medicines = 0;
    $total_stock_units = 0;
    $_SESSION['error_message'] = "Could not load inventory data.";
}

include 'templates/header.php'; // This will correctly load the Shop Admin layout
?>

<div class="fade-in p-4 sm:p-6 lg:p-8" x-data="manageStock(<?= htmlspecialchars(json_encode($shop_inventory)) ?>)">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-800">Manage Shop Inventory</h1>
            <p class="text-gray-600 text-sm sm:text-base">View, search, and manage all stock items in your shop.</p>
        </div>
        <a href="inventory_add.php" class="btn-primary w-full sm:w-auto text-center"><i class="fas fa-plus mr-2"></i> Add New Stock</a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <div class="bg-white p-5 sm:p-6 rounded-lg shadow-md border">
            <p class="text-sm font-medium text-gray-500">Unique Medicine Types</p>
            <p class="text-2xl sm:text-3xl font-bold mt-1"><?= e($total_unique_medicines) ?></p>
        </div>
        <div class="bg-white p-5 sm:p-6 rounded-lg shadow-md border">
            <p class="text-sm font-medium text-gray-500">Total Stock Units</p>
            <p class="text-2xl sm:text-3xl font-bold mt-1"><?= e(number_format($total_stock_units)) ?></p>
        </div>
    </div>
    
    <!-- Session Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <p><?= e($_SESSION['success_message']); ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <p><?= e($_SESSION['error_message']); ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Inventory Table and Filters -->
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md border">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-4">
            <div class="relative w-full md:w-1/2 lg:w-1/3">
                <input type="text" x-model="searchQuery" placeholder="Search by name, manufacturer, or batch..." class="w-full p-3 pl-10 border border-gray-300 rounded-lg text-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <label class="flex items-center space-x-2 text-sm">
                    <input type="checkbox" x-model="showLowStockOnly" class="rounded text-teal-600 focus:ring-teal-500">
                    <span>Low Stock</span>
                </label>
                <label class="flex items-center space-x-2 text-sm">
                    <input type="checkbox" x-model="showExpiringSoon" class="rounded text-teal-600 focus:ring-teal-500">
                    <span>Expiring Soon</span>
                </label>
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="overflow-x-auto hidden md:block">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="item in filteredInventory" :key="item.batch_id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold" x-text="item.name"></div>
                                <div class="text-xs text-gray-500" x-text="item.manufacturer"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="item.batch_number"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold" 
                                :class="item.quantity <= item.reorder_level ? 'text-orange-600' : 'text-gray-900'" 
                                x-text="item.quantity"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right" x-text="'৳' + parseFloat(item.price).toFixed(2)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" 
                                :class="isExpiringSoon(item.expiry_date) ? 'text-red-600' : 'text-gray-600'" 
                                x-text="formatDate(item.expiry_date)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                <a :href="'inventory_edit.php?id=' + item.batch_id" class="text-teal-600 hover:text-teal-900">Edit</a>
                                <form action="inventory_process.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this batch?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="batch_id" :value="item.batch_id">
                                    <input type="hidden" name="action" value="delete_batch">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    </template>
                    <template x-if="filteredInventory.length === 0">
                        <tr><td colspan="6" class="text-center py-12 text-gray-500">No items match your filters.</td></tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-4">
            <template x-for="item in filteredInventory" :key="item.batch_id">
                <div class="bg-white p-4 rounded-lg shadow-md border">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-bold text-base text-gray-900" x-text="item.name"></div>
                            <div class="text-xs text-gray-500" x-text="item.manufacturer"></div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-base" 
                                :class="item.quantity <= item.reorder_level ? 'text-orange-600' : 'text-gray-900'" 
                                x-text="'Stock: ' + item.quantity"></div>
                            <div class="text-xs text-gray-500" x-text="'Batch: ' + item.batch_number"></div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 text-sm text-gray-700 mb-3 border-t pt-3">
                        <div><span class="font-medium">Price:</span> <span x-text="'৳' + parseFloat(item.price).toFixed(2)"></span></div>
                        <div :class="isExpiringSoon(item.expiry_date) ? 'text-red-600 font-medium' : ''">
                            <span class="font-medium">Expiry:</span> <span x-text="formatDate(item.expiry_date)"></span>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 border-t pt-3">
                        <a :href="'inventory_edit.php?id=' + item.batch_id" class="text-teal-600 hover:text-teal-900 text-sm">Edit</a>
                        <form action="inventory_process.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this batch?');">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="batch_id" :value="item.batch_id">
                            <input type="hidden" name="action" value="delete_batch">
                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                        </form>
                    </div>
                </div>
            </template>
            <template x-if="filteredInventory.length === 0">
                <div class="text-center py-12 text-gray-500">No items match your filters.</div>
            </template>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('manageStock', (inventoryData) => ({
        searchQuery: '',
        showLowStockOnly: false,
        showExpiringSoon: false,
        inventory: inventoryData,
        
        isExpiringSoon(dateStr) {
            const expiryDate = new Date(dateStr);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Normalize today to start of day
            const thirtyDaysFromNow = new Date(today);
            thirtyDaysFromNow.setDate(today.getDate() + 30);
            
            // Check if expiryDate is after today AND before or on thirtyDaysFromNow
            return expiryDate >= today && expiryDate < thirtyDaysFromNow;
        },

        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        get filteredInventory() {
            let items = this.inventory;

            if (this.showLowStockOnly) {
                items = items.filter(item => item.quantity <= item.reorder_level);
            }
            
            if (this.showExpiringSoon) {
                items = items.filter(item => this.isExpiringSoon(item.expiry_date));
            }

            if (this.searchQuery.trim() !== '') {
                const searchLower = this.searchQuery.toLowerCase();
                items = items.filter(item => 
                    item.name.toLowerCase().includes(searchLower) ||
                    item.manufacturer.toLowerCase().includes(searchLower) ||
                    item.batch_number.toLowerCase().includes(searchLower)
                );
            }
            
            return items;
        }
    }));
});
</script>