<?php
$pageTitle = 'My Profile';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

require_once 'classes/Loyalty.php';

$user = getCurrentUser();
$userId = $user['id'];

// Get full user details
$userDetails = Database::getInstance()->fetchOne("
    SELECT u.*, r.role_name, s.name as shop_name, s.city as shop_city
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN shops s ON u.shop_id = s.id
    WHERE u.id = ?
", [$userId]);

// Get loyalty points if customer
$loyaltyPoints = 0;
$pointsBreakdown = null;
$pointsHistory = [];

if ($user['role_name'] === 'customer') {
    $loyalty = new Loyalty();
    $loyaltyPoints = $loyalty->getUserPoints($userId);
    $pointsBreakdown = $loyalty->getPointsBreakdown($userId);
    $pointsHistory = $loyalty->getTransactionHistory($userId, 20);
    $tier = $loyalty->getUserTier($userId);
}

// Order statistics
$orderStats = Database::getInstance()->fetchOne("
    SELECT COUNT(*) as total_orders,
           SUM(total_amount) as total_spent,
           MAX(created_at) as last_order_date
    FROM orders
    WHERE user_id = ?
", [$userId]);
?>

<div class="max-w-6xl mx-auto">
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <h2 class="text-2xl font-bold">My Profile</h2>
        <p class="text-gray-600">Manage your account information</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Profile Sidebar -->
        <div class="space-y-4">

            <!-- Profile Card -->
            <div class="bg-white border-2 border-gray-300 p-6">

                <!-- =============================== -->
                <!--     PROFILE PICTURE UPDATED     -->
                <!-- =============================== -->
                <div class="text-center mb-4">
                    <?php if ($userDetails['profile_picture'] && file_exists(UPLOAD_PATH . $userDetails['profile_picture'])): ?>
                        <img src="<?php echo UPLOAD_URL . $userDetails['profile_picture']; ?>" 
                             alt="Profile Picture" 
                             class="w-24 h-24 rounded-full mx-auto border-4 border-blue-600 object-cover mb-3">
                    <?php else: ?>
                        <div class="w-24 h-24 bg-blue-600 text-white rounded-full flex items-center justify-center text-4xl font-bold mx-auto mb-3">
                            <?php echo strtoupper(substr($userDetails['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="text-xl font-bold"><?php echo clean($userDetails['full_name']); ?></h3>
                    <div class="text-sm text-gray-600"><?php echo clean($userDetails['email']); ?></div>
                </div>
                <!-- =============================== -->
                <!--   END UPDATED PROFILE SECTION   -->
                <!-- =============================== -->

                <div class="space-y-2">

                    <!-- Member ID -->
                    <?php if ($userDetails['role_name'] === 'customer' && $userDetails['member_id']): ?>
                    <div class="p-4 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg mb-3">
                        <div class="text-xs opacity-80 mb-1">YOUR MEMBER ID</div>
                        <div class="text-2xl font-bold font-mono tracking-wider">
                            <?php echo clean($userDetails['member_id']); ?>
                        </div>
                        <div class="text-xs opacity-80 mt-1">Show this at the counter</div>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Role:</span>
                        <span class="font-bold px-2 py-1 bg-blue-100 text-xs">
                            <?php echo strtoupper(str_replace('_', ' ', $userDetails['role_name'])); ?>
                        </span>
                    </div>

                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-bold px-2 py-1 <?php echo $userDetails['status'] === 'active' ? 'bg-green-100' : 'bg-red-100'; ?> text-xs">
                            <?php echo strtoupper($userDetails['status']); ?>
                        </span>
                    </div>

                    <?php if ($userDetails['shop_name']): ?>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Shop:</span>
                        <span class="font-bold text-sm"><?php echo clean($userDetails['shop_name']); ?></span>
                    </div>

                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">City:</span>
                        <span class="font-bold text-sm"><?php echo clean($userDetails['shop_city']); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between py-2">
                        <span class="text-gray-600">Member Since:</span>
                        <span class="font-bold text-sm"><?php echo date('M Y', strtotime($userDetails['created_at'])); ?></span>
                    </div>
                </div>

                <a href="edit-profile.php" class="block w-full mt-4 p-2 bg-blue-600 text-white text-center font-bold">
                    EDIT PROFILE
                </a>
            </div>

            <!-- Loyalty Points Card -->
            <?php if ($user['role_name'] === 'customer'): ?>
            <div class="bg-gradient-to-br from-yellow-400 to-orange-500 border-2 border-yellow-600 p-6 text-white">
                <h3 class="text-lg font-bold mb-2">💎 Loyalty Points</h3>
                <div class="text-4xl font-bold mb-2"><?php echo number_format($loyaltyPoints); ?></div>
                <div class="text-sm opacity-90">Available Balance</div>

                <?php if (isset($tier)): ?>
                <div class="mt-4 pt-4 border-t border-white border-opacity-30">
                    <div class="text-sm font-bold"><?php echo $tier['tier']; ?> Member</div>
                    <div class="text-xs opacity-90"><?php echo $tier['benefits']; ?></div>
                </div>
                <?php endif; ?>

                <div class="mt-4 pt-4 border-t border-white border-opacity-30 text-xs">
                    <div class="flex justify-between mb-1">
                        <span>Earned:</span>
                        <span class="font-bold">+<?php echo number_format($pointsBreakdown['earned'] ?? 0); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Redeemed:</span>
                        <span class="font-bold">-<?php echo number_format($pointsBreakdown['redeemed'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="md:col-span-2 space-y-4">

            <!-- Contact Info -->
            <div class="bg-white border-2 border-gray-300 p-6">
                <h3 class="text-xl font-bold mb-4">Contact Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Full Name</label>
                        <div class="p-2 bg-gray-50 border"><?php echo clean($userDetails['full_name']); ?></div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Email</label>
                        <div class="p-2 bg-gray-50 border"><?php echo clean($userDetails['email']); ?></div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Phone</label>
                        <div class="p-2 bg-gray-50 border"><?php echo clean($userDetails['phone'] ?: 'Not provided'); ?></div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Last Login</label>
                        <div class="p-2 bg-gray-50 border">
                            <?php echo $userDetails['last_login'] ? date('M d, Y h:i A', strtotime($userDetails['last_login'])) : 'Never'; ?>
                        </div>
                    </div>

                    <?php if ($userDetails['address']): ?>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">Address</label>
                        <div class="p-2 bg-gray-50 border"><?php echo nl2br(clean($userDetails['address'])); ?></div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Order Stats -->
            <?php if ($user['role_name'] === 'customer'): ?>
            <div class="bg-white border-2 border-gray-300 p-6">
                <h3 class="text-xl font-bold mb-4">Order Statistics</h3>

                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-blue-50 border">
                        <div class="text-3xl font-bold text-blue-600"><?php echo $orderStats['total_orders'] ?? 0; ?></div>
                        <div class="text-sm text-gray-600">Total Orders</div>
                    </div>

                    <div class="text-center p-4 bg-green-50 border">
                        <div class="text-2xl font-bold text-green-600">
                            <?php echo formatPrice($orderStats['total_spent'] ?? 0); ?>
                        </div>
                        <div class="text-sm text-gray-600">Total Spent</div>
                    </div>

                    <div class="text-center p-4 bg-purple-50 border">
                        <div class="text-sm font-bold text-purple-600">
                            <?php echo $orderStats['last_order_date'] ? date('M d, Y', strtotime($orderStats['last_order_date'])) : 'Never'; ?>
                        </div>
                        <div class="text-sm text-gray-600">Last Order</div>
                    </div>
                </div>
            </div>

            <!-- Points History -->
            <?php if (!empty($pointsHistory)): ?>
            <div class="bg-white border-2 border-gray-300 p-6">
                <h3 class="text-xl font-bold mb-4">Recent Points Activity</h3>

                <div class="space-y-2">
                    <?php foreach (array_slice($pointsHistory, 0, 10) as $transaction): ?>
                    <div class="flex justify-between items-center p-3 border-b">
                        <div class="flex-1">
                            <div class="font-semibold"><?php echo clean($transaction['description']); ?></div>
                            <div class="text-xs text-gray-600">
                                <?php echo date('M d, Y h:i A', strtotime($transaction['created_at'])); ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold <?php echo $transaction['points'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $transaction['points'] > 0 ? '+' : ''; ?><?php echo $transaction['points']; ?> pts
                            </div>
                            <div class="text-xs text-gray-600"><?php echo ucfirst($transaction['type']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="bg-white border-2 border-gray-300 p-6">
                <h3 class="text-xl font-bold mb-4">Quick Actions</h3>

                <div class="grid grid-cols-2 gap-3">
                    <a href="edit-profile.php" class="p-3 border-2 border-blue-300 bg-blue-50 text-center hover:bg-blue-100">
                        <div class="text-2xl mb-1">✏️</div>
                        <div class="font-bold text-sm">Edit Profile</div>
                    </a>

                    <a href="change-password.php" class="p-3 border-2 border-green-300 bg-green-50 text-center hover:bg-green-100">
                        <div class="text-2xl mb-1">🔒</div>
                        <div class="font-bold text-sm">Change Password</div>
                    </a>

                    <?php if ($user['role_name'] === 'customer'): ?>
                    <a href="customer/orders.php" class="p-3 border-2 border-purple-300 bg-purple-50 text-center hover:bg-purple-100">
                        <div class="text-2xl mb-1">📦</div>
                        <div class="font-bold text-sm">My Orders</div>
                    </a>

                    <a href="customer/cart.php" class="p-3 border-2 border-orange-300 bg-orange-50 text-center hover:bg-orange-100">
                        <div class="text-2xl mb-1">🛒</div>
                        <div class="font-bold text-sm">Shopping Cart</div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
