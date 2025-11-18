<?php
$pageTitle = 'Medicines';
require_once '../includes/header.php';

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';

// Build query
$sql = "SELECT m.*, c.name as category_name, 
        MIN(sm.selling_price) as min_price,
        MAX(sm.selling_price) as max_price,
        SUM(sm.stock) as total_stock
        FROM medicines m
        JOIN categories c ON m.category_id = c.id
        LEFT JOIN shop_medicines sm ON m.id = sm.medicine_id
        WHERE m.status = 'active'";

$params = [];

if ($search) {
    $sql .= " AND (m.name LIKE ? OR m.generic_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $sql .= " AND m.category_id = ?";
    $params[] = $category;
}

$sql .= " GROUP BY m.id";

// Sorting
switch ($sortBy) {
    case 'price_low':
        $sql .= " ORDER BY min_price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY min_price DESC";
        break;
    case 'name':
    default:
        $sql .= " ORDER BY m.name ASC";
        break;
}

$medicines = Database::getInstance()->fetchAll($sql, $params);

// Get all categories
$categories = Database::getInstance()->fetchAll("SELECT * FROM categories ORDER BY name");

// Get medicine class for images
require_once '../classes/Medicine.php';
$medicineClass = new Medicine();
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Medicine Catalog</h2>
    
    <!-- Search and Filters -->
    <form method="GET" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block mb-2">Search</label>
                <input type="text" name="search" value="<?php echo clean($search); ?>" 
                       placeholder="Medicine name or generic..." class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div>
                <label class="block mb-2">Category</label>
                <select name="category" class="w-full p-2 border-2 border-gray-400">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo clean($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block mb-2">Sort By</label>
                <select name="sort" class="w-full p-2 border-2 border-gray-400">
                    <option value="name" <?php echo $sortBy == 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="price_low" <?php echo $sortBy == 'price_low' ? 'selected' : ''; ?>>Price (Low to High)</option>
                    <option value="price_high" <?php echo $sortBy == 'price_high' ? 'selected' : ''; ?>>Price (High to Low)</option>
                </select>
            </div>
            
            <div>
                <label class="block mb-2">&nbsp;</label>
                <button type="submit" class="w-full p-2 bg-blue-600 text-white font-bold">FILTER</button>
            </div>
        </div>
    </form>
    
    <!-- Results Count -->
    <div class="mb-4 text-gray-600">
        Found <?php echo count($medicines); ?> medicines
    </div>
</div>

<!-- Medicine Grid -->
<?php if (empty($medicines)): ?>
    <div class="bg-white border-2 border-gray-300 p-6 text-center text-gray-600">
        No medicines found matching your criteria.
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($medicines as $med): ?>
        <div class="bg-white border-2 border-gray-300 p-4">
            <!-- Medicine Image -->
            <img src="<?php echo $medicineClass->getImageUrl($med['image']); ?>" 
                 alt="<?php echo clean($med['name']); ?>"
                 class="w-full h-40 object-cover border mb-3">
            
            <div class="mb-2">
                <div class="font-bold text-lg"><?php echo clean($med['name']); ?></div>
                <div class="text-sm text-gray-600"><?php echo clean($med['generic_name']); ?></div>
            </div>
            
            <div class="mb-2">
                <span class="text-xs bg-gray-200 px-2 py-1"><?php echo clean($med['category_name']); ?></span>
            </div>
            
            <div class="mb-2">
                <div class="text-sm text-gray-600">Form: <?php echo clean($med['dosage_form']); ?></div>
                <div class="text-sm text-gray-600">Strength: <?php echo clean($med['strength']); ?></div>
            </div>
            
            <div class="mb-2">
                <div class="text-sm text-gray-600">Manufacturer:</div>
                <div class="text-sm font-semibold"><?php echo clean($med['manufacturer']); ?></div>
            </div>
            
            <div class="mb-3">
                <?php if ($med['total_stock'] > 0): ?>
                    <div class="text-lg font-bold text-green-600">
                        <?php echo formatPrice($med['min_price']); ?>
                        <?php if ($med['max_price'] != $med['min_price']): ?>
                            - <?php echo formatPrice($med['max_price']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-gray-500">Available: <?php echo $med['total_stock']; ?> units</div>
                <?php else: ?>
                    <div class="text-red-600 font-bold">Out of Stock</div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="space-y-2">
                <a href="medicine-detail.php?id=<?php echo $med['id']; ?>" 
                   class="block w-full p-2 bg-blue-600 text-white text-center font-bold">
                    VIEW DETAILS
                </a>
                
                <?php if ($med['total_stock'] > 0): ?>
                    <?php
                    // Get cheapest shop for quick add to cart
                    $cheapestShop = Database::getInstance()->fetchOne("
                        SELECT id, selling_price, stock 
                        FROM shop_medicines 
                        WHERE medicine_id = ? AND stock > 0
                        ORDER BY selling_price ASC 
                        LIMIT 1
                    ", [$med['id']]);
                    ?>
                    
                    <?php if ($cheapestShop && isLoggedIn() && hasRole('customer')): ?>
                    <form method="POST" action="<?php echo SITE_URL; ?>/ajax/add-to-cart.php" class="add-to-cart-form">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="shop_medicine_id" value="<?php echo $cheapestShop['id']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="w-full p-2 bg-green-600 text-white font-bold">
                            🛒 ADD TO CART
                        </button>
                    </form>
                    <?php elseif (!isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/auth/login.php" class="block w-full p-2 bg-gray-400 text-white text-center font-bold">
                        LOGIN TO BUY
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add to Cart Success Message -->
<div id="cartMessage" class="hidden fixed bottom-4 right-4 bg-green-600 text-white px-6 py-4 rounded-lg shadow-lg z-50">
    ✓ Added to cart successfully!
</div>

<script>
// Handle AJAX add to cart
document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = 'ADDING...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.innerHTML = '✓ ADDED';
                button.classList.remove('bg-green-600');
                button.classList.add('bg-green-700');
                
                // Show success message
                const msg = document.getElementById('cartMessage');
                msg.classList.remove('hidden');
                setTimeout(() => {
                    msg.classList.add('hidden');
                }, 3000);
                
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.classList.remove('bg-green-700');
                    button.classList.add('bg-green-600');
                    button.disabled = false;
                }, 2000);
            } else {
                alert(data.message || 'Failed to add to cart');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add to cart');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>