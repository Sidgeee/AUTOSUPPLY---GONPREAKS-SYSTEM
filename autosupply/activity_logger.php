<?php
// activity_logger.php
// Include this, then call log_activity() after any action worth tracking
// on the Employees > Activity page (sale completed, stock changed, account
// changed, login/logout, clock in/out).

function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, description, reference_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $action_type, $description, $reference_id);
    $stmt->execute();
    $stmt->close();
}
?>