<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get wishlist from localStorage
$wishlist = json_decode($_COOKIE['wishlist'] ?? '[]', true) ?? [];

// Fetch wishlist medicines
$wishlist_medicines = [];
if (!empty($wishlist)) {
    $placeholders = str_repeat('?,', count($wishlist) - 1) . '?';
    $sql = "SELECT * FROM medicines WHERE id IN ($placeholders) AND quantity > 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($wishlist);
    $wishlist_medicines = $stmt->fetchAll();
}

// Add to cart from wishlist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    // Add to cart in localStorage
    $cart = json_decode($_COOKIE['cart'] ?? '[]', true) ?? [];
    
    $existing_item = array_search($medicine_id, array_column($cart, 'id'));
    
    if ($existing_item !== false) {
        $cart[$existing_item]['quantity'] += $quantity;
    } else {
        $cart[] = [
            'id' => $medicine_id,
            'quantity' => $quantity
        ];
    }
    
    setcookie('cart', json_encode($cart), time() + (86400 * 30), '/'); // 30 days
    
    // Remove from wishlist
    $wishlist = array_diff($wishlist, [$medicine_id]);
    setcookie('wishlist', json_encode(array_values($wishlist)), time() + (86400 * 30), '/');
    
    header('Location: wishlist.php');
    exit();
}

// Remove from wishlist
if (isset($_GET['remove'])) {
    $medicine_id = $_GET['remove'];
    $wishlist = array_diff($wishlist, [$medicine_id]);
    setcookie('wishlist', json_encode(array_values($wishlist)), time() + (86400 * 30), '/');
    
    header('Location: wishlist.php');
    exit();
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">আমার উইশলিস্ট</h1>
    
    <?php if (count($wishlist_medicines) > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-gray-100 p-4 font-semibold hidden md:grid">
                <div class="col-span-5">পণ্য</div>
                <div class="col-span-2 text-center">দাম</div>
                <div class="col-span-3 text-center">স্টক স্ট্যাটাস</div>
                <div class="col-span-2 text-center">অ্যাকশন</div>
            </div>
            
            <?php foreach ($wishlist_medicines as $medicine): 
                $is_low_stock = $medicine['quantity'] <= $medicine['reorder_level'];
                $is_expiring_soon = false;
                
                $today = new DateTime();
                $expiry_date = new DateTime($medicine['expiry_date']);
                $days_until_expiry = $today->diff($expiry_date)->days;
                
                if ($expiry_date < $today) {
                    $stock_status = 'text-red-600';
                    $stock_text = 'এক্সপায়ার্ড';
                } elseif ($days_until_expiry <= 30) {
                    $stock_status = 'text-yellow-600';
                    $stock_text = 'শীঘ্রই এক্সপায়ার';
                    $is_expiring_soon = true;
                } else {
                    $stock_status = 'text-green-600';
                    $stock_text = 'স্টকে আছে';
                }
            ?>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 p-4 border-b items-center">
                <div class="md:col-span-5 flex items-center">
                    <a href="medicine_details.php?id=<?php echo $medicine['id']; ?>" class="flex items-center">
                        <img src="../assets/images/medicines/<?php echo $medicine['image'] ?: 'default.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($medicine['name']); ?>" 
                             class="w-16 h-16 object-cover rounded-lg mr-4">
                        <div>
                            <h3 class="font-semibold hover:text-blue-600"><?php echo htmlspecialchars($medicine['name']); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($medicine['manufacturer']); ?></p>
                        </div>
                    </a>
                </div>
                
                <div class="md:col-span-2 text-center">
                    <span class="font-bold text-blue-600">৳<?php echo number_format($medicine['price'], 2); ?></span>
                </div>
                
                <div class="md:col-span-3 text-center">
                    <span class="<?php echo $stock_status; ?> font-semibold">
                        <?php if ($medicine['quantity'] > 0): ?>
                            <i class="fas fa-check-circle mr-1"></i> <?php echo $stock_text; ?>
                        <?php else: ?>
                            <i class="fas fa-times-circle mr-1"></i> স্টকে নেই
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="md:col-span-2 flex flex-col md:flex-row justify-center gap-2">
                    <form method="POST" action="" class="w-full">
                        <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                        <button type="submit" name="add_to_cart" 
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 text-sm 
                                       <?php echo $medicine['quantity'] == 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo $medicine['quantity'] == 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus mr-1"></i> কার্টে যোগ
                        </button>
                    </form>
                    
                    <a href="wishlist.php?remove=<?php echo $medicine['id']; ?>" 
                       class="bg-red-100 text-red-600 py-2 px-4 rounded-lg hover:bg-red-200 text-sm text-center">
                        <i class="fas fa-trash mr-1"></i> মুছুন
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="p-4 flex flex-col md:flex-row justify-between items-center bg-gray-50">
                <a href="index.php" class="text-blue-600 hover:underline mb-2 md:mb-0">
                    <i class="fas fa-arrow-left mr-1"></i> শপিং চালিয়ে যান
                </a>
                
                <button onclick="clearWishlist()" class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 text-sm">
                    <i class="fas fa-trash mr-1"></i> সব মুছুন
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-12 bg-white rounded-lg shadow-md">
            <i class="far fa-heart text-5xl text-gray-300 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">আপনার উইশলিস্ট খালি</h2>
            <p class="text-gray-600 mb-6">আপনি এখনো কোন পণ্য উইশলিস্টে যোগ করেননি</p>
            <a href="index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-shopping-cart mr-2"></i>এখনই শপিং করুন
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// Clear wishlist
function clearWishlist() {
    if (confirm('আপনি কি নিশ্চিত যে আপনি আপনার উইশলিস্ট খালি করতে চান?')) {
        document.cookie = "wishlist=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        location.reload();
    }
}

// Update cart count
function updateCartCount() {
    const cart = JSON.parse(getCookie('cart') || '[]');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cartCount').textContent = totalItems;
}

// Get cookie value
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

// Update cart count on page load
document.addEventListener('DOMContentLoaded', updateCartCount);
</script>

<?php include '../includes/footer.php'; ?>