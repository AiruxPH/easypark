<?php

$host = "srv1858.hstgr.io";
$user = "u130348899_randythegreat";
$pass = "RandyBOY999999@";
$db = "u130348899_easypark_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: ". $conn->connect_error);
}
?>