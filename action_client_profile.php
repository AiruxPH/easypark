<?php
// action_client_profile.php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php'; // for logActivity

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine action. Check specific keys or a dedicated 'action' key.
    // For consistency with typical AJAX, let's look for 'action' key, 
    // but the existing forms might just have specific submit buttons.
    // We will update frontend to send 'action'.

    $action = $_POST['action'] ?? '';

    // 1. Update Profile Details
    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (!$first_name || !$last_name) {
            $response['message'] = 'First and Last name are required.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, phone = ? WHERE user_id = ?');
                $stmt->execute([$first_name, $middle_name, $last_name, $phone, $user_id]);

                // Update session name if changed
                $_SESSION['username'] = $first_name;

                logActivity($pdo, $user_id, 'client', 'update_profile', "Updated profile details");
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
            // Verify current - fetching plain text for now based on existing legacy code, 
            // BUT standard is password_verify. 
            // Checking existing code: "if ($row && $current === $row['password'])" -> It uses PLAIN TEXT currently.
            // We should stick to existing pattern OR upgrade it. 
            // The prompt didn't ask to fix security, but we should be careful.
            // Let's stick to the behavior found in profile.php to avoid breaking if they are indeed plain text.

            $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $current === $user['password']) {
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                $stmt->execute([$new, $user_id]);
                logActivity($pdo, $user_id, 'client', 'change_password', "Changed account password");
                $response = ['success' => true, 'message' => 'Password changed successfully.'];
            } else {
                $response['message'] = 'Current password is incorrect.';
            }
        }
    }

    // 3. Add Vehicle
    elseif ($action === 'add_vehicle') {
        $plate = trim($_POST['plate_number'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $model_id = intval($_POST['model_id'] ?? 0);

        if ($plate && $color && $model_id) {
            try {
                $stmt = $pdo->prepare('INSERT INTO vehicles (user_id, model_id, plate_number, color) VALUES (?, ?, ?, ?)');
                $stmt->execute([$user_id, $model_id, $plate, $color]);

                $newId = $pdo->lastInsertId();
                logActivity($pdo, $user_id, 'client', 'add_vehicle', "Added vehicle $plate");

                // Return new vehicle data for UI
                $stmt = $pdo->prepare('SELECT v.*, vm.brand, vm.model, vm.type FROM vehicles v LEFT JOIN Vehicle_Models vm ON v.model_id = vm.model_id WHERE v.vehicle_id = ?');
                $stmt->execute([$newId]);
                $veh = $stmt->fetch(PDO::FETCH_ASSOC);

                $response = [
                    'success' => true,
                    'message' => 'Vehicle added successfully.',
                    'vehicle' => $veh
                ];
            } catch (PDOException $e) {
                $response['message'] = "Error adding vehicle: " . $e->getMessage();
            }
        } else {
            $response['message'] = 'Please fill all vehicle fields.';
        }
    }

    // 4. Edit Vehicle
    elseif ($action === 'edit_vehicle') {
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        $plate = trim($_POST['plate_number'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $model_id = intval($_POST['model_id'] ?? 0);

        if ($vehicle_id && $plate && $color && $model_id) {
            // Check if vehicle exists and belongs to user
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM vehicles WHERE vehicle_id = ? AND user_id = ?');
            $stmt->execute([$vehicle_id, $user_id]);
            if ($stmt->fetchColumn() == 0) {
                $response['message'] = 'Vehicle not found.';
            } else {
                try {
                    $stmt = $pdo->prepare('UPDATE vehicles SET model_id = ?, plate_number = ?, color = ? WHERE vehicle_id = ? AND user_id = ?');
                    $stmt->execute([$model_id, $plate, $color, $vehicle_id, $user_id]);
                    logActivity($pdo, $user_id, 'client', 'edit_vehicle', "Updated vehicle ID $vehicle_id");
                    $response = ['success' => true, 'message' => 'Vehicle updated successfully.'];
                } catch (PDOException $e) {
                    $response['message'] = "Error updating vehicle: " . $e->getMessage();
                }
            }
        } else {
            $response['message'] = 'Please fill all vehicle fields.';
        }
    }

    // 5. Delete Vehicle
    elseif ($action === 'delete_vehicle') {
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        if ($vehicle_id) {
            // Check for active reservation
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE vehicle_id = ? AND status NOT IN ("cancelled", "completed") AND end_time > NOW()');
            $stmt->execute([$vehicle_id]);
            if ($stmt->fetchColumn() > 0) {
                $response['message'] = 'Cannot delete vehicle with an active reservation.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM vehicles WHERE vehicle_id = ? AND user_id = ?');
                $stmt->execute([$vehicle_id, $user_id]);
                logActivity($pdo, $user_id, 'client', 'delete_vehicle', "Deleted vehicle ID $vehicle_id");
                $response = ['success' => true, 'message' => 'Vehicle deleted successfully.'];
            }
        }
    }

    // 5. Upload Picture
    elseif ($action === 'upload_pic') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['profile_pic']['tmp_name'];
            $fileName = basename($_FILES['profile_pic']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExt, $allowed)) {
                $newName = 'profile_' . $user_id . '_' . time() . '.' . $fileExt;
                $targetPath = 'images/' . $newName;

                // Use absolute path check if needed, but relative usually works if CWD is correct.
                // script is in root, so images/ is correct.

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    // Delete old
                    $stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
                    $stmt->execute([$user_id]);
                    $curr = $stmt->fetchColumn();
                    if ($curr && $curr !== 'default.jpg' && file_exists('images/' . $curr)) {
                        unlink('images/' . $curr);
                    }

                    $stmt = $pdo->prepare('UPDATE users SET image = ? WHERE user_id = ?');
                    $stmt->execute([$newName, $user_id]);

                    $response = [
                        'success' => true,
                        'message' => 'Profile picture updated.',
                        'image_url' => $targetPath
                    ];
                } else {
                    $response['message'] = 'Failed to save file.';
                }
            } else {
                $response['message'] = 'Invalid file type. Allowed: jpg, png, gif, webp.';
            }
        } else {
            $response['message'] = 'No valid file uploaded.';
        }
    }

    // 6. Delete Picture
    elseif ($action === 'delete_pic') {
        $stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $curr = $stmt->fetchColumn();

        if ($curr && $curr !== 'default.jpg' && file_exists('images/' . $curr)) {
            unlink('images/' . $curr);
        }

        $stmt = $pdo->prepare('UPDATE users SET image = NULL WHERE user_id = ?');
        $stmt->execute([$user_id]);

        $response = [
            'success' => true,
            'message' => 'Profile picture removed.',
            'image_url' => 'images/default.jpg'
        ];
    }

    // 7. Forgot Password (Security Word Check / Reset)
    // Note: This logic was previously checking specific POST keys. We'll adapt it to standard actions if possible?
    // But the frontend script sends 'forgot_password_action'.
    // We can just check for that key being set in the main logic flow or add it as a case here.
    // The previous code had `if (isset($_POST['forgot_password_action']))` OUTSIDE the main block?
    // No, it was inside the file.
    // Let's add it here as a check.
    elseif (isset($_POST['forgot_password_action'])) {
        if (!isset($_POST['fp_security_word'])) {
            $response['message'] = 'Security word required.';
        } else {
            $fp_security_word = trim($_POST['fp_security_word']);
            $stmt = $pdo->prepare('SELECT security_word FROM users WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || strtolower($fp_security_word) !== strtolower($row['security_word'])) {
                $response['message'] = 'Incorrect security word.';
            } else {
                // If new password fields are present, update password
                if (!empty($_POST['fp_new_password']) && !empty($_POST['fp_confirm_new_password'])) {
                    $new = $_POST['fp_new_password'];
                    $confirm = $_POST['fp_confirm_new_password'];
                    if ($new !== $confirm) {
                        $response['message'] = 'Passwords do not match.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                        $stmt->execute([$new, $user_id]);
                        $response = ['success' => true, 'message' => 'Password reset successful!'];
                    }
                } else {
                    // Security word correct, prompt for new password
                    $response = ['success' => true];
                }
            }
        }
    }
}

echo json_encode($response);
exit;
