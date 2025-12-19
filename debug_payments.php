<?php
// debug_payments.php
require_once 'includes/db.php';
ini_set('display_errors', 1);

echo "<h1>Database Diagnostic Tool</h1>";
echo "<a href='bookings.php'>Back to Bookings</a><hr>";

// 1. Get last 5 extended reservations (or just recent ones)
$sql = "SELECT * FROM reservations ORDER BY start_time DESC LIMIT 5";
$stmt = $pdo->query($sql);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($reservations as $r) {
    echo "<h3>Reservation #{$r['reservation_id']} ({$r['status']})</h3>";
    echo "<p>User ID: {$r['user_id']} | End Time: {$r['end_time']}</p>";

    // Check PAYMENTS table
    echo "<h4>Table: `payments` (WHERE reservation_id = {$r['reservation_id']})</h4>";
    $stmtP = $pdo->prepare("SELECT * FROM payments WHERE reservation_id = ?");
    $stmtP->execute([$r['reservation_id']]);
    $payments = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    if (count($payments) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%'>";
        echo "<tr style='background:#f0f0f0'><th>ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>";
        foreach ($payments as $p) {
            echo "<tr>";
            echo "<td>{$p['payment_id']}</td>";
            echo "<td>{$p['amount']}</td>";
            echo "<td>{$p['method']}</td>";
            echo "<td>{$p['status']}</td>";
            echo "<td>{$p['payment_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>No rows in `payments` table!</p>";
    }

    // Check COIN_TRANSACTIONS table
    echo "<h4>Table: `coin_transactions` (Matching User & Description)</h4>";
    $stmtC = $pdo->prepare("SELECT * FROM coin_transactions WHERE user_id = ? AND description LIKE ?");
    $stmtC->execute([$r['user_id'], "%{$r['reservation_id']}%"]);
    $trans = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    if (count($trans) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%'>";
        echo "<tr style='background:#e0f0ff'><th>ID</th><th>Type</th><th>Amount</th><th>Description</th><th>Date</th></tr>";
        foreach ($trans as $t) {
            echo "<tr>";
            echo "<td>{$t['transaction_id']}</td>";
            echo "<td>{$t['transaction_type']}</td>";
            echo "<td>{$t['amount']}</td>";
            echo "<td>{$t['description']}</td>";
            echo "<td>{$t['transaction_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No matching coin transactions.</p>";
    }
    echo "<hr>";
}
?>