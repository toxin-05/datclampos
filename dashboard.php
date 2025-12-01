<?php
require_once 'includes/header.php';

// Get dashboard statistics
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_sales_today = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
$revenue_today = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level")->fetchColumn();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <h4><?php echo $total_products; ?></h4>
                        <p>Total Products</p>
                    </div>
                    <div class="ms-auto">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <h4><?php echo $total_sales_today; ?></h4>
                        <p>Sales Today</p>
                    </div>
                    <div class="ms-auto">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <h4>$<?php echo number_format($revenue_today, 2); ?></h4>
                        <p>Revenue Today</p>
                    </div>
                    <div class="ms-auto">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <h4><?php echo $low_stock; ?></h4>
                        <p>Low Stock Items</p>
                    </div>
                    <div class="ms-auto">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Recent Sales</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT s.*, u.full_name FROM sales s JOIN users u ON s.user_id = u.id ORDER BY s.sale_date DESC LIMIT 10");
                            while ($sale = $stmt->fetch()):
                            ?>
                            <tr>
                                <td>#<?php echo $sale['id']; ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?></td>
                                <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td><?php echo ucfirst($sale['payment_method']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo base_url('modules/sales/create.php'); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-cash-register"></i> New Sale
                    </a>
                  
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>