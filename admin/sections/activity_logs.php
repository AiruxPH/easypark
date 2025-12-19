<?php
// admin/sections/activity_logs.php

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$current_user_type = $_SESSION['user_type'];

// Only Admin and Staff can access this page
if ($current_user_type !== 'admin' && $current_user_type !== 'staff') {
    echo '<div class="alert alert-danger">Unauthorized access.</div>';
    exit();
}

// Filter & Pagination Logic
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$params = [];
$whereClauses = ["1=1"];

// Role-Based Filtering
if ($current_user_type === 'staff') {
    $whereClauses[] = "l.user_type IN ('client', 'staff')";
}

// Action Filter
if (!empty($actionFilter)) {
    $whereClauses[] = "l.action = ?";
    $params[] = $actionFilter;
}

// Search Filter
if (!empty($search)) {
    $whereClauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR l.details LIKE ? OR l.action LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

$whereSql = implode(' AND ', $whereClauses);

// Count Total Records for Pagination
$countSql = "SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON l.user_id = u.user_id WHERE $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch Logs
$sql = "SELECT l.*, u.first_name, u.last_name, u.email 
        FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.user_id 
        WHERE $whereSql
        ORDER BY l.created_at DESC 
        LIMIT $limit OFFSET $offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database Query Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $logs = [];
}

