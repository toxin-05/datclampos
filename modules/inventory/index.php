<?php
require_once '../../includes/header.php';

// Handle filters
$low_stock = isset($_GET['low_stock']) ? true : false;
$category_id = $_GET['category_id'] ?? '';

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";

$params = [];

if ($low_stock) {
    $sql .= " AND p.stock_quantity <= p.min_stock_level";
}

if (!empty($category_id)) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$sql .= " ORDER BY p.stock_quantity ASC, p.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get inventory statistics
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$low_stock_count = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level")->fetchColumn();
$out_of_stock_count = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0")->fetchColumn();
$total_inventory_value = $pdo->query("SELECT COALESCE(SUM(stock_quantity * cost_price), 0) FROM products")->fetchColumn();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Inventory Management</h1>
    <a href="update.php" class="btn btn-primary">
        <i class="fas fa-edit"></i> Update Stock
    </a>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h4><?php echo $total_products; ?></h4>
                <p>Total Products</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h4><?php echo $low_stock_count; ?></h4>
                <p>Low Stock Items</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h4><?php echo $out_of_stock_count; ?></h4>
                <p>Out of Stock</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h4>$<?php echo number_format($total_inventory_value, 2); ?></h4>
                <p>Inventory Value</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="low_stock" id="low_stock" <?php echo $low_stock ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="low_stock">
                        Show Low Stock Only
                    </label>
                </div>
            </div>
            <div class="col-md-4">
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="index.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Stock Level</th>
                        <th>Stock Status</th>
                        <th>Cost Price</th>
                        <th>Stock Value</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No products found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($products as $product): 
                        $stock_value = $product['stock_quantity'] * $product['cost_price'];
                        $status_class = '';
                        $status_text = '';
                        
                        if ($product['stock_quantity'] == 0) {
                            $status_class = 'bg-danger';
                            $status_text = 'Out of Stock';
                        } elseif ($product['stock_quantity'] <= $product['min_stock_level']) {
                            $status_class = 'bg-warning';
                            $status_text = 'Low Stock';
                        } else {
                            $status_class = 'bg-success';
                            $status_text = 'In Stock';
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="fw-bold"><?php echo $product['stock_quantity']; ?></span>
                            <?php echo $product['unit']; ?>
                        </td>
                        <td><?php echo $product['min_stock_level']; ?></td>
                        <td>
                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td>$<?php echo number_format($product['cost_price'], 2); ?></td>
                        <td>$<?php echo number_format($stock_value, 2); ?></td>
                        <td>
                            <?php 
                            // Get last inventory update
                            $stmt = $pdo->prepare("SELECT created_at FROM inventory_logs WHERE product_id = ? ORDER BY created_at DESC LIMIT 1");
                            $stmt->execute([$product['id']]);
                            $last_update = $stmt->fetchColumn();
                            echo $last_update ? date('M j, Y', strtotime($last_update)) : 'Never';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>