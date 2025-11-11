<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get order ID from URL or form
$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? 0;

// Fetch order details
$order = null;
$order_items = [];
$order_statuses = [];

if ($order_id) {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, a.* 
        FROM orders o
        JOIN addresses a ON o.shipping_address_id = a.id
        WHERE o.id = ? AND o.customer_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if ($order) {
        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, m.name, m.image 
            FROM order_items oi
            JOIN medicines m ON oi.medicine_id = m.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();

        // Get order status history
        $stmt = $pdo->prepare("
            SELECT * FROM order_status_history 
            WHERE order_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$order_id]);
        $order_statuses = $stmt->fetchAll();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">অর্ডার ট্র্যাক করুন</h1>
    
    <!-- Order Search Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
            <div class="flex-grow">
                <label class="block text-gray-700 text-sm font-bold mb-2">অর্ডার নম্বর</label>
                <input type="text" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>" 
                       class="w-full px-3 py-2 border rounded-lg" placeholder="অর্ডার আইডি লিখুন" required>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>ট্র্যাক করুন
                </button>
            </div>
        </form>
    </div>
    
    <?php if ($order): ?>
        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">অর্ডার সারাংশ #<?php echo $order['id']; ?></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">অর্ডার তথ্য</h3>
                    <p><span class="font-medium">অর্ডার তারিখ:</span> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                    <p><span class="font-medium">পেমেন্ট মেথড:</span> 
                        <?php 
                        $payment_methods = [
                            'cod' => 'ক্যাশ অন ডেলিভারি',
                            'bkash' => 'bKash',
                            'nagad' => 'নগদ',
                            'card' => 'ক্রেডিট/ডেবিট কার্ড'
                        ];
                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method']; 
                        ?>
                    </p>
                    <p><span class="font-medium">পেমেন্ট স্ট্যাটাস:</span> 
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php echo $order['payment_status'] == 'completed' ? 'bg-green-100 text-green-800' : 
                                  ($order['payment_status'] == 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">ডেলিভারি ঠিকানা</h3>
                    <p><?php echo htmlspecialchars($order['name']); ?></p>
                    <p><?php echo htmlspecialchars($order['address']); ?>, <?php echo htmlspecialchars($order['area']); ?></p>
                    <p><?php echo htmlspecialchars($order['city']); ?></p>
                    <p>ফোন: <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Order Status Timeline -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">অর্ডার স্ট্যাটাস</h2>
            
            <div class="relative">
                <div class="absolute left-4 top-0 h-full w-0.5 bg-gray-200"></div>
                
                <?php
                $statuses = [
                    'pending' => ['icon' => 'fas fa-clock', 'text' => 'অপেক্ষমান', 'color' => 'text-gray-500'],
                    'confirmed' => ['icon' => 'fas fa-check-circle', 'text' => 'কনফার্মড', 'color' => 'text-blue-500'],
                    'processing' => ['icon' => 'fas fa-cog', 'text' => 'প্রসেসিং', 'color' => 'text-yellow-500'],
                    'shipped' => ['icon' => 'fas fa-shipping-fast', 'text' => 'শিপড', 'color' => 'text-purple-500'],
                    'out_for_delivery' => ['icon' => 'fas fa-truck', 'text' => 'ডেলিভারির জন্য বের হয়েছে', 'color' => 'text-orange-500'],
                    'delivered' => ['icon' => 'fas fa-check-circle', 'text' => 'ডেলিভার্ড', 'color' => 'text-green-500'],
                    'cancelled' => ['icon' => 'fas fa-times-circle', 'text' => 'বাতিল', 'color' => 'text-red-500']
                ];
                
                $current_status = $order['status'];
                $status_found = false;
                
                foreach ($statuses as $status => $details):
                    $is_completed = false;
                    $is_current = false;
                    $status_time = '';
                    
                    // Check if this status has been reached
                    foreach ($order_statuses as $status_entry) {
                        if ($status_entry['status'] == $status) {
                            $is_completed = true;
                            $status_time = date('d/m/Y H:i', strtotime($status_entry['created_at']));
                            
                            if ($status == $current_status) {
                                $is_current = true;
                            }
                            break;
                        }
                    }
                    
                    // If current status not found in history, mark as current
                    if (!$status_found && $status == $current_status) {
                        $is_current = true;
                        $status_found = true;
                    }
                ?>
                <div class="relative mb-8 pl-12">
                    <div class="absolute left-0 -ml-4 w-8 h-8 rounded-full 
                                <?php echo $is_completed ? 'bg-green-500' : ($is_current ? 'bg-blue-500' : 'bg-gray-300'); ?> 
                                flex items-center justify-center text-white">
                        <i class="<?php echo $details['icon']; ?>"></i>
                    </div>
                    
                    <h3 class="font-semibold <?php echo $is_completed || $is_current ? $details['color'] : 'text-gray-400'; ?>">
                        <?php echo $details['text']; ?>
                        <?php if ($is_current): ?>
                            <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">বর্তমান</span>
                        <?php endif; ?>
                    </h3>
                    
                    <?php if ($is_completed): ?>
                        <p class="text-sm text-gray-500 mt-1">সম্পন্ন হয়েছে: <?php echo $status_time; ?></p>
                    <?php elseif ($is_current): ?>
                        <p class="text-sm text-gray-500 mt-1">প্রক্রিয়াধীন</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($order['tracking_number'])): ?>
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">ট্র্যাকিং নম্বর</h3>
                    <p class="text-lg font-mono"><?php echo htmlspecialchars($order['tracking_number']); ?></p>
                    <p class="text-sm text-gray-600 mt-2">এই নম্বরটি দিয়ে আপনি কুরিয়ার সার্ভিসে ট্র্যাক করতে পারবেন</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Items -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">অর্ডারকৃত আইটেমসমূহ</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-2 px-4 text-left">পণ্য</th>
                            <th class="py-2 px-4 text-center">দাম</th>
                            <th class="py-2 px-4 text-center">পরিমাণ</th>
                            <th class="py-2 px-4 text-right">মোট</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr class="border-t">
                            <td class="py-2 px-4">
                                <div class="flex items-center">
                                    <img src="../assets/images/medicines/<?php echo $item['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="w-10 h-10 object-cover rounded-lg mr-3">
                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                            </td>
                            <td class="py-2 px-4 text-center">৳<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="py-2 px-4 text-center"><?php echo $item['quantity']; ?></td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr class="border-t font-semibold">
                            <td colspan="3" class="py-2 px-4 text-right">সাবটোটাল:</td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="py-2 px-4 text-right">ডেলিভারি চার্জ:</td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($order['delivery_charge'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="py-2 px-4 text-right">ডিসকাউন্ট:</td>
                            <td class="py-2 px-4 text-right">-৳<?php echo number_format($order['discount'], 2); ?></td>
                        </tr>
                        <tr class="border-t font-bold text-lg">
                            <td colspan="3" class="py-2 px-4 text-right">সর্বমোট:</td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($order['final_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php if ($order['status'] == 'pending' || $order['status'] == 'confirmed'): ?>
                <div class="mt-6 text-right">
                    <button onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        <i class="fas fa-times mr-2"></i>অর্ডার বাতিল করুন
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($order_id): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-exclamation-triangle text-5xl text-yellow-400 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">অর্ডার খুঁজে পাওয়া যায়নি</h2>
            <p class="text-gray-600 mb-6">দয়া করে সঠিক অর্ডার নম্বর লিখুন</p>
            <a href="track_order.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>আবার চেষ্টা করুন
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// Cancel order
function cancelOrder(orderId) {
    if (confirm('আপনি কি নিশ্চিত যে আপনি এই অর্ডারটি বাতিল করতে চান?')) {
        fetch('cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('অর্ডারটি সফলভাবে বাতিল করা হয়েছে।');
                location.reload();
            } else {
                alert('অর্ডার বাতিল করতে সমস্যা হয়েছে: ' + (data.error || 'অজানা ত্রুটি'));
            }
        })
        .catch(error => {
            alert('একটি ত্রুটি ঘটেছে: ' + error.message);
        });
    }
}

// Track courier (example with Redx)
function trackCourier(trackingNumber) {
    // For demo purposes - in real implementation, use the courier's API
    const couriers = [
        {
            name: 'RedX',
            url: `https://redx.com.bd/tracking/${trackingNumber}`,
            icon: 'fas fa-truck'
        },
        {
            name: 'Pathao',
            url: `https://pathao.com/track/${trackingNumber}`,
            icon: 'fas fa-motorcycle'
        },
        {
            name: 'eCourier',
            url: `https://ecourier.com.bd/track/${trackingNumber}`,
            icon: 'fas fa-shipping-fast'
        }
    ];
    
    let trackingHtml = '<div class="space-y-2 mt-4">';
    trackingHtml += '<p class="text-sm font-semibold">কুরিয়ার সার্ভিসে ট্র্যাক করুন:</p>';
    
    couriers.forEach(courier => {
        trackingHtml += `
            <a href="${courier.url}" target="_blank" 
               class="inline-flex items-center bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-lg text-sm mr-2">
                <i class="${courier.icon} mr-1"></i> ${courier.name}
            </a>
        `;
    });
    
    trackingHtml += '</div>';
    
    // Add to tracking info div
    document.getElementById('trackingInfo').innerHTML += trackingHtml;
}

// Call this function if tracking number exists
<?php if (!empty($order['tracking_number'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        trackCourier('<?php echo $order['tracking_number']; ?>');
    });
<?php endif; ?>


</script>

<?php include '../includes/footer.php'; ?>