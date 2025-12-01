<?php
require_once __DIR__ . '/../../includes/header.php';

// Only allow admin access
if (!isAdmin()) {
    redirect('../../dashboard.php');
}

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$user_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('index.php');
}

// Prevent editing of primary admin (id=1) by non-primary admins
if ($user['id'] == 1 && $_SESSION['user_id'] != 1) {
    $_SESSION['error'] = "You cannot edit the primary administrator account.";
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    if (empty($username) || empty($full_name)) {
        $errors[] = "Username and full name are required.";
    }

    // Check if username already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = "Username already exists. Please choose a different username.";
    }

    if (!empty($password)) {
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
    }

    if (empty($errors)) {
        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, full_name = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $hashed_password, $full_name, $role, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $full_name, $role, $user_id]);
            }
            
            $_SESSION['success'] = "User updated successfully!";
            redirect('index.php');
        } catch (PDOException $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit User</h1>
    <a href="index.php" class="btn btn-secondary">Back to Users</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Edit User Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role *</label>
                                <select class="form-control" name="role" required>
                                    <option value="cashier" <?php echo $user['role'] == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                                <small class="text-muted">Only enter if you want to change the password</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" placeholder="Leave blank to keep current password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Note:</strong> User will be logged out if password is changed.
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>User Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>User ID:</strong><br>
                    <?php echo $user['id']; ?>
                </div>
                <div class="mb-3">
                    <strong>Account Created:</strong><br>
                    <?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?>
                </div>
                <div class="mb-3">
                    <strong>Current Role:</strong><br>
                    <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                
                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    You are currently editing your own account.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>