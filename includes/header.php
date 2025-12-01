<?php 
// Start session and check auth
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo $_SESSION['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="<?php echo base_url('css/style.css'); ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo base_url('dashboard.php'); ?>">
                <i class="fas fa-tools"></i> Hardware POS
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-1"></i> <?php echo $_SESSION['full_name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo base_url('includes/logout.php'); ?>">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="settingsModalLabel">
                        <i class="fas fa-cog me-2"></i>System Settings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="fas fa-palette me-2"></i>Theme Settings</h6>
                        <div class="theme-selector">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="theme-option <?php echo ($_SESSION['theme'] ?? 'light') === 'light' ? 'active' : ''; ?>" data-theme="light">
                                        <div class="theme-preview light-theme">
                                            <div class="preview-header"></div>
                                            <div class="preview-sidebar"></div>
                                            <div class="preview-content"></div>
                                        </div>
                                        <span class="theme-label">Light Mode</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="theme-option <?php echo ($_SESSION['theme'] ?? 'light') === 'dark' ? 'active' : ''; ?>" data-theme="dark">
                                        <div class="theme-preview dark-theme">
                                            <div class="preview-header"></div>
                                            <div class="preview-sidebar"></div>
                                            <div class="preview-content"></div>
                                        </div>
                                        <span class="theme-label">Dark Mode</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="fas fa-bell me-2"></i>Notification Settings</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="lowStockNotifications" checked>
                            <label class="form-check-label" for="lowStockNotifications">
                                Low stock notifications
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="salesNotifications" checked>
                            <label class="form-check-label" for="salesNotifications">
                                Sales completion notifications
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="mb-3"><i class="fas fa-receipt me-2"></i>Receipt Settings</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoPrintReceipts">
                            <label class="form-check-label" for="autoPrintReceipts">
                                Auto-print receipts after sale
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveSettings">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo base_url('dashboard.php'); ?>">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo base_url('modules/sales/create.php'); ?>">
                                <i class="fas fa-cash-register"></i> New Sale
                            </a>
                        </li>
                       
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo base_url('modules/reports/index.php'); ?>">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo base_url('modules/customers/index.php'); ?>">
                                <i class="fas fa-users"></i> Customers
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo base_url('modules/inventory/index.php'); ?>">
                                <i class="fas fa-warehouse"></i> Inventory
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo base_url('modules/products/index.php'); ?>">
                                <i class="fas fa-boxes"></i> Products
                            </a>
                        </li>

                        <li class="nav-item">
                           <a class="nav-link" href="<?php echo base_url('modules/users/index.php'); ?>">
                                <i class="fas fa-users-cog"></i> User Management
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

<style>
/* Theme Selector Styles */
.theme-option {
    cursor: pointer;
    padding: 15px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    text-align: center;
    transition: all 0.3s ease;
}

.theme-option.active {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.theme-option:hover {
    border-color: #0d6efd;
}

.theme-preview {
    width: 100%;
    height: 80px;
    border-radius: 6px;
    margin-bottom: 8px;
    position: relative;
    overflow: hidden;
}

.light-theme .preview-header {
    height: 12px;
    background: #0d6efd;
}

.light-theme .preview-sidebar {
    position: absolute;
    left: 0;
    top: 12px;
    width: 30%;
    height: 68px;
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
}

.light-theme .preview-content {
    position: absolute;
    right: 0;
    top: 12px;
    width: 70%;
    height: 68px;
    background: #ffffff;
}

.dark-theme .preview-header {
    height: 12px;
    background: #0d6efd;
}

.dark-theme .preview-sidebar {
    position: absolute;
    left: 0;
    top: 12px;
    width: 30%;
    height: 68px;
    background: #2d3748;
    border-right: 1px solid #4a5568;
}

.dark-theme .preview-content {
    position: absolute;
    right: 0;
    top: 12px;
    width: 70%;
    height: 68px;
    background: #1a202c;
}

.theme-label {
    font-weight: 500;
    color: #495057;
}

/* Dark mode styles */
[data-bs-theme="dark"] {
    --bs-body-bg: #1a202c;
    --bs-body-color: #e2e8f0;
    --bs-light: #2d3748;
    --bs-border-color: #4a5568;
}

[data-bs-theme="dark"] .sidebar {
    background-color: #2d3748 !important;
    border-right-color: #4a5568 !important;
}

[data-bs-theme="dark"] .nav-link {
    color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .nav-link:hover,
[data-bs-theme="dark"] .nav-link.active {
    background-color: #4a5568 !important;
    color: #ffffff !important;
}

[data-bs-theme="dark"] .card {
    background-color: #2d3748;
    border-color: #4a5568;
}

[data-bs-theme="dark"] .table {
    --bs-table-bg: #2d3748;
    --bs-table-color: #e2e8f0;
    --bs-table-border-color: #4a5568;
}

[data-bs-theme="dark"] .form-control {
    background-color: #2d3748;
    border-color: #4a5568;
    color: #e2e8f0;
}

[data-bs-theme="dark"] .form-control:focus {
    background-color: #2d3748;
    border-color: #0d6efd;
    color: #e2e8f0;
}

[data-bs-theme="dark"] .text-muted {
    color: #a0aec0 !important;
}

[data-bs-theme="dark"] .modal-content {
    background-color: #2d3748;
    color: #e2e8f0;
}

[data-bs-theme="dark"] .dropdown-menu {
    background-color: #2d3748;
    border-color: #4a5568;
}

[data-bs-theme="dark"] .dropdown-item {
    color: #e2e8f0;
}

[data-bs-theme="dark"] .dropdown-item:hover {
    background-color: #4a5568;
    color: #ffffff;
}
</style>

<script>
// Theme Management
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme selector
    initializeThemeSelector();
    
    // Initialize settings modal
    initializeSettingsModal();
});

function initializeThemeSelector() {
    const themeOptions = document.querySelectorAll('.theme-option');
    
    themeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const selectedTheme = this.dataset.theme;
            
            // Remove active class from all options
            themeOptions.forEach(opt => opt.classList.remove('active'));
            
            // Add active class to selected option
            this.classList.add('active');
            
            // Update theme
            updateTheme(selectedTheme);
        });
    });
}

