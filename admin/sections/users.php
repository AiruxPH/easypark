<?php

$currentAdminEmail = $_SESSION['user_email'] ?? '';
$isSuperAdmin = ($currentAdminEmail === 'admin@gmail.com');

// Get filters and pagination parameters
$search = trim($_GET['search'] ?? '');
$userType = $_GET['user_type'] ?? '';
$active = $_GET['active'] ?? ''; // '1', '0', or ''
$sort = $_GET['sort'] ?? 'user_id';
$order = strtoupper($_GET['order'] ?? '') === 'DESC' ? 'DESC' : 'ASC';
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
$allowedSort = ['user_id', 'first_name', 'email', 'user_type', 'is_active', 'created_at'];
$sort = in_array($sort, $allowedSort) ? $sort : 'user_id';

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

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $newUserType = $_POST['user_type'] ?? '';
    // Security: Only Super Admin can create 'admin' type
    if (!$isSuperAdmin && $newUserType === 'admin') {
        echo '<div class="alert alert-danger shadow-sm">You do not have permission to add admin accounts.</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, email, password, phone, user_type, security_word, created_at, is_active, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->execute([
            trim($_POST['first_name']),
            trim($_POST['middle_name']),
            trim($_POST['last_name']),
            trim($_POST['email']),
            $_POST['password'],
            trim($_POST['phone']),
            $newUserType,
            'default',
            1,
            'default.jpg'
        ]);
        echo '<div class="alert alert-success shadow-sm" id="user-success-msg">User added successfully.</div>';
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $editUserId = intval($_POST['user_id']);

    // Check target user existing data
    $target = $pdo->prepare("SELECT user_type, email FROM users WHERE user_id = ?");
    $target->execute([$editUserId]);
    $targetRow = $target->fetch(PDO::FETCH_ASSOC);

    $targetType = $targetRow['user_type'] ?? null;
    $targetEmail = $targetRow['email'] ?? null;

    $error = null;
    $newType = $_POST['user_type'] ?? '';

    // Security Checks
    if ($targetEmail === 'admin@gmail.com') {
        $error = "You cannot edit the Super Admin account.";
    } elseif (!$isSuperAdmin && $targetType === 'admin') {
        $error = "You do not have permission to edit Admin accounts.";
    } elseif (!$isSuperAdmin && $newType === 'admin') {
        $error = "You do not have permission to promote users to Admin.";
    }

    if ($error) {
        echo '<div class="alert alert-danger shadow-sm">' . $error . '</div>';
    } else {
        $activeStatus = $_POST['active'];

        $sql = "UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, phone=?, user_type=?, is_active=? WHERE user_id=?";
        $updateParams = [
            trim($_POST['first_name']),
            trim($_POST['middle_name']),
            trim($_POST['last_name']),
            trim($_POST['email']),
            trim($_POST['phone']),
            $newType,
            $activeStatus,
            $editUserId
        ];

        // Handle Password Update if provided
        if (!empty($_POST['password'])) {
            $sql = "UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, phone=?, user_type=?, is_active=?, password=? WHERE user_id=?";
            // Insert password into params array before user_id
            array_pop($updateParams); // remove user_id
            $updateParams[] = $_POST['password']; // add password
            $updateParams[] = $editUserId; // add user_id back
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateParams);
        echo '<div class="alert alert-success shadow-sm" id="user-success-msg">User updated successfully.</div>';
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

    $error = null;
    if ($targetEmail === 'admin@gmail.com') {
        $error = "You cannot delete the Super Admin account.";
    } elseif (!$isSuperAdmin && $targetType === 'admin') {
        $error = "You do not have permission to delete Admin accounts.";
    }

    if ($error) {
        echo '<div class="alert alert-danger shadow-sm">' . $error . '</div>';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->execute([$deleteUserId]);
        echo '<div class="alert alert-success shadow-sm" id="user-success-msg">User deleted successfully.</div>';
    }
}

