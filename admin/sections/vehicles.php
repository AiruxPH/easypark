<?php
// vehicles.php - Vehicles section for admin panel

require_once '../../db.php';

// Fetch vehicles with user info
$sql = "SELECT v.vehicle_id, v.plate_number, v.vehicle_type, v.brand, v.model, v.color, v.created_at, u.first_name, u.last_name
    FROM vehicles v
    LEFT JOIN users u ON v.user_id = u.user_id
    ORDER BY v.vehicle_id DESC";
$result = $conn->query($sql);
?>

<div class="container mt-4">
    <h2>Vehicles</h2>
    <table class="table table-bordered table-striped mt-3">
    <thead>
        <tr>
        <th>ID</th>
        <th>Plate Number</th>
        <th>Type</th>
        <th>Brand</th>
        <th>Model</th>
        <th>Color</th>
        <th>Owner</th>
        <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
            <td><?= htmlspecialchars($row['vehicle_id']) ?></td>
            <td><?= htmlspecialchars($row['plate_number']) ?></td>
            <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
            <td><?= htmlspecialchars($row['brand']) ?></td>
            <td><?= htmlspecialchars($row['model']) ?></td>
            <td><?= htmlspecialchars($row['color']) ?></td>
            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            </tr>
        <?php endwhile; ?>
        <?php else: ?>
        <tr>
            <td colspan="8" class="text-center">No vehicles found.</td>
        </tr>
        <?php endif; ?>
    </tbody>
    </table>
</div>