function updateTheme(theme) {
    // Update HTML attribute
    document.documentElement.setAttribute('data-bs-theme', theme);
    
    // Store theme preference
    localStorage.setItem('preferredTheme', theme);
    
    // Update session via AJAX
    updateThemePreference(theme);
}

function updateThemePreference(theme) {
    fetch('<?php echo base_url("includes/update_theme.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'theme=' + theme
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to update theme preference');
        }
    })
    .catch(error => {
        console.error('Error updating theme:', error);
    });
}

function initializeSettingsModal() {
    const saveSettingsBtn = document.getElementById('saveSettings');
    
    saveSettingsBtn.addEventListener('click', function() {
        // Get selected theme
        const activeTheme = document.querySelector('.theme-option.active').dataset.theme;
        
        // Update theme
        updateTheme(activeTheme);
        
        // Save other settings to localStorage
        const settings = {
            lowStockNotifications: document.getElementById('lowStockNotifications').checked,
            salesNotifications: document.getElementById('salesNotifications').checked,
            autoPrintReceipts: document.getElementById('autoPrintReceipts').checked
        };
        
        localStorage.setItem('posSettings', JSON.stringify(settings));
        
        // Show success message
        showNotification('Settings saved successfully!', 'success');
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
    });
    
    // Load saved settings
    loadSettings();
}

function loadSettings() {
    const savedSettings = localStorage.getItem('posSettings');
    if (savedSettings) {
        const settings = JSON.parse(savedSettings);
        
        document.getElementById('lowStockNotifications').checked = settings.lowStockNotifications;
        document.getElementById('salesNotifications').checked = settings.salesNotifications;
        document.getElementById('autoPrintReceipts').checked = settings.autoPrintReceipts;
    }
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.settings-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `settings-notification alert alert-${type} position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close float-end" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

// Apply saved theme on page load
const savedTheme = localStorage.getItem('preferredTheme') || '<?php echo $_SESSION["theme"] ?? "light"; ?>';
document.documentElement.setAttribute('data-bs-theme', savedTheme);

// Update theme selector to match current theme
document.addEventListener('DOMContentLoaded', function() {
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    const themeOptions = document.querySelectorAll('.theme-option');
    
    themeOptions.forEach(option => {
        option.classList.remove('active');
        if (option.dataset.theme === currentTheme) {
            option.classList.add('active');
        }
    });
});
</script>