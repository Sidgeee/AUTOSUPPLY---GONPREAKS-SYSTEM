<?php
session_start();
include 'db_connect.php';

// Fail-safe: if activity_logger.php is missing or broken, don't let that
// break clock in/out - just skip the log entry instead of crashing.
@include_once 'activity_logger.php';
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {}
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['Cashier', 'Driver', 'Restocker'])) {
    echo json_encode(['success' => false, 'message' => 'Your role does not use the time clock.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$today = date('Y-m-d');

if ($action === 'clock_in') {
    $stmt = $conn->prepare("SELECT attendance_id FROM attendance_logs WHERE user_id=? AND log_date=? AND time_out IS NULL");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'You are already clocked in.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO attendance_logs (user_id, time_in, log_date) VALUES (?, NOW(), ?)");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    log_activity($conn, $user_id, 'CLOCK_IN', 'Clocked in for the day.');
    echo json_encode(['success' => true, 'status' => 'in', 'time' => date('h:i A')]);

} elseif ($action === 'clock_out') {
    $stmt = $conn->prepare("SELECT attendance_id FROM attendance_logs WHERE user_id=? AND log_date=? AND time_out IS NULL ORDER BY attendance_id DESC LIMIT 1");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'You are not currently clocked in.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE attendance_logs SET time_out = NOW() WHERE attendance_id = ?");
    $stmt->bind_param("i", $row['attendance_id']);
    $stmt->execute();
    log_activity($conn, $user_id, 'CLOCK_OUT', 'Clocked out for the day.');
    echo json_encode(['success' => true, 'status' => 'out', 'time' => date('h:i A')]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}