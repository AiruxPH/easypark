<?php
// admin/sections/transactions.php

global $pdo;

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$countStmt = $pdo->query("SELECT COUNT(*) FROM payments");
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch all payments with reservation and user info
$sql = "
    SELECT 
        p.reference_number,
        p.amount,
        p.method,
        p.status AS payment_status,
        p.payment_date,
        r.reservation_id,
        r.start_time,
        r.end_time,
        r.status AS reservation_status,
        u.first_name,
        u.last_name,
        u.email
    FROM payments p
    LEFT JOIN reservations r ON p.reservation_id = r.reservation_id
    LEFT JOIN users u ON r.user_id = u.user_id
    ORDER BY p.payment_date DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Transactions</h2>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Reference #</th>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Reservation ID</th>
                            <th>Payment Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Payment Status</th>
                            <th>Reservation Status</th>
                            <th>Reservation Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && count($result) > 0): ?>
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['reference_number']) ?></td>
                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['reservation_id']) ?></td>
                                    <td><?= htmlspecialchars($row['payment_date']) ?></td>
                                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($row['method'])) ?></td>
                                    <td>
                                        <?php
                                        $status = $row['payment_status'];
                                        $badge = 'secondary';
                                        if ($status == 'successful')
                                            $badge = 'success';
                                        elseif ($status == 'pending')
                                            $badge = 'warning';
                                        elseif ($status == 'failed')
                                            $badge = 'danger';
                                        elseif ($status == 'refunded')
                                            $badge = 'info';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars(ucfirst($row['reservation_status'])) ?></td>
                                    <td>
                                        <?= htmlspecialchars($row['start_time']) ?><br>
                                        <small>to</small><br>
                                        <?= htmlspecialchars($row['end_time']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?section=transactions&page=1">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?section=transactions&page=<?= $page - 1 ?>">Previous</a>
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
                            echo '<a class="page-link" href="?section=transactions&page=' . $i . '">' . $i . '</a>';
                            echo '</li>';
                        }
                        if ($end < $totalPages) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?section=transactions&page=<?= $page + 1 ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?section=transactions&page=<?= $totalPages ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>