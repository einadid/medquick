<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get medicine ID from URL
$medicine_id = $_GET['id'] ?? 0;

// Fetch medicine details
$stmt = $pdo->prepare("
    SELECT m.*, 
           (SELECT GROUP_CONCAT(category) FROM medicine_categories mc WHERE mc.medicine_id = m.id) as categories
    FROM medicines m
    WHERE m.id = ?
");
$stmt->execute([$medicine_id]);
$medicine = $stmt->fetch();

if (!$medicine) {
    die("ঔষধ খুঁজে পাওয়া যায়নি!");
}

// Fetch related medicines
$stmt = $pdo->prepare("
    SELECT * FROM medicines 
    WHERE category = ? AND id != ? AND quantity > 0
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$medicine['category'], $medicine_id]);
$related_medicines = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                    <i class="fas fa-home mr-2"></i> হোম
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400"></i>
                    <a href="category.php?cat=<?php echo urlencode($medicine['category']); ?>" 
                       class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">
                        <?php echo htmlspecialchars($medicine['category']); ?>
                    </a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400"></i>
                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">
                        <?php echo htmlspecialchars($medicine['name']); ?>
                    </span>
                </div>
            </li>
        </ol>
    </nav>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <!-- Medicine Images -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="mb-4">
                <img src="../assets/images/medicines/<?php echo $medicine['image'] ?: 'default.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($medicine['name']); ?>" 
                     class="w-full h-96 object-contain">
            </div>
            
            <div class="grid grid-cols-4 gap-2">
                <button class="border rounded-lg p-1 hover:border-blue-500">
                    <img src="../assets/images/medicines/<?php echo $medicine['image'] ?: 'default.jpg'; ?>" 
                         alt="Thumbnail 1" class="w-full h-20 object-cover">
                </button>
                <!-- Add more thumbnails if available -->
            </div>
        </div>
        
        <!-- Medicine Details -->
        <div>
            <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($medicine['name']); ?></h1>
            
            <div class="flex items-center mb-4">
                <div class="flex text-yellow-400 mr-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star"></i>
                    <?php endfor; ?>
                </div>
                <span class="text-sm text-gray-600">(১২ রিভিউ)</span>
            </div>
            
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <span class="text-3xl font-bold text-blue-600">৳<?php echo number_format($medicine['price'], 2); ?></span>
                    <span class="ml-2 text-sm text-gray-500 line-through">৳<?php echo number_format($medicine['price'] * 1.1, 2); ?></span>
                    <span class="ml-2 bg-red-100 text-red-800 text-xs px-2 py-1 rounded">১০% ছাড়</span>
                </div>
            </div>
            
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">বিস্তারিত বিবরণ</h2>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($medicine['description'] ?: 'কোন বিবরণ পাওয়া যায়নি')); ?></p>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500">প্রস্তুতকারক</h3>
                    <p><?php echo htmlspecialchars($medicine['manufacturer']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500">ক্যাটাগরি</h3>
                    <p><?php echo htmlspecialchars($medicine['category']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500">স্টক স্ট্যাটাস</h3>
                    <p class="<?php echo $medicine['quantity'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $medicine['quantity'] > 0 ? 'স্টকে আছে' : 'স্টকে নেই'; ?>
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500">এক্সপায়ারি তারিখ</h3>
                    <p><?php echo date('d/m/Y', strtotime($medicine['expiry_date'])); ?></p>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 mb-6">
                <div class="flex items-center border rounded-lg overflow-hidden">
                    <button class="px-3 py-2 bg-gray-100 hover:bg-gray-200" 
                            onclick="updateQuantity('decrease')">-</button>
                    <input type="number" id="quantity" value="1" min="1" max="<?php echo $medicine['quantity']; ?>" 
                           class="w-16 text-center border-0">
                    <button class="px-3 py-2 bg-gray-100 hover:bg-gray-200" 
                            onclick="updateQuantity('increase')">+</button>
                </div>
                
                <button onclick="addToCart(<?php echo $medicine['id']; ?>, '<?php echo addslashes($medicine['name']); ?>', <?php echo $medicine['price']; ?>, parseInt(document.getElementById('quantity').value))" 
                        class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors <?php echo $medicine['quantity'] == 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                        <?php echo $medicine['quantity'] == 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-cart-plus mr-2"></i>কার্টে যোগ করুন
                </button>
                
                <button onclick="addToWishlist(<?php echo $medicine['id']; ?>)" 
                        class="p-2 text-gray-500 hover:text-red-500">
                    <i class="far fa-heart text-2xl"></i>
                </button>
            </div>
            
            <div class="flex space-x-2">
                <button class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700">
                    <i class="fas fa-bolt mr-2"></i>অর্ডার করুন
                </button>
                <button class="flex-1 bg-yellow-500 text-white py-2 px-4 rounded-lg hover:bg-yellow-600">
                    <i class="fas fa-phone-alt mr-2"></i>কল করুন
                </button>
            </div>
        </div>
    </div>
    
    <!-- Additional Information Tabs -->
    <div class="bg-white rounded-lg shadow-md mb-12">
        <div class="border-b">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="productTabs" data-tabs-toggle="#productTabsContent" role="tablist">
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 rounded-t-lg" id="description-tab" data-tabs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="false">বিবরণ</button>
                </li>
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="specifications-tab" data-tabs-target="#specifications" type="button" role="tab" aria-controls="specifications" aria-selected="false">স্পেসিফিকেশন</button>
                </li>
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="reviews-tab" data-tabs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">রিভিউ</button>
                </li>
            </ul>
        </div>
        <div id="productTabsContent">
            <div class="hidden p-4 rounded-lg" id="description" role="tabpanel" aria-labelledby="description-tab">
                <h3 class="text-lg font-semibold mb-2">পণ্যের বিবরণ</h3>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($medicine['description'] ?: 'কোন বিবরণ পাওয়া যায়নি')); ?></p>
            </div>
            <div class="hidden p-4 rounded-lg" id="specifications" role="tabpanel" aria-labelledby="specifications-tab">
                <h3 class="text-lg font-semibold mb-2">স্পেসিফিকেশন</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p><span class="font-semibold">জেনেরিক নাম:</span> <?php echo htmlspecialchars($medicine['generic_name'] ?: 'N/A'); ?></p>
                        <p><span class="font-semibold">শক্তি:</span> <?php echo htmlspecialchars($medicine['strength'] ?: 'N/A'); ?></p>
                        <p><span class="font-semibold">ডোজ ফর্ম:</span> <?php echo htmlspecialchars($medicine['dosage_form'] ?: 'N/A'); ?></p>
                    </div>
                    <div>
                        <p><span class="font-semibold">প্যাক সাইজ:</span> <?php echo htmlspecialchars($medicine['pack_size'] ?: 'N/A'); ?></p>
                        <p><span class="font-semibold">স্টোরেজ:</span> <?php echo htmlspecialchars($medicine['storage'] ?: 'N/A'); ?></p>
                        <p><span class="font-semibold">এক্সপায়ারি তারিখ:</span> <?php echo date('d/m/Y', strtotime($medicine['expiry_date'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="hidden p-4 rounded-lg" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                <h3 class="text-lg font-semibold mb-4">গ্রাহক রিভিউ</h3>
                
                <div class="flex items-center mb-6">
                    <div class="mr-4">
                        <span class="text-5xl font-bold">4.5</span>
                        <span class="text-gray-500">/5</span>
                    </div>
                    <div>
                        <div class="flex items-center mb-1">
                            <div class="flex text-yellow-400 mr-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm text-gray-600">(১২ রিভিউ)</span>
                        </div>
                        <div class="w-48 bg-gray-200 rounded-full h-2.5">
                            <div class="bg-yellow-400 h-2.5 rounded-full" style="width: 90%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Review Form -->
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h4 class="font-semibold mb-2">রিভিউ লিখুন</h4>
                    <form>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">রেটিং</label>
                            <div class="flex space-x-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="far fa-star text-2xl text-yellow-400 cursor-pointer" 
                                       data-rating="<?php echo $i; ?>" 
                                       onmouseover="highlightStars(this)" 
                                       onmouseout="resetStars()" 
                                       onclick="setRating(this)"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="rating" name="rating" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">রিভিউ</label>
                            <textarea class="w-full border rounded-lg p-2" rows="3"></textarea>
                        </div>
                        <button type="button" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            রিভিউ সাবমিট করুন
                        </button>
                    </form>
                </div>
                
                <!-- Reviews List -->
                <div class="space-y-6">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="border-b pb-6">
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 bg-gray-200 rounded-full mr-3"></div>
                            <div>
                                <h4 class="font-semibold">রহিম ইসলাম</h4>
                                <div class="flex text-yellow-400 text-sm">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                            <span class="ml-auto text-sm text-gray-500">৩ দিন আগে</span>
                        </div>
                        <p class="text-gray-600">খুব ভালো ঔষধ। দ্রুত ডেলিভারি পেয়েছি।</p>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (count($related_medicines) > 0): ?>
    <div class="mb-12">
        <h2 class="text-2xl font-bold mb-6">সম্পর্কিত ঔষধসমূহ</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($related_medicines as $related): 
                $is_low_stock = $related['quantity'] <= $related['reorder_level'];
                $is_expiring_soon = false;
                
                $today = new DateTime();
                $expiry_date = new DateTime($related['expiry_date']);
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
                    <a href="medicine_details.php?id=<?php echo $related['id']; ?>">
                        <img src="../assets/images/medicines/<?php echo $related['image'] ?: 'default.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($related['name']); ?>" 
                             class="w-full h-48 object-cover">
                    </a>
                    
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
                    <a href="medicine_details.php?id=<?php echo $related['id']; ?>" class="block">
                        <h3 class="font-bold text-lg mb-1 hover:text-blue-600"><?php echo htmlspecialchars($related['name']); ?></h3>
                    </a>
                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($related['manufacturer']); ?></p>
                    
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-xl font-bold text-blue-600">৳<?php echo number_format($related['price'], 2); ?></span>
                        
                        <?php if ($related['quantity'] > 0): ?>
                            <span class="text-sm text-gray-500">স্টক: <?php echo $related['quantity']; ?></span>
                        <?php else: ?>
                            <span class="text-sm text-red-500">স্টকে নেই</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="addToCart(<?php echo $related['id']; ?>, '<?php echo addslashes($related['name']); ?>', <?php echo $related['price']; ?>, 1)" 
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors <?php echo $related['quantity'] == 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                                <?php echo $related['quantity'] == 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus mr-2"></i>কার্টে যোগ করুন
                        </button>
                        
                        <button onclick="window.location.href='medicine_details.php?id=<?php echo $related['id']; ?>'" 
                                class="bg-gray-200 text-gray-700 py-2 px-3 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Update quantity
function updateQuantity(action) {
    const quantityInput = document.getElementById('quantity');
    let quantity = parseInt(quantityInput.value);
    
    if (action === 'increase') {
        quantity = Math.min(quantity + 1, <?php echo $medicine['quantity']; ?>);
    } else if (action === 'decrease') {
        quantity = Math.max(quantity - 1, 1);
    }
    
    quantityInput.value = quantity;
}

// Add to cart
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

// Add to wishlist
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

// Update cart count
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cartCount').textContent = totalItems;
}

// Show alert
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

// Star rating
function highlightStars(star) {
    const rating = parseInt(star.getAttribute('data-rating'));
    const stars = document.querySelectorAll('[data-rating]');
    
    stars.forEach(s => {
        const sRating = parseInt(s.getAttribute('data-rating'));
        if (sRating <= rating) {
            s.classList.remove('far');
            s.classList.add('fas');
        }
    });
}

function resetStars() {
    const stars = document.querySelectorAll('[data-rating]');
    const currentRating = parseInt(document.getElementById('rating').value);
    
    stars.forEach(star => {
        const rating = parseInt(star.getAttribute('data-rating'));
        if (rating > currentRating) {
            star.classList.remove('fas');
            star.classList.add('far');
        }
    });
}

function setRating(star) {
    const rating = parseInt(star.getAttribute('data-rating'));
    document.getElementById('rating').value = rating;
    
    const stars = document.querySelectorAll('[data-rating]');
    stars.forEach(s => {
        const sRating = parseInt(s.getAttribute('data-rating'));
        if (sRating <= rating) {
            s.classList.remove('far');
            s.classList.add('fas');
        } else {
            s.classList.remove('fas');
            s.classList.add('far');
        }
    });
}

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('[data-tabs-toggle]');
    const tabContents = document.querySelectorAll('[data-tabs-target]');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-tabs-target'));
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // Show selected tab content
            target.classList.remove('hidden');
            
            // Update active tab
            tabs.forEach(t => {
                t.classList.remove('border-blue-600', 'text-blue-600');
                t.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
            });
            
            this.classList.add('border-blue-600', 'text-blue-600');
            this.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
        });
    });
    
    // Activate first tab by default
    if (tabs.length > 0) {
        tabs[0].click();
    }
});

// Update cart count on page load
document.addEventListener('DOMContentLoaded', updateCartCount);
</script>

<?php include '../includes/footer.php'; ?>