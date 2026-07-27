<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
require_once 'icons.php';
guard(['Admin', 'Account Manager']);

$filter_user = intval($_GET['user_id'] ?? 0);

$where = $filter_user ? "WHERE a.user_id = $filter_user" : "";
$logs = $conn->query("
    SELECT a.*, u.full_name, u.role
    FROM attendance_logs a
    JOIN users u ON a.user_id = u.user_id
    $where
    ORDER BY a.log_date DESC, a.attendance_id DESC
    LIMIT 100
");

$staff = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('Cashier','Driver','Restocker') ORDER BY full_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Log | GonPreaks AutoSupply</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { margin:0; background:#020617; color:#f1f5f9; font-family:'Inter',sans-serif; display:flex; }
        .main-content { flex-grow:1; padding:50px 40px; height:100vh; overflow-y:auto; box-sizing:border-box; }
        .breadcrumb { font-size:0.75rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:1.5px; }
        .page-title { font-size:2.2rem; font-weight:900; margin:5px 0 25px; }
        .filter-bar { margin-bottom:20px; }
        select { padding:12px 16px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:white; border-radius:12px; }
        table { width:100%; border-collapse:separate; border-spacing:0 8px; }
        td, th { padding:12px 16px; text-align:left; }
        th { font-size:0.7rem; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; }
        td { background:rgba(255,255,255,0.02); font-size:0.9rem; }
        td:first-child { border-radius:12px 0 0 12px; }
        td:last-child { border-radius:0 12px 12px 0; }
        .badge { padding:4px 10px; border-radius:6px; font-size:0.65rem; font-weight:800; background:rgba(56,189,248,0.15); color:#38bdf8; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <span class="breadcrumb">SYSTEMS / TEAM / ATTENDANCE</span>
        <h1 class="page-title"><?php echo icon('history', 26); ?> Attendance Log</h1>

        <div class="filter-bar">
            <form method="GET">
                <select name="user_id" onchange="this.form.submit()">
                    <option value="0">All Employees</option>
                    <?php while ($s = $staff->fetch_assoc()): ?>
                        <option value="<?php echo $s['user_id']; ?>" <?php echo $filter_user == $s['user_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <table>
            <thead><tr><th>Employee</th><th>Role</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Hours</th></tr></thead>
            <tbody>
            <?php if ($logs->num_rows === 0): ?>
                <tr><td colspan="6" style="text-align:center; padding:40px; opacity:0.4;">No attendance records yet.</td></tr>
            <?php else: while ($l = $logs->fetch_assoc()):
                $hours = '-';
                if ($l['time_in'] && $l['time_out']) {
                    $diff = (strtotime($l['time_out']) - strtotime($l['time_in'])) / 3600;
                    $hours = number_format($diff, 1) . ' hrs';
                }
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($l['full_name']); ?></strong></td>
                    <td><span class="badge"><?php echo htmlspecialchars($l['role']); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($l['log_date'])); ?></td>
                    <td><?php echo $l['time_in'] ? date('h:i A', strtotime($l['time_in'])) : '-'; ?></td>
                    <td><?php echo $l['time_out'] ? date('h:i A', strtotime($l['time_out'])) : '<span style="color:#4ade80;">Still in</span>'; ?></td>
                    <td><?php echo $hours; ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>