<?php
include 'db.php'; // use correct path here

$now = date('Y-m-d H:i:s');

// Expire pending and confirmed
$sql1 = "UPDATE reservations SET status = 'expired' WHERE end_time < :now AND status IN ('pending', 'confirmed')";
// Complete ongoing
$sql2 = "UPDATE reservations SET status = 'completed' WHERE end_time < :now AND status = 'ongoing'";

try {
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute(['now' => $now]);

    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute(['now' => $now]);

    echo "Cron job ran successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
