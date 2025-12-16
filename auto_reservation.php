<?php
include 'includes/db.php';
include 'includes/constants.php';

$logFile = __DIR__ . '/cron_log.txt';
$log = "[" . date('Y-m-d H:i:s') . "] Cron job started.\n";

try {
    $now = date('Y-m-d H:i:s');

    // Cancel no-show reservations
    $stmt_cancel = $pdo->prepare("
        UPDATE reservations
        SET status = 'cancelled'
        WHERE start_time < :now AND status = 'pending'
    ");
    $stmt_cancel->execute(['now' => $now]);
    $log .= "Cancelled reservations: " . $stmt_cancel->rowCount() . "\n";

    // Expire outdated reservations
    $stmt_expire = $pdo->prepare("
        UPDATE reservations
        SET status = 'expired'
        WHERE end_time < :now AND status IN ('pending', 'confirmed')
    ");
    $stmt_expire->execute(['now' => $now]);
    $log .= "Expired reservations: " . $stmt_expire->rowCount() . "\n";

    // Complete finished ongoing reservations
    // DISABLED: To allow "Live Overstay" charging, we do NOT auto-complete ongoing.
    // Use manual completion or a separate overly-long expiry check.
    // Pay-as-you-go: Calculate and deduct overstay penalties for ongoing reservations
    $stmt_overstay = $pdo->prepare("
        SELECT r.reservation_id, r.user_id, r.end_time, r.parking_slot_id, p.amount as paid_amount, s.slot_type 
        FROM reservations r
        JOIN payments p ON r.reservation_id = p.reservation_id
        JOIN parking_slots s ON r.parking_slot_id = s.parking_slot_id
        WHERE r.status = 'ongoing' AND r.end_time < :now
    ");
    $stmt_overstay->execute(['now' => $now]);
    $overstays = $stmt_overstay->fetchAll(PDO::FETCH_ASSOC);

    foreach ($overstays as $row) {
        $r_id = $row['reservation_id'];
        $u_id = $row['user_id'];
        $paid = floatval($row['paid_amount']);
        $end_ts = strtotime($row['end_time']);
        $now_ts = strtotime($now);
        $s_type = $row['slot_type'];

        // Calculate total overstay duration
        $diff = $now_ts - $end_ts;
        if ($diff > 0) {
            $overhours = ceil($diff / 3600);

            // Get rate
            $rate = 0;
            if (defined('SLOT_RATES') && isset(SLOT_RATES[$s_type]['hour'])) {
                $rate = SLOT_RATES[$s_type]['hour'];
            }

            // Determine base cost (assuming everything paid so far IS the base + prev penalty)
            // But we can just use the logic: Total Owed = Base + Current Penalty.
            // Problem: We don't know the Base Cost easily without recalculating duration. 
            // Better approach: Calculate incremental penalty.

            // Actually, safest is: 
            // Total Penalty Owed = overhours * rate.
            // Previous Penalty Paid = (Current Total Paid - Original Base Cost).
            // But we don't have Original Base clearly separate.

            // Alternative "Differential" Logic:
            // Calculate what SHOULD be the total amount now.
            // We need to know original duration cost.
            // Let's assume the user paid correctly for the initial duration.
            // So any extra above that is penalty.

            // Let's do this: Store the "last charged hour" or just rely on total amount.
            // If we assume `paid_amount` is accurate:
            // New Total should be: Initial_Cost + (OverHours * Rate).
            // But we don't know Initial_Cost easily in this loop query.

            // Simplified Approach:
            // Just charge for the *current* hour if not yet charged.
            // Too risky.

            // "Pay-as-you-go" Logic utilizing 'payments.amount' as the tracker:
            // 1. Calculate the TOTAL expected cost for the *entire* duration (start to NOW).
            //    Start Time needed.

            // Let's fetch start_time too.
            $stmt_details = $pdo->prepare("SELECT start_time FROM reservations WHERE reservation_id = ?");
            $stmt_details->execute([$r_id]);
            $start_time = $stmt_details->fetchColumn();
            $start_ts = strtotime($start_time);

            // Total duration in hours (ceil) from Start to Now
            $total_duration_hours = ceil(($now_ts - $start_ts) / 3600);

            // Calculate Total Expected Cost
            // Note: This assumes simplified Hourly pricing for everything.
            // If original booking was Daily, this might overcharge if we switch to Hourly.
            // BUT, for Overstay, we usually just add hourly penalty on top of original.

            // Let's stick to: Penalty = (Now - End) * Rate.
            // Total Expected = Original_Payment + Penalty.
            // But we don't know "Original_Payment". `paid_amount` includes previous penalties.

            // WORKAROUND: We assume the user paid the correct amount for the initial duration.
            // We calculate initial duration cost? 
            // No, user might have used a 'Day' rate which is cheaper.

            // BEST APPROACH:
            // We calculate the *Overstay* cost strictly.
            // Total Overstay Cost = $overhours * $rate.
            // We need to know how much *Penalty* has effectively been paid.
            // We can't know that without a separate column.

            // TRICK:
            // We can check if we charged for this specific hour? No.

            // OK, let's use a standard "Total Time" calculation if we assume hourly rates for overstay.
            // Let's rely on the difference.
            // Current Total Paid vs (Original Cost + Current Penalty).
            // We need Original Cost.
            // Let's re-calculate Original Cost based on `duration` stored in reservation?
            // `duration` column stores the count (e.g. 2). `duration_type`? Not stored in `reservations` table (based on schema).
            // Wait, schema check... `reservations` has `duration` (int). `start_time`, `end_time`.
            // We can derive original duration = (end - start).

            $orig_duration_sec = $end_ts - $start_ts;
            // This is the planned duration.

            // We can't re-calculate original price perfectly if we don't know if they used Day or Hour rate.
            // BUT, `payments` table has the initial amount.
            // If we only UPDATE `payments.amount`, it grows.

            // HACK:
            // We will store the "Last Updated Overstay Hour" in a separate place? No schema change allowed.
            // We can use `updated_at` timestamp?

            // Let's use the logic:
            // 1. Calculate `Target Penalty` = `overhours * rate`.
            // 2. We need `Current Penalty Paid`.
            //    `Current Penalty Paid` = `Total Paid` - `Original Price`.
            //    `Original Price` is NOT stored separately.

            // OK, CRITICAL: We need a way to know the Base Price.
            // We can infer it: `Total Paid` - `(Previous Overhours * Rate)`. This is circular.

            // ALTERNATIVE:
            // Valid "Pay-as-you-go" strategy without schema change:
            // Calculate cost for the *current timestamp*.
            // Compare with `paid_amount`.
            // IF (Rate * Total_Hours_Parked) > Paid_Amount? 
            // This forces "Hourly Rate" on the whole session, potentially negating "Daily Rate" savings.
            // User might reject.

            // DECISION:
            // To properly support "Pay-as-you-go" without schema change, we MUST assume:
            // The `amount` in `payments` is the Source of Truth.
            // We calculate: `New_Total` = `Original_Amount` + `Penalty`.
            // Problem: We lose `Original_Amount` once we update `payments.amount`.

            // SOLUTION:
            // We do NOT update `payments.amount` in the Cron Job? No, we must to track debt.
            // We need to store `base_amount`.
            // I will use a clever trick:
            // I will NOT update `payments.amount` for the *penalty* in the cron.
            // I will insert a NEW correction? No.

            // OK, I'll use the `reservations` table logic.
            // `duration` is there.
            // If I can't reliably get Base Price, I can't reliably do differential billing.

            // WAIT! `payments` table has `amount`.
            // Can I add a new row to `payments` for each penalty?
            // "INSERT INTO payments ... method='wallet_penalty'".
            // Then `Total Paid` = SUM(payments where res_id = X).
            // This preserves the initial payment record!
            // YES! This is the solution.

            // PLAN REVISED:
            // 1. Get `SUM(amount)` for this reservation.
            // 2. Calculate `Total Cost` = `Base Price` + `Total Overstay Penalty`.
            //    Using `Base Price` from the *First* payment record (assuming it's the base).
            // 3. Difference = `Total Cost` - `Sum Paid`.
            // 4. If Diff > 0, Insert NEW payment record for that amount (The penalty).
            // 5. Deduct from User Coins.

            // Determine Base Price:
            // Select amount FROM payments WHERE reservation_id = ? ORDER BY payment_date ASC LIMIT 1.
            $stmt_base = $pdo->prepare("SELECT amount FROM payments WHERE reservation_id = ? ORDER BY payment_date ASC LIMIT 1");
            $stmt_base->execute([$r_id]);
            $base_price = floatval($stmt_base->fetchColumn());

            // Get Total Paid
            $stmt_total = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE reservation_id = ?");
            $stmt_total->execute([$r_id]);
            $total_paid = floatval($stmt_total->fetchColumn());

            // Calculate Required Total
            $required_total = $base_price + ($overhours * $rate);

            $to_charge = $required_total - $total_paid;

            if ($to_charge > 0) {
                // Charge the user
                $pdo->prepare("UPDATE users SET coins = coins - ? WHERE user_id = ?")->execute([$to_charge, $u_id]);

                // Log transaction
                $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, 'payment', 'Overstay Penalty')")->execute([$u_id, -$to_charge]);

                // Record the penalty payment
                $pdo->prepare("INSERT INTO payments (reservation_id, user_id, amount, status, method, payment_date) VALUES (?, ?, ?, 'successful', 'coins', NOW())")->execute([$r_id, $u_id, $to_charge]);

                $log .= "Charged overstay penalty: $to_charge for Res ID: $r_id\n";
            }
        }
    }

    // Update payments: refunded
    $stmt_refund = $pdo->prepare("
        UPDATE payments
        SET status = 'refunded'
        WHERE reservation_id IN (
            SELECT reservation_id FROM reservations
            WHERE status IN ('cancelled', 'expired')
        ) AND status != 'refunded'
    ");
    $stmt_refund->execute();
    $log .= "Refunded payments: " . $stmt_refund->rowCount() . "\n";

    // Update payments: successful
    $stmt_success = $pdo->prepare("
        UPDATE payments
        SET status = 'successful'
        WHERE reservation_id IN (
            SELECT reservation_id FROM reservations
            WHERE status = 'completed'
        ) AND status != 'successful'
    ");
    $stmt_success->execute();
    $log .= "Successful payments: " . $stmt_success->rowCount() . "\n";

    // Mark slots with active reservations as 'reserved'
    $stmt_reserve = $pdo->prepare("
        UPDATE parking_slots
        SET slot_status = 'reserved'
        WHERE parking_slot_id IN (
            SELECT parking_slot_id
            FROM reservations
            WHERE start_time <= :now AND end_time > :now
            AND status IN ('confirmed', 'ongoing')
        ) AND slot_status = 'available'
    ");
    $stmt_reserve->execute(['now' => $now]);
    $log .= "Reserved slots: " . $stmt_reserve->rowCount() . "\n";

    // Free up parking slots (Excluding those that are currently active)
    $stmt_free = $pdo->prepare("
        UPDATE parking_slots
        SET slot_status = 'available'
        WHERE parking_slot_id IN (
            SELECT r.parking_slot_id
            FROM reservations r
            WHERE r.end_time < :now
              AND r.status IN ('expired', 'completed', 'cancelled')
        )
        AND parking_slot_id NOT IN (
            SELECT parking_slot_id FROM reservations
            WHERE start_time <= :now AND end_time > :now
            AND status IN ('confirmed', 'ongoing')
        )
    ");
    $stmt_free->execute(['now' => $now]);
    $log .= "Freed parking slots: " . $stmt_free->rowCount() . "\n";

    $log .= "Cron job finished successfully.\n\n";
} catch (PDOException $e) {
    $log .= "Error: " . $e->getMessage() . "\n\n";
}

// Write to log file
file_put_contents($logFile, $log, FILE_APPEND);
?>