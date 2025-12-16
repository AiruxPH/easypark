<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

require_once '../includes/db.php';
$staff_id = $_SESSION['user_id'];

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update Profile Details
    if ($action === 'update_details') {
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (!$first_name || !$last_name) {
            $response['message'] = 'First and Last name are required.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE user_id = ?');
                $stmt->execute([$first_name, $middle_name, $last_name, $phone, $staff_id]);
                $response = ['success' => true, 'message' => 'Profile details updated successfully.'];
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
        }
    }

    // 2. Change Password
    elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_new_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $response['message'] = 'Please fill all password fields.';
        } elseif ($new !== $confirm) {
            $response['message'] = 'New passwords do not match.';
        } else {
            // Verify current
            $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
            $stmt->execute([$staff_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($current, $user['password'])) {
                // Update
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                $stmt->execute([$hashed, $staff_id]);
                $response = ['success' => true, 'message' => 'Password changed successfully.'];
            } else {
                $response['message'] = 'Current password is incorrect.';
            }
        }
    }

    // 3. Upload Picture
    elseif ($action === 'upload_pic') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['profile_pic']['tmp_name'];
            $fileName = basename($_FILES['profile_pic']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExt, $allowed)) {
                $newName = 'profile_staff_' . $staff_id . '_' . time() . '.' . $fileExt;
                $targetPath = '../images/' . $newName;

                // Delete old image first
                $stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
                $stmt->execute([$staff_id]);
                $oldImg = $stmt->fetchColumn();

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    if ($oldImg && $oldImg !== 'default.jpg' && file_exists('../images/' . $oldImg)) {
                        unlink('../images/' . $oldImg);
                    }

                    $stmt = $pdo->prepare('UPDATE users SET image = ? WHERE user_id = ?');
                    $stmt->execute([$newName, $staff_id]);

                    $response = [
                        'success' => true,
                        'message' => 'Profile picture updated.',
                        'new_image_url' => '../images/' . $newName
                    ];
                } else {
                    $response['message'] = 'Failed to move uploaded file.';
                }
            } else {
                $response['message'] = 'Invalid file type. Allowed: jpg, png, gif, webp.';
            }
        } else {
            $response['message'] = 'No file uploaded or upload error.';
        }
    }

    // 4. Delete Picture
    elseif ($action === 'delete_pic') {
        $stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
        $stmt->execute([$staff_id]);
        $oldImg = $stmt->fetchColumn();

        if ($oldImg && $oldImg !== 'default.jpg' && file_exists('../images/' . $oldImg)) {
            unlink('../images/' . $oldImg);
        }

        $stmt = $pdo->prepare('UPDATE users SET image = NULL WHERE user_id = ?');
        $stmt->execute([$staff_id]);

        $response = ['success' => true, 'message' => 'Profile picture removed.', 'new_image_url' => '../images/default.jpg'];
    }
}

echo json_encode($response);
