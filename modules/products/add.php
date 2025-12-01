<?php
require_once '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $cost_price = $_POST['cost_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $min_stock_level = $_POST['min_stock_level'];
    $barcode = $_POST['barcode'];
    $unit = $_POST['unit'];

    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, category_id, price, cost_price, stock_quantity, min_stock_level, barcode, unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $category_id, $price, $cost_price, $stock_quantity, $min_stock_level, $barcode, $unit]);
        
        // Log inventory change for initial stock
        $product_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, quantity_change, new_quantity, reason, user_id) VALUES (?, ?, ?, 'restock', ?)");
        $stmt->execute([$product_id, $stock_quantity, $stock_quantity, $_SESSION['user_id']]);
        
        $_SESSION['success'] = "Product added successfully!";
        redirect('index.php');
    } catch (PDOException $e) {
        $error = "Error adding product: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add Product</h1>
    <a href="index.php" class="btn btn-secondary">Back to Products</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-control" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Barcode</label>
                        <input type="text" class="form-control" name="barcode">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Selling Price *</label>
                                <input type="number" step="0.01" class="form-control" name="price" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cost Price *</label>
                                <input type="number" step="0.01" class="form-control" name="cost_price" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" name="stock_quantity" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" class="form-control" name="min_stock_level" value="5">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <select class="form-control" name="unit">
                            <option value="pcs">Pieces</option>
                            <option value="set">Set</option>
                            <option value="box">Box</option>
                            <option value="roll">Roll</option>
                            <option value="pack">Pack</option>
                            <option value="kg">Kilogram</option>
                            <option value="m">Meter</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>