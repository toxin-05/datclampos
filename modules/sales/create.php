<?php
require_once '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process sale
    $customer_id = $_POST['customer_id'] ?: 1; // Default to walk-in customer
    $payment_method = $_POST['payment_method'];
    $amount_tendered = floatval($_POST['amount_tendered']);
    
    // Validate cart items
    if (empty($_POST['cart_items']) || $_POST['cart_items'] == '[]') {
        $error = "Cart is empty! Please add products to complete the sale.";
    } else {
        // Calculate totals from cart items
        $cart_items = json_decode($_POST['cart_items'], true);
        
        // Validate JSON decoding
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = "Invalid cart data. Please try again.";
        } else {
            $total_amount = 0;
            
            foreach ($cart_items as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }
            
            $change_amount = $amount_tendered - $total_amount;
            
            if ($total_amount <= 0) {
                $error = "Total amount must be greater than zero!";
            } elseif ($change_amount >= 0) {
                // Start transaction
                $pdo->beginTransaction();
                
                try {
                    // Insert sale
                    $stmt = $pdo->prepare("INSERT INTO sales (customer_id, total_amount, amount_tendered, change_amount, payment_method, user_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$customer_id, $total_amount, $amount_tendered, $change_amount, $payment_method, $_SESSION['user_id']]);
                    $sale_id = $pdo->lastInsertId();
                    
                    // Insert sale items and update inventory
                    foreach ($cart_items as $item) {
                        // Check if product still has sufficient stock
                        $check_stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                        $check_stmt->execute([$item['id']]);
                        $product = $check_stmt->fetch();
                        
                        if (!$product) {
                            throw new Exception("Product not found!");
                        }
                        
                        if ($product['stock_quantity'] < $item['quantity']) {
                            throw new Exception("Insufficient stock for product: " . $item['name']);
                        }
                        
                        // Insert sale item
                        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $item['price'] * $item['quantity']]);
                        
                        // Update product stock
                        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                        $stmt->execute([$item['quantity'], $item['id']]);
                        
                        // Get current stock after update for logging
                        $current_stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                        $current_stmt->execute([$item['id']]);
                        $current_stock = $current_stmt->fetchColumn();
                        
                        // Log inventory change
                        $stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, quantity_change, new_quantity, reason, reference_id, user_id) VALUES (?, ?, ?, 'sale', ?, ?)");
                        $stmt->execute([$item['id'], -$item['quantity'], $current_stock, $sale_id, $_SESSION['user_id']]);
                    }
                    
                    $pdo->commit();
                    
                    // SUCCESS: Set success message and redirect to view page
                    $_SESSION['sale_success'] = "Sale #$sale_id completed successfully!";
                    $_SESSION['last_sale_id'] = $sale_id;
                    
                    // Redirect to view page
                    header("Location: view.php?id=" . $sale_id);
                    exit;
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Sale failed: " . $e->getMessage();
                    error_log("Sale creation error: " . $e->getMessage());
                }
            } else {
                $error = "Insufficient amount tendered! Amount tendered: $" . number_format($amount_tendered, 2) . ", Total: $" . number_format($total_amount, 2) . ", Short: $" . number_format(abs($change_amount), 2);
            }
        }
    }
}

