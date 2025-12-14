<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>test</title>
</head>

<body>
    <form method="POST" action="reservation_process.php">
        <label>Slot Type:</label>
        <select name="slot_type">
            <option value="two_wheeler">Two-Wheeler</option>
            <option value="compact">Compact</option>
            <option value="standard">Standard</option>
            <option value="large">Large</option>
        </select>

        <label>Duration Type:</label>
        <select name="duration">
            <option value="hour">Per Hour</option>
            <option value="day">Per Day</option>
        </select>

        <label>Value (number of hours/days):</label>
        <input type="number" name="value" min="1" required>

        <button type="submit">Calculate</button>
    </form>
</body>

</html>

<?php

$slot_type = $_POST['slot_type'];      // 'compact'
$duration_type = $_POST['duration'];   // 'hour' or 'day'
$duration_value = $_POST['value'];     // 5 (e.g. 5 hours or 5 days)

require_once 'includes/constants.php'; // Load the pricing config

// Validate input
if (!isset(SLOT_RATES[$slot_type][$duration_type])) {
    die("Invalid slot type or duration.");
}

$rate = SLOT_RATES[$slot_type][$duration_type];
$total_price = $rate * $duration_value;

echo "Rate for {$slot_type} per {$duration_type}: ₱" . number_format($rate, 2) . "<br>";
echo "Total for {$duration_value} {$duration_type}(s): ₱" . number_format($total_price, 2);


?>