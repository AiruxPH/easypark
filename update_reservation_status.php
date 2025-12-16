<?php
// update_reservation_status.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reservation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}
require_once 'includes/db.php';
require_once 'includes/constants.php';
$reservation_id = intval($_POST['reservation_id']);
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'cancel') {
    // Allow cancel if status is pending or confirmed
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->execute([$reservation_id]);
    if ($stmt->rowCount() > 0) {
        // Free up the slot if no other active reservation exists for it
        $stmt = $pdo->prepare("SELECT parking_slot_id FROM reservations WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
        $slot_id = $stmt->fetchColumn();
        if ($slot_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE parking_slot_id = ? AND status IN ('pending', 'confirmed', 'ongoing') AND reservation_id != ?");
            $stmt->execute([$slot_id, $reservation_id]);
            $active_count = $stmt->fetchColumn();
            if ($active_count == 0) {
                $stmt = $pdo->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?");
                $stmt->execute([$slot_id]);
            }
        }

        // Notify User
        require_once 'includes/notifications.php';
        // Get user_id of the reservation
        $stmt = $pdo->prepare("SELECT user_id FROM reservations WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);
        $r_user_id = $stmt->fetchColumn();
        if ($r_user_id) {
            sendNotification($pdo, $r_user_id, 'Booking Cancelled', 'Your reservation (ID: ' . $reservation_id . ') has been cancelled.', 'info', 'bookings.php');
        }

        echo json_encode(['success' => true, 'message' => 'Booking cancelled.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to cancel booking.']);
    }
    exit();
} elseif ($action === 'complete') {
    // Modify: Check for overstay and deduct coins
    $stmt = $pdo->prepare("SELECT user_id, end_time, parking_slot_id FROM reservations WHERE reservation_id = ? AND status = 'ongoing'");
    $stmt->execute([$reservation_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        $user_id = $res['user_id'];
        $end_time = $res['end_time'];
        $slot_id = $res['parking_slot_id'];

        $now = new DateTime();
        $end = new DateTime($end_time);

        // Calculate penalty if overstayed
        if ($now > $end) {
            // Get slot type to find rate
            $stmtRate = $pdo->prepare("SELECT slot_type FROM parking_slots WHERE parking_slot_id = ?");
            $stmtRate->execute([$slot_id]);
            $s_type = $stmtRate->fetchColumn();

            $rate = 0;
            if ($s_type && defined('SLOT_RATES') && isset(SLOT_RATES[$s_type]['hour'])) {
                $rate = SLOT_RATES[$s_type]['hour'];
            }

            // Calculate hours (ceil)
            $diffSeconds = $now->getTimestamp() - $end->getTimestamp();
            $overhours = ceil($diffSeconds / 3600);

            // DIFFERENTIAL BILLING LOGIC:
            // 1. Get Base Price
            $stmt_base = $pdo->prepare("SELECT amount FROM payments WHERE reservation_id = ? ORDER BY payment_date ASC LIMIT 1");
            $stmt_base->execute([$reservation_id]);
            $base_price = floatval($stmt_base->fetchColumn());

            // 2. Get Total Paid
            $stmt_total = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE reservation_id = ?");
            $stmt_total->execute([$reservation_id]);
            $total_paid = floatval($stmt_total->fetchColumn());

            // 3. Required Total
            $required_total = $base_price + ($overhours * $rate);

            $to_charge = $required_total - $total_paid;

            if ($to_charge > 0) {
                // Deduct coins (allow debt)
                $pdo->prepare("UPDATE users SET coins = coins - ? WHERE user_id = ?")->execute([$to_charge, $user_id]);
                // Log transaction
                $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, 'payment', 'Overstay Penalty (Manual Completion)')")->execute([$user_id, -$to_charge]);
                // Record the penalty payment as new row
                $pdo->prepare("INSERT INTO payments (reservation_id, user_id, amount, status, method, payment_date) VALUES (?, ?, ?, 'successful', 'coins', NOW())")->execute([$reservation_id, $user_id, $to_charge]);
            }
        }

        // Proceed to complete
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ?");
        $stmt->execute([$reservation_id]);

        // Free up slot logic
        if ($slot_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE parking_slot_id = ? AND status IN ('pending', 'confirmed', 'ongoing') AND reservation_id != ?");
            $stmt->execute([$slot_id, $reservation_id]);
            $active_count = $stmt->fetchColumn();
            if ($active_count == 0) {
                $stmt = $pdo->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?");
                $stmt->execute([$slot_id]);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Booking completed. Overstay fees applied if applicable.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to complete booking or invalid status.']);
    }
    exit();
} else {
    // Fallback: original logic (for timer expiry)
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ? AND status IN ('confirmed', 'ongoing') AND end_time <= NOW()");
    $stmt->execute([$reservation_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No update made']);
    }
    exit();
}