// Get products and customers
$products = $pdo->query("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY name")->fetchAll();
$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">New Sale</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-primary me-2" onclick="toggleScanner()">
            <i class="fas fa-barcode me-1"></i> 
            <span id="scannerToggleText">Enable Scanner</span>
        </button>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['sale_success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $_SESSION['sale_success']; unset($_SESSION['sale_success']); ?>
    </div>
<?php endif; ?>

<!-- Barcode Scanner Interface -->
<div id="scannerInterface" class="card mb-4" style="display: none;">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-barcode me-2"></i>Barcode Scanner
            <small class="float-end" id="scannerStatus">Ready</small>
        </h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="barcodeInput" class="form-control form-control-lg" 
                           placeholder="Scan barcode or enter manually..." autocomplete="off">
                    <button class="btn btn-success" type="button" onclick="manualBarcodeSearch()">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
                <div class="form-text">
                    <i class="fas fa-info-circle me-1"></i>
                    Point barcode scanner at this field or type barcode and press Enter
                </div>
            </div>
            <div class="col-md-4">
                <div class="scanner-stats text-center">
                    <div class="scanner-stat">
                        <span class="stat-value" id="scannedCount">0</span>
                        <span class="stat-label">Items Scanned</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scanner Feedback -->
        <div id="scannerFeedback" class="mt-3" style="display: none;">
            <div class="alert alert-info">
                <i class="fas fa-spinner fa-spin me-2"></i>
                <span id="scannerMessage">Processing barcode...</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-boxes me-2"></i>Available Products
                </h5>
                <span class="badge bg-primary" id="productCount"><?php echo count($products); ?> products</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="searchProduct" class="form-control" placeholder="Search products...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select id="categoryFilter" class="form-control">
                            <option value="">All Categories</option>
                            <?php
                            $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
                            foreach ($categories as $category):
                            ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row" id="productGrid">
                    <?php if (empty($products)): ?>
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h5>No products available</h5>
                            <p class="text-muted">All products are out of stock or no products have been added.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-4 mb-3 product-item" data-category="<?php echo $product['category_id']; ?>" data-barcode="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                            <div class="card product-card h-100" data-product='<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8'); ?>'>
                                <div class="card-body text-center d-flex flex-column">
                                    <h6 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <p class="product-price text-success fw-bold mt-auto">$<?php echo number_format($product['price'], 2); ?></p>
                                    <?php if ($product['barcode']): ?>
                                    <small class="text-muted mb-1">
                                        <i class="fas fa-barcode me-1"></i>
                                        <?php echo htmlspecialchars($product['barcode']); ?>
                                    </small>
                                    <?php endif; ?>
                                    <small class="product-stock text-info">
                                        <i class="fas fa-cubes me-1"></i>
                                        Stock: <?php echo $product['stock_quantity']; ?>
                                    </small>
                                    <?php if ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                                        <small class="text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Low Stock
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h5>
            </div>
            <div class="card-body">
                <form id="saleForm" method="POST" action="create.php">
                    <div id="cartItems" class="mb-3">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-cart-plus fa-2x mb-2"></i>
                            <p>Your cart is empty<br>Click on products or scan barcodes to add them</p>
                        </div>
                    </div>
                    
                    <div id="cartSummary" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user me-2"></i>Customer</label>
                            <select name="customer_id" class="form-control">
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-credit-card me-2"></i>Payment Method</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile">Mobile Payment</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-money-bill-wave me-2"></i>Amount Tendered</label>
                            <input type="number" step="0.01" min="0" name="amount_tendered" class="form-control" required placeholder="0.00">
                            <small class="text-muted">Enter the amount received from customer</small>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="completeSaleBtn">
                                <i class="fas fa-check-circle me-2"></i>Complete Sale
                            </button>
                        </div>
                    </div>
                    
                    <input type="hidden" name="cart_items" id="cartItemsInput" value="[]">
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let scannerEnabled = false;
let scannedItemsCount = 0;
let barcodeBuffer = '';
let barcodeTimeout;

// Barcode Scanner Functions
function toggleScanner() {
    scannerEnabled = !scannerEnabled;
    const scannerInterface = document.getElementById('scannerInterface');
    const toggleText = document.getElementById('scannerToggleText');
    const barcodeInput = document.getElementById('barcodeInput');
    
    if (scannerEnabled) {
        scannerInterface.style.display = 'block';
        toggleText.textContent = 'Disable Scanner';
        barcodeInput.focus();
        showNotification('Barcode scanner enabled. Start scanning!', 'success');
        updateScannerStatus('Active - Ready to scan');
    } else {
        scannerInterface.style.display = 'none';
        toggleText.textContent = 'Enable Scanner';
        showNotification('Barcode scanner disabled', 'info');
        updateScannerStatus('Disabled');
    }
}

function updateScannerStatus(status) {
    document.getElementById('scannerStatus').textContent = status;
}

function updateScannedCount() {
    document.getElementById('scannedCount').textContent = scannedItemsCount;
}

function showScannerFeedback(message, type = 'info') {
    const feedback = document.getElementById('scannerFeedback');
    const messageEl = document.getElementById('scannerMessage');
    
    messageEl.textContent = message;
    feedback.className = `alert alert-${type} mt-3`;
    feedback.style.display = 'block';
    
    setTimeout(() => {
        feedback.style.display = 'none';
    }, 3000);
}

