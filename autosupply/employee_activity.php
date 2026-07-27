<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
guard(['Admin', 'Account Manager']);

$user_id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();
if (!$emp) { die("Employee not found. <a href='users.php'>Go back</a>"); }

// Sales this person handled
$stmt = $conn->prepare("SELECT sale_id, total_amount, payment_method, created_at FROM sales WHERE handled_by_user_id = ? ORDER BY created_at DESC LIMIT 25");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// General activity log (stock changes, account changes, logins, clock events)
$stmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Attendance history
$stmt = $conn->prepare("SELECT * FROM attendance_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'icons.php';
$icon_map = [
    'ACCOUNT_CREATED' => 'user-check', 'ACCOUNT_UPDATED' => 'edit',
    'ACCOUNT_STATUS_CHANGED' => 'users-gear', 'SALE_COMPLETED' => 'cash-register',
    'STOCK_ADDED' => 'package', 'STOCK_UPDATED' => 'boxes', 'STOCK_DELETED' => 'trash',
    'LOGIN' => 'logout', 'LOGOUT' => 'logout',
    'CLOCK_IN' => 'clock', 'CLOCK_OUT' => 'clock',
    'DRIVER_STATUS' => 'route', 'DELIVERY_UPDATED' => 'route', 'DELIVERY_ASSIGNED' => 'route', 'DELIVERY_UNASSIGNED' => 'route',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($emp['full_name']); ?> | Activity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { margin:0; background:#020617; color:#f1f5f9; font-family:'Inter',sans-serif; display:flex; }
        .main-content { flex-grow:1; padding:50px 40px; height:100vh; overflow-y:auto; box-sizing:border-box; }
        .breadcrumb { font-size:0.75rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:1.5px; }
        .page-title { font-size:2.2rem; font-weight:900; margin:5px 0 25px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:25px; align-items:start; }
        .panel { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.1); border-radius:24px; padding:28px; }
        .panel h3 { margin:0 0 16px; font-size:0.85rem; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; }
        .log-row { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.06); font-size:0.85rem; }
        .log-row i { color:#38bdf8; width:16px; }
        .log-time { opacity:0.4; font-size:0.7rem; margin-left:auto; white-space:nowrap; }
        table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        td, th { padding:8px 4px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.06); }
        th { color:#38bdf8; font-size:0.7rem; text-transform:uppercase; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <span class="breadcrumb">SYSTEMS / TEAM / <?php echo strtoupper(htmlspecialchars($emp['full_name'])); ?></span>
        <h1 class="page-title"><?php echo htmlspecialchars($emp['full_name']); ?>'s Activity <span style="font-size:1rem; opacity:0.5;">(<?php echo htmlspecialchars($emp['role']); ?>)</span></h1>

        <div class="grid">
            <div class="panel">
                <h3>Recent Activity</h3>
                <?php if (empty($logs)): ?>
                    <p style="opacity:0.4;">No recorded activity yet.</p>
                <?php else: foreach ($logs as $l): ?>
                    <div class="log-row">
                        <?php echo icon($icon_map[$l['action_type']] ?? 'history', 14); ?>
                        <span><?php echo htmlspecialchars($l['description']); ?></span>
                        <span class="log-time"><?php echo date('M d, h:i A', strtotime($l['created_at'])); ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="panel">
                <h3>Attendance (last 20 days)</h3>
                <table>
                    <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th></tr></thead>
                    <tbody>
                    <?php if (empty($attendance)): ?>
                        <tr><td colspan="3" style="opacity:0.4;">No attendance records yet.</td></tr>
                    <?php else: foreach ($attendance as $a): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($a['log_date'])); ?></td>
                            <td><?php echo $a['time_in'] ? date('h:i A', strtotime($a['time_in'])) : '-'; ?></td>
                            <td><?php echo $a['time_out'] ? date('h:i A', strtotime($a['time_out'])) : '<span style="color:#4ade80;">Still in</span>'; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel" style="grid-column: 1 / -1;">
                <h3>Sales Handled (last 25)</h3>
                <table>
                    <thead><tr><th>Order #</th><th>Total</th><th>Payment</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="4" style="opacity:0.4;">No sales recorded for this employee.</td></tr>
                    <?php else: foreach ($sales as $s): ?>
                        <tr>
                            <td>#<?php echo str_pad($s['sale_id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td>₱<?php echo number_format($s['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($s['payment_method']); ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>