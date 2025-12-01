<?php
require_once '../../includes/header.php';

// Handle search
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM customers WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get customer statistics
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$customers_with_sales = $pdo->query("SELECT COUNT(DISTINCT customer_id) FROM sales WHERE customer_id > 1")->fetchColumn();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Customers</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Customer
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h4><?php echo $total_customers; ?></h4>
                <p>Total Customers</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h4><?php echo $customers_with_sales; ?></h4>
                <p>Customers with Sales</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h4><?php echo $total_customers - 1; ?></h4>
                <p>Active Customers</p>
            </div>
        </div>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Search customers by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
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
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Total Sales</th>
                        <th>Total Spent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No customers found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($customers as $customer): 
                        // Get customer sales statistics
                        $stmt = $pdo->prepare("SELECT COUNT(*) as sales_count, COALESCE(SUM(total_amount), 0) as total_spent FROM sales WHERE customer_id = ?");
                        $stmt->execute([$customer['id']]);
                        $sales_stats = $stmt->fetch();
                    ?>
                    <tr>
                        <td><?php echo $customer['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                            <?php if ($customer['id'] == 1): ?>
                            <span class="badge bg-secondary">Walk-in</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($customer['email'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></td>
                        <td>
                            <?php if ($customer['address']): ?>
                            <span title="<?php echo htmlspecialchars($customer['address']); ?>">
                                <?php echo strlen($customer['address']) > 30 ? substr($customer['address'], 0, 30) . '...' : $customer['address']; ?>
                            </span>
                            <?php else: ?>
                            N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo $sales_stats['sales_count']; ?></td>
                        <td>$<?php echo number_format($sales_stats['total_spent'], 2); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($customer['id'] != 1): ?>
                            <a href="delete.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this customer?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
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