<?php
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$customer_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    try {
        $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $address, $customer_id]);
        
        $_SESSION['success'] = "Customer updated successfully!";
        redirect('index.php');
    } catch (PDOException $e) {
        $error = "Error updating customer: " . $e->getMessage();
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Customer</h1>
    <a href="index.php" class="btn btn-secondary">Back to Customers</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Customer</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>