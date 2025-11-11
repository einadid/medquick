<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get category from URL
$category = $_GET['cat'] ?? '';

// Fetch medicines by category
$sql = "SELECT * FROM medicines WHERE quantity > 0";
$params = [];

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

// Get sorting
$sort = $_GET['sort'] ?? 'name_asc';
switch ($sort) {
    case 'name_desc':
        $sql .= " ORDER BY name DESC";
        break;
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY created_at DESC";
        break;
    default:
        $sql .= " ORDER BY name ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

// Get all categories
$categories = $pdo->query("SELECT DISTINCT category FROM medicines WHERE quantity > 0 ORDER BY category")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <div class="flex flex-col md:flex-row gap-6">
        <!-- Categories Sidebar -->
        <div class="md:w-1/4">
            <div class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                <h2 class="text-xl font-bold mb-4">ক্যাটাগরি</h2>
                
                <ul class="space-y-2">
                    <li>
                        <a href="category.php" 
                           class="block px-3 py-2 rounded-lg hover:bg-blue-50 <?php echo empty($category) ? 'bg-blue-100 text-blue-600 font-semibold' : ''; ?>">
                            সব ক্যাটাগরি
                        </a>
                    </li>
                    
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="category.php?cat=<?php echo urlencode($cat['category']); ?>" 
                           class="block px-3 py-2 rounded-lg hover:bg-blue-50 <?php echo $category == $cat['category'] ? 'bg-blue-100 text-blue-600 font-semibold' : ''; ?>">
                            <?php echo htmlspecialchars($cat['category']); ?>
                            
                            <?php
                            // Count items in this category
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medicines WHERE category = ? AND quantity > 0");
                            $stmt->execute([$cat['category']]);
                            $count = $stmt->fetch()['count'];
                            ?>
                            <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded-full float-right">
                                <?php echo $count; ?>
                            </span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- Category Content -->
        <div class="md:w-3/4">
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <h1 class="text-2xl font-bold">
                            <?php echo !empty($category) ? htmlspecialchars($category) : 'সব ঔষধ'; ?>
                        </h1>
                        <p class="text-gray-600">
                            <?php echo count($medicines); ?> টি ঔষধ পাওয়া গেছে
                        </p>
                    </div>
                    
                    <div class="mt-4 md:mt-0">
                        <label class="mr-2">সাজান:</label>
                        <select onchange="window.location.href=this.value" class="border rounded-lg p-2">
                            <option value="category.php?cat=<?php echo urlencode($category); ?>&sort=name_asc" 
                                <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>নাম (ক-হ)</option>
                            <option value="category.php?cat=<?php echo urlencode($category); ?>&sort=name_desc" 
                                <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>নাম (হ-ক)</option>
                            <option value="category.php?cat=<?php echo urlencode($category); ?>&sort=price_asc" 
                                <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>দাম (কম থেকে বেশি)</option>
                            <option value="category.php?cat=<?php echo urlencode($category); ?>&sort=price_desc" 
                                <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>দাম (বেশি থেকে কম)</option>
                            <option value="category.php?cat=<?php echo urlencode($category); ?>&sort=newest" 
                                <?php echo $sort == 'newest' ? 'selected' : ''; ?>>নতুনতম</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <?php if (count($medicines) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($medicines as $medicine): 
                        $is_low_stock = $medicine['quantity'] <= $medicine['reorder_level'];
                        $is_expiring_soon = false;
                        
                        $today = new DateTime();
                        $expiry_date = new DateTime($medicine['expiry_date']);
                        $days_until_expiry = $today->diff($expiry_date)->days;
                        
                        if ($expiry_date < $today) {
                            $stock_status = 'bg-red-100 text-red-800';
                            $stock_text = 'এক্সপায়ার্ড';
                        } elseif ($days_until_expiry <= 30) {
                            $stock_status = 'bg-yellow-100 text-yellow-800';
                            $stock_text = 'শীঘ্রই এক্সপায়ার';
                            $is_expiring_soon = true;
                        } else {
                            $stock_status = 'bg-green-100 text-green-800';
                            $stock_text = 'স্টকে আছে';
                        }
                    ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <img src="../assets/images/medicines/<?php echo $medicine['image'] ?: 'default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($medicine['name']); ?>" 
                                 class="w-full h-48 object-cover">
                            
                            <?php if ($is_low_stock): ?>
                                <span class="absolute top-2 left-2 bg-orange-500 text-white text-xs px-2 py-1 rounded">
                                    কম স্টক
                                </span>
                            <?php endif; ?>
                            
                            <span class="absolute top-2 right-2 <?php echo $stock_status; ?> text-xs px-2 py-1 rounded">
                                <?php echo $stock_text; ?>
                            </span>
                            
                            <button onclick="addToWishlist(<?php echo $medicine['id']; ?>)" 
                                    class="absolute bottom-2 right-2 bg-white p-2 rounded-full shadow-md hover:bg-gray-100">
                                <i class="far fa-heart text-gray-500 hover:text-red-500"></i>
                            </button>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($medicine['name']); ?></h3>
                            <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($medicine['manufacturer']); ?></p>
                            
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xl font-bold text-blue-600">৳<?php echo number_format($medicine['price'], 2); ?></span>
                                
                                <?php if ($medicine['quantity'] > 0): ?>
                                    <span class="text-sm text-gray-500">স্টক: <?php echo $medicine['quantity']; ?></span>
                                <?php else: ?>
                                    <span class="text-sm text-red-500">স্টকে নেই</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="addToCart(<?php echo $medicine['id']; ?>, '<?php echo addslashes($medicine['name']); ?>', <?php echo $medicine['price']; ?>, 1)" 
                                        class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors <?php echo $medicine['quantity'] == 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                                        <?php echo $medicine['quantity'] == 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus mr-2"></i>কার্টে যোগ করুন
                                </button>
                                
                                <button onclick="window.location.href='medicine_details.php?id=<?php echo $medicine['id']; ?>'" 
                                        class="bg-gray-200 text-gray-700 py-2 px-3 rounded-lg hover:bg-gray-300 transition-colors">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="mt-8 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <a href="#" class="px-3 py-1 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        
                        <a href="#" class="px-3 py-1 bg-blue-600 text-white rounded-lg">1</a>
                        <a href="#" class="px-3 py-1 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300">2</a>
                        <a href="#" class="px-3 py-1 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300">3</a>
                        
                        <span class="px-2">...</span>
                        
                        <a href="#" class="px-3 py-1 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300">10</a>
                        
                        <a href="#" class="px-3 py-1 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </nav>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-md">
                    <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                    <h2 class="text-xl font-bold mb-2">কোন ঔষধ পাওয়া যায়নি</h2>
                    <p class="text-gray-600 mb-6">এই ক্যাটাগরিতে কোন ঔষধ পাওয়া যায়নি</p>
                    <a href="index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-arrow-left mr-2"></i>হোমপেজে ফিরে যান
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Cart functions
function addToCart(medicineId, medicineName, price, quantity) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Check if medicine already in cart
    const existingItem = cart.find(item => item.id === medicineId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({
            id: medicineId,
            name: medicineName,
            price: price,
            quantity: quantity
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    showAlert('success', 'কার্টে যোগ করা হয়েছে!');
}

function addToWishlist(medicineId) {
    let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    
    if (!wishlist.includes(medicineId)) {
        wishlist.push(medicineId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        showAlert('success', 'উইশলিস্টে যোগ করা হয়েছে!');
    } else {
        showAlert('info', 'ইতিমধ্যেই উইশলিস্টে আছে!');
    }
}

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cartCount').textContent = totalItems;
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Update cart count on page load
document.addEventListener('DOMContentLoaded', updateCartCount);
</script>

<?php include '../includes/footer.php'; ?>