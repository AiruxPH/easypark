<?php
// Get filters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($status && in_array($status, ['available', 'reserved', 'occupied'])) {
    $where[] = 'slot_status = :status';
    $params[':status'] = $status;
}

if ($type && in_array($type, ['two_wheeler', 'standard'])) {
    $where[] = 'slot_type = :type';
    $params[':type'] = $type;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM parking_slots $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get slots for current page
$sql = "SELECT * FROM parking_slots $whereClause ORDER BY slot_number ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle slot status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_slot'])) {
    $slot_id = $_POST['slot_id'];
    $new_status = $_POST['new_status'];

    if (in_array($new_status, ['available', 'reserved', 'occupied'])) {
        $stmt = $pdo->prepare('UPDATE parking_slots SET slot_status = ? WHERE parking_slot_id = ?');
        $stmt->execute([$new_status, $slot_id]);

        // RACE CONDITION RESOLUTION:
        // If status is changed to 'occupied', check for active reservations for this slot.
        // The one that "won" the race (arrived first) stays, others are cancelled.
        if ($new_status === 'occupied') {
            // Find all active reservations for this slot right now
            $stmt = $pdo->prepare("SELECT reservation_id FROM reservations WHERE parking_slot_id = ? AND status IN ('confirmed', 'ongoing') AND start_time <= NOW() AND end_time > NOW() ORDER BY created_at ASC");
            $stmt->execute([$slot_id]);
            $active_reservations = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($active_reservations) > 0) {
                // Winner is the first one (oldest created). All others get cancelled.
                $winner_id = $active_reservations[0];

                // Mark winner as 'ongoing' if not already
                $pdo->prepare("UPDATE reservations SET status = 'ongoing' WHERE reservation_id = ?")->execute([$winner_id]);

                // Cancel the losers
                if (count($active_reservations) > 1) {
                    $losers = array_slice($active_reservations, 1);
                    $loser_ids_str = implode(',', $losers);
                    $pdo->exec("UPDATE reservations SET status = 'cancelled' WHERE reservation_id IN ($loser_ids_str)");
                }
            }
        }

        header('Location: ?section=parking&status=' . urlencode($status) . '&type=' . urlencode($type) . '&page=' . $page);
        exit;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Parking Slots Management</h2>
        <button class="btn btn-primary" data-toggle="modal" data-target="#addSlotModal">
            <i class="fa fa-plus"></i> Add New Slot
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <input type="hidden" name="section" value="parking">
                <select name="status" class="form-control mr-2">
                    <option value="">All Statuses</option>
                    <option value="available" <?= $status === 'available' ? ' selected' : '' ?>>Available</option>
                    <option value="reserved" <?= $status === 'reserved' ? ' selected' : '' ?>>Reserved</option>
                    <option value="occupied" <?= $status === 'occupied' ? ' selected' : '' ?>>Occupied</option>
                </select>
                <select name="type" class="form-control mr-2">
                    <option value="">All Types</option>
                    <option value="two_wheeler" <?= $type === 'two_wheeler' ? ' selected' : '' ?>>Two Wheeler</option>
                    <option value="standard" <?= $type === 'standard' ? ' selected' : '' ?>>Standard</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if ($status || $type): ?>
                    <a href="?section=parking" class="btn btn-secondary ml-2">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Slots Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Slot ID</th>
                            <th>Slot Number</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slots as $slot): ?>
                            <tr>
                                <td><?= htmlspecialchars($slot['parking_slot_id']) ?></td>
                                <td><?= htmlspecialchars($slot['slot_number']) ?></td>
                                <td><span
                                        class="badge badge-info"><?= ucwords(str_replace('_', ' ', htmlspecialchars($slot['slot_type']))) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'available' => 'success',
                                        'reserved' => 'warning',
                                        'occupied' => 'danger'
                                    ][$slot['slot_status']] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $statusClass ?>">
                                        <?= ucfirst(htmlspecialchars($slot['slot_status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary"
                                        onclick="editSlot(<?= htmlspecialchars(json_encode($slot)) ?>)">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger"
                                        onclick="deleteSlot(<?= $slot['parking_slot_id'] ?>)">
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
                                <a class="page-link"
                                    href="?section=parking&page=1&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?section=parking&page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>">Previous</a>
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
                            echo '<a class="page-link" href="?section=parking&page=' . $i . '&status=' . urlencode($status) . '&type=' . urlencode($type) . '">' . $i . '</a>';
                            echo '</li>';
                        }

                        if ($end < $totalPages) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?section=parking&page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?section=parking&page=<?= $totalPages ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Slot Modal -->
<div class="modal fade" id="addSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Parking Slot</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addSlotForm" method="POST">
                    <div class="form-group">
                        <label>Slot Number</label>
                        <input type="text" class="form-control" name="slot_number" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" name="slot_type" required>
                            <option value="two_wheeler">Two Wheeler</option>
                            <option value="standard">Standard</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Slot</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Slot Modal -->
<div class="modal fade" id="editSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Parking Slot</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editSlotForm" method="POST">
                    <input type="hidden" name="slot_id" id="edit_slot_id">
                    <div class="form-group">
                        <label>Slot Number</label>
                        <input type="text" class="form-control" id="edit_slot_number" name="slot_number" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" id="edit_slot_type" name="slot_type" required>
                            <option value="two_wheeler">Two Wheeler</option>
                            <option value="standard">Standard</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="edit_slot_status" name="slot_status" required onchange="toggleBookerSelection()">
                            <option value="available">Available</option>
                            <option value="reserved">Reserved</option>
                            <option value="occupied">Occupied</option>
                        </select>
                    </div>
                    <!-- Booker Selection Field (Hidden by default) -->
                    <div class="form-group" id="booker_selection_group" style="display:none;">
                        <label>Identify Occupant (Winner)</label>
                        <select class="form-control" id="winning_reservation_id" name="winning_reservation_id">
                            <option value="">-- Autoselect (First Come) --</option>
                        </select>
                        <small class="form-text text-muted">Select the customer who actually arrived. All other conflicting bookings will be cancelled.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Slot</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function editSlot(slot) {
        $('#edit_slot_id').val(slot.parking_slot_id);
        $('#edit_slot_number').val(slot.slot_number);
        $('#edit_slot_type').val(slot.slot_type);
        $('#edit_slot_status').val(slot.slot_status);
        toggleBookerSelection(); // Reset visibility
        $('#editSlotModal').modal('show');
    }

    function toggleBookerSelection() {
        const status = document.getElementById('edit_slot_status').value;
        const group = document.getElementById('booker_selection_group');
        const slotId = document.getElementById('edit_slot_id').value;
        
        if (status === 'occupied') {
            group.style.display = 'block';
            // fetch bookers via AJAX
            fetch(`ajax/get_slot_reservations.php?slot_id=${slotId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('winning_reservation_id');
                    select.innerHTML = '<option value="">-- Autoselect (First Come) --</option>';
                    if(data.success && data.data.length > 0) {
                        data.data.forEach(res => {
                            const label = `${res.first_name} ${res.last_name} (${res.plate_number}) - ${res.start_time}`;
                            const option = document.createElement('option');
                            option.value = res.reservation_id;
                            option.textContent = label;
                            select.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.textContent = "No active reservations found instantly";
                        option.disabled = true;
                        select.appendChild(option);
                    }
                });
        } else {
            group.style.display = 'none';
        }
    }

    function deleteSlot(slotId) {
        if (confirm('Are you sure you want to delete this parking slot?')) {
            // Submit delete request
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
            <input type="hidden" name="delete_slot" value="1">
            <input type="hidden" name="slot_id" value="${slotId}">
        `;
            document.body.append(form);
            form.submit();
        }
    }
</script>