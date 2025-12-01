<?php
require_once __DIR__ . '/../../includes/config.php';

// Only allow admin access
if (!isAdmin()) {
    redirect('../../dashboard.php');
}

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$user_id = $_GET['id'];

// Prevent deletion of current user or primary admin
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account.";
    redirect('index.php');
}

if ($user_id == 1) {
    $_SESSION['error'] = "Cannot delete the primary administrator account.";
    redirect('index.php');
}

try {
    // Check if user has any sales (optional - you might want to keep sales records)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $sales_count = $stmt->fetchColumn();
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    if ($sales_count > 0) {
        $_SESSION['success'] = "User deleted successfully! Their sales records have been preserved.";
    } else {
        $_SESSION['success'] = "User deleted successfully!";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
}

redirect('index.php');
?>