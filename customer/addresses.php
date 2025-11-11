<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Fetch customer addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

// Add new address
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_address'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $area = $_POST['area'];
    $city = $_POST['city'];
    $postal_code = $_POST['postal_code'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    try {
        // If this is set as default, remove default from others
        if ($is_default) {
            $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE customer_id = ?")->execute([$_SESSION['user_id']]);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO addresses (customer_id, name, phone, address, area, city, postal_code, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $name,
            $phone,
            $address,
            $area,
            $city,
            $postal_code,
            $is_default
        ]);
        
        $_SESSION['success'] = "ঠিকানা সফলভাবে যোগ করা হয়েছে!";
        header('Location: addresses.php');
        exit();
        
    } catch (PDOException $e) {
        $error = "ঠিকানা যোগ করতে সমস্যা: " . $e->getMessage();
    }
}

// Update address
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_address'])) {
    $address_id = $_POST['address_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $area = $_POST['area'];
    $city = $_POST['city'];
    $postal_code = $_POST['postal_code'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    try {
        // If this is set as default, remove default from others
        if ($is_default) {
            $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE customer_id = ?")->execute([$_SESSION['user_id']]);
        }
        
        $stmt = $pdo->prepare("
            UPDATE addresses 
            SET name = ?, phone = ?, address = ?, area = ?, city = ?, postal_code = ?, is_default = ?
            WHERE id = ? AND customer_id = ?
        ");
        
        $stmt->execute([
            $name,
            $phone,
            $address,
            $area,
            $city,
            $postal_code,
            $is_default,
            $address_id,
            $_SESSION['user_id']
        ]);
        
        $_SESSION['success'] = "ঠিকানা সফলভাবে আপডেট করা হয়েছে!";
        header('Location: addresses.php');
        exit();
        
    } catch (PDOException $e) {
        $error = "ঠিকানা আপডেট করতে সমস্যা: " . $e->getMessage();
    }
}

// Delete address
if (isset($_GET['delete'])) {
    $address_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND customer_id = ?");
        $stmt->execute([$address_id, $_SESSION['user_id']]);
        
        $_SESSION['success'] = "ঠিকানা সফলভাবে মুছে ফেলা হয়েছে!";
        header('Location: addresses.php');
        exit();
        
    } catch (PDOException $e) {
        $error = "ঠিকানা মুছতে সমস্যা: " . $e->getMessage();
    }
}

// Set default address
if (isset($_GET['set_default'])) {
    $address_id = $_GET['set_default'];
    
    try {
        $pdo->beginTransaction();
        
        // Remove default from all addresses
        $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE customer_id = ?")->execute([$_SESSION['user_id']]);
        
        // Set new default
        $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND customer_id = ?")->execute([$address_id, $_SESSION['user_id']]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "ডিফল্ট ঠিকানা সফলভাবে পরিবর্তন করা হয়েছে!";
        header('Location: addresses.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "ডিফল্ট ঠিকানা পরিবর্তন করতে সমস্যা: " . $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">আমার ঠিকানাসমূহ</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add New Address Form -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <h2 class="text-xl font-bold mb-4">নতুন ঠিকানা যোগ করুন</h2>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">প্রাপকের নাম*</label>
                        <input type="text" name="name" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ফোন নম্বর*</label>
                        <input type="text" name="phone" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ঠিকানা*</label>
                        <textarea name="address" class="w-full px-3 py-2 border rounded-lg" rows="3" required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">এলাকা*</label>
                            <input type="text" name="area" class="w-full px-3 py-2 border rounded-lg" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">শহর*</label>
                            <select name="city" class="w-full px-3 py-2 border rounded-lg" required>
                                <option value="">নির্বাচন করুন</option>
                                <option value="ঢাকা">ঢাকা</option>
                                <option value="চট্টগ্রাম">চট্টগ্রাম</option>
                                <option value="রাজশাহী">রাজশাহী</option>
                                <option value="খুলনা">খুলনা</option>
                                <option value="বরিশাল">বরিশাল</option>
                                <option value="সিলেট">সিলেট</option>
                                <option value="রংপুর">রংপুর</option>
                                <option value="ময়মনসিংহ">ময়মনসিংহ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">পোস্টাল কোড</label>
                        <input type="text" name="postal_code" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_default" class="form-checkbox">
                            <span class="ml-2 text-sm text-gray-700">ডিফল্ট ঠিকানা হিসেবে সেট করুন</span>
                        </label>
                    </div>
                    
                    <button type="submit" name="add_address" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                        ঠিকানা সংরক্ষণ করুন
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Address List -->
        <div class="lg:col-span-2">
            <?php if (count($addresses) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($addresses as $address): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 relative <?php echo $address['is_default'] ? 'border-2 border-blue-500' : 'border'; ?>">
                        <?php if ($address['is_default']): ?>
                            <span class="absolute top-2 right-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                ডিফল্ট ঠিকানা
                            </span>
                        <?php endif; ?>
                        
                        <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($address['name']); ?></h3>
                        <p class="text-gray-600 mb-1">
                            <i class="fas fa-phone-alt mr-2"></i> <?php echo htmlspecialchars($address['phone']); ?>
                        </p>
                        <p class="text-gray-600 mb-1">
                            <i class="fas fa-map-marker-alt mr-2"></i> 
                            <?php echo htmlspecialchars($address['address']); ?>, 
                            <?php echo htmlspecialchars($address['area']); ?>, 
                            <?php echo htmlspecialchars($address['city']); ?>
                        </p>
                        <?php if (!empty($address['postal_code'])): ?>
                            <p class="text-gray-600">
                                <i class="fas fa-mail-bulk mr-2"></i> পোস্টাল কোড: <?php echo htmlspecialchars($address['postal_code']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="mt-4 flex space-x-2">
                            <?php if (!$address['is_default']): ?>
                                <a href="addresses.php?set_default=<?php echo $address['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-star mr-1"></i> ডিফল্ট সেট করুন
                                </a>
                            <?php endif; ?>
                            
                            <button onclick="editAddress(<?php echo $address['id']; ?>)" 
                                    class="text-green-600 hover:text-green-800 text-sm ml-2">
                                <i class="fas fa-edit mr-1"></i> এডিট
                            </button>
                            
                            <a href="addresses.php?delete=<?php echo $address['id']; ?>" 
                               class="text-red-600 hover:text-red-800 text-sm ml-2"
                               onclick="return confirm('আপনি কি এই ঠিকানাটি মুছে ফেলতে চান?')">
                                <i class="fas fa-trash mr-1"></i> মুছুন
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i class="fas fa-map-marker-alt text-5xl text-gray-300 mb-4"></i>
                    <h2 class="text-xl font-bold mb-2">কোন ঠিকানা পাওয়া যায়নি</h2>
                    <p class="text-gray-600 mb-6">আপনি এখনো কোন ঠিকানা যোগ করেননি</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Address Modal -->
<div id="editAddressModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">ঠিকানা এডিট করুন</h2>
            
            <form id="editAddressForm" method="POST" action="">
                <input type="hidden" name="address_id" id="edit_address_id">
                <input type="hidden" name="update_address" value="1">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">প্রাপকের নাম*</label>
                    <input type="text" name="name" id="edit_name" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ফোন নম্বর*</label>
                    <input type="text" name="phone" id="edit_phone" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ঠিকানা*</label>
                    <textarea name="address" id="edit_address" class="w-full px-3 py-2 border rounded-lg" rows="3" required></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">এলাকা*</label>
                        <input type="text" name="area" id="edit_area" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">শহর*</label>
                        <select name="city" id="edit_city" class="w-full px-3 py-2 border rounded-lg" required>
                            <option value="">নির্বাচন করুন</option>
                            <option value="ঢাকা">ঢাকা</option>
                            <option value="চট্টগ্রাম">চট্টগ্রাম</option>
                            <option value="রাজশাহী">রাজশাহী</option>
                            <option value="খুলনা">খুলনা</option>
                            <option value="বরিশাল">বরিশাল</option>
                            <option value="সিলেট">সিলেট</option>
                            <option value="রংপুর">রংপুর</option>
                            <option value="ময়মনসিংহ">ময়মনসিংহ</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">পোস্টাল কোড</label>
                    <input type="text" name="postal_code" id="edit_postal_code" class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_default" id="edit_is_default" class="form-checkbox">
                        <span class="ml-2 text-sm text-gray-700">ডিফল্ট ঠিকানা হিসেবে সেট করুন</span>
                    </label>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                        বাতিল
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        আপডেট করুন
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit address
function editAddress(addressId) {
    // Fetch address details via AJAX
    fetch(`get_address.php?id=${addressId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const address = data.address;
                
                // Fill the form
                document.getElementById('edit_address_id').value = address.id;
                document.getElementById('edit_name').value = address.name;
                document.getElementById('edit_phone').value = address.phone;
                document.getElementById('edit_address').value = address.address;
                document.getElementById('edit_area').value = address.area;
                document.getElementById('edit_city').value = address.city;
                document.getElementById('edit_postal_code').value = address.postal_code;
                document.getElementById('edit_is_default').checked = address.is_default == 1;
                
                // Show the modal
                document.getElementById('editAddressModal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            } else {
                alert('ঠিকানা লোড করতে সমস্যা হয়েছে');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('একটি ত্রুটি ঘটেছে');
        });
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editAddressModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Close modal when clicking outside
document.getElementById('editAddressModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>