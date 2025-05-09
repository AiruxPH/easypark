<?php
    include 'db.php';
    include 'functions.php';

    $username = trim($_POST['username']);

    if (empty($username) || empty($_POST['password'])) {
        header("Location: register.php?error=empty");
        exit();
    }

    $password = hash('sha256', $_POST['password']);

    

    $sql_check = "SELECT user_id FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: register.php?error=1");
        exit();
    }
    $stmt->close();

    $sql_insert = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("ss", $username, $password);

    if($stmt->execute() === TRUE) {

        
            $user_id = $conn->insert_id; // ID of the new user
            $username = $_POST['username'];
            $message = "New user registered: $username";
            $visibility = 'admin'; // or 'staff' or 'all'
        
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, visibility) VALUES (?, ?, ?)");

$notif_stmt->bind_param("iss", $user_id, $message, $vis1);
$vis1 = 'admin';
$notif_stmt->execute();

$notif_stmt->bind_param("iss", $user_id, $message, $vis2);
$vis2 = 'staff';
$notif_stmt->execute();

        
        


        header("Location: register.php?success=1");
    } else {
        header("Location: register.php?error=1");
    }
    exit();
?>