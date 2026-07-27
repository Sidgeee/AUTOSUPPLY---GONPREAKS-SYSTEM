<?php
session_start();
include 'db_connect.php';
@include_once 'activity_logger.php';
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {}
}

if (isset($_SESSION['user_id'])) {
    log_activity($conn, $_SESSION['user_id'], 'LOGOUT', "{$_SESSION['name']} logged out.");
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: login.php?msg=logged_out");
exit();
?>