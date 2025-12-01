<?php
require_once '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $quantity_change = $_POST['quantity_change'];
    $reason = $_POST['reason'];
    $notes = $_POST['notes'];
    
    try {
        $pdo->beginTransaction();
        
        // Get current stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $current_stock = $stmt->fetchColumn();
        $new_quantity = $current_stock + $quantity_change;
        
        if ($new_quantity < 0) {
            throw new Exception("Cannot reduce stock below zero!");
        }
        
        // Update product stock
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $product_id]);
        
        // Log inventory change
        $stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, quantity_change, new_quantity, reason, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $quantity_change, $new_quantity, $reason, $_SESSION['user_id']]);
        
        $pdo->commit();
        $_SESSION['success'] = "Stock updated successfully!";
        redirect('index.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating stock: " . $e->getMessage();
    }
}

// Get products for dropdown
$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Update Stock</h1>
    <a href="index.php" class="btn btn-secondary">Back to Inventory</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Product *</label>
                        <select class="form-control" name="product_id" required id="productSelect">
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" data-stock="<?php echo $product['stock_quantity']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> (Current: <?php echo $product['stock_quantity']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Stock</label>
                        <input type="text" class="form-control" id="currentStock" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantity Change *</label>
                        <input type="number" class="form-control" name="quantity_change" required placeholder="Positive for restock, negative for adjustment">
                        <small class="text-muted">Use positive numbers to add stock, negative to remove stock</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason *</label>
                        <select class="form-control" name="reason" required>
                            <option value="restock">Restock/New Delivery</option>
                            <option value="adjustment">Stock Adjustment</option>
                            <option value="return">Customer Return</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Optional notes about this stock change"></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Stock Updates</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Change</th>
                                <th>Reason</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT il.*, p.name as product_name 
                                                FROM inventory_logs il 
                                                JOIN products p ON il.product_id = p.id 
                                                ORDER BY il.created_at DESC 
                                                LIMIT 10");
                            $recent_logs = $stmt->fetchAll();
                            ?>
                            <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['product_name']); ?></td>
                                <td>
                                    <span class="<?php echo $log['quantity_change'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $log['quantity_change'] > 0 ? '+' : ''; ?><?php echo $log['quantity_change']; ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($log['reason']); ?></td>
                                <td><?php echo date('M j, g:i A', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('productSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const currentStock = selectedOption.dataset.stock || '0';
    document.getElementById('currentStock').value = currentStock;
});
</script>

<?php require_once '../../includes/footer.php'; ?>