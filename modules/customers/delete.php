<?php
require_once '../../includes/config.php';

if (!isset($_GET['id']) || $_GET['id'] == 1) {
    header("Location: index.php");
    exit;
}

$customer_id = $_GET['id'];

try {
    // Check if customer has sales history
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $sales_count = $stmt->fetchColumn();
    
    if ($sales_count > 0) {
        $_SESSION['error'] = "Cannot delete customer with sales history. You can update their information instead.";
    } else {
        // Delete customer
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $_SESSION['success'] = "Customer deleted successfully!";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting customer: " . $e->getMessage();
}

header("Location: index.php");
exit;