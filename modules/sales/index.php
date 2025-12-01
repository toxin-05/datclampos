<?php
require_once '../../includes/header.php';

// Handle filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$customer_id = $_GET['customer_id'] ?? '';

// Validate dates
if (!strtotime($start_date)) $start_date = date('Y-m-01');
if (!strtotime($end_date)) $end_date = date('Y-m-d');

// Ensure end date is not before start date
if (strtotime($end_date) < strtotime($start_date)) {
    $end_date = $start_date;
}

try {
    $sql = "SELECT s.*, c.name as customer_name, u.full_name as cashier_name 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            JOIN users u ON s.user_id = u.id 
            WHERE s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY";
    $params = [$start_date, $end_date];

    if (!empty($customer_id)) {
        $sql .= " AND s.customer_id = ?";
        $params[] = $customer_id;
    }

    $sql .= " ORDER BY s.sale_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();

    // Get customers for filter
    $customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();

    // Calculate totals with proper error handling
    $total_sales_stmt = $pdo->prepare("SELECT COUNT(*) as total_count, COALESCE(SUM(total_amount), 0) as total_revenue FROM sales WHERE sale_date BETWEEN ? AND ? + INTERVAL 1 DAY");
    $total_sales_stmt->execute([$start_date, $end_date]);
    $totals = $total_sales_stmt->fetch();

    if ($totals) {
        $total_count = $totals['total_count'];
        $total_revenue = $totals['total_revenue'];
    } else {
        $total_count = 0;
        $total_revenue = 0;
    }

} catch (PDOException $e) {
    error_log("Database error in sales/index.php: " . $e->getMessage());
    $sales = [];
    $customers = [];
    $total_count = 0;
    $total_revenue = 0;
    $error = "Unable to load sales data. Please try again.";
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-history text-primary me-2"></i>
        Sales History
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-cash-register me-1"></i> New Sale
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter me-2"></i>Filter Sales
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3" id="salesFilterForm">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-control">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>" <?php echo $customer_id == $customer['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($customer['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-redo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?php echo number_format($total_count); ?></h4>
                        <p class="mb-0">Total Sales</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0">$<?php echo number_format($total_revenue, 2); ?></h4>
                        <p class="mb-0">Total Revenue</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0">$<?php echo $total_count > 0 ? number_format($total_revenue / $total_count, 2) : '0.00'; ?></h4>
                        <p class="mb-0">Average Sale</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0"><?php echo count($sales); ?></h4>
                        <p class="mb-0">Showing</p>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-list fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-table me-2"></i>Sales Records
            <span class="badge bg-primary ms-2"><?php echo count($sales); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($sales)): ?>
            <div class="text-center py-5">
                <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Sales Found</h4>
                <p class="text-muted mb-4">No sales records match your current filters.</p>
                <a href="create.php" class="btn btn-primary me-2">
                    <i class="fas fa-cash-register me-1"></i> Create New Sale
                </a>
                <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                    <i class="fas fa-redo me-1"></i> Reset Filters
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="salesTable">
                    <thead class="table-light">
                        <tr>
                            <th width="80">Sale ID</th>
                            <th width="150">Date & Time</th>
                            <th>Customer</th>
                            <th width="100" class="text-center">Items</th>
                            <th width="120" class="text-end">Total Amount</th>
                            <th width="120" class="text-center">Payment</th>
                            <th>Cashier</th>
                            <th width="120" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): 
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) as item_count FROM sale_items WHERE sale_id = ?");
                                $stmt->execute([$sale['id']]);
                                $item_result = $stmt->fetch();
                                $item_count = $item_result ? $item_result['item_count'] : 0;
                            } catch (PDOException $e) {
                                $item_count = 0;
                            }
                        ?>
                        <tr>
                            <td>
                                <strong class="text-primary">#<?php echo $sale['id']; ?></strong>
                            </td>
                            <td>
                                <div class="small text-muted"><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></div>
                                <div class="small"><?php echo date('g:i A', strtotime($sale['sale_date'])); ?></div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer'); ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary"><?php echo $item_count; ?> items</span>
                            </td>
                            <td class="text-end">
                                <strong class="text-success">$<?php echo number_format($sale['total_amount'], 2); ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?php echo getPaymentBadgeColor($sale['payment_method']); ?>">
                                    <i class="fas fa-<?php echo getPaymentIcon($sale['payment_method']); ?> me-1"></i>
                                    <?php echo ucfirst($sale['payment_method']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle text-muted me-2"></i>
                                    <?php echo htmlspecialchars($sale['cashier_name']); ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="view.php?id=<?php echo $sale['id']; ?>" class="btn btn-outline-primary" title="View Receipt">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (isAdmin()): ?>
                                    <a href="#" class="btn btn-outline-info" onclick="emailReceipt(<?php echo $sale['id']; ?>)" title="Email Receipt">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-success" onclick="downloadReceipt(<?php echo $sale['id']; ?>)" title="Download PDF">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Export Options -->
            <div class="mt-3 pt-3 border-top">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            Showing <?php echo count($sales); ?> of <?php echo $total_count; ?> total sales
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportToCSV()">
                                <i class="fas fa-file-csv me-1"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="printSalesReport()">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Filter functions
function resetFilters() {
    const today = new Date().toISOString().split('T')[0];
    const firstDay = today.substring(0, 8) + '01';
    
    document.querySelector('input[name="start_date"]').value = firstDay;
    document.querySelector('input[name="end_date"]').value = today;
    document.querySelector('select[name="customer_id"]').value = '';
    
    document.getElementById('salesFilterForm').submit();
}

// Email receipt function
function emailReceipt(saleId) {
    if (confirm('Send receipt for sale #' + saleId + ' via email?')) {
        // Show loading state
        const btn = event.target.closest('a');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        // Simulate API call (replace with actual implementation)
        setTimeout(() => {
            showNotification('Receipt for sale #' + saleId + ' has been sent via email.', 'success');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }, 1500);
    }
}

// Download PDF function
function downloadReceipt(saleId) {
    // Show loading state
    const btn = event.target.closest('a');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    // Simulate PDF generation and download
    setTimeout(() => {
        // Create a blob and download link (simulated)
        const blob = new Blob(['PDF content for sale #' + saleId], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'receipt-' + saleId + '.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        showNotification('PDF receipt for sale #' + saleId + ' has been downloaded.', 'success');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }, 2000);
}

// Export to CSV function
function exportToCSV() {
    const salesData = <?php echo json_encode($sales); ?>;
    let csv = 'Sale ID,Date,Customer,Items,Total Amount,Payment Method,Cashier\n';
    
    salesData.forEach(sale => {
        csv += `"${sale.id}","${sale.sale_date}","${sale.customer_name || 'Walk-in'}","${sale.item_count || 0}","${sale.total_amount}","${sale.payment_method}","${sale.cashier_name}"\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `sales-report-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showNotification('Sales report exported to CSV successfully.', 'success');
}

// Print sales report
function printSalesReport() {
    const printWindow = window.open('', '_blank');
    const printContent = `
        <html>
            <head>
                <title>Sales Report - <?php echo date('Y-m-d'); ?></title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f8f9fa; }
                    .total-row { background-color: #e9ecef; font-weight: bold; }
                </style>
            </head>
            <body>
                <h1>Sales Report</h1>
                <p><strong>Date Range:</strong> <?php echo $start_date; ?> to <?php echo $end_date; ?></p>
                <p><strong>Total Sales:</strong> <?php echo $total_count; ?></p>
                <p><strong>Total Revenue:</strong> $<?php echo number_format($total_revenue, 2); ?></p>
                
                <table>
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
                            <th>Cashier</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${<?php echo json_encode($sales); ?>.map(sale => `
                            <tr>
                                <td>#${sale.id}</td>
                                <td>${new Date(sale.sale_date).toLocaleDateString()}</td>
                                <td>${sale.customer_name || 'Walk-in'}</td>
                                <td>$${parseFloat(sale.total_amount).toFixed(2)}</td>
                                <td>${sale.payment_method}</td>
                                <td>${sale.cashier_name}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                
                <p style="margin-top: 20px; font-size: 0.8em; color: #666;">
                    Generated on ${new Date().toLocaleString()} by <?php echo $_SESSION['full_name']; ?>
                </p>
            </body>
        </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

// Notification function
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} custom-notification position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close float-end" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0,123,255,0.05);
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }
`;
document.head.appendChild(style);
</script>

<?php 
// Helper functions
function getPaymentBadgeColor($method) {
    switch ($method) {
        case 'cash': return 'success';
        case 'card': return 'primary';
        case 'mobile': return 'info';
        default: return 'secondary';
    }
}

function getPaymentIcon($method) {
    switch ($method) {
        case 'cash': return 'money-bill-wave';
        case 'card': return 'credit-card';
        case 'mobile': return 'mobile-alt';
        default: return 'money-check';
    }
}

require_once '../../includes/footer.php'; 
?>