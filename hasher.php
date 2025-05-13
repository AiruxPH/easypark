<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasher</title>
</head>
<body>
    <form action="hasher.php" method = "POST">
        <input type="text" name="password" placeholder="Enter Password">
        <input type="submit" value="Submit">

    </form>


</body>
</html>

<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Please submit the form.');
}else
{
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        die('Please enter a password.');
    } else {
        $password = $_POST['password'];
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<h1>Hashed Password</h1>";
    echo "<p>Original Password: <strong>" . htmlspecialchars($password) . "</strong></p>";
    echo "<p>Hashed Password: <strong>" . htmlspecialchars($hashed) . "</strong></p>";
    echo "<p>Hash Algorithm: <strong>" . htmlspecialchars(password_get_info($hashed)['algoName']) . "</strong></p>";
    echo "<p>Hash Options: <strong>" . htmlspecialchars(json_encode(password_get_info($hashed)['options'])) . "</strong></p>";
    echo "<p><a href='hasher.php'>Go Back</a></p>"; 
    }
    
}
// index.php
?>