function processBarcode(barcode) {
    if (!barcode || barcode.trim() === '') return;
    
    showScannerFeedback(`Searching for product with barcode: ${barcode}`, 'info');
    
    // Search for product by barcode
    const productElement = document.querySelector(`.product-item[data-barcode="${barcode}"]`);
    
    if (productElement) {
        const productCard = productElement.querySelector('.product-card');
        const product = JSON.parse(productCard.dataset.product);
        
        // Highlight the found product
        productCard.classList.add('scanned-product');
        setTimeout(() => {
            productCard.classList.remove('scanned-product');
        }, 2000);
        
        // Add to cart
        addToCart(product);
        scannedItemsCount++;
        updateScannedCount();
        showScannerFeedback(`Added "${product.name}" to cart`, 'success');
        
        // Scroll to product
        productElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        showScannerFeedback(`No product found with barcode: ${barcode}`, 'error');
        
        // Try manual search as fallback
        manualBarcodeSearch(barcode);
    }
    
    // Clear input
    document.getElementById('barcodeInput').value = '';
}

function manualBarcodeSearch(barcode = null) {
    const searchTerm = barcode || document.getElementById('barcodeInput').value;
    
    if (!searchTerm) {
        showNotification('Please enter a barcode to search', 'error');
        return;
    }
    
    // Search in product names and barcodes
    const searchTermLower = searchTerm.toLowerCase();
    let found = false;
    
    document.querySelectorAll('.product-item').forEach(item => {
        const productName = item.querySelector('.product-name').textContent.toLowerCase();
        const productBarcode = item.dataset.barcode ? item.dataset.barcode.toLowerCase() : '';
        
        if (productName.includes(searchTermLower) || productBarcode.includes(searchTermLower)) {
            const productCard = item.querySelector('.product-card');
            const product = JSON.parse(productCard.dataset.product);
            
            // Highlight and add to cart
            productCard.classList.add('scanned-product');
            setTimeout(() => {
                productCard.classList.remove('scanned-product');
            }, 2000);
            
            addToCart(product);
            scannedItemsCount++;
            updateScannedCount();
            showScannerFeedback(`Added "${product.name}" to cart`, 'success');
            
            item.scrollIntoView({ behavior: 'smooth', block: 'center' });
            found = true;
        }
    });
    
    if (!found) {
        showScannerFeedback(`No product found matching: ${searchTerm}`, 'error');
    }
    
    document.getElementById('barcodeInput').value = '';
}

// Barcode scanner input handling
document.getElementById('barcodeInput').addEventListener('input', function(e) {
    // Clear previous timeout
    if (barcodeTimeout) {
        clearTimeout(barcodeTimeout);
    }
    
    // Set timeout to detect end of barcode input
    barcodeTimeout = setTimeout(() => {
        if (this.value.length > 3) { // Minimum barcode length
            processBarcode(this.value);
        }
    }, 100); // Short timeout for barcode scanners
});

document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        manualBarcodeSearch();
    }
});

// Keyboard shortcut to focus barcode input
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + B to focus barcode input
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        if (!scannerEnabled) {
            toggleScanner();
        }
        document.getElementById('barcodeInput').focus();
    }
    
    // F2 to toggle scanner
    if (e.key === 'F2') {
        e.preventDefault();
        toggleScanner();
    }
});

// Existing cart functions
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function() {
        const product = JSON.parse(this.dataset.product);
        addToCart(product);
    });
});

function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        if (existingItem.quantity < product.stock_quantity) {
            existingItem.quantity++;
            showNotification(`Increased quantity for ${product.name}`, 'success');
        } else {
            showNotification(`Not enough stock for ${product.name}! Available: ${product.stock_quantity}`, 'error');
            return;
        }
    } else {
        if (product.stock_quantity > 0) {
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                quantity: 1,
                stock: parseInt(product.stock_quantity),
                barcode: product.barcode || ''
            });
            showNotification(`Added ${product.name} to cart`, 'success');
        } else {
            showNotification(`${product.name} is out of stock!`, 'error');
            return;
        }
    }
    
    updateCartDisplay();
}

