<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "login_crud";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set Timezone
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");
?>