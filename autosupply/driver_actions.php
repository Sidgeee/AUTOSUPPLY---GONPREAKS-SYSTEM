<?php
session_start();
include 'db_connect.php';

@include_once 'activity_logger.php';
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {}
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Driver') {
    echo json_encode(['success' => false, 'message' => 'Drivers only.']);
    exit;
}

$driver_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'set_status') {
    $valid = ['Off-Duty', 'Available', 'In-Transit', 'On-Site'];
    $status = $_POST['status'] ?? '';
    if (!in_array($status, $valid)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE users SET driver_status = ? WHERE user_id = ?");
    $stmt->bind_param("si", $status, $driver_id);
    $stmt->execute();
    log_activity($conn, $driver_id, 'DRIVER_STATUS', "Set driving status to $status.");
    echo json_encode(['success' => true, 'status' => $status]);

} elseif ($action === 'update_delivery') {
    $delivery_id = intval($_POST['delivery_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    $valid = ['En Route', 'Delivered', 'Issue'];

    if (!in_array($new_status, $valid)) {
        echo json_encode(['success' => false, 'message' => 'Invalid delivery status.']);
        exit;
    }

    // Only allow the driver to update deliveries assigned to them
    $stmt = $conn->prepare("SELECT delivery_id FROM deliveries WHERE delivery_id = ? AND driver_user_id = ?");
    $stmt->bind_param("ii", $delivery_id, $driver_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'This delivery is not assigned to you.']);
        exit;
    }

    if ($new_status === 'Delivered') {
        $stmt = $conn->prepare("UPDATE deliveries SET delivery_status = ?, delivered_at = NOW() WHERE delivery_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE deliveries SET delivery_status = ? WHERE delivery_id = ?");
    }
    $stmt->bind_param("si", $new_status, $delivery_id);
    $stmt->execute();

    log_activity($conn, $driver_id, 'DELIVERY_UPDATED', "Marked delivery #$delivery_id as $new_status.", $delivery_id);
    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}