// Helper for sorting links
function sortLink($col, $label, $currentSort, $currentOrder, $search, $type, $active)
{
    $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($currentSort === $col) {
        $icon = $currentOrder === 'ASC' ? '<i class="fa fa-sort-up ml-1"></i>' : '<i class="fa fa-sort-down ml-1"></i>';
    } else {
        $icon = '<i class="fa fa-sort text-gray-300 ml-1"></i>';
    }

    $url = "?section=users&sort=$col&order=$newOrder&search=" . urlencode($search) .
        "&user_type=" . urlencode($type) . "&active=" . urlencode($active);

    return "<a href='$url' class='text-decoration-none text-dark font-weight-bold'>$label $icon</a>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Users Management</h2>
        <?php if ($isSuperAdmin): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addUserModal">
                <i class="fa fa-plus fa-sm text-white-50"></i> Add New User
            </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm border-bottom-primary">
        <div class="card-body py-3">
            <form method="GET" class="form-inline justify-content-center">
                <input type="hidden" name="section" value="users">

                <div class="input-group mr-2 mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-0"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control bg-light border-0 small"
                        placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="input-group mr-2 mb-2">
                    <select name="user_type" class="custom-select custom-select-sm border-0 bg-light">
                        <option value="">All Roles</option>
                        <option value="admin" <?= $userType === 'admin' ? ' selected' : '' ?>>Admin</option>
                        <option value="staff" <?= $userType === 'staff' ? ' selected' : '' ?>>Staff</option>
                        <option value="client" <?= $userType === 'client' ? ' selected' : '' ?>>Client</option>
                    </select>
                </div>

                <div class="input-group mr-2 mb-2">
                    <select name="active" class="custom-select custom-select-sm border-0 bg-light">
                        <option value="">All Status</option>
                        <option value="1" <?= $active === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $active === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-sm btn-primary shadow-sm mb-2">
                    <i class="fa fa-filter"></i> Apply
                </button>
                <?php if ($search || $userType || $active !== ''): ?>
                    <a href="?section=users" class="btn btn-sm btn-light ml-2 mb-2 text-danger">
                        <i class="fa fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive">
                <table class="table table-hover align-items-center table-flush" id="dataTable" width="100%"
                    cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th class="pl-4">
                                <?= sortLink('user_id', 'ID', $sort, $order, $search, $userType, $active) ?></th>
                            <th><?= sortLink('first_name', 'Name', $sort, $order, $search, $userType, $active) ?></th>
                            <th><?= sortLink('email', 'Email', $sort, $order, $search, $userType, $active) ?></th>
                            <th>Phone</th>
                            <th><?= sortLink('user_type', 'Role', $sort, $order, $search, $userType, $active) ?></th>
                            <th><?= sortLink('is_active', 'Status', $sort, $order, $search, $userType, $active) ?></th>
                            <th class="text-right pr-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $isTargetAdmin = ($user['user_type'] === 'admin');
                            $isSuperAdminUser = ($user['email'] === 'admin@gmail.com');
                            // Can update if: I am Super Admin OR (Target is NOT Admin AND NOT Super Admin)
                            $canEditDelete = $isSuperAdmin || (!$isTargetAdmin && !$isSuperAdminUser);
                            ?>
                            <tr>
                                <td class="pl-4 font-weight-bold text-gray-800">#<?= htmlspecialchars($user['user_id']) ?>
                                </td>
                                <td>
                                    <div class="font-weight-bold">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                    <div class="small text-gray-500"><?= htmlspecialchars($user['middle_name']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td>
                                    <?php if ($isSuperAdminUser): ?>
                                        <span class="badge badge-dark shadow-sm px-2 py-1">👑 Super Admin</span>
                                    <?php else: ?>
                                        <?php
                                        $badgeClass = 'secondary';
                                        $userIcon = 'user';
                                        switch ($user['user_type']) {
                                            case 'admin':
                                                $badgeClass = 'danger';
                                                $userIcon = 'shield';
                                                break;
                                            case 'staff':
                                                $badgeClass = 'warning';
                                                $userIcon = 'id-badge';
                                                break;
                                            case 'client':
                                                $badgeClass = 'info';
                                                $userIcon = 'user';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-<?= $badgeClass ?> px-2 py-1">
                                            <i class="fa fa-<?= $userIcon ?>"></i>
                                            <?= ucfirst(htmlspecialchars($user['user_type'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active'] == 1): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right pr-4">
                                    <?php if ($canEditDelete): ?>
                                        <button class="btn btn-sm btn-outline-primary shadow-sm"
                                            onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)" title="Edit User">
                                            <i class="fa fa-pen"></i>
                                        </button>
                                        <?php if (!$isSuperAdminUser): // Never delete super admin ?>
                                            <button class="btn btn-sm btn-outline-danger shadow-sm ml-1"
                                                onclick="deleteUser(<?= $user['user_id'] ?>, '<?= $user['user_type'] ?>', '<?= $user['email'] ?>')"
                                                title="Delete User">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="fa fa-lock"></i> Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 mb-3">
                    <ul class="pagination pagination-sm justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?section=users&page=1&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>&sort=<?= $sort ?>&order=<?= $order ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?section=users&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a>
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
                                <a class="page-link"
                                    href="?section=users&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&user_type=<?= urlencode($userType) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a>
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
                        <h5 class="modal-title">Add New User</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Middle Name</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="user_type" id="add_user_type" class="form-control" required>
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
                        <button type="submit" name="add_user" class="btn btn-primary">Create Account</button>
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
                        <h5 class="modal-title">Edit User Details</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>First Name</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Middle Name</label>
                                <input type="text" class="form-control" id="edit_middle_name" name="middle_name">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Last Name</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Role</label>
                                <select name="user_type" id="edit_user_type" class="form-control" required>
                                    <?php if ($isSuperAdmin): ?>
                                        <option value="admin">Admin</option>
                                    <?php endif; ?>
                                    <option value="staff">Staff</option>
                                    <option value="client">Client</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Status</label>
                                <select class="form-control" id="edit_active" name="active" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <div class="form-group">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Change Password
                                (Optional)</label>
                            <input type="password" class="form-control" name="password"
                                placeholder="Leave blank to keep current">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="edit_user" id="editUserBtn" class="btn btn-primary">Save
                            Changes</button>
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
        $('#edit_active').val(user.is_active);

        // Disable editing admin if not super admin
        if (user.user_type === 'admin' && <?= json_encode(!$isSuperAdmin) ?>) {
            $('#editUserBtn').prop('disabled', true);
            $('#editUserForm select, #editUserForm input').prop('disabled', true);
            // Maybe show an alert or tooltip in a real app
        } else {
            $('#editUserBtn').prop('disabled', false);
            $('#editUserForm select, #editUserForm input').prop('disabled', false);
        }

        $('#editUserModal').modal('show');
    }

    function deleteUser(userId, userType, userEmail) {
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

    document.addEventListener('DOMContentLoaded', function () {
        var msg = document.getElementById('user-success-msg');
        if (msg) {
            setTimeout(function () {
                window.location.href = window.location.href; // refresh to clear post data/msg properly or just reload
            }, 1200);
        }
    });
</script>