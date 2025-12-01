<?php
require_once __DIR__ . '/../../includes/header.php';

// Only allow admin access
if (!isAdmin()) {
    redirect('../../dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);

    // Validate inputs
    $errors = [];

    if (empty($username) || empty($password) || empty($full_name)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = "Username already exists. Please choose a different username.";
    }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $role]);
            
            $_SESSION['success'] = "User created successfully!";
            redirect('index.php');
        } catch (PDOException $e) {
            $error = "Error creating user: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New User</h1>
    <a href="index.php" class="btn btn-secondary">Back to Users</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>User Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
                                <small class="text-muted">Unique username for login</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo $_POST['full_name'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role *</label>
                                <select class="form-control" name="role" required>
                                    <option value="cashier" <?php echo ($_POST['role'] ?? '') == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                                    <option value="admin" <?php echo ($_POST['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                                <small class="text-muted">Administrators have full system access</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> The new user will be able to login immediately with the provided credentials.
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Role Permissions</h5>
            </div>
            <div class="card-body">
                <h6>Administrator</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Full system access</li>
                    <li><i class="fas fa-check text-success me-2"></i> User management</li>
                    <li><i class="fas fa-check text-success me-2"></i> All reports</li>
                    <li><i class="fas fa-check text-success me-2"></i> System settings</li>
                </ul>
                
                <h6>Cashier</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Process sales</li>
                    <li><i class="fas fa-check text-success me-2"></i> View products</li>
                    <li><i class="fas fa-check text-success me-2"></i> View inventory</li>
                    <li><i class="fas fa-times text-danger me-2"></i> User management</li>
                    <li><i class="fas fa-times text-danger me-2"></i> System settings</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>