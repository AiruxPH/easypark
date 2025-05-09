<?php
// Basic MySQL connection
$conn = new mysqli("localhost", "root", "", "testdb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the action type from POST
$action = $_POST['action'] ?? null;

switch ($action) {
    case "1":
        insertUser($conn);
        break;
    case "2":
        updateUser($conn);
        break;
    case "3":
        deleteUser($conn);
        break;
    case "4":
        viewUsers($conn);
        break;
    default:
        echo "Invalid or no action.";
}

// === CRUD Operations ===

function insertUser($conn) {
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');

    if ($name && $email) {
        $sql = "INSERT INTO users (name, email) VALUES ('$name', '$email')";
        if ($conn->query($sql)) {
            echo "User inserted.";
        } else {
            echo "Insert failed: " . $conn->error;
        }
    } else {
        echo "Name and email required.";
    }
}

function updateUser($conn) {
    $id = intval($_POST['id'] ?? 0);
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');

    if ($id && $name && $email) {
        $sql = "UPDATE users SET name='$name', email='$email' WHERE id=$id";
        if ($conn->query($sql)) {
            echo "User updated.";
        } else {
            echo "Update failed: " . $conn->error;
        }
    } else {
        echo "All fields required for update.";
    }
}

function deleteUser($conn) {
    $id = intval($_POST['id'] ?? 0);

    if ($id) {
        $sql = "DELETE FROM users WHERE id=$id";
        if ($conn->query($sql)) {
            echo "User deleted.";
        } else {
            echo "Delete failed: " . $conn->error;
        }
    } else {
        echo "ID required.";
    }
}

function viewUsers($conn) {
    $sql = "SELECT * FROM users";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: {$row['id']} | Name: {$row['name']} | Email: {$row['email']}<br>";
        }
    } else {
        echo "No users found.";
    }
}

$conn->close();
?>
