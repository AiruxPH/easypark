<?php
// staff/action_booking.php
session_start();
require_once '../includes/db.php';
require_once '../includes/notifications.php'; // Include the notification helper

// 1. Permission Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'staff' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: ../index.php");
    exit();
}

// 2. Validate Input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['action'])) {
    $r_id = intval($_POST['reservation_id']);
    $action = $_POST['action'];
    $staff_id = $_SESSION['user_id'];

    // Fetch reservation details to get user_id for notification
    $stmt = $pdo->prepare("SELECT user_id, parking_slot_id, description FROM reservations WHERE reservation_id = ?"); // description might not exist? check schema if needed. 
    // Actually just get user_id and slot_id.
    $stmt = $pdo->prepare("SELECT r.user_id, r.parking_slot_id, s.slot_number FROM reservations r JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id WHERE r.reservation_id = ?");
    $stmt->execute([$r_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        $u_id = $res['user_id'];
        $slot_id = $res['parking_slot_id'];
        $slot_num = $res['slot_number'];

        try {
            $pdo->beginTransaction();

            if ($action === 'confirm') {
                // Pending -> Confirmed
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ? AND status = 'pending'");
                $stmt->execute([$r_id]);

                if ($stmt->rowCount() > 0) {
                    sendNotification($pdo, $u_id, 'Reservation Confirmed', "Your booking for slot $slot_num (ID: $r_id) has been confirmed by staff.", 'success', 'bookings.php');
                    logActivity($pdo, $staff_id, 'staff', 'confirm_booking', "Confirmed booking #$r_id for slot $slot_num");
                }

            } elseif ($action === 'cancel') {
                // Pending/Confirmed -> Cancelled
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ? AND status IN ('pending', 'confirmed')");
                $stmt->execute([$r_id]);

                if ($stmt->rowCount() > 0) {
                    // Free up the slot
                    $stmt_free = $pdo->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?");
                    $stmt_free->execute([$slot_id]);

                    // Refund logic
                    $stmt_pay = $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE reservation_id = ?");
                    $stmt_pay->execute([$r_id]);

                    // Refund coins?
                    $stmt_amount = $pdo->prepare("SELECT amount FROM payments WHERE reservation_id = ? AND status = 'refunded' AND method = 'coins'");
                    $stmt_amount->execute([$r_id]);
                    $paid = $stmt_amount->fetchColumn();

                    $refundMsg = "";
                    if ($paid > 0) {
                        $pdo->prepare("UPDATE users SET coins = coins + ? WHERE user_id = ?")->execute([$paid, $u_id]);
                        $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, 'refund', 'Refund for Reservation #$r_id')")->execute([$u_id, $paid]);
                        $refundMsg = " (Refunded $paid coins)";
                    }

                    sendNotification($pdo, $u_id, 'Reservation Cancelled', "Your booking (ID: $r_id) has been cancelled by staff.", 'error', 'bookings.php');
                    logActivity($pdo, $staff_id, 'staff', 'cancel_booking', "Cancelled booking #$r_id$refundMsg");
                }

            } elseif ($action === 'accept') {
                // Confirmed -> Ongoing (Customer Arrived)
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'ongoing' WHERE reservation_id = ? AND status = 'confirmed'");
                $stmt->execute([$r_id]);

                if ($stmt->rowCount() > 0) {
                    sendNotification($pdo, $u_id, 'Parking Started', "You have checked in for slot $slot_num. Your timer has started.", 'info', 'bookings.php');
                    logActivity($pdo, $staff_id, 'staff', 'mark_arrived', "Marked booking #$r_id as arrived (ongoing)");
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            // error_log($e->getMessage()); 
        }
    }
}

// 3. Redirect Back
header("Location: staff-dashboard.php");
exit();
?>