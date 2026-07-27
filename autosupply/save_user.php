<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
@include_once 'activity_logger.php';
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {}
}
guard(['Admin', 'Account Manager']);

$actor_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role      = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role, status) VALUES (?, ?, ?, ?, 'Active')");
    $stmt->bind_param("ssss", $full_name, $username, $password, $role);

    if ($stmt->execute()) {
        log_activity($conn, $actor_id, 'ACCOUNT_CREATED', "Created employee account \"$full_name\" ($role).", $conn->insert_id);
    } else {
        die("Error creating account: " . htmlspecialchars($conn->error) . " <a href='users.php'>Go back</a>");
    }

} elseif ($action === 'update') {
    $user_id   = intval($_POST['user_id']);
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $role      = $_POST['role'];
    $password  = $_POST['password'] ?? '';

    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, role=?, password=? WHERE user_id=?");
        $stmt->bind_param("ssssi", $full_name, $username, $role, $hashed, $user_id);
        $log_note = "Updated details and reset password for \"$full_name\".";
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, role=? WHERE user_id=?");
        $stmt->bind_param("sssi", $full_name, $username, $role, $user_id);
        $log_note = "Updated details for \"$full_name\".";
    }

    if ($stmt->execute()) {
        log_activity($conn, $actor_id, 'ACCOUNT_UPDATED', $log_note, $user_id);
    } else {
        die("Error updating account: " . htmlspecialchars($conn->error) . " <a href='users.php'>Go back</a>");
    }

} elseif ($action === 'toggle_status') {
    $user_id = intval($_POST['user_id']);

    if ($user_id === $actor_id) {
        die("You cannot deactivate your own account. <a href='users.php'>Go back</a>");
    }

    $stmt = $conn->prepare("SELECT full_name, status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();

    if ($target) {
        $new_status = $target['status'] === 'Active' ? 'Inactive' : 'Active';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        log_activity($conn, $actor_id, 'ACCOUNT_STATUS_CHANGED', "Set \"{$target['full_name']}\" to $new_status.", $user_id);
    }
}

header("Location: users.php");
exit();
?>