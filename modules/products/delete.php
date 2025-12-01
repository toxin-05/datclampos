<?php
require_once '../../includes/config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$product_id = $_GET['id'];

try {
    // Check if product has sales history
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $sales_count = $stmt->fetchColumn();
    
    if ($sales_count > 0) {
        $_SESSION['error'] = "Cannot delete product with sales history. You can deactivate it instead.";
    } else {
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $_SESSION['success'] = "Product deleted successfully!";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
}

header("Location: index.php");
exit;