function updateCartDisplay() {
    const cartItems = document.getElementById('cartItems');
    const cartInput = document.getElementById('cartItemsInput');
    const cartSummary = document.getElementById('cartSummary');
    
    cartItems.innerHTML = '';
    let total = 0;
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-cart-plus fa-2x mb-2"></i>
                <p>Your cart is empty<br>Click on products or scan barcodes to add them</p>
            </div>
        `;
        cartSummary.style.display = 'none';
    } else {
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            cartItems.innerHTML += `
                <div class="cart-item d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${item.name}</h6>
                        <small class="text-muted">$${item.price.toFixed(2)} × ${item.quantity}</small>
                        ${item.barcode ? `<br><small class="text-muted"><i class="fas fa-barcode me-1"></i>${item.barcode}</small>` : ''}
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-success">$${itemTotal.toFixed(2)}</div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="decreaseQuantity(${index})">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="removeFromCart(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="increaseQuantity(${index})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartItems.innerHTML += `
            <div class="cart-total d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                <h5 class="mb-0">Total:</h5>
                <h5 class="mb-0 text-success">$${total.toFixed(2)}</h5>
            </div>
        `;
        
        cartSummary.style.display = 'block';
        
        // Auto-fill amount tendered with total
        const amountInput = document.querySelector('input[name="amount_tendered"]');
        if (!amountInput.value || parseFloat(amountInput.value) < total) {
            amountInput.value = total.toFixed(2);
        }
    }
    
    cartInput.value = JSON.stringify(cart);
}

function increaseQuantity(index) {
    if (cart[index].quantity < cart[index].stock) {
        cart[index].quantity++;
        updateCartDisplay();
        showNotification(`Increased quantity for ${cart[index].name}`, 'success');
    } else {
        showNotification(`Cannot add more ${cart[index].name}. Maximum stock available: ${cart[index].stock}`, 'error');
    }
}

function decreaseQuantity(index) {
    if (cart[index].quantity > 1) {
        cart[index].quantity--;
        updateCartDisplay();
        showNotification(`Decreased quantity for ${cart[index].name}`, 'info');
    } else {
        removeFromCart(index);
    }
}

function removeFromCart(index) {
    const itemName = cart[index].name;
    cart.splice(index, 1);
    updateCartDisplay();
    showNotification(`Removed ${itemName} from cart`, 'warning');
}

function showNotification(message, type) {
    // Remove any existing notifications
    const existingAlert = document.querySelector('.notification-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type === 'error' ? 'danger' : type} notification-alert position-fixed`;
    alert.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : 'info'}-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 3000);
}

// Search and filter functionality
document.getElementById('searchProduct').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    filterProducts();
});

document.getElementById('categoryFilter').addEventListener('change', filterProducts);

function filterProducts() {
    const searchTerm = document.getElementById('searchProduct').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    
    let visibleCount = 0;
    
    document.querySelectorAll('.product-item').forEach(item => {
        const productName = item.querySelector('.product-name').textContent.toLowerCase();
        const productCategory = item.dataset.category;
        
        const matchesSearch = productName.includes(searchTerm);
        const matchesCategory = !categoryFilter || productCategory === categoryFilter;
        
        if (matchesSearch && matchesCategory) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    document.getElementById('productCount').textContent = `${visibleCount} products`;
}

// Form validation and submission
document.getElementById('saleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (cart.length === 0) {
        showNotification('Please add at least one product to complete the sale', 'error');
        return;
    }
    
    const amountTendered = parseFloat(document.querySelector('input[name="amount_tendered"]').value);
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    if (isNaN(amountTendered) || amountTendered <= 0) {
        showNotification('Please enter a valid amount tendered', 'error');
        return;
    }
    
    if (amountTendered < total) {
        showNotification(`Insufficient amount tendered. Total: $${total.toFixed(2)}, Tendered: $${amountTendered.toFixed(2)}`, 'error');
        return;
    }
    
    // Show loading state
    const submitBtn = document.getElementById('completeSaleBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing Sale...';
    submitBtn.disabled = true;
    
    // Submit the form
    this.submit();
});

// Add CSS for scanner and animations
const style = document.createElement('style');
style.textContent = `
    .notification-alert {
        animation: slideInRight 0.3s ease;
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
    
    .product-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .product-card:hover {
        transform: translateY(-2px);
        border-color: #007bff;
        box-shadow: 0 4px 12px rgba(0,123,255,0.15);
    }
    
    .scanned-product {
        animation: scanHighlight 2s ease;
        border-color: #28a745 !important;
        box-shadow: 0 0 20px rgba(40, 167, 69, 0.3) !important;
    }
    
    @keyframes scanHighlight {
        0% {
            border-color: #28a745;
            box-shadow: 0 0 20px rgba(40, 167, 69, 0.5);
        }
        50% {
            border-color: #28a745;
            box-shadow: 0 0 30px rgba(40, 167, 69, 0.8);
        }
        100% {
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }
    }
    
    .scanner-stats {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
    }
    
    .scanner-stat {
        text-align: center;
    }
    
    .stat-value {
        display: block;
        font-size: 2rem;
        font-weight: bold;
        color: #007bff;
    }
    
    .stat-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    #barcodeInput {
        font-family: 'Courier New', monospace;
        font-size: 1.1rem;
    }
`;
document.head.appendChild(style);
</script>

<?php require_once '../../includes/footer.php'; ?>