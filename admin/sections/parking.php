<?php
// Get filters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'slot_number'; // default sort
$order = $_GET['order'] ?? 'ASC';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20; // Maintain pagination
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(ps.slot_number LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status && in_array($status, ['available', 'reserved', 'occupied', 'unavailable'])) {
    $where[] = 'ps.slot_status = :status';
    $params[':status'] = $status;
}

if ($type) {
    $where[] = 'ps.slot_type = :type';
    $params[':type'] = $type;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Validation for sort
$allowedSort = ['slot_number', 'slot_type', 'slot_status'];
$sort = in_array($sort, $allowedSort) ? $sort : 'slot_number';
$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM parking_slots ps $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get available types dynamically for filter
$typesStmt = $pdo->query("SELECT DISTINCT slot_type FROM parking_slots ORDER BY slot_type");
$availableTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get slots with active reservation info
$sql = "
    SELECT 
        ps.*,
        r.reservation_id,
        r.status as res_status,
        v.plate_number,
        CONCAT(u.first_name, ' ', u.last_name) as owner_name,
        r.start_time,
        r.end_time
    FROM parking_slots ps
    LEFT JOIN reservations r ON ps.parking_slot_id = r.parking_slot_id 
        AND r.status IN ('confirmed', 'ongoing')
        AND (
            r.status = 'ongoing' 
            OR 
            (r.start_time <= NOW() AND r.end_time >= NOW())
        )
        LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    LEFT JOIN users u ON r.user_id = u.user_id
    $whereClause 
    ORDER BY ps.$sort $order 
    LIMIT :limit OFFSET :offset
";

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
    $new_status = $_POST['slot_status'];
    $winning_res_id = $_POST['winning_reservation_id'] ?? null;

    if (in_array($new_status, ['available', 'reserved', 'occupied', 'unavailable'])) {
        $stmt = $pdo->prepare('UPDATE parking_slots SET slot_status = ? WHERE parking_slot_id = ?');
        $stmt->execute([$new_status, $slot_id]);

        // Log Activity
        logActivity($pdo, $_SESSION['user_id'], 'admin', 'parking_status_update', "Updated slot ID $slot_id status to '$new_status'");

        // RACE CONDITION RESOLUTION:
        if ($new_status === 'occupied') {
            if ($winning_res_id) {
                // Admin manually selected a winner
                $pdo->prepare("UPDATE reservations SET status = 'ongoing' WHERE reservation_id = ?")->execute([$winning_res_id]);
                // Cancel others for this slot that were "active"
                $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE parking_slot_id = ? AND reservation_id != ? AND status IN ('confirmed','ongoing') AND start_time <= NOW()")->execute([$slot_id, $winning_res_id]);
            } else {
                // Fallback: Winner is the first one (oldest created). All others get cancelled.
                $stmt = $pdo->prepare("SELECT reservation_id FROM reservations WHERE parking_slot_id = ? AND status IN ('confirmed', 'ongoing') AND start_time <= NOW() AND end_time > NOW() ORDER BY created_at ASC");
                $stmt->execute([$slot_id]);
                $active_reservations = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (count($active_reservations) > 0) {
                    $winner_id = $active_reservations[0];
                    $pdo->prepare("UPDATE reservations SET status = 'ongoing' WHERE reservation_id = ?")->execute([$winner_id]);

                    if (count($active_reservations) > 1) {
                        $losers = array_slice($active_reservations, 1);
                        $loser_ids_str = implode(',', $losers);
                        $pdo->exec("UPDATE reservations SET status = 'cancelled' WHERE reservation_id IN ($loser_ids_str)");
                    }
                }
            }
        } elseif ($new_status === 'reserved') {
            // NEW: Admin Confirms a Pending Reservation
            if ($winning_res_id) {
                // 1. Mark selected as confirmed
                $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?")->execute([$winning_res_id]);

                // 2. Auto-Cancel conflicting pending reservations
                // Fetch times of winner
                $stmt = $pdo->prepare("SELECT start_time, end_time FROM reservations WHERE reservation_id = ?");
                $stmt->execute([$winning_res_id]);
                $win = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($win) {
                    $start = $win['start_time'];
                    $end = $win['end_time'];

                    // Find conflicts
                    $conflictStmt = $pdo->prepare("SELECT reservation_id, user_id FROM reservations WHERE parking_slot_id = ? AND status = 'pending' AND reservation_id != ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))");
                    $conflictStmt->execute([$slot_id, $winning_res_id, $end, $start, $end, $start, $start, $end]);
                    $conflicts = $conflictStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($conflicts as $c) {
                        $c_id = $c['reservation_id'];
                        $c_uid = $c['user_id'];
                        // Cancel
                        $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?")->execute([$c_id]);

                        // Refund Logic (Copy from staff/action_booking.php)
                        $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE reservation_id = ?")->execute([$c_id]);
                        $stmt_amount = $pdo->prepare("SELECT amount FROM payments WHERE reservation_id = ? AND status = 'refunded' AND method = 'coins'");
                        $stmt_amount->execute([$c_id]);
                        $paid = $stmt_amount->fetchColumn();

                        if ($paid > 0) {
                            $pdo->prepare("UPDATE users SET coins = coins + ? WHERE user_id = ?")->execute([$paid, $c_uid]);
                            $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, 'refund', 'Refund for Auto-Cancelled Res #$c_id')")->execute([$c_uid, $paid]);
                        }
                    }
                }
            }

        } elseif ($new_status === 'unavailable') {
            // Maintenance Mode: Cancel ALL active/upcoming reservations for this slot
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE parking_slot_id = ? AND status IN ('confirmed', 'ongoing') AND end_time > NOW()");
            $stmt->execute([$slot_id]);
        }

        header('Location: ?section=parking&status=' . urlencode($status) . '&type=' . urlencode($type) . '&page=' . $page);
        exit;
    }
}

