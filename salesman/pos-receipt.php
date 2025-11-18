<?php
$pageTitle = 'POS Receipt';
require_once '../includes/header.php';
requireRole('salesman');

$saleId = $_GET['sale_id'] ?? 0;

// Get sale details
$sale = Database::getInstance()->fetchOne("
    SELECT ps.*, 
           s.name as shop_name, s.address as shop_address, s.phone as shop_phone,
           u.full_name as customer_name, u.member_id, u.email as customer_email, u.phone as customer_phone,
           sl.full_name as salesman_name
    FROM pos_sales ps
    JOIN shops s ON ps.shop_id = s.id
    LEFT JOIN users u ON ps.customer_id = u.id
    JOIN users sl ON ps.salesman_id = sl.id
    WHERE ps.id = ?
", [$saleId]);

if (!$sale) {
    die('Sale not found');
}

// Get sale items
$items = Database::getInstance()->fetchAll("
    SELECT psi.*, m.name as medicine_name, m.generic_name
    FROM pos_sale_items psi
    JOIN medicines m ON psi.medicine_id = m.id
    WHERE psi.pos_sale_id = ?
", [$saleId]);

// Get customer's new point balance if customer exists
$newPointBalance = 0;
if ($sale['customer_id']) {
    require_once '../classes/Loyalty.php';
    $loyalty = new Loyalty();
    $newPointBalance = $loyalty->getUserPoints($sale['customer_id']);
}
?>

<!-- Print Styles -->
<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        margin: 0;
        padding: 20px;
    }
    .receipt {
        width: 80mm;
        margin: 0 auto;
    }
}

.receipt {
    max-width: 400px;
    margin: 0 auto;
    font-family: 'Courier New', monospace;
}
</style>

<div class="no-print bg-white border-2 border-gray-300 p-6 mb-4">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold">Receipt</h2>
        <div class="space-x-2">
            <button onclick="window.print()" class="px-6 py-3 bg-blue-600 text-white font-bold">
                🖨️ PRINT RECEIPT
            </button>
            <a href="pos.php" class="inline-block px-6 py-3 bg-green-600 text-white font-bold">
                ✓ NEW SALE
            </a>
        </div>
    </div>
</div>

<!-- Receipt Content -->
<div class="receipt bg-white border-2 border-gray-300 p-6">
    <!-- Header -->
    <div class="text-center border-b-2 border-dashed pb-4 mb-4">
        <div class="text-2xl font-bold"><?php echo clean($sale['shop_name']); ?></div>
        <div class="text-sm"><?php echo clean($sale['shop_address']); ?></div>
        <div class="text-sm">Tel: <?php echo clean($sale['shop_phone']); ?></div>
        <div class="text-lg font-bold mt-2">SALES RECEIPT</div>
    </div>
    
    <!-- Receipt Info -->
    <div class="text-sm mb-4">
        <div class="flex justify-between">
            <span>Receipt No:</span>
            <span class="font-bold"><?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="flex justify-between">
            <span>Date:</span>
            <span><?php echo date('d M Y, h:i A', strtotime($sale['created_at'])); ?></span>
        </div>
        <div class="flex justify-between">
            <span>Cashier:</span>
            <span><?php echo clean($sale['salesman_name']); ?></span>
        </div>
    </div>
    
    <!-- Customer Info (if linked) -->
    <?php if ($sale['customer_id']): ?>
    <div class="border-2 border-blue-300 bg-blue-50 p-3 mb-4">
        <div class="font-bold text-center mb-2">💎 MEMBER PURCHASE</div>
        <div class="text-sm">
            <div class="text-center mb-2">
                <div class="text-xs text-gray-600">Member ID</div>
                <div class="text-2xl font-bold font-mono bg-white border px-3 py-2 rounded">
                    <?php echo clean($sale['member_id']); ?>
                </div>
            </div>
            <div class="flex justify-between border-t pt-2">
                <span>Name:</span>
                <span class="font-bold"><?php echo clean($sale['customer_name']); ?></span>
            </div>
            <div class="flex justify-between">
                <span>Phone:</span>
                <span><?php echo clean($sale['customer_phone']); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Items -->
    <div class="border-b-2 border-dashed pb-2 mb-2">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <th class="text-left">Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="py-1">
                        <div class="font-bold"><?php echo clean($item['medicine_name']); ?></div>
                        <div class="text-xs text-gray-600"><?php echo clean($item['generic_name']); ?></div>
                    </td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-right font-bold"><?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Total -->
    <div class="mb-4">
        <div class="flex justify-between text-xl font-bold">
            <span>TOTAL:</span>
            <span><?php echo formatPrice($sale['total_amount']); ?></span>
        </div>
        <div class="flex justify-between text-sm">
            <span>Payment:</span>
            <span><?php echo strtoupper($sale['payment_method']); ?></span>
        </div>
    </div>
    
    <!-- Loyalty Points Section (if customer linked) -->
    <?php if ($sale['customer_id']): ?>
    <div class="border-2 border-yellow-400 bg-yellow-50 p-3 mb-4">
        <div class="font-bold text-center mb-2">🎉 LOYALTY POINTS</div>
        <div class="text-sm">
            <div class="flex justify-between">
                <span>Points Earned:</span>
                <span class="font-bold text-green-600">+<?php echo number_format($sale['points_earned']); ?> pts</span>
            </div>
            <div class="flex justify-between">
                <span>New Balance:</span>
                <span class="font-bold text-blue-600"><?php echo number_format($newPointBalance); ?> pts</span>
            </div>
            <div class="text-xs text-center mt-2 text-gray-600">
                (1 point = 1 BDT discount on next purchase)
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="text-center text-xs border-t-2 border-dashed pt-4 mt-4">
        <div class="mb-2">Thank you for your purchase!</div>
        <div class="mb-2">Goods once sold cannot be returned</div>
        <div class="mb-2">Keep this receipt for warranty claims</div>
        
        <?php if (!$sale['customer_id']): ?>
        <div class="mt-4 p-2 border border-blue-400 bg-blue-50">
            <div class="font-bold mb-1">💡 Become a Member!</div>
            <div class="text-xs">
                Register at QuickMed to earn points<br>
                and get exclusive discounts!
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <div class="font-bold">QuickMed Pharmacy</div>
            <div>www.quickmed.com</div>
        </div>
    </div>
</div>

<script>
// Auto-print on load (optional)
// window.onload = function() {
//     window.print();
// }
</script>

<?php require_once '../includes/footer.php'; ?>