<?php
// role_guard.php
// Usage: put this near the top of a page, AFTER session_start():
//
//     require_once 'role_guard.php';
//     guard(['Admin', 'Account Manager']);
//
// Anyone not logged in, or logged in with a role not in the list, sees a
// clean "Access Denied" screen instead of the page content.

function guard($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        http_response_code(403);
        die('
            <!DOCTYPE html>
            <html><head><meta charset="UTF-8"><title>Access Denied</title></head>
            <body style="margin:0; font-family:Inter,sans-serif; background:#020617; color:#f1f5f9; height:100vh; display:flex; align-items:center; justify-content:center; text-align:center;">
                <div>
                    <div style="font-size:3rem; color:#fb7185; margin-bottom:10px;">&#9888;</div>
                    <h2 style="margin:0 0 8px;">Access Denied</h2>
                    <p style="color:#94a3b8; margin-bottom:20px;">Your account role does not have permission to view this page.</p>
                    <a href="dashboard.php" style="color:#38bdf8; text-decoration:none; font-weight:700;">&larr; Back to Dashboard</a>
                </div>
            </body></html>
        ');
    }
}
?>