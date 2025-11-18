<?php
$pageTitle = 'POS Return';
require_once '../includes/header.php';
requireRole('salesman');

$user = getCurrentUser();
$shopId = $user['shop_id'];

// Handle return submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_return'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $posSaleId = $_POST['pos_sale_id'];
    $returnItems = $_POST['return_items'] ?? [];
    $reason = $_POST['reason'];
    
    if (empty($returnItems)) {
        setFlash('error', 'Please select items to return');
        redirect('/salesman/pos-return.php');
    }
    
    Database::getInstance()->getConnection()->beginTransaction();
    
    try {
        // Get sale details
        $sale = Database::getInstance()->fetchOne("
            SELECT * FROM pos_sales WHERE id = ? AND shop_id = ?
        ", [$posSaleId, $shopId]);
        
        if (!$sale) {
            throw new Exception('Sale not found');
        }
        
        // Calculate refund amount
        $refundAmount = 0;
        foreach ($returnItems as $itemId) {
            $item = Database::getInstance()->fetchOne("
                SELECT * FROM pos_sale_items WHERE id = ? AND pos_sale_id = ?
            ", [$itemId, $posSaleId]);
            
            if ($item) {
                $refundAmount += $item['quantity'] * $item['price'];
            }
        }
        
        // Create return record
        $returnId = Database::getInstance()->insert('pos_returns', [
            'pos_sale_id' => $posSaleId,
            'shop_id' => $shopId,
            'salesman_id' => $_SESSION['user_id'],
            'customer_id' => $sale['customer_id'],
            'total_refund_amount' => $refundAmount,
            'reason' => $reason,
            'status' => 'completed'
        ]);
        
        // Add return items and restore stock
        foreach ($returnItems as $itemId) {
            $item = Database::getInstance()->fetchOne("
                SELECT * FROM pos_sale_items WHERE id = ?
            ", [$itemId]);
            
            if ($item) {
                // Add to return items
                Database::getInstance()->insert('pos_return_items', [
                    'pos_return_id' => $returnId,
                    'pos_sale_item_id' => $itemId,
                    'medicine_id' => $item['medicine_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
                
                // Restore stock
                Database::getInstance()->query("
                    UPDATE shop_medicines 
                    SET stock = stock + ? 
                    WHERE id = ?
                ", [$item['quantity'], $item['shop_medicine_id']]);
            }
        }
        
        // Deduct points if customer had earned them
        if ($sale['customer_id'] && $sale['points_earned'] > 0) {
            require_once '../classes/Loyalty.php';
            $loyalty = new Loyalty();
            $loyalty->deductPoints(
                $sale['customer_id'],
                $sale['points_earned'],
                "Return processed for POS Sale #$posSaleId"
            );
        }
        
        Database::getInstance()->getConnection()->commit();
        
        $_SESSION['return_id'] = $returnId;
        logAudit($_SESSION['user_id'], 'pos_return_processed', "Return #$returnId for sale #$posSaleId");
        
        redirect('/salesman/pos-return-receipt.php?return_id=' . $returnId);
        
    } catch (Exception $e) {
        Database::getInstance()->getConnection()->rollBack();
        setFlash('error', 'Return failed: ' . $e->getMessage());
    }
}

// Search for sale by invoice number
$sale = null;
$saleItems = [];

if (isset($_GET['invoice'])) {
    $invoiceNumber = $_GET['invoice'];
    
    $sale = Database::getInstance()->fetchOne("
        SELECT ps.*, u.full_name as customer_name, u.member_id
        FROM pos_sales ps
        LEFT JOIN users u ON ps.customer_id = u.id
        WHERE ps.id = ? AND ps.shop_id = ?
    ", [$invoiceNumber, $shopId]);
    
    if ($sale) {
        $saleItems = Database::getInstance()->fetchAll("
            SELECT psi.*, m.name as medicine_name, m.generic_name
            FROM pos_sale_items psi
            JOIN medicines m ON psi.medicine_id = m.id
            WHERE psi.pos_sale_id = ?
        ", [$sale['id']]);
        
        // Check if already returned
        $existingReturn = Database::getInstance()->fetchOne("
            SELECT id FROM pos_returns WHERE pos_sale_id = ?
        ", [$sale['id']]);
        
        if ($existingReturn) {
            $alreadyReturned = true;
        }
    }
}
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">POS Return / Refund</h2>
    <p class="text-gray-600">Process returns for walk-in sales</p>
</div>

<!-- Invoice Search -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4">Search Invoice</h3>
    
    <form method="GET" class="flex gap-2">
        <input type="number" 
               name="invoice" 
               placeholder="Enter Invoice Number (Receipt No)" 
               required
               value="<?php echo $_GET['invoice'] ?? ''; ?>"
               class="flex-1 p-3 border-2 border-gray-400 text-lg">
        <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold">
            SEARCH
        </button>
    </form>
</div>

<!-- Sale Details -->
<?php if (isset($_GET['invoice']) && !$sale): ?>
    <div class="bg-red-100 border-2 border-red-400 text-red-700 p-4">
        Invoice #<?php echo clean($_GET['invoice']); ?> not found in this shop.
    </div>
<?php elseif ($sale): ?>
    
    <?php if (isset($alreadyReturned)): ?>
        <div class="bg-yellow-100 border-2 border-yellow-400 text-yellow-700 p-4 mb-4">
            ⚠️ This invoice has already been returned.
        </div>
    <?php endif; ?>
    
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <h3 class="text-xl font-bold mb-4">Invoice Details</h3>
        
        <table class="w-full border-2 border-gray-300 mb-4">
            <tr>
                <td class="p-2 border font-bold bg-gray-100">Invoice No:</td>
                <td class="p-2 border"><?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></td>
            </tr>
            <tr>
                <td class="p-2 border font-bold bg-gray-100">Date:</td>
                <td class="p-2 border"><?php echo date('M d, Y h:i A', strtotime($sale['created_at'])); ?></td>
            </tr>
            <tr>
                <td class="p-2 border font-bold bg-gray-100">Customer:</td>
                <td class="p-2 border">
                    <?php if ($sale['customer_name']): ?>
                        <?php echo clean($sale['customer_name']); ?> 
                        (<?php echo clean($sale['member_id']); ?>)
                    <?php else: ?>
                        Guest Customer
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="p-2 border font-bold bg-gray-100">Total Amount:</td>
                <td class="p-2 border font-bold text-lg"><?php echo formatPrice($sale['total_amount']); ?></td>
            </tr>
        </table>
    </div>
    
    <!-- Return Form -->
    <?php if (!isset($alreadyReturned)): ?>
    <div class="bg-white border-2 border-gray-300 p-6">
        <h3 class="text-xl font-bold mb-4">Select Items to Return</h3>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="process_return" value="1">
            <input type="hidden" name="pos_sale_id" value="<?php echo $sale['id']; ?>">
            
            <table class="w-full border-2 border-gray-300 mb-4">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">Select</th>
                        <th class="p-2 text-left border">Medicine</th>
                        <th class="p-2 text-center border">Qty</th>
                        <th class="p-2 text-right border">Price</th>
                        <th class="p-2 text-right border">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saleItems as $item): ?>
                    <tr>
                        <td class="p-2 border text-center">
                            <input type="checkbox" 
                                   name="return_items[]" 
                                   value="<?php echo $item['id']; ?>"
                                   class="w-5 h-5">
                        </td>
                        <td class="p-2 border">
                            <div class="font-bold"><?php echo clean($item['medicine_name']); ?></div>
                            <div class="text-sm text-gray-600"><?php echo clean($item['generic_name']); ?></div>
                        </td>
                        <td class="p-2 border text-center"><?php echo $item['quantity']; ?></td>
                        <td class="p-2 border text-right"><?php echo formatPrice($item['price']); ?></td>
                        <td class="p-2 border text-right font-bold">
                            <?php echo formatPrice($item['quantity'] * $item['price']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Return Reason *</label>
                <textarea name="reason" 
                          required 
                          rows="3" 
                          placeholder="Enter reason for return..."
                          class="w-full p-2 border-2 border-gray-400"></textarea>
            </div>
            
            <button type="submit" 
                    class="w-full p-4 bg-red-600 text-white font-bold text-lg"
                    onclick="return confirm('Process this return and issue refund?')">
                PROCESS RETURN & REFUND
            </button>
        </form>
    </div>
    <?php endif; ?>
    
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>