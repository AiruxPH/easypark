<?php
// admin/sections/transactions.php

require_once '../../db.php';

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
";
$result = $conn->query($sql);
?>

<div class="container mt-4">
    <h2>Transactions</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
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
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
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
                                    if ($status == 'successful') $badge = 'success';
                                    elseif ($status == 'pending') $badge = 'warning';
                                    elseif ($status == 'failed') $badge = 'danger';
                                    elseif ($status == 'refunded') $badge = 'info';
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
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>