// Get unique actions for filter dropdown
$actionStmt = $pdo->prepare("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
$actionStmt->execute();
$actions = $actionStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4 bg-dark-theme text-light">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Activity Logs</h6>

                <form class="form-inline" method="GET">
                    <input type="hidden" name="section" value="activity_logs">

                    <!-- Search Input -->
                    <input type="text" name="search"
                        class="form-control form-control-sm bg-dark text-light border-secondary mr-2"
                        placeholder="Search..." value="<?= htmlspecialchars($search) ?>">

                    <label class="mr-2 text-muted">Filter:</label>
                    <select name="action" class="form-control form-control-sm bg-dark text-light border-secondary mr-2"
                        onchange="this.form.submit()">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= $actionFilter === $act ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $act))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-sm btn-primary mr-2"><i class="fa fa-search"></i></button>

                    <?php if ($actionFilter || $search): ?>
                        <a href="?section=activity_logs" class="btn btn-sm btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-dark table-hover" id="activityTable" width="100%"
                        cellspacing="0">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No activity logs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                                        <td>
                                            <?php if ($log['user_id']): ?>
                                                <?php if (isset($log['first_name'])): ?>
                                                    <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?>
                                                    <small class="d-block text-muted"><?= htmlspecialchars($log['email']) ?></small>
                                                <?php else: ?>
                                                    <span class="text-danger">Deleted User (ID: <?= $log['user_id'] ?>)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">System / Guest</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = 'secondary';
                                            if ($log['user_type'] === 'admin')
                                                $badgeClass = 'danger';
                                            if ($log['user_type'] === 'staff')
                                                $badgeClass = 'warning';
                                            if ($log['user_type'] === 'client')
                                                $badgeClass = 'info';
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?>">
                                                <?= ucfirst($log['user_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-light">
                                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $log['action']))) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($log['details']) ?></td>
                                        <td>
                                            <code class="text-muted"><?= htmlspecialchars($log['ip_address']) ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- First & Prev -->
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?section=activity_logs&page=1&action=<?= urlencode($actionFilter) ?>&search=<?= urlencode($search) ?>">First</a>
                                </li>
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?section=activity_logs&page=<?= $page - 1 ?>&action=<?= urlencode($actionFilter) ?>&search=<?= urlencode($search) ?>">Prev</a>
                                </li>

                                <!-- Page Numbers (Window of 5) -->
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                    ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link"
                                            href="?section=activity_logs&page=<?= $i ?>&action=<?= urlencode($actionFilter) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Next & Last -->
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?section=activity_logs&page=<?= $page + 1 ?>&action=<?= urlencode($actionFilter) ?>&search=<?= urlencode($search) ?>">Next</a>
                                </li>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="?section=activity_logs&page=<?= $totalPages ?>&action=<?= urlencode($actionFilter) ?>&search=<?= urlencode($search) ?>">Last</a>
                                </li>
                            </ul>

                            <!-- Jump to Page -->
                            <form action="" method="GET" class="form-inline justify-content-center mt-2">
                                <input type="hidden" name="section" value="activity_logs">
                                <input type="hidden" name="action" value="<?= htmlspecialchars($actionFilter) ?>">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                <label class="mr-2 text-muted small">Jump to:</label>
                                <input type="number" name="page" min="1" max="<?= $totalPages ?>"
                                    class="form-control form-control-sm bg-dark text-light border-secondary"
                                    style="width: 70px;" placeholder="<?= $page ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary ml-1">Go</button>
                            </form>
                        </nav>
                    <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const tableBody = document.querySelector('table tbody');
        const filterForm = document.querySelector('form');

        // Inputs
        const searchInput = document.querySelector('input[name="search"]');
        const actionSelect = document.querySelector('select[name="action"]');
        // We need user_type_context to respect the role filter (staff/client vs all)
        // Since we can't easily read PHP session in JS, we infer it from PHP rendered HTML or rely on default 'admin'.
        // Actually, the server script needs it. We can add a hidden input or just let the backend handle it if we pass it? 
        // The backend relies on session or context. Let's look at `search_activity_logs_html.php`.
        // It reads $_GET['user_type_context']. We should pass the session user type if possible.
        // Or simpler: The backend script `search_activity_logs_html.php` should really inspect $_SESSION['user_type'].
        // But for now let's pass it if we can, or fix the backend to use session.
        // Re-checking backend: `search_activity_logs_html.php` uses `$_GET['user_type_context']`.
        // So we should inject it here.
        const currentUserType = '<?= $current_user_type ?>';

        let currentPage = 1;

        function loadLogs(page = 1) {
            currentPage = page;

            const params = new URLSearchParams();
            if (searchInput && searchInput.value) params.append('search', searchInput.value);
            if (actionSelect && actionSelect.value) params.append('action', actionSelect.value);
            params.append('user_type_context', currentUserType);
            params.append('page', page);

            // Fetch
            const newUrl = 'index.php?section=activity_logs&' + params.toString();
            window.history.pushState({ path: newUrl }, '', newUrl);

            fetch('ajax/search_activity_logs_html.php?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (tableBody) tableBody.innerHTML = data.table_html;

                        // Update Pagination
                        const nav = document.querySelector('nav[aria-label="Page navigation"]');
                        if (data.pagination_html) {
                            if (nav) {
                                const ul = nav.querySelector('ul');
                                if (ul) ul.outerHTML = data.pagination_html;
                                else nav.innerHTML = data.pagination_html + nav.innerHTML.replace(/<ul.*<\/ul>/s, '');
                            } else {
                                const container = document.querySelector('.table-responsive');
                                if (container) container.insertAdjacentHTML('beforeend', `<nav aria-label="Page navigation" class="mt-4">${data.pagination_html}</nav>`);
                            }
                        } else {
                            if (nav) nav.style.display = 'none';
                        }
                    }
                })
                .catch(err => console.error('Logs Load Error:', err));
        }

        // Listeners
        if (filterForm) {
            filterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                loadLogs(1);
            });
        }
        if (actionSelect) {
            // Remove the inline onchange="this.form.submit()" first if it exists, or just override it?
            // The replace_file_content overwrites the HTML, but the PHP loop above generated it.
            // Waait. The PHP file has `onchange="this.form.submit()"`.
            // We should remove that attribute in our JS or just preventDefault?
            // Better: remove it programmatically.
            actionSelect.removeAttribute('onchange');
            actionSelect.addEventListener('change', () => loadLogs(1));
        }

        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadLogs(1), 500);
            });
        }

        document.body.addEventListener('click', function (e) {
            if (e.target.closest('.page-link')) {
                const link = e.target.closest('.page-link');
                const page = link.getAttribute('data-page');
                if (page) {
                    e.preventDefault();
                    loadLogs(page);
                }
            }
        });
    });
</script>