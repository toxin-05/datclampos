// Global functions for the POS system
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Confirm before delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Calculate change
function calculateChange(total, tendered) {
    return parseFloat(tendered) - parseFloat(total);
}

// Auto-calculate totals in forms
function autoCalculate() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const price = parseFloat(document.getElementById('price').value) || 0;
    const total = quantity * price;
    
    if (document.getElementById('total')) {
        document.getElementById('total').value = total.toFixed(2);
    }
}