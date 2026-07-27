<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
guard(['Cashier', 'Driver', 'Restocker']);

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT * FROM attendance_logs WHERE user_id=? AND log_date=? ORDER BY attendance_id DESC LIMIT 1");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_log = $stmt->get_result()->fetch_assoc();
$is_clocked_in = $today_log && !$today_log['time_out'];

$stmt = $conn->prepare("SELECT * FROM attendance_logs WHERE user_id=? ORDER BY log_date DESC LIMIT 14");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Time Clock | GonPreaks AutoSupply</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { margin:0; background:#020617; color:#f1f5f9; font-family:'Inter',sans-serif; display:flex; }
        .main-content { flex-grow:1; padding:50px 40px; height:100vh; overflow-y:auto; box-sizing:border-box; }
        .breadcrumb { font-size:0.75rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:1.5px; }
        .page-title { font-size:2.2rem; font-weight:900; margin:5px 0 25px; }
        .clock-panel { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.1); border-radius:28px; padding:50px; text-align:center; max-width:420px; }
        #clock-time { font-family:'JetBrains Mono', monospace; font-size:2.2rem; font-weight:700; color:#38bdf8; margin-bottom:6px; }
        #clock-date { opacity:0.5; font-size:0.85rem; margin-bottom:30px; }
        .status-pill { display:inline-block; padding:8px 18px; border-radius:50px; font-size:0.75rem; font-weight:800; text-transform:uppercase; margin-bottom:25px; }
        .status-in { background:rgba(74,222,128,0.15); color:#4ade80; border:1px solid rgba(74,222,128,0.3); }
        .status-out { background:rgba(255,255,255,0.05); color:#94a3b8; border:1px solid rgba(255,255,255,0.1); }
        .btn-clock { width:100%; padding:20px; border:none; border-radius:16px; font-weight:900; font-size:1.1rem; text-transform:uppercase; cursor:pointer; }
        .btn-in { background:#4ade80; color:#020617; }
        .btn-out { background:#fb7185; color:#020617; }
        .history-panel { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.1); border-radius:28px; padding:30px; margin-top:25px; max-width:600px; }
        table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        td, th { padding:10px 6px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.06); }
        th { color:#38bdf8; font-size:0.7rem; text-transform:uppercase; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <span class="breadcrumb">SYSTEMS / TEAM / TIME CLOCK</span>
        <h1 class="page-title">Time Clock</h1>

        <div class="clock-panel">
            <div id="clock-time"></div>
            <div id="clock-date"></div>

            <div>
                <span class="status-pill <?php echo $is_clocked_in ? 'status-in' : 'status-out'; ?>" id="statusPill">
                    <?php echo $is_clocked_in ? 'On Duty since ' . date('h:i A', strtotime($today_log['time_in'])) : 'Not Clocked In'; ?>
                </span>
            </div>

            <button class="btn-clock <?php echo $is_clocked_in ? 'btn-out' : 'btn-in'; ?>" id="clockBtn"
                    onclick="doClock('<?php echo $is_clocked_in ? 'clock_out' : 'clock_in'; ?>')">
                <?php echo $is_clocked_in ? 'Clock Out' : 'Clock In'; ?>
            </button>
        </div>

        <div class="history-panel">
            <h3 style="color:#38bdf8; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; margin-top:0;">Last 14 Days</h3>
            <table>
                <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th></tr></thead>
                <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="3" style="opacity:0.4;">No attendance records yet.</td></tr>
                <?php else: foreach ($history as $h): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($h['log_date'])); ?></td>
                        <td><?php echo $h['time_in'] ? date('h:i A', strtotime($h['time_in'])) : '-'; ?></td>
                        <td><?php echo $h['time_out'] ? date('h:i A', strtotime($h['time_out'])) : '<span style="color:#4ade80;">Still in</span>'; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock-time').innerText = now.toLocaleTimeString();
            document.getElementById('clock-date').innerText = now.toLocaleDateString(undefined, { weekday:'long', year:'numeric', month:'long', day:'numeric' });
        }
        updateClock();
        setInterval(updateClock, 1000);

        function doClock(action) {
            const btn = document.getElementById('clockBtn');
            btn.disabled = true;

            fetch('clock_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${action}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                    btn.disabled = false;
                }
            })
            .catch(() => { alert('Connection error.'); btn.disabled = false; });
        }
    </script>
</body>
</html>