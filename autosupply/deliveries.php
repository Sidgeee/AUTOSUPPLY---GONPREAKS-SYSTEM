<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
require_once 'icons.php';
guard(['Admin', 'Account Manager']);

$drivers = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'Driver' AND status = 'Active' ORDER BY full_name ASC");

$deliveries = $conn->query("
    SELECT d.*, s.total_amount, s.payment_method, u.full_name AS driver_name_assigned
    FROM deliveries d
    LEFT JOIN sales s ON d.sale_id = s.sale_id
    LEFT JOIN users u ON d.driver_user_id = u.user_id
    ORDER BY FIELD(d.delivery_status, 'Issue','Pending','Assigned','En Route','Delivered','Cancelled'), d.delivery_id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deliveries | GonPreaks AutoSupply</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { margin:0; background:#020617; color:#f1f5f9; font-family:'Inter',sans-serif; display:flex; }
        .main-content { flex-grow:1; padding:50px 40px; height:100vh; overflow-y:auto; box-sizing:border-box; }
        .breadcrumb { font-size:0.75rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:1.5px; }
        .page-title { font-size:2.2rem; font-weight:900; margin:5px 0 25px; }
        table { width:100%; border-collapse:separate; border-spacing:0 8px; }
        td, th { padding:14px 16px; text-align:left; }
        th { font-size:0.7rem; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; }
        td { background:rgba(255,255,255,0.02); }
        td:first-child { border-radius:12px 0 0 12px; }
        td:last-child { border-radius:0 12px 12px 0; }
        .badge { padding:5px 12px; border-radius:50px; font-size:0.7rem; font-weight:800; text-transform:uppercase; }
        .b-pending { background:rgba(148,163,184,0.15); color:#94a3b8; }
        .b-assigned { background:rgba(56,189,248,0.15); color:#38bdf8; }
        .b-enroute { background:rgba(250,204,21,0.15); color:#facc15; }
        .b-delivered { background:rgba(74,222,128,0.15); color:#4ade80; }
        .b-issue { background:rgba(251,113,133,0.15); color:#fb7185; }
        .b-cancelled { background:rgba(148,163,184,0.1); color:#64748b; }
        select.assign-select { padding:8px 10px; background:#0f172a; color:white; border:1px solid rgba(255,255,255,0.1); border-radius:8px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <span class="breadcrumb">SYSTEMS / OPERATIONS / DELIVERIES</span>
        <h1 class="page-title">Delivery Assignments</h1>

        <table>
            <thead>
                <tr>
                    <th>Ref#</th><th>Customer</th><th>Address</th><th>Total</th><th>Status</th><th>Assigned Driver</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $badge_map = [
                'Pending' => 'b-pending', 'Assigned' => 'b-assigned', 'En Route' => 'b-enroute',
                'Delivered' => 'b-delivered', 'Issue' => 'b-issue', 'Cancelled' => 'b-cancelled'
            ];
            if ($deliveries->num_rows === 0):
            ?>
                <tr><td colspan="6" style="text-align:center; padding:40px; opacity:0.4;">No deliveries recorded yet.</td></tr>
            <?php else: while ($d = $deliveries->fetch_assoc()):
                $badge_class = $badge_map[$d['delivery_status']] ?? 'b-pending';
            ?>
                <tr>
                    <td style="font-family:monospace; color:#38bdf8;">#<?php echo str_pad($d['sale_id'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($d['customer_name'] ?: 'Walk-in'); ?></td>
                    <td style="font-size:0.8rem; opacity:0.8;"><?php echo htmlspecialchars($d['delivery_address'] ?: '-'); ?></td>
                    <td>₱<?php echo number_format($d['total_amount'] ?? 0, 2); ?></td>
                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($d['delivery_status']); ?></span></td>
                    <td>
                        <?php if (in_array($d['delivery_status'], ['Delivered', 'Cancelled'])): ?>
                            <?php echo htmlspecialchars($d['driver_name_assigned'] ?? '-'); ?>
                        <?php else: ?>
                            <form action="assign_delivery.php" method="POST" style="display:flex; gap:8px;">
                                <input type="hidden" name="delivery_id" value="<?php echo $d['delivery_id']; ?>">
                                <select name="driver_user_id" class="assign-select" onchange="this.form.submit()">
                                    <option value="">Unassigned</option>
                                    <?php
                                    mysqli_data_seek($drivers, 0);
                                    while ($drv = $drivers->fetch_assoc()):
                                        $sel = ($drv['user_id'] == $d['driver_user_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $drv['user_id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($drv['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>