<?php
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$product_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $cost_price = $_POST['cost_price'];
    $min_stock_level = $_POST['min_stock_level'];
    $barcode = $_POST['barcode'];
    $unit = $_POST['unit'];

    try {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, category_id = ?, price = ?, cost_price = ?, min_stock_level = ?, barcode = ?, unit = ? WHERE id = ?");
        $stmt->execute([$name, $description, $category_id, $price, $cost_price, $min_stock_level, $barcode, $unit, $product_id]);
        
        $_SESSION['success'] = "Product updated successfully!";
        redirect('index.php');
    } catch (PDOException $e) {
        $error = "Error updating product: " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Product</h1>
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
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-control" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Barcode</label>
                        <input type="text" class="form-control" name="barcode" value="<?php echo htmlspecialchars($product['barcode']); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Selling Price *</label>
                                <input type="number" step="0.01" class="form-control" name="price" value="<?php echo $product['price']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cost Price *</label>
                                <input type="number" step="0.01" class="form-control" name="cost_price" value="<?php echo $product['cost_price']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Current Stock</label>
                                <input type="text" class="form-control" value="<?php echo $product['stock_quantity']; ?>" readonly>
                                <small class="text-muted">Use inventory module to update stock</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Min Stock Level</label>
                                <input type="number" class="form-control" name="min_stock_level" value="<?php echo $product['min_stock_level']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <select class="form-control" name="unit">
                            <option value="pcs" <?php echo $product['unit'] == 'pcs' ? 'selected' : ''; ?>>Pieces</option>
                            <option value="set" <?php echo $product['unit'] == 'set' ? 'selected' : ''; ?>>Set</option>
                            <option value="box" <?php echo $product['unit'] == 'box' ? 'selected' : ''; ?>>Box</option>
                            <option value="roll" <?php echo $product['unit'] == 'roll' ? 'selected' : ''; ?>>Roll</option>
                            <option value="pack" <?php echo $product['unit'] == 'pack' ? 'selected' : ''; ?>>Pack</option>
                            <option value="kg" <?php echo $product['unit'] == 'kg' ? 'selected' : ''; ?>>Kilogram</option>
                            <option value="m" <?php echo $product['unit'] == 'm' ? 'selected' : ''; ?>>Meter</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Product</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>