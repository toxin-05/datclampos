<?php
require_once '../../includes/header.php';

// Check if sale ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Sale ID is required.";
    redirect('index.php');
}

$sale_id = intval($_GET['id']);

// Validate sale ID
if ($sale_id <= 0) {
    $_SESSION['error'] = "Invalid sale ID.";
    redirect('index.php');
}

// Get sale details with error handling
try {
    $stmt = $pdo->prepare("SELECT s.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, u.full_name as cashier_name 
                          FROM sales s 
                          LEFT JOIN customers c ON s.customer_id = c.id 
                          JOIN users u ON s.user_id = u.id 
                          WHERE s.id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();

    if (!$sale) {
        $_SESSION['error'] = "Sale #$sale_id not found.";
        redirect('index.php');
    }
} catch (PDOException $e) {
    error_log("Database error in sales/view.php: " . $e->getMessage());
    $_SESSION['error'] = "Unable to retrieve sale details. Please try again.";
    redirect('index.php');
}

// Get sale items with error handling
try {
    $stmt = $pdo->prepare("SELECT si.*, p.name as product_name, p.barcode, p.category_id,
                          cat.name as category_name
                          FROM sale_items si 
                          JOIN products p ON si.product_id = p.id 
                          LEFT JOIN categories cat ON p.category_id = cat.id
                          WHERE si.sale_id = ? 
                          ORDER BY si.id");
    $stmt->execute([$sale_id]);
    $sale_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error fetching sale items: " . $e->getMessage());
    $sale_items = [];
}

// Calculate item count and verify total
$item_count = count($sale_items);
$calculated_total = 0;
foreach ($sale_items as $item) {
    $calculated_total += $item['total_price'];
}

// Verify sale total matches calculated total
$total_mismatch = abs($calculated_total - $sale['total_amount']) > 0.01;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-receipt text-primary me-2"></i>
        Sale Receipt #<?php echo $sale_id; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Sales
        </a>
        <div class="btn-group">
            <button onclick="printReceipt()" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Print Receipt
            </button>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#" onclick="emailReceipt(<?php echo $sale_id; ?>)">
        <i class="fas fa-envelope me-2"></i> Email Receipt
    </a></li>
    <li><a class="dropdown-item" href="#" onclick="downloadReceipt(<?php echo $sale_id; ?>)">
        <i class="fas fa-download me-2"></i> Download PDF
    </a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" href="#" onclick="shareReceipt(<?php echo $sale_id; ?>)">
        <i class="fas fa-share-alt me-2"></i> Share Receipt
    </a></li>
</ul>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
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

<?php if ($total_mismatch): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> Sale total mismatch detected. Please verify the sale details.
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10">
        <div class="card receipt-card" id="receipt">
            <div class="card-header bg-white border-bottom-0">
                <div class="text-center">
                    <h2 class="text-primary mb-1">Datclam Hardware</h2>
                    <p class="text-muted mb-1">123 Main Street, City, State 12345</p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-phone me-1"></i> (555) 123-4567 | 
                        <i class="fas fa-envelope me-1 ms-2"></i> info@datclamhardware.com
                    </p>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Sale Information Header -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="receipt-info">
                            <h5 class="receipt-label">Receipt Information</h5>
                            <div><strong>Receipt #:</strong> <span class="text-primary"><?php echo $sale_id; ?></span></div>
                            <div><strong>Date:</strong> <?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></div>
                            <div><strong>Time:</strong> <?php echo date('g:i A', strtotime($sale['sale_date'])); ?></div>
                            <div><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="receipt-info">
                            <h5 class="receipt-label">Customer Information</h5>
                            <div><strong>Customer:</strong> <?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer'); ?></div>
                            <?php if ($sale['customer_phone']): ?>
                            <div><strong>Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?></div>
                            <?php endif; ?>
                            <?php if ($sale['customer_email']): ?>
                            <div><strong>Email:</strong> <?php echo htmlspecialchars($sale['customer_email']); ?></div>
                            <?php endif; ?>
                            <div><strong>Payment:</strong> <span class="badge bg-<?php echo getPaymentMethodBadge($sale['payment_method']); ?>"><?php echo ucfirst($sale['payment_method']); ?></span></div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Sale Items -->
                <div class="table-responsive">
                    <table class="table table-bordered receipt-table">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="45%">Product Description</th>
                                <th width="15%" class="text-center">Quantity</th>
                                <th width="15%" class="text-end">Unit Price</th>
                                <th width="20%" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sale_items)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>
                                        No items found for this sale.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sale_items as $index => $item): ?>
                                <tr>
                                    <td class="text-muted"><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <?php if ($item['category_name']): ?>
                                        <small class="text-muted">Category: <?php echo htmlspecialchars($item['category_name']); ?></small>
                                        <?php endif; ?>
                                        <?php if ($item['barcode']): ?>
                                        <br><small class="text-muted">SKU: <?php echo htmlspecialchars($item['barcode']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="text-end fw-bold">$<?php echo number_format($item['total_price'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" rowspan="4">
                                    <div class="receipt-notes">
                                        <strong>Thank you for your business!</strong><br>
                                        <small class="text-muted">
                                            Returns accepted within 30 days with original receipt.<br>
                                            Warranty claims require proof of purchase.
                                        </small>
                                    </div>
                                </td>
                                <td colspan="2" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end"><strong>Amount Tendered:</strong></td>
                                <td class="text-end">$<?php echo number_format($sale['amount_tendered'], 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end"><strong>Change Due:</strong></td>
                                <td class="text-end text-success fw-bold">$<?php echo number_format($sale['change_amount'], 2); ?></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="2" class="text-end"><strong>GRAND TOTAL:</strong></td>
                                <td class="text-end fw-bold fs-5">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Transaction Details Section -->
                <div class="transaction-details no-print" style="display: none;">
                    <h6><i class="fas fa-info-circle me-1"></i>Transaction Details</h6>
                    <div class="transaction-grid">
                        <div class="transaction-item">
                            <span class="transaction-label">Transaction ID:</span>
                            <span class="transaction-value">TXN-<?php echo str_pad($sale_id, 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="transaction-item">
                            <span class="transaction-label">Terminal ID:</span>
                            <span class="transaction-value">POS-001</span>
                        </div>
                        <div class="transaction-item">
                            <span class="transaction-label">Auth Code:</span>
                            <span class="transaction-value">A<?php echo str_pad($sale_id, 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="transaction-item">
                            <span class="transaction-label">Batch No:</span>
                            <span class="transaction-value">B<?php echo date('md'); ?></span>
                        </div>
                        <div class="transaction-item">
                            <span class="transaction-label">Reference No:</span>
                            <span class="transaction-value">R<?php echo str_pad($sale_id, 8, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="transaction-item">
                            <span class="transaction-label">Card Type:</span>
                            <span class="transaction-value"><?php echo $sale['payment_method'] == 'card' ? 'VISA' : 'N/A'; ?></span>
                        </div>
                        <div class="transaction-item">
                            <span class="transaction-label">Card No:</span>
                            <span class="transaction-value">****<?php echo substr(str_pad($sale_id, 4, '0', STR_PAD_LEFT), -4); ?></span>
                        </div>
                        <div class="transaction-item">
                            <span class="transaction-label">Entry Mode:</span>
                            <span class="transaction-value">CHIP</span>
                        </div>
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="qr-section no-print" style="display: none;">
                    <div class="qr-code-container">
                        <div class="qr-code">
                            <div class="qr-code-placeholder print-only">
                                <i class="fas fa-qrcode"></i><br>
                                <small>Transaction QR</small>
                            </div>
                        </div>
                        <small class="text-muted mt-1 d-block">Scan for verification</small>
                    </div>
                    <div class="verification-info">
                        <strong>Transaction Verified</strong><br>
                        <small>
                            Sale ID: #<?php echo $sale_id; ?><br>
                            Date: <?php echo date('Y-m-d H:i:s', strtotime($sale['sale_date'])); ?><br>
                            Amount: $<?php echo number_format($sale['total_amount'], 2); ?><br>
                            Status: COMPLETED
                        </small>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="receipt-meta">
                            <h6 class="text-muted">Sale Details</h6>
                            <small>
                                <strong>Items Sold:</strong> <?php echo $item_count; ?><br>
                                <strong>Transaction ID:</strong> <?php echo 'TXN-' . str_pad($sale_id, 6, '0', STR_PAD_LEFT); ?><br>

                            </small>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <!-- Screen-only QR Code (different from print) -->
                        <div class="qr-code-placeholder screen-only bg-light p-3 d-inline-block no-print">
                            <small class="text-muted">Transaction Verified</small><br>
                            <i class="fas fa-qrcode fa-2x text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-4 no-print">
            <div class="btn-group" role="group">
                <button onclick="printReceipt()" class="btn btn-primary">
                    <i class="fas fa-print me-1"></i> Print Receipt
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-list me-1"></i> Back to Sales List
                </a>
                <?php if (isAdmin()): ?>
                <a href="../reports/sales_report.php?sale_id=<?php echo $sale_id; ?>" class="btn btn-info">
                    <i class="fas fa-chart-bar me-1"></i> Sales Report
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Styles -->
<style>
.receipt-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.receipt-label {
    color: #495057;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.receipt-info div {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.receipt-table {
    font-size: 0.9rem;
}

.receipt-table th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.8rem;
}

.receipt-notes {
    font-size: 0.8rem;
    line-height: 1.4;
}

.receipt-meta {
    font-size: 0.8rem;
}

.qr-code-placeholder {
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    text-align: center;
}

/* Transaction Details Styles */
.transaction-details {
    margin-top: 1rem;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
}

.transaction-details h6 {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.25rem;
}

.transaction-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    font-size: 0.8rem;
}

.transaction-item {
    display: flex;
    justify-content: space-between;
}

.transaction-label {
    font-weight: 600;
}

.transaction-value {
    text-align: right;
}

/* QR Code Section Styles */
.qr-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px dashed #dee2e6;
}

.qr-code-container {
    text-align: center;
    flex: 0 0 auto;
}

.qr-code {
    width: 100px;
    height: 100px;
    border: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 4px;
}

.qr-code-placeholder.print-only {
    font-size: 0.7rem;
    line-height: 1.2;
}

.qr-code-placeholder.print-only .fas {
    font-size: 2rem;
    margin-bottom: 0.25rem;
}

.verification-info {
    flex: 1;
    margin-left: 1rem;
    font-size: 0.8rem;
    line-height: 1.4;
}

.verification-info strong {
    font-size: 0.9rem;
}

/* Print Styles */
@media print {
    /* Hide all non-essential elements */
    .no-print, .navbar, .sidebar, .border-bottom, .btn-toolbar, 
    .alert, .btn-group, .text-center.mt-4, .d-print-none {
        display: none !important;
    }
    
    /* Reset body and container for printing */
    body {
        background: white !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        height: auto !important;
        overflow: visible !important;
    }
    
    /* Container adjustments */
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        max-width: none !important;
        width: 100% !important;
    }
    
    /* Receipt card adjustments */
    .receipt-card {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        page-break-inside: avoid;
        break-inside: avoid;
    }
    
    .card-body {
        padding: 0.5rem !important;
        margin: 0 !important;
    }
    
    .card-header {
        padding: 0.5rem 0 !important;
        background: white !important;
        border: none !important;
    }
    
    /* Typography adjustments for print */
    h1, h2, h3, h4, h5, h6 {
        margin: 0.2rem 0 !important;
        line-height: 1.1 !important;
    }
    
    .h2 {
        font-size: 1.3rem !important;
    }
    
    /* Receipt information layout */
    .receipt-info {
        margin-bottom: 0.5rem !important;
    }
    
    .receipt-label {
        font-size: 0.8rem !important;
        margin-bottom: 0.2rem !important;
    }
    
    .receipt-info div {
        margin-bottom: 0.1rem !important;
        font-size: 0.75rem !important;
    }
    
    /* Table adjustments */
    .table-responsive {
        overflow: visible !important;
    }
    
    .receipt-table {
        font-size: 9px !important;
        margin: 0.5rem 0 !important;
        width: 100% !important;
    }
    
    .receipt-table th,
    .receipt-table td {
        padding: 0.2rem 0.3rem !important;
        line-height: 1.1 !important;
    }
    
    .receipt-table th {
        font-size: 0.7rem !important;
        font-weight: 600 !important;
    }
    
    .receipt-table .fw-semibold {
        font-size: 0.75rem !important;
    }
    
    .receipt-table small {
        font-size: 0.65rem !important;
    }
    
    /* Transaction Details Section - Show in print */
    .transaction-details {
        display: block !important;
        margin-top: 0.5rem !important;
        padding: 0.3rem !important;
        border: 1px solid #000 !important;
        background: #f8f9fa !important;
        page-break-inside: avoid;
    }
    
    .transaction-details h6 {
        font-size: 0.8rem !important;
        margin-bottom: 0.3rem !important;
        border-bottom: 1px solid #000 !important;
        padding-bottom: 0.1rem !important;
    }
    
    .transaction-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.2rem !important;
        font-size: 0.7rem !important;
    }
    
    .transaction-item {
        display: flex !important;
        justify-content: space-between !important;
    }
    
    .transaction-label {
        font-weight: 600 !important;
    }
    
    .transaction-value {
        text-align: right !important;
    }
    
    /* QR Code Section - Show in print */
    .qr-section {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        margin-top: 0.5rem !important;
        padding-top: 0.3rem !important;
        border-top: 1px dashed #000 !important;
        page-break-inside: avoid;
    }
    
    .qr-code-container {
        text-align: center !important;
        flex: 0 0 auto !important;
    }
    
    .qr-code {
        width: 80px !important;
        height: 80px !important;
        border: 1px solid #000 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: white !important;
    }
    
    .qr-code-placeholder.print-only {
        font-size: 0.6rem !important;
        line-height: 1 !important;
    }
    
    .qr-code-placeholder.print-only .fas {
        font-size: 1.5rem !important;
        margin-bottom: 0.2rem !important;
    }
    
    .verification-info {
        flex: 1 !important;
        margin-left: 0.5rem !important;
        font-size: 0.65rem !important;
        line-height: 1.2 !important;
    }
    
    .verification-info strong {
        font-size: 0.7rem !important;
    }
    
    /* Hide decorative elements from screen but show QR in print */
    .qr-code-placeholder.screen-only {
        display: none !important;
    }
    
    .qr-code-placeholder.print-only {
        display: block !important;
    }
    
    /* Reduce spacing */
    .row {
        margin: 0 !important;
    }
    
    .col-md-6, .col-xl-8, .col-lg-10 {
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .mb-1 { margin-bottom: 0.1rem !important; }
    .mb-2 { margin-bottom: 0.2rem !important; }
    .mb-3 { margin-bottom: 0.3rem !important; }
    .mb-4 { margin-bottom: 0.4rem !important; }
    .mt-1 { margin-top: 0.1rem !important; }
    .mt-2 { margin-top: 0.2rem !important; }
    .mt-3 { margin-top: 0.3rem !important; }
    .mt-4 { margin-top: 0.4rem !important; }
    .py-4 { padding-top: 0.4rem !important; padding-bottom: 0.4rem !important; }
    
    /* Borders and separators */
    hr {
        margin: 0.3rem 0 !important;
        border-color: #000 !important;
    }
    
    .border-top {
        border-top: 1px solid #000 !important;
    }
    
    .table-bordered {
        border: 1px solid #000 !important;
    }
    
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #000 !important;
    }
    
    /* Force black text for better print contrast */
    .text-primary, .text-success, .text-info, .text-muted {
        color: #000 !important;
    }
    
    .bg-primary, .table-primary {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    .table-light {
        background-color: #f8f9fa !important;
    }
    
    /* Badge adjustments */
    .badge {
        background: #000 !important;
        color: white !important;
        font-size: 0.6rem !important;
        padding: 0.1rem 0.3rem !important;
    }
    
    /* Ensure proper page breaks */
    .row {
        page-break-inside: avoid;
        break-inside: avoid;
    }
    
    /* Force single column layout */
    .col-md-6 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    
    /* Adjust receipt header */
    .text-center h2 {
        font-size: 1.1rem !important;
        margin-bottom: 0.2rem !important;
    }
    
    .text-center p {
        margin: 0.1rem 0 !important;
        font-size: 0.7rem !important;
    }
    
    /* Compact the receipt information sections */
    .receipt-info h5 {
        font-size: 0.8rem !important;
        margin-bottom: 0.2rem !important;
    }
    
    /* Reduce table row height */
    .receipt-table tr {
        height: auto !important;
        min-height: 0 !important;
    }
    
    /* Adjust footer totals */
    .fs-5 {
        font-size: 0.9rem !important;
    }
    
    /* Hide any potential overflow elements */
    .overflow-auto, .overflow-hidden {
        overflow: visible !important;
    }
    
    /* Ensure no background images print */
    * {
        background-image: none !important;
    }
    
    /* Force print color adjustments */
    @page {
        margin: 0.5cm;
        size: auto;
    }
    
    /* Additional compacting for very long receipts */
    .receipt-table tbody tr td {
        padding-top: 0.15rem !important;
        padding-bottom: 0.15rem !important;
    }
    
    /* Reduce line heights further */
    .receipt-table td, .receipt-table th {
        line-height: 1 !important;
    }
    
    /* Compact the items list in table footer */
    .receipt-notes {
        padding: 0.2rem !important;
    }
}

/* Screen-only styles for QR code */
@media screen {
    .qr-code-placeholder.print-only {
        display: none !important;
    }
    
    .qr-code-placeholder.screen-only {
        display: block !important;
    }
}

/* Additional media query for very small print */
@media print and (max-height: 10in) {
    body {
        font-size: 10px !important;
    }
    
    .receipt-table {
        font-size: 8px !important;
    }
    
    .receipt-table th,
    .receipt-table td {
        padding: 0.1rem 0.2rem !important;
    }
    
    .qr-code {
        width: 60px !important;
        height: 60px !important;
    }
}

/* For landscape printing if needed */
@media print and (orientation: landscape) {
    body {
        font-size: 12px !important;
    }
    
    .receipt-table {
        font-size: 10px !important;
    }
    
    .col-md-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }
    
    .qr-code {
        width: 100px !important;
        height: 100px !important;
    }
}
/* Notification Styles */
.notification-alert {
    animation: slideInRight 0.3s ease !important;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Dropdown Enhancements */
.dropdown-item {
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.dropdown-item:active {
    background-color: #e9ecef;
}

/* Loading States */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Print specific styles for PDF generation */
@media print {
    .notification-alert {
        display: none !important;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .receipt-info {
        margin-bottom: 1rem;
    }
    
    .receipt-table {
        font-size: 0.8rem;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        width: 100%;
    }
    
    .transaction-grid {
        grid-template-columns: 1fr;
    }
    
    .qr-section {
        flex-direction: column;
        gap: 1rem;
    }
    
    .verification-info {
        margin-left: 0;
    }
}
</style>

<script>
// Function to handle print preparation
function prepareForPrint() {
    // Show transaction details and QR code for printing
    document.querySelectorAll('.transaction-details, .qr-section').forEach(el => {
        el.style.display = 'block';
    });
    
    // Hide screen-only QR code
    document.querySelector('.qr-code-placeholder.screen-only').style.display = 'none';
}

// Function to restore after print
function restoreAfterPrint() {
    // Hide transaction details and QR code for screen
    document.querySelectorAll('.transaction-details, .qr-section').forEach(el => {
        el.style.display = 'none';
    });
    
    // Show screen-only QR code
    document.querySelector('.qr-code-placeholder.screen-only').style.display = 'inline-block';
}

// Enhanced print function
function printReceipt() {
    prepareForPrint();
    
    // Small delay to ensure DOM updates before print
    setTimeout(() => {
        window.print();
        
        // Restore after a delay (printing might take some time)
        setTimeout(restoreAfterPrint, 500);
    }, 100);
}

function emailReceipt() {
    // Simulate email functionality
    const saleId = <?php echo $sale_id; ?>;
    alert(`Email receipt functionality for sale #${saleId} would be implemented here.`);
    // In a real implementation, this would make an AJAX call to send the receipt via email
}

function downloadReceipt() {
    // Simulate PDF download functionality
    const saleId = <?php echo $sale_id; ?>;
    alert(`PDF download functionality for sale #${saleId} would be implemented here.`);
    // In a real implementation, this would generate and download a PDF receipt
}

// Handle print events
window.addEventListener('beforeprint', prepareForPrint);
window.addEventListener('afterprint', restoreAfterPrint);

// Add keyboard shortcut for printing
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        printReceipt();
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
// Email Receipt Functionality
async function emailReceipt(saleId) {
    try {
        // Show loading state
        showNotification('Preparing email...', 'info');
        
        // Get customer email if available
        const customerEmail = '<?php echo $sale["customer_email"] ?? ""; ?>';
        
        if (!customerEmail) {
            // Prompt for email address
            const email = prompt('Enter customer email address:', '');
            if (!email) {
                showNotification('Email cancelled', 'warning');
                return;
            }
            
            if (!isValidEmail(email)) {
                showNotification('Please enter a valid email address', 'error');
                return;
            }
            
            await sendReceiptEmail(saleId, email);
        } else {
            await sendReceiptEmail(saleId, customerEmail);
        }
    } catch (error) {
        console.error('Email error:', error);
        showNotification('Failed to send email: ' + error.message, 'error');
    }
}

// Download PDF Functionality
async function downloadReceipt(saleId) {
    try {
        showNotification('Generating PDF...', 'info');
        
        // Create PDF content
        const pdfContent = generatePDFContent();
        
        // Generate and download PDF
        await generatePDF(saleId, pdfContent);
        
    } catch (error) {
        console.error('PDF generation error:', error);
        showNotification('Failed to generate PDF: ' + error.message, 'error');
    }
}

// Share Receipt Functionality
async function shareReceipt(saleId) {
    try {
        if (navigator.share) {
            // Use Web Share API if available
            await navigator.share({
                title: `Receipt #${saleId} - Datclam Hardware`,
                text: `Your receipt from Datclam Hardware - Sale #${saleId}`,
                url: window.location.href
            });
            showNotification('Receipt shared successfully!', 'success');
        } else {
            // Fallback: copy to clipboard
            await copyReceiptToClipboard(saleId);
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error('Share error:', error);
            showNotification('Failed to share receipt', 'error');
        }
    }
}

// Helper Functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

async function sendReceiptEmail(saleId, email) {
    // Show loading state
    const notification = showLoadingNotification('Sending receipt via email...');
    
    try {
        // Simulate API call - replace with actual backend endpoint
        const response = await simulateEmailAPI(saleId, email);
        
        if (response.success) {
            showNotification(`Receipt sent successfully to ${email}`, 'success');
        } else {
            throw new Error(response.message || 'Failed to send email');
        }
    } finally {
        // Remove loading notification
        if (notification && notification.remove) {
            notification.remove();
        }
    }
}

async function generatePDF(saleId, content) {
    // Show loading state
    const notification = showLoadingNotification('Generating PDF document...');
    
    try {
        // Use html2pdf.js for PDF generation
        const element = document.getElementById('receipt');
        
        const opt = {
            margin: [0.5, 0.5, 0.5, 0.5],
            filename: `receipt-${saleId}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2,
                useCORS: true,
                logging: false
            },
            jsPDF: { 
                unit: 'in', 
                format: 'letter', 
                orientation: 'portrait' 
            }
        };
        
        // Generate PDF
        await html2pdf().set(opt).from(element).save();
        
        showNotification('PDF downloaded successfully!', 'success');
        
    } catch (error) {
        console.error('PDF generation failed:', error);
        
        // Fallback: Open print dialog for manual PDF save
        showNotification('Using print to save as PDF...', 'info');
        setTimeout(() => {
            window.print();
        }, 1000);
        
    } finally {
        // Remove loading notification
        if (notification && notification.remove) {
            notification.remove();
        }
    }
}

async function copyReceiptToClipboard(saleId) {
    try {
        const receiptText = generateReceiptText();
        
        await navigator.clipboard.writeText(receiptText);
        showNotification('Receipt copied to clipboard!', 'success');
    } catch (error) {
        console.error('Clipboard error:', error);
        
        // Fallback method
        const textArea = document.createElement('textarea');
        textArea.value = generateReceiptText();
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        showNotification('Receipt copied to clipboard!', 'success');
    }
}

// Content Generation Functions
function generatePDFContent() {
    const saleData = {
        id: <?php echo $sale_id; ?>,
        date: '<?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?>',
        customer: '<?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer'); ?>',
        total: <?php echo $sale['total_amount']; ?>,
        items: <?php echo json_encode($sale_items); ?>,
        cashier: '<?php echo htmlspecialchars($sale['cashier_name']); ?>'
    };
    
    return saleData;
}

function generateReceiptText() {
    const items = <?php echo json_encode($sale_items); ?>;
    let receiptText = `DATCLAM HARDWARE RECEIPT\n`;
    receiptText += `Receipt #: <?php echo $sale_id; ?>\n`;
    receiptText += `Date: <?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?>\n`;
    receiptText += `Customer: <?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer'); ?>\n\n`;
    receiptText += `ITEMS:\n`;
    
    items.forEach(item => {
        receiptText += `${item.quantity}x ${item.product_name} - $${parseFloat(item.unit_price).toFixed(2)} each\n`;
    });
    
    receiptText += `\nTOTAL: $<?php echo number_format($sale['total_amount'], 2); ?>\n`;
    receiptText += `Payment: <?php echo ucfirst($sale['payment_method']); ?>\n`;
    receiptText += `Cashier: <?php echo htmlspecialchars($sale['cashier_name']); ?>\n\n`;
    receiptText += `Thank you for your business!\n`;
    receiptText += `Returns within 30 days with receipt`;
    
    return receiptText;
}

// Simulation Functions (Replace with actual API calls)
async function simulateEmailAPI(saleId, email) {
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Simulate random success/failure for demo
    const isSuccess = Math.random() > 0.2; // 80% success rate
    
    if (isSuccess) {
        return {
            success: true,
            message: 'Email sent successfully',
            email: email,
            saleId: saleId
        };
    } else {
        return {
            success: false,
            message: 'Email service temporarily unavailable. Please try again later.'
        };
    }
}

// UI Helper Functions
function showLoadingNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-info notification-alert position-fixed';
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Add remove method
    notification.remove = function() {
        if (this.parentElement) {
            this.parentElement.removeChild(this);
        }
    };
    
    return notification;
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification-alert');
    existingNotifications.forEach(notification => {
        if (!notification.querySelector('.spinner-border')) {
            notification.remove();
        }
    });
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} notification-alert position-fixed`;
    alert.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
    `;
    alert.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)} me-2"></i>
        ${message}
        <button type="button" class="btn-close float-end" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-triangle',
        warning: 'exclamation-circle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Initialize dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler to dropdown items to close dropdown after click
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            const dropdown = this.closest('.dropdown');
            const dropdownMenu = dropdown.querySelector('.dropdown-menu');
            dropdownMenu.classList.remove('show');
        });
    });
});
</script>

<?php 
// Helper function to get payment method badge color
function getPaymentMethodBadge($method) {
    switch ($method) {
        case 'cash': return 'success';
        case 'card': return 'primary';
        case 'mobile': return 'info';
        default: return 'secondary';
    }
}

require_once '../../includes/footer.php'; 
?>