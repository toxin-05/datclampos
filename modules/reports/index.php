<?php
require_once '../../includes/header.php';

// Default date range (current month)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'sales_summary';

// Get report data based on type
if ($report_type == 'sales_summary') {
    $stmt = $pdo->prepare("SELECT 
                          COUNT(*) as total_sales,
                          COALESCE(SUM(total_amount), 0) as total_revenue,
                          COALESCE(AVG(total_amount), 0) as avg_sale,
                          COALESCE(SUM(amount_tendered - total_amount), 0) as total_change
                          FROM sales 
                          WHERE sale_date BETWEEN ? AND ? + INTERVAL 1 DAY");
    $stmt->execute([$start_date, $end_date]);
    $summary = $stmt->fetch();
    
    // Sales by payment method
    $stmt = $pdo->prepare("SELECT payment_method, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
                          FROM sales 
                          WHERE sale_date BETWEEN ? AND ? + INTERVAL 1 DAY 
                          GROUP BY payment_method");
    $stmt->execute([$start_date, $end_date]);
    $payment_methods = $stmt->fetchAll();
    
    // Daily sales trend
    $stmt = $pdo->prepare("SELECT DATE(sale_date) as sale_day, COUNT(*) as sales_count, COALESCE(SUM(total_amount), 0) as daily_total 
                          FROM sales 
                          WHERE sale_date BETWEEN ? AND ? + INTERVAL 1 DAY 
                          GROUP BY DATE(sale_date) 
                          ORDER BY sale_day");
    $stmt->execute([$start_date, $end_date]);
    $daily_sales = $stmt->fetchAll();
    
} elseif ($report_type == 'product_sales') {
    $stmt = $pdo->prepare("SELECT p.name, p.category_id, c.name as category_name,
                          SUM(si.quantity) as total_sold,
                          SUM(si.total_price) as total_revenue,
                          AVG(si.unit_price) as avg_price
                          FROM sale_items si
                          JOIN products p ON si.product_id = p.id
                          LEFT JOIN categories c ON p.category_id = c.id
                          JOIN sales s ON si.sale_id = s.id
                          WHERE s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY
                          GROUP BY p.id, p.name, p.category_id, c.name
                          ORDER BY total_sold DESC");
    $stmt->execute([$start_date, $end_date]);
    $product_sales = $stmt->fetchAll();
    
} elseif ($report_type == 'inventory') {
    $stmt = $pdo->prepare("SELECT p.name, c.name as category_name, p.stock_quantity, p.min_stock_level,
                          p.cost_price, (p.stock_quantity * p.cost_price) as stock_value,
                          CASE 
                              WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                              WHEN p.stock_quantity <= p.min_stock_level THEN 'Low Stock'
                              ELSE 'In Stock'
                          END as status
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          ORDER BY p.stock_quantity ASC, p.name");
    $stmt->execute();
    $inventory_report = $stmt->fetchAll();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Reports & Analytics</h1>
</div>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select name="report_type" class="form-control" onchange="this.form.submit()">
                    <option value="sales_summary" <?php echo $report_type == 'sales_summary' ? 'selected' : ''; ?>>Sales Summary</option>
                    <option value="product_sales" <?php echo $report_type == 'product_sales' ? 'selected' : ''; ?>>Product Sales</option>
                    <option value="inventory" <?php echo $report_type == 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($report_type == 'sales_summary'): ?>
<!-- Sales Summary Report -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h4><?php echo $summary['total_sales']; ?></h4>
                <p>Total Sales</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h4>$<?php echo number_format($summary['total_revenue'], 2); ?></h4>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h4>$<?php echo number_format($summary['avg_sale'], 2); ?></h4>
                <p>Average Sale</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h4>$<?php echo number_format($summary['total_change'], 2); ?></h4>
                <p>Total Change Given</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Sales by Payment Method</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th>Number of Sales</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_methods as $method): ?>
                            <tr>
                                <td><?php echo ucfirst($method['payment_method']); ?></td>
                                <td><?php echo $method['count']; ?></td>
                                <td>$<?php echo number_format($method['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Daily Sales Trend</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Number of Sales</th>
                                <th>Daily Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_sales as $day): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($day['sale_day'])); ?></td>
                                <td><?php echo $day['sales_count']; ?></td>
                                <td>$<?php echo number_format($day['daily_total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($report_type == 'product_sales'): ?>
<!-- Product Sales Report -->
<div class="card">
    <div class="card-header">
        <h5>Product Sales Report</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Units Sold</th>
                        <th>Total Revenue</th>
                        <th>Average Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($product_sales)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No sales data found for the selected period</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($product_sales as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo $product['total_sold']; ?></td>
                        <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                        <td>$<?php echo number_format($product['avg_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($report_type == 'inventory'): ?>
<!-- Inventory Report -->
<div class="card">
    <div class="card-header">
        <h5>Inventory Status Report</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Stock Level</th>
                        <th>Status</th>
                        <th>Cost Price</th>
                        <th>Stock Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_report as $item): 
                        $status_class = '';
                        if ($item['status'] == 'Out of Stock') {
                            $status_class = 'bg-danger';
                        } elseif ($item['status'] == 'Low Stock') {
                            $status_class = 'bg-warning';
                        } else {
                            $status_class = 'bg-success';
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo $item['stock_quantity']; ?></td>
                        <td><?php echo $item['min_stock_level']; ?></td>
                        <td><span class="badge <?php echo $status_class; ?>"><?php echo $item['status']; ?></span></td>
                        <td>$<?php echo number_format($item['cost_price'], 2); ?></td>
                        <td>$<?php echo number_format($item['stock_value'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>