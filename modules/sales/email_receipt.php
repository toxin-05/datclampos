<?php
require_once '../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sale_id = intval($_POST['sale_id']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    // Get sale details (similar to view.php)
    $stmt = $pdo->prepare("SELECT s.*, c.name as customer_name, u.full_name as cashier_name 
                          FROM sales s 
                          LEFT JOIN customers c ON s.customer_id = c.id 
                          JOIN users u ON s.user_id = u.id 
                          WHERE s.id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        exit;
    }
    
    // Generate email content
    $subject = "Your Receipt from Datclam Hardware - Sale #$sale_id";
    $message = generateEmailContent($sale, $sale_id);
    
    // Send email (configure your mail server)
    $headers = "From: no-reply@datclamhardware.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    if (mail($email, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Receipt sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
}

function generateEmailContent($sale, $sale_id) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .receipt { border: 1px solid #ddd; padding: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            .total { font-weight: bold; font-size: 18px; }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="header">
                <h2>Datclam Hardware</h2>
                <p>Receipt #<?php echo $sale_id; ?></p>
            </div>
            <p>Thank you for your purchase!</p>
            <p><strong>Total: $<?php echo number_format($sale['total_amount'], 2); ?></strong></p>
            <p>Date: <?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>