// Handle Add Slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    try {
        $slotNum = trim($_POST['slot_number']);
        $slotType = $_POST['slot_type'];

        // Check duplicate
        $check = $pdo->prepare("SELECT parking_slot_id FROM parking_slots WHERE slot_number = ?");
        $check->execute([$slotNum]);

        if ($check->rowCount() > 0) {
            echo '<div class="alert alert-danger shadow-sm">Slot number ' . htmlspecialchars($slotNum) . ' already exists.</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO parking_slots (slot_number, slot_type, slot_status) VALUES (?, ?, 'available')");
            $stmt->execute([$slotNum, $slotType]);

            $newId = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['user_id'], 'admin', 'parking_add', "Added new parking slot: $slotNum ($slotType)");

            header('Location: ?section=parking&status=' . urlencode($status) . '&type=' . urlencode($type));
            exit;
        }
    } catch (Exception $e) {
        if (ob_get_level())
            ob_end_clean();
        die('<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>');
    }
}

// Handle Delete Slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slot'])) {
    $delId = $_POST['slot_id'];

    // Get details for log
    $stmt = $pdo->prepare("SELECT slot_number FROM parking_slots WHERE parking_slot_id = ?");
    $stmt->execute([$delId]);
    $slotNum = $stmt->fetchColumn();

    if ($slotNum) {
        $stmt = $pdo->prepare("DELETE FROM parking_slots WHERE parking_slot_id = ?");
        $stmt->execute([$delId]);

        logActivity($pdo, $_SESSION['user_id'], 'admin', 'parking_delete', "Deleted parking slot: $slotNum");
    }
    header('Location: ?section=parking');
    exit;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">Parking Map Overview</h2>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addSlotModal">
            <i class="fa fa-plus-circle fa-sm text-white-50"></i> Add New Slot
        </button>
    </div>

    <!-- Filters & Search -->
    <div class="card mb-4 shadow-sm border-bottom-primary">
        <div class="card-body py-3">
            <form method="GET" class="form-inline justify-content-center">
                <input type="hidden" name="section" value="parking">

                <div class="input-group mr-2 mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control border-0 small"
                        placeholder="Search Slot (e.g. A-1)" value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="input-group mr-2 mb-2">
                    <select name="status" class="custom-select custom-select-sm border-0">
                        <option value="">All Statuses</option>
                        <option value="available" <?= $status === 'available' ? ' selected' : '' ?>>🟢 Available</option>
                        <option value="reserved" <?= $status === 'reserved' ? ' selected' : '' ?>>🟡 Reserved</option>
                        <option value="occupied" <?= $status === 'occupied' ? ' selected' : '' ?>>🔴 Occupied</option>
                        <option value="unavailable" <?= $status === 'unavailable' ? ' selected' : '' ?>>⚪ Unavailable
                        </option>
                    </select>
                </div>

                <div class="input-group mr-2 mb-2">
                    <select name="type" class="custom-select custom-select-sm border-0 text-capitalize">
                        <option value="">All Vehicle Types</option>
                        <?php foreach ($availableTypes as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $type === $t ? ' selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($t))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group mr-2 mb-2">
                    <label class="mr-2 small">Sort:</label>
                    <select name="sort" class="custom-select custom-select-sm border-0" onchange="this.form.submit()">
                        <option value="slot_number" <?= $sort === 'slot_number' ? 'selected' : '' ?>>Slot Number</option>
                        <option value="slot_status" <?= $sort === 'slot_status' ? 'selected' : '' ?>>Status</option>
                        <option value="slot_type" <?= $sort === 'slot_type' ? 'selected' : '' ?>>Type</option>
                    </select>
                    <select name="order" class="custom-select custom-select-sm border-0 ml-1"
                        onchange="this.form.submit()">
                        <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Asc</option>
                        <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Desc</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-sm btn-primary shadow-sm mb-2 ml-2">
                    <i class="fa fa-filter"></i> Apply
                </button>
                <?php if ($search || $status || $type): ?>
                    <a href="?section=parking" class="btn btn-sm btn-light ml-2 mb-2 text-danger">
                        <i class="fa fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Visual Parking Grid -->
    <div class="parking-grid-container">
        <?php foreach ($slots as $slot): ?>
            <?php
            $statusClass = 'slot-available';
            $icon = 'fa-check';
            $statusLabel = 'Available';

            // Determining visual status
            if ($slot['slot_status'] === 'occupied') {
                $statusClass = 'slot-occupied';
                $icon = 'fa-car';
                $statusLabel = 'Occupied';
            } elseif ($slot['slot_status'] === 'reserved') {
                $statusClass = 'slot-reserved';
                $icon = 'fa-clock-o';
                $statusLabel = 'Reserved';
            } elseif ($slot['slot_status'] === 'unavailable') {
                $statusClass = 'slot-unavailable';
                $icon = 'fa-ban';
                $statusLabel = 'Unavailable';
            }
            ?>
            <div class="parking-slot-box <?= $statusClass ?>"
                onclick="editSlot(<?= htmlspecialchars(json_encode($slot)) ?>)">
                <div class="slot-number"><?= $slot['slot_number'] ?></div>
                <div class="slot-icon"><i class="fa <?= $icon ?>"></i></div>
                <div class="slot-type">
                    <?php if ($slot['slot_type'] === 'two_wheeler'): ?>
                        <i class="fa fa-motorcycle"></i> Moto
                    <?php elseif ($slot['slot_type'] === 'standard'): ?>
                        <i class="fa fa-car"></i> Car
                    <?php else: ?>
                        <?= ucfirst($slot['slot_type']) ?>
                    <?php endif; ?>
                </div>

                <?php if ($slot['plate_number']): ?>
                    <div class="occupant-info">
                        <small>Plate:</small>
                        <strong><?= htmlspecialchars($slot['plate_number']) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4 mb-5">
            <ul class="pagination pagination-sm justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link"
                        href="?section=parking&page=1&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">First</a>
                </li>
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link"
                        href="?section=parking&page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a>
                </li>

                <!-- Window of 5 -->
                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                    ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link"
                            href="?section=parking&page=<?= $i ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link"
                        href="?section=parking&page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a>
                </li>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link"
                        href="?section=parking&page=<?= $totalPages ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Last</a>
                </li>
            </ul>

            <!-- Jump to Page -->
            <form action="" method="GET" class="form-inline justify-content-center mt-2">
                <input type="hidden" name="section" value="parking">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">

                <label class="mr-2 text-muted small">Jump to:</label>
                <input type="number" name="page" min="1" max="<?= $totalPages ?>"
                    class="form-control form-control-sm border-secondary" style="width: 70px;" placeholder="<?= $page ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary ml-1">Go</button>
            </form>
        </nav>
    <?php endif; ?>

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
                    <input type="hidden" name="add_slot" value="1">
                    <div class="form-group">
                        <label>Slot Number</label>
                        <input type="text" class="form-control" name="slot_number" required placeholder="e.g., A-001">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" name="slot_type" required>
                            <?php foreach ($availableTypes as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>">
                                    <?= ucfirst(str_replace('_', ' ', htmlspecialchars($t))) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (empty($availableTypes)): ?>
                                <option value="standard">Standard</option>
                                <option value="two_wheeler">Two Wheeler</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Slot</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Slot Modal (Improved UI) -->
<div class="modal fade" id="editSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Slot: <span id="modalResultSlotNumber"
                        class="font-weight-bold text-primary"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- NEW: Current Occupant Info Display -->
                <div id="current_occupant_info" style="display:none;" class="mb-3"></div>

                <form id="editSlotForm" method="POST">
                    <input type="hidden" name="update_slot" value="1">
                    <input type="hidden" name="slot_id" id="edit_slot_id">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Slot Number</label>
                            <input type="text" class="form-control" id="edit_slot_number" name="slot_number" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Vehicle Type</label>
                            <select class="form-control" id="edit_slot_type" name="slot_type" required>
                                <?php foreach ($availableTypes as $t): ?>
                                    <option value="<?= htmlspecialchars($t) ?>">
                                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($t))) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (empty($availableTypes)): ?>
                                    <option value="standard">Standard</option>
                                    <option value="two_wheeler">Two Wheeler</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Current Status</label>
                        <select class="form-control font-weight-bold" id="edit_slot_status" name="slot_status" required
                            onchange="toggleBookerSelection()">
                            <option value="available" class="text-success">🟢 Available</option>
                            <option value="reserved" class="text-warning">🟡 Reserved</option>
                            <option value="occupied" class="text-danger">🔴 Occupied</option>
                            <option value="unavailable" class="text-secondary">⚪ Unavailable (Maintenance)</option>
                        </select>
                    </div>

                    <!-- Booker Selection Field (Hidden by default) -->
                    <div class="form-group p-3 rounded border border-secondary" id="booker_selection_group"
                        style="display:none; background-color: #2c2f33;">
                        <label class="font-weight-bold text-light">🚗 Identify the Arriving Vehicle</label>
                        <select class="form-control bg-dark text-light border-secondary" id="winning_reservation_id"
                            name="winning_reservation_id">
                            <option value="">-- Autoselect (First Come) --</option>
                        </select>
                        <small class="form-text text-muted mt-2">
                            <i class="fa fa-info-circle"></i> Selecting a specific vehicle will mark it as "arrived" and
                            automatically <strong>cancel</strong> any other conflicting bookings for this slot.
                        </small>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-danger" onclick="deleteSlotConfirm()">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let originalSlotStatus = '';

    function editSlot(slot) {
        // Store original status
        originalSlotStatus = slot.slot_status;

        $('#edit_slot_id').val(slot.parking_slot_id);
        $('#edit_slot_number').val(slot.slot_number);
        $('#modalResultSlotNumber').text(slot.slot_number);
        $('#edit_slot_type').val(slot.slot_type);
        $('#edit_slot_status').val(slot.slot_status);

        // Display Current Occupant Info if Occupied
        if (slot.slot_status === 'occupied' || (slot.slot_status === 'reserved' && slot.owner_name)) {
            let statusBadge = slot.slot_status === 'occupied'
                ? '<span class="badge badge-danger">Occupied</span>'
                : '<span class="badge badge-warning">Reserved</span>';

            let html = `
                <div class="card border-left-info shadow-sm h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Current Occupant</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${slot.owner_name || 'Unknown User'}</div>
                                <div class="text-gray-800 mt-1"><i class="fa fa-car"></i> ${slot.plate_number || 'No Plate'}</div>
                                <div class="small text-muted mt-1"><i class="fa fa-clock-o"></i> ${slot.start_time} - ${slot.end_time}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fa fa-user-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#current_occupant_info').html(html).show();
        } else {
            $('#current_occupant_info').hide();
        }

        toggleBookerSelection(); // Reset visibility
        $('#editSlotModal').modal('show');
    }

    function toggleBookerSelection() {
        const status = document.getElementById('edit_slot_status').value;
        const group = document.getElementById('booker_selection_group');
        const slotId = document.getElementById('edit_slot_id').value;

        // Logic: Only show the selection if we are TRANSITIONING to a new state.
        let show = false;
        if (status === 'reserved' && originalSlotStatus !== 'reserved') {
            show = true;
        } else if (status === 'occupied' && originalSlotStatus !== 'occupied') {
            show = true;
        }

        if (show) {
            group.style.display = 'block';

            // Update Label based on status
            const label = group.querySelector('label');
            if (status === 'reserved') {
                label.textContent = '📝 Confirm a Pending Reservation';
                label.style.color = '#f6c23e'; // Warning color
            } else {
                label.textContent = '🚗 Identify the Arriving Vehicle';
                label.style.color = '#333';
            }

            // fetch bookers via AJAX
            fetch(`ajax/get_slot_reservations.php?slot_id=${slotId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('winning_reservation_id');
                    select.innerHTML = '<option value="">-- Autoselect (First Come) --</option>';
                    if (data.success && data.data.length > 0) {
                        data.data.forEach(res => {
                            // Filter logic: 
                            // If status is 'reserved', we prefer 'pending' ones.
                            // If status is 'occupied', we prefer 'confirmed' ones.
                            // But listing all is fine.
                            const statusIcon = res.status === 'pending' ? '⏳' : '✅';
                            const label = `${statusIcon} [${res.status.toUpperCase()}] ${res.first_name} ${res.last_name} (${res.plate_number})`;
                            const option = document.createElement('option');
                            option.value = res.reservation_id;
                            option.textContent = label;
                            select.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.textContent = "No pending/confirmed reservations found";
                        option.disabled = true;
                        select.appendChild(option);
                    }
                });
        } else {
            group.style.display = 'none';
        }
    }

    function deleteSlotConfirm() {
        const slotId = $('#edit_slot_id').val();
        if (confirm('Are you sure you want to delete this parking slot? This cannot be undone.')) {
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