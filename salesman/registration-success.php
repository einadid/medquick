<?php
$pageTitle = 'Registration Success';
require_once '../includes/header.php';
requireRole('salesman');

if (!isset($_SESSION['new_customer'])) {
    redirect('/salesman/register-customer.php');
}

$customer = $_SESSION['new_customer'];
unset($_SESSION['new_customer']); // Clear after display
?>

<div class="max-w-2xl mx-auto">
    <div class="text-center mb-6">
        <div class="text-8xl mb-4">✅</div>
        <h2 class="text-3xl font-bold text-green-600 mb-2">Customer Account Created!</h2>
        <p class="text-gray-600">Membership activated successfully</p>
    </div>
    
    <!-- Customer Credentials Card -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-8 mb-6">
        <h3 class="text-2xl font-bold mb-6 text-center">Customer Login Credentials</h3>
        
        <div class="space-y-4">
            <div class="bg-white bg-opacity-20 rounded p-4">
                <div class="text-sm opacity-80 mb-1">Customer Name</div>
                <div class="text-2xl font-bold"><?php echo clean($customer['name']); ?></div>
            </div>
            
            <div class="bg-white bg-opacity-20 rounded p-4">
                <div class="text-sm opacity-80 mb-1">Email (Username)</div>
                <div class="text-xl font-mono"><?php echo clean($customer['email']); ?></div>
            </div>
            
            <div class="bg-white bg-opacity-20 rounded p-4">
                <div class="text-sm opacity-80 mb-1">Member ID</div>
                <div class="text-3xl font-bold font-mono tracking-wider">
                    <?php echo clean($customer['member_id']); ?>
                </div>
            </div>
            
            <div class="bg-white bg-opacity-20 rounded p-4">
                <div class="text-sm opacity-80 mb-1">Default Password</div>
                <div class="text-2xl font-mono"><?php echo clean($customer['password']); ?></div>
            </div>
        </div>
        
        <div class="mt-6 text-center text-sm opacity-90">
            💎 100 Welcome Bonus Points Added
        </div>
    </div>
    
    <!-- Print Instructions -->
    <div class="bg-yellow-50 border-2 border-yellow-400 p-6 mb-6">
        <h3 class="font-bold mb-3">📋 IMPORTANT: Give These Details to Customer</h3>
        <div class="space-y-2 text-sm">
            <div class="flex items-start gap-2">
                <span class="font-bold">1.</span>
                <span>Write down or print these credentials for the customer</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="font-bold">2.</span>
                <span>Customer can login at: <code class="bg-white px-2 py-1 border"><?php echo SITE_URL; ?></code></span>
            </div>
            <div class="flex items-start gap-2">
                <span class="font-bold">3.</span>
                <span>Customer should change password immediately after first login</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="font-bold">4.</span>
                <span>Customer can use Member ID: <strong><?php echo $customer['member_id']; ?></strong> at the counter</span>
            </div>
        </div>
    </div>
    
    <!-- Printable Card -->
    <div id="printCard" class="bg-white border-2 border-gray-300 p-6 mb-6">
        <div class="text-center border-b-2 pb-4 mb-4">
            <div class="text-2xl font-bold">QuickMed Pharmacy</div>
            <div class="text-lg">Member Welcome Card</div>
        </div>
        
        <table class="w-full text-sm">
            <tr>
                <td class="py-2 font-bold">Name:</td>
                <td class="py-2"><?php echo clean($customer['name']); ?></td>
            </tr>
            <tr>
                <td class="py-2 font-bold">Member ID:</td>
                <td class="py-2"><span class="text-xl font-mono font-bold"><?php echo clean($customer['member_id']); ?></span></td>
            </tr>
            <tr>
                <td class="py-2 font-bold">Email:</td>
                <td class="py-2"><?php echo clean($customer['email']); ?></td>
            </tr>
            <tr>
                <td class="py-2 font-bold">Password:</td>
                <td class="py-2 font-mono"><?php echo clean($customer['password']); ?></td>
            </tr>
            <tr>
                <td class="py-2 font-bold">Points:</td>
                <td class="py-2">100 Welcome Points</td>
            </tr>
        </table>
        
        <div class="mt-4 p-3 bg-blue-50 border border-blue-300 text-xs">
            <div class="font-bold mb-1">Login Instructions:</div>
            <div>1. Visit: <?php echo SITE_URL; ?></div>
            <div>2. Click "Login" and use your email & password</div>
            <div>3. Change your password from Profile → Change Password</div>
            <div>4. Show your Member ID at the counter to earn points!</div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="grid grid-cols-2 gap-4">
        <button onclick="window.print()" class="p-4 bg-blue-600 text-white font-bold">
            🖨️ PRINT CARD
        </button>
        <a href="register-customer.php" class="p-4 bg-green-600 text-white text-center font-bold">
            ➕ REGISTER ANOTHER
        </a>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printCard, #printCard * {
        visibility: visible;
    }
    #printCard {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>