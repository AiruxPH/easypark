<?php
// staff/action_booking.php
session_start();
require_once '../includes/db.php';
require_once '../includes/notifications.php'; // Include the notification helper
require_once '../includes/functions.php'; // Include general functions (logActivity)

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
                // 1. Race Condition Check: Ensure no other Confirmed/Ongoing overlap exists
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE                         parking_slot_id = ? 
                        AND status IN ('confirmed', 'ongoing') 
                        AND reservation_id != ?
                        AND (
                            (start_time < (SELECT end_time FROM reservations WHERE reservation_id = ?) AND end_time > (SELECT start_time FROM reservations WHERE reservation_id = ?))
                        )");
                $checkStmt->execute([$slot_id, $r_id, $r_id, $r_id]);

                if ($checkStmt->fetchColumn() > 0) {
                    // Conflict detected!
                    // We could redirect with error, but for now let's just do nothing or log.
                    // Ideally, return a specific error flag.
                    $_SESSION['error_msg'] = "Failed to confirm: Slot is already booked for this time range.";
                } else {
                    // 2. Pending -> Confirmed
                    $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ? AND status = 'pending'");
                    $stmt->execute([$r_id]);

                    if ($stmt->rowCount() > 0) {
                        // LOCK THE SLOT
                        $pdo->prepare("UPDATE parking_slots SET slot_status = 'reserved' WHERE parking_slot_id = ?")->execute([$slot_id]);
                        sendNotification($pdo, $u_id, 'Reservation Confirmed', "Your booking for slot $slot_num (ID: $r_id) has been confirmed by staff.", 'success', 'bookings.php');
                        logActivity($pdo, $staff_id, 'staff', 'confirm_booking', "Confirmed booking #$r_id for slot $slot_num");

                        // 3. Auto-Cancel Conflicting Pending Reservations
                        // Fetch details of current reservation for overlap check
                        $timeStmt = $pdo->prepare("SELECT start_time, end_time FROM reservations WHERE reservation_id = ?");
                        $timeStmt->execute([$r_id]);
                        $currentRes = $timeStmt->fetch(PDO::FETCH_ASSOC);

                        if ($currentRes) {
                            $start = $currentRes['start_time'];
                            $end = $currentRes['end_time'];

                            // Find conflicting pending
                            $conflictStmt = $pdo->prepare("SELECT reservation_id, user_id FROM reservations WHERE                                     parking_slot_id = ? 
                                    AND status = 'pending' 
                                    AND reservation_id != ?
                                    AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))");
                            $conflictStmt->execute([$slot_id, $r_id, $end, $start, $end, $start, $start, $end]);
                            $conflicts = $conflictStmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($conflicts as $conflict) {
                                $c_id = $conflict['reservation_id'];
                                $c_uid = $conflict['user_id'];

                                // Cancel it
                                $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?")->execute([$c_id]);

                                // Refund logic if needed (Assuming pending didn't pay yet, or hold usage)
                                // Add refund logic here identical to 'cancel' block if they paid in advance. 
                                // For now assuming pending hasn't charged wallet fully or needs refund? 
                                // Standard logic usually charges on creation, so YES refund needed.

                                $stmt_pay = $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE reservation_id = ?");
                                $stmt_pay->execute([$c_id]);

                                // Check coins paid
                                $stmt_amount = $pdo->prepare("SELECT amount FROM payments WHERE reservation_id = ? AND status = 'refunded' AND method = 'coins'");
                                $stmt_amount->execute([$c_id]);
                                $paid = $stmt_amount->fetchColumn();

                                $refundMsg = "";
                                if ($paid > 0) {
                                    $pdo->prepare("UPDATE users SET coins = coins + ? WHERE user_id = ?")->execute([$paid, $c_uid]);
                                    $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, 'refund', 'Refund for Auto-Cancelled Res #$c_id')")->execute([$c_uid, $paid]);
                                    $refundMsg = " (Refunded $paid coins)";
                                }

                                sendNotification($pdo, $c_uid, 'Reservation Cancelled', "Your pending booking (ID: $c_id) was cancelled because another user was confirmed for this slot.", 'error', 'bookings.php');
                                logActivity($pdo, $staff_id, 'system', 'auto_cancel', "Auto-cancelled booking #$c_id due to conflict with #$r_id$refundMsg");
                            }
                        }
                    }
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
                    // MARK AS OCCUPIED
                    $pdo->prepare("UPDATE parking_slots SET slot_status = 'occupied' WHERE parking_slot_id = ?")->execute([$slot_id]);
                    sendNotification($pdo, $u_id, 'Parking Started', "You have checked in for slot $slot_num. Your timer has started.", 'info', 'bookings.php');
                    logActivity($pdo, $staff_id, 'staff', 'mark_arrived', "Marked booking #$r_id as arrived (ongoing)");
                }

            } elseif ($action === 'complete') {
                // Ongoing -> Completed (Customer Leaving)
                // 1. Check for Overstay
                $stmt = $pdo->prepare("SELECT end_time, slot_type FROM reservations r JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id WHERE reservation_id = ? AND status = 'ongoing'");
                $stmt->execute([$r_id]);
                $resData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($resData) {
                    $end = new DateTime($resData['end_time']);
                    $now = new DateTime();

                    if ($now > $end) {
                        // Calculate Penalty
                        $diffSeconds = $now->getTimestamp() - $end->getTimestamp();
                        $overhours = ceil($diffSeconds / 3600);

                        $s_type = $resData['slot_type'];
                        $rate = 0;
                        if (defined('SLOT_RATES') && isset(SLOT_RATES[$s_type]['hour'])) {
                            $rate = SLOT_RATES[$s_type]['hour'];
                        }

                        // Determine Base Price (First payment)
                        $stmt_base = $pdo->prepare("SELECT amount FROM payments WHERE reservation_id = ? ORDER BY payment_date ASC LIMIT 1");
                        $stmt_base->execute([$r_id]);
                        $base_price = floatval($stmt_base->fetchColumn());

                        // Total Paid
                        $stmt_total = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE reservation_id = ?");
                        $stmt_total->execute([$r_id]);
                        $total_paid = floatval($stmt_total->fetchColumn());

                        // Required Total
                        $required_total = $base_price + ($overhours * $rate);
                        $to_charge = $required_total - $total_paid;

                        if ($to_charge > 0) {
                            // Deduct coins
                            $pdo->prepare("UPDATE users SET coins = coins - ? WHERE user_id = ?")->execute([$to_charge, $u_id]);
                            // Log transaction
                            $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, 'payment', 'Overstay Penalty (Staff Completion)')")->execute([$u_id, -$to_charge]);
                            // Record Payment
                            $pdo->prepare("INSERT INTO payments (reservation_id, user_id, amount, status, method, payment_date) VALUES (?, ?, ?, 'successful', 'coins', NOW())")->execute([$r_id, $u_id, $to_charge]);

                            // Notify User
                            sendNotification($pdo, $u_id, 'Overstay Penalty', "An overstay penalty of " . number_format($to_charge, 2) . " coins has been deducted for Reservation #$r_id.", 'warning', 'bookings.php');
                        }
                    }

                    // 2. Mark as Completed
                    $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ?");
                    $stmt->execute([$r_id]);

                    if ($stmt->rowCount() > 0) {
                        // 3. Free up slot (if no future overlap immediately active? Logic simplifies to 'available' if count=0)
                        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE parking_slot_id = ? AND status IN ('pending', 'confirmed', 'ongoing') AND reservation_id != ?");
                        $stmtCount->execute([$slot_id, $r_id]);
                        if ($stmtCount->fetchColumn() == 0) {
                            $pdo->prepare("UPDATE parking_slots SET slot_status = 'available' WHERE parking_slot_id = ?")->execute([$slot_id]);
                        }

                        sendNotification($pdo, $u_id, 'Parking Completed', "Your booking for slot $slot_num is now complete. Thank you!", 'success', 'bookings.php');
                        logActivity($pdo, $staff_id, 'staff', 'mark_completed', "Marked booking #$r_id as completed");
                    }
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "System Error: " . $e->getMessage();
        }
    }
}

// 3. Redirect Back
header("Location: staff-dashboard.php");
exit();
?>