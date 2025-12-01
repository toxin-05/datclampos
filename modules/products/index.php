<?php
require_once __DIR__ . '/../../includes/header.php';

// Handle product search
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.barcode = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

if (!empty($category_id)) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$sql .= " ORDER BY p.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Products</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?php echo base_url('modules/products/add.php'); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Product
        </a>
    </div>
</div>

<!-- Search and Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
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
                <button type="submit" class="btn btn-primary w-100">Search</button>
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
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Cost</th>
                        <th>Stock</th>
                        <th>Barcode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No products found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            <?php if ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                            <span class="badge bg-warning">Low Stock</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td>$<?php echo number_format($product['cost_price'], 2); ?></td>
                        <td>
                            <span class="<?php echo $product['stock_quantity'] <= $product['min_stock_level'] ? 'text-danger fw-bold' : ''; ?>">
                                <?php echo $product['stock_quantity']; ?>
                            </span>
                            <?php echo $product['unit']; ?>
                        </td>
                        <td><?php echo $product['barcode'] ?: 'N/A'; ?></td>
                        <td>
                            <a href="<?php echo base_url('modules/products/edit.php?id=' . $product['id']); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo base_url('modules/products/delete.php?id=' . $product['id']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this product?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>