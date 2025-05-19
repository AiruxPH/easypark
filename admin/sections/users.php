<?php
// Get filters and pagination parameters
$search = $_GET['search'] ?? '';
$userType = $_GET['user_type'] ?? '';
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

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Validate and sanitize sort column
$allowedSort = ['user_id', 'first_name', 'last_name', 'email', 'user_type'];
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
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Users Management</h2>
        <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
            <i class="fa fa-plus"></i> Add New User
        </button>
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
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search || $userType): ?>
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
                        <tr>
                            <td><?= htmlspecialchars($user['user_id']) ?></td>
                            <td>
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']) ?>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td>
                                <span class="badge badge-<?= $user['user_type'] === 'admin' ? 'danger' : ($user['user_type'] === 'staff' ? 'warning' : 'info') ?>">
                                    <?= ucfirst(htmlspecialchars($user['user_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $user['active'] ? 'success' : 'danger' ?>">
                                    <?= $user['active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['user_id'] ?>)">
                                    <i class="fa fa-trash"></i>
                                </button>
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
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" method="POST">
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
                    <div class="form-group">
                        <label>User Type</label>
                        <select class="form-control" name="user_type" required>
                            <option value="client">Client</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST">
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
                    <div class="form-group">
                        <label>User Type</label>
                        <select class="form-control" id="edit_user_type" name="user_type" required>
                            <option value="client">Client</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
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
                    <button type="submit" class="btn btn-primary">Update User</button>
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
    $('#edit_active').val(user.active);
    $('#editUserModal').modal('show');
}

function deleteUser(userId) {
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
</script>
