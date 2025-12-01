<?php
require_once __DIR__ . '/../../includes/header.php';

// Only allow admin access
if (!isAdmin()) {
    redirect('../../dashboard.php');
}

// Handle user search
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY role, username";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get user statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$cashier_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'cashier'")->fetchColumn();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">User Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>
</div>

<!-- User Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h4><?php echo $total_users; ?></h4>
                <p>Total Users</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h4><?php echo $admin_count; ?></h4>
                <p>Administrators</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h4><?php echo $cashier_count; ?></h4>
                <p>Cashiers</p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="role_filter" class="form-control">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                    <option value="cashier" <?php echo $role_filter == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
            <div class="col-md-2">
                <a href="index.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No users found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                            <span class="badge bg-info">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td>
                            <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php 
                            // This would normally come from a last_login field in the database
                            echo 'Recently';
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-success">Active</span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != $_SESSION['user_id'] && $user['id'] != 1): ?>
                            <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete your own account or primary admin">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>