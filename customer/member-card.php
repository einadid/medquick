<?php
$pageTitle = 'Member Card';
require_once '../includes/header.php';
requireRole('customer');

$user = getCurrentUser();

require_once '../classes/Loyalty.php';
$loyalty = new Loyalty();
$points = $loyalty->getUserPoints($user['id']);
$tier = $loyalty->getUserTier($user['id']);
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <h2 class="text-2xl font-bold">My Member Card</h2>
        <p class="text-gray-600">Show this at the counter to earn points</p>
    </div>
    
    <!-- Digital Member Card -->
    <div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white rounded-lg shadow-2xl p-8 mb-4">
        <div class="flex justify-between items-start mb-8">
            <div>
                <div class="text-sm opacity-80">QuickMed Pharmacy</div>
                <div class="text-2xl font-bold">MEMBER CARD</div>
            </div>
            <div class="bg-white text-blue-600 px-3 py-1 rounded font-bold">
                <?php echo $tier['tier']; ?>
            </div>
        </div>
        
        <div class="mb-6">
            <div class="text-sm opacity-80 mb-1">Member ID</div>
            <div class="text-4xl font-bold font-mono tracking-wider">
                <?php echo clean($user['member_id']); ?>
            </div>
        </div>
        
        <div class="flex justify-between items-end">
            <div>
                <div class="text-sm opacity-80">Member Name</div>
                <div class="text-xl font-bold"><?php echo clean($user['full_name']); ?></div>
            </div>
            <div class="text-right">
                <div class="text-sm opacity-80">Points Balance</div>
                <div class="text-2xl font-bold"><?php echo number_format($points); ?> pts</div>
            </div>
        </div>
    </div>
    
    <!-- QR Code (Optional - requires QR library) -->
    <div class="bg-white border-2 border-gray-300 p-6 text-center">
        <div class="font-bold mb-2">Quick Scan Code</div>
        <div class="bg-gray-200 w-48 h-48 mx-auto flex items-center justify-center border-2">
            <!-- QR Code would go here -->
            <div class="text-6xl font-bold text-gray-400"><?php echo $user['id']; ?></div>
        </div>
        <div class="mt-2 text-sm text-gray-600">
            Show this to the cashier or provide your Member ID
        </div>
    </div>
    
    <!-- Instructions -->
    <div class="bg-blue-50 border-2 border-blue-300 p-6 mt-4">
        <h3 class="font-bold mb-2">💡 How to Earn Points</h3>
        <ul class="text-sm space-y-1">
            <li>✓ Tell the cashier your Member ID before purchase</li>
            <li>✓ Earn 100 points for every 1000 BDT spent</li>
            <li>✓ Use points as discount (1 point = 1 BDT)</li>
            <li>✓ Get special offers for <?php echo $tier['tier']; ?> members</li>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>