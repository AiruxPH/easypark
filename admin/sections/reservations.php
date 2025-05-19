<?php
// admin/sections/reservations.php

// Fetch reservations with user, vehicle, and parking slot info
$sql = "
    SELECT 
        r.reservation_id,
        u.first_name, u.last_name, u.email,
        v.plate_number, v.vehicle_type,
        ps.slot_number, ps.slot_type,
        r.start_time, r.end_time, r.duration, r.status,
        r.created_at, r.updated_at
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    LEFT JOIN parking_slots ps ON r.parking_slot_id = ps.parking_slot_id
    ORDER BY r.created_at DESC
";
$res = $conn->query($sql);
?>

<div class="container mt-4">
    <h2>Reservations</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Vehicle</th>
                    <th>Type</th>
                    <th>Slot</th>
                    <th>Slot Type</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Duration (hrs)</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res && $res->num_rows > 0): ?>
                    <?php while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['reservation_id']) ?></td>
                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['plate_number']) ?></td>
                            <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                            <td><?= htmlspecialchars($row['slot_number']) ?></td>
                            <td><?= htmlspecialchars($row['slot_type']) ?></td>
                            <td><?= htmlspecialchars($row['start_time']) ?></td>
                            <td><?= htmlspecialchars($row['end_time']) ?></td>
                            <td><?= htmlspecialchars($row['duration']) ?></td>
                            <td>
                                <span class="badge bg-<?= $row['status'] === 'confirmed' ? 'success' : ($row['status'] === 'cancelled' ? 'danger' : 'secondary') ?>">
                                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td><?= htmlspecialchars($row['updated_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="text-center">No reservations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>