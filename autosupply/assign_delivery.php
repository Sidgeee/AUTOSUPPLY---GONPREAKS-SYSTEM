<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
guard(['Admin', 'Account Manager']);

@include_once 'activity_logger.php';
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {}
}

$delivery_id = intval($_POST['delivery_id'] ?? 0);
$driver_user_id = $_POST['driver_user_id'] !== '' ? intval($_POST['driver_user_id']) : null;
$actor_id = $_SESSION['user_id'];

if ($delivery_id) {
    if ($driver_user_id) {
        $stmt = $conn->prepare("UPDATE deliveries SET driver_user_id = ?, delivery_status = 'Assigned' WHERE delivery_id = ?");
        $stmt->bind_param("ii", $driver_user_id, $delivery_id);
        $stmt->execute();
        log_activity($conn, $actor_id, 'DELIVERY_ASSIGNED', "Assigned delivery #$delivery_id to driver #$driver_user_id.", $delivery_id);
    } else {
        $stmt = $conn->prepare("UPDATE deliveries SET driver_user_id = NULL, delivery_status = 'Pending' WHERE delivery_id = ?");
        $stmt->bind_param("i", $delivery_id);
        $stmt->execute();
        log_activity($conn, $actor_id, 'DELIVERY_UNASSIGNED', "Unassigned delivery #$delivery_id.", $delivery_id);
    }
}

header("Location: deliveries.php");
exit();
?>