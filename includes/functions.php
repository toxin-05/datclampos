<?php
/**
 * Utility functions for the POS system
 */

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatDate($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}

function getStockStatus($current, $min) {
    if ($current == 0) {
        return ['class' => 'bg-danger', 'text' => 'Out of Stock'];
    } elseif ($current <= $min) {
        return ['class' => 'bg-warning', 'text' => 'Low Stock'];
    } else {
        return ['class' => 'bg-success', 'text' => 'In Stock'];
    }
}

function logActivity($pdo, $user_id, $action, $details = '') {
    // You can create an activity_logs table if needed
    // For now, this is a placeholder function
    return true;
}

/**
 * Generate a random barcode
 */
function generateBarcode() {
    return 'HW' . time() . rand(100, 999);
}

/**
 * Calculate profit margin
 */
function calculateProfitMargin($cost, $price) {
    if ($cost == 0) return 0;
    return (($price - $cost) / $price) * 100;
}
?>