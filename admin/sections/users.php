<?php
session_start();
$currentAdminEmail = $_SESSION['user_email'] ?? '';
$isSuperAdmin = ($currentAdminEmail === 'admin@gmail.com');

// Get filters and pagination parameters
$search = $_GET['search'] ?? '';
$userType = $_GET['user_type'] ?? '';
$active = $_GET['active'] ?? ''; // '1', '0', or ''
$sort = $_GET['sort'] ?? 'user_id';
$order = $_GET['order'] ?? 'ASC';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($userType) {
    $where[] = "user_type = :user_type";
    $params[':user_type'] = $userType;
}

if ($active !== '' && ($active === '1' || $active === '0')) {
    $where[] = "is_active = :is_active";
    $params[':is_active'] = $active;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Validate and sanitize sort column
$allowedSort = ['user_id', 'first_name', 'last_name', 'email', 'user_type', 'is_active'];
$sort = in_array($sort, $allowedSort) ? $sort : 'user_id';
$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get users for current page
$sql = "SELECT * FROM users $whereClause ORDER BY $sort $order LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $user_id = $_POST['user_id'];
    $active = $_POST['active'];
    
    $stmt = $pdo->prepare('UPDATE users SET active = ? WHERE user_id = ?');
    $stmt->execute([$active, $user_id]);
    header('Location: ?section=users&search=' . urlencode($search) . '&user_type=' . urlencode($userType) . '&page=' . $page);
    exit;
}

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $userType = $_POST['user_type'] ?? '';
    if (!$isSuperAdmin && $userType === 'admin') {
        echo '<div class="alert alert-danger">You do not have permission to add admin accounts.</div>';
    } else {
        // Basic validation (add more as needed)
        $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, email, password, phone, user_type, security_word, created_at, is_active, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->execute([
            $_POST['first_name'] ?? '', $_POST['middle_name'] ?? '', $_POST['last_name'] ?? '', $_POST['email'] ?? '',
            $_POST['password'] ?? '', $_POST['phone'] ?? '', $userType, $_POST['security_word'] ?? '', 1, 'default.jpg'
        ]);
        echo '<div class="alert alert-success" id="user-success-msg">User added successfully.</div>';
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $editUserId = intval($_POST['user_id']);
    $target = $pdo->prepare("SELECT user_type, email FROM users WHERE user_id = ?");
    $target->execute([$editUserId]);
    $targetRow = $target->fetch(PDO::FETCH_ASSOC);
    $targetType = $targetRow['user_type'] ?? null;
    $targetEmail = $targetRow['email'] ?? null;
    if ($targetEmail === 'admin@gmail.com') {
        echo '<div class="alert alert-danger">You cannot edit the Super Admin account.</div>';
    } elseif (!$isSuperAdmin && $targetType === 'admin') {
        echo '<div class="alert alert-danger">You do not have permission to edit admin accounts.</div>';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, phone=?, user_type=?, is_active=? WHERE user_id=?");
        $stmt->execute([
            $_POST['first_name'] ?? '', $_POST['middle_name'] ?? '', $_POST['last_name'] ?? '', $_POST['email'] ?? '',
            $_POST['phone'] ?? '', $_POST['user_type'] ?? '', $_POST['is_active'] ?? 1, $editUserId
        ]);
        echo '<div class="alert alert-success" id="user-success-msg">User updated successfully.</div>';
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $deleteUserId = intval($_POST['user_id']);
    $target = $pdo->prepare("SELECT user_type, email FROM users WHERE user_id = ?");
    $target->execute([$deleteUserId]);
    $targetRow = $target->fetch(PDO::FETCH_ASSOC);
    $targetType = $targetRow['user_type'] ?? null;
    $targetEmail = $targetRow['email'] ?? null;
    if ($targetEmail === 'admin@gmail.com') {
        echo '<div class="alert alert-danger">You cannot delete the Super Admin account.</div>';
    } elseif (!$isSuperAdmin && $targetType === 'admin') {
        echo '<div class="alert alert-danger">You do not have permission to delete admin accounts.</div>';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->execute([$deleteUserId]);
        echo '<div class="alert alert-success" id="user-success-msg">User deleted successfully.</div>';
    }
}

// Fetch current admin info
$currentAdmin = null;
if ($currentAdminEmail) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$currentAdminEmail]);
    $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Users Management</h2>
        <?php if ($isSuperAdmin): ?>
        <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
            <i class="fa fa-plus"></i> Add New User
        </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <input type="hidden" name="section" value="users">
                <div class="col-md-4 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="user_type" class="form-control">
                        <option value="">All User Types</option>
                        <option value="admin"<?= $userType === 'admin' ? ' selected' : '' ?>>Admin</option>
                        <option value="staff"<?= $userType === 'staff' ? ' selected' : '' ?>>Staff</option>
                        <option value="client"<?= $userType === 'client' ? ' selected' : '' ?>>Client</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="active" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" <?= $active === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $active === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search || $userType || $active !== ''): ?>
                        <a href="?section=users" class="btn btn-secondary ml-2">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>
                                <a href="?section=users&sort=user_id&order=<?= $sort === 'user_id' && $order === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>">
                                    ID
                                    <?php if ($sort === 'user_id'): ?>
                                        <i class="fa fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?section=users&sort=first_name&order=<?= $sort === 'first_name' && $order === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>">
                                    Name
                                    <?php if ($sort === 'first_name'): ?>
                                        <i class="fa fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?section=users&sort=email&order=<?= $sort === 'email' && $order === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>">
                                    Email
                                    <?php if ($sort === 'email'): ?>
                                        <i class="fa fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Phone</th>
                            <th>
                                <a href="?section=users&sort=user_type&order=<?= $sort === 'user_type' && $order === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>">
                                    Type
                                    <?php if ($sort === 'user_type'): ?>
                                        <i class="fa fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <?php
                            $isTargetAdmin = ($user['user_type'] === 'admin');
                            $isSuperAdminUser = ($user['email'] === 'admin@gmail.com');
                            $canEditDelete = ($isSuperAdmin && !$isSuperAdminUser) || (!$isTargetAdmin && !$isSuperAdminUser);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($user['user_id']) ?></td>
                            <td>
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']) ?>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td>
                                <?php if ($isSuperAdminUser): ?>
                                    <span class="badge bg-dark">Super Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-<?= $user['user_type'] === 'admin' ? 'danger' : ($user['user_type'] === 'staff' ? 'warning' : 'info') ?>">
                                        <?= ucfirst(htmlspecialchars($user['user_type'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $user['is_active'] == 1 ? 'Active' : 'Inactive' ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary"
                                    onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)"
                                    <?= !$canEditDelete ? 'disabled' : '' ?>>Edit</button>
                                <button class="btn btn-sm btn-danger"
                                    onclick="deleteUser(<?= $user['user_id'] ?>, '<?= $user['user_type'] ?>', '<?= $user['email'] ?>')"
                                    <?= !$canEditDelete ? 'disabled' : '' ?>>Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?section=users&page=1&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>&sort=<?= $sort ?>&order=<?= $order ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?section=users&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    if ($start > 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }

                    for ($i = $start; $i <= $end; $i++) {
                        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '">';
                        echo '<a class="page-link" href="?section=users&page=' . $i . '&search=' . urlencode($search) . '&user_type=' . urlencode($userType) . '&sort=' . $sort . '&order=' . $order . '">' . $i . '</a>';
                        echo '</li>';
                    }

                    if ($end < $totalPages) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?section=users&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?section=users&page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="addUserForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" class="form-control" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_user_type" class="form-label">User Type</label>
                            <select name="user_type" id="add_user_type" class="form-select" required>
                                <?php if ($isSuperAdmin): ?>
                                    <option value="admin">Admin</option>
                                <?php endif; ?>
                                <option value="staff">Staff</option>
                                <option value="client">Client</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editUserForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" class="form-control" id="edit_middle_name" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_user_type" class="form-label">User Type</label>
                            <select name="user_type" id="edit_user_type" class="form-select" required>
                                <?php if ($isSuperAdmin): ?>
                                    <option value="admin">Admin</option>
                                <?php endif; ?>
                                <option value="staff">Staff</option>
                                <option value="client">Client</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" id="edit_active" name="active" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="edit_user" id="editUserBtn" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    $('#edit_user_id').val(user.user_id);
    $('#edit_first_name').val(user.first_name);
    $('#edit_middle_name').val(user.middle_name);
    $('#edit_last_name').val(user.last_name);
    $('#edit_email').val(user.email);
    $('#edit_phone').val(user.phone);
    $('#edit_user_type').val(user.user_type);
    // Use user.is_active (1/0) instead of user.active
    $('#edit_active').val(user.is_active);

    // Disable editing admin if not super admin
    if (user.user_type === 'admin' && <?= json_encode(!$isSuperAdmin) ?>) {
        $('#editUserBtn').prop('disabled', true);
        $('#editUserForm select, #editUserForm input').prop('disabled', true);
    } else {
        $('#editUserBtn').prop('disabled', false);
        $('#editUserForm select, #editUserForm input').prop('disabled', false);
    }

    $('#editUserModal').modal('show');
}

function deleteUser(userId, userType, userEmail) {
    // Block delete for admin if not super admin, or if super admin user
    if ((userType === 'admin' && <?= json_encode(!$isSuperAdmin) ?>) || userEmail === 'admin@gmail.com') {
        alert('You do not have permission to delete this account.');
        return;
    }
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_user" value="1">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.append(form);
        form.submit();
    }
}

// Reload page after a successful user action
document.addEventListener('DOMContentLoaded', function() {
    var msg = document.getElementById('user-success-msg');
    if (msg) {
        setTimeout(function() {
            window.location.reload();
        }, 1200);
    }
});
</script>
