<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Fetch categories
$categories = $pdo->query("SELECT DISTINCT category FROM medicines ORDER BY category")->fetchAll();

// Fetch featured medicines
$featured_medicines = $pdo->query("
    SELECT * FROM medicines 
    WHERE quantity > 0 
    ORDER BY RAND() 
    LIMIT 8
")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-700 text-white rounded-lg p-8 mb-8">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">আপনার প্রয়োজনীয় ঔষধ খুঁজুন</h1>
            <p class="text-lg mb-6">বাড়িতে বসেই অর্ডার করুন, পৌঁছে যাবে আপনার দরজায়</p>
            
            <!-- Search Form -->
            <form method="GET" action="search.php" class="flex">
                <input type="text" name="q" placeholder="মেডিসিন বা রোগের নাম লিখুন..." 
                       class="flex-grow px-4 py-3 rounded-l-lg text-gray-800 focus:outline-none">
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-black px-6 py-3 rounded-r-lg font-bold">
                    <i class="fas fa-search mr-2"></i>খুঁজুন
                </button>
            </form>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold mb-6">ক্যাটাগরি অনুযায়ী ব্রাউজ করুন</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($categories as $category): ?>
                <a href="category.php?cat=<?php echo urlencode($category['category']); ?>" 
                   class="bg-white p-4 rounded-lg shadow-md text-center hover:bg-blue-50 transition-colors">
                    <div class="text-3xl mb-2 text-blue-600">
                        <i class="fas fa-pills"></i>
                    </div>
                    <h3 class="font-semibold"><?php echo htmlspecialchars($category['category']); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Featured Medicines -->
    <div class="mb-12">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">ফিচার্ড মেডিসিন</h2>
            <a href="all_medicines.php" class="text-blue-600 hover:underline">সব দেখুন</a>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($featured_medicines as $medicine): 
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
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
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
    </div>

    <!-- How It Works -->
    <div class="bg-gray-50 rounded-lg p-8 mb-12">
        <h2 class="text-2xl font-bold text-center mb-8">কিভাবে অর্ডার করবেন?</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-blue-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg mb-2">১. ঔষধ খুঁজুন</h3>
                <p class="text-gray-600">আপনার প্রয়োজনীয় ঔষধ সার্চ করুন অথবা ক্যাটাগরি ব্রাউজ করুন</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cart-plus text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg mb-2">২. কার্টে যোগ করুন</h3>
                <p class="text-gray-600">পছন্দের ঔষধ কার্টে যোগ করুন এবং পরিমাণ নির্ধারণ করুন</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-truck text-purple-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg mb-2">৩. অর্ডার দিন</h3>
                <p class="text-gray-600">ঠিকানা দিয়ে অর্ডার সম্পন্ন করুন, ঔষধ পৌঁছে যাবে আপনার দরজায়</p>
            </div>
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

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cartCount').textContent = totalItems;
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
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