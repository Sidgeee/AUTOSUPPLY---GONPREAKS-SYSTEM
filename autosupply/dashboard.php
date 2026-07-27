<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db_connect.php';
require_once 'role_guard.php';
require_once 'icons.php';
guard(['Admin', 'Account Manager', 'Cashier', 'Driver', 'Restocker']);

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GonPreaks AutoSupply</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
        .live-clock { background: var(--glass-bg); padding: 12px 25px; border-radius: 15px; border: 1px solid var(--glass-border); text-align: right; backdrop-filter: blur(10px); }
        #clock-time { font-size: 1.5rem; font-weight: 800; color: var(--accent-blue); display: block; letter-spacing: 1px; }
        #clock-date { font-size: 0.7rem; opacity: 0.5; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; }
        .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 22px; margin-bottom: 30px; }
        .stat-card { background: var(--glass-bg); padding: 26px; border-radius: 20px; border: 1px solid var(--glass-border); position: relative; overflow: hidden; transition: 0.3s ease; backdrop-filter: blur(15px); }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--accent-blue); box-shadow: var(--shadow); }
        .card-icon { position: absolute; right: 14px; bottom: 10px; opacity: 0.08; transform: scale(2.4); color: var(--text-color); }
        .role-badge { font-size: 0.65rem; background: var(--accent-blue); color: #0f172a; padding: 3px 10px; border-radius: 6px; font-weight: 800; text-transform: uppercase; margin-left: 10px; }
        .alert-item { padding: 15px; background: rgba(239, 68, 68, 0.05); border-left: 4px solid var(--accent-red); margin-bottom: 12px; border-radius: 8px; transition: 0.3s; }
        .alert-item:hover { background: rgba(239, 68, 68, 0.1); }

        /* Driver-specific styling */
        .driver-status-select { padding: 10px 16px; border-radius: 12px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.05); color: white; font-weight: 700; font-size: 0.85rem; }
        .delivery-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 18px; padding: 22px; margin-bottom: 16px; }
        .delivery-card .top-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
        .delivery-status-pill { padding: 5px 12px; border-radius:50px; font-size:0.65rem; font-weight:800; text-transform:uppercase; }
        .status-pending { background: rgba(148,163,184,0.15); color:#94a3b8; }
        .status-assigned { background: rgba(56,189,248,0.15); color:#38bdf8; }
        .status-enroute { background: rgba(250,204,21,0.15); color:#facc15; }
        .status-issue { background: rgba(251,113,133,0.15); color:#fb7185; }
        .delivery-actions { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
        .dbtn { padding: 10px 18px; border-radius: 10px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; cursor: pointer; border: none; text-decoration: none; display:inline-flex; align-items:center; gap:6px; }
        .dbtn-nav { background: rgba(56,189,248,0.15); color: #38bdf8; }
        .dbtn-enroute { background: rgba(250,204,21,0.15); color: #facc15; }
        .dbtn-delivered { background: #4ade80; color: #020617; }
        .dbtn-issue { background: rgba(251,113,133,0.15); color: #fb7185; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header-flex">
            <div>
                <h1 style="margin:0; font-size: 2.2rem; font-weight: 800;">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> <span class="role-badge"><?php echo htmlspecialchars($role); ?></span></h1>
                <p style="opacity: 0.5; margin: 8px 0 0 0; letter-spacing: 0.5px;">GonPreaks AutoSupply Enterprise System</p>
            </div>
            <div class="live-clock">
                <span id="clock-time">00:00:00</span>
                <span id="clock-date">LOADING DATE...</span>
            </div>
        </div>

        <?php if ($role === 'Driver'): ?>
            <?php include 'dashboard_driver.php'; ?>

        <?php elseif ($role === 'Admin' || $role === 'Account Manager'): ?>
            <?php
            // All date math done in MySQL (CURDATE()/NOW()) rather than PHP date() strings,
            // so there's no mismatch between the app server's clock and the DB server's clock.
            $daily   = $conn->query("SELECT SUM(total_amount) t FROM sales WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['t'] ?? 0;
            $weekly  = $conn->query("SELECT SUM(total_amount) t FROM sales WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)")->fetch_assoc()['t'] ?? 0;
            $monthly = $conn->query("SELECT SUM(total_amount) t FROM sales WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())")->fetch_assoc()['t'] ?? 0;
            $annual  = $conn->query("SELECT SUM(total_amount) t FROM sales WHERE YEAR(created_at) = YEAR(CURDATE())")->fetch_assoc()['t'] ?? 0;
            $low_stock = $conn->query("SELECT COUNT(*) c FROM inventory WHERE stock_quantity <= 5")->fetch_assoc()['c'];
            $catalog   = $conn->query("SELECT COUNT(*) c FROM inventory")->fetch_assoc()['c'];
            ?>
            <div class="dash-grid">
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">TODAY'S SALES</p>
                    <h2 style="color:var(--accent-green); margin:12px 0; font-size:1.7rem;">₱ <?php echo number_format($daily, 2); ?></h2>
                    <span class="card-icon"><?php echo icon('coins', 22); ?></span>
                </div>
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">THIS WEEK</p>
                    <h2 style="color:var(--accent-blue); margin:12px 0; font-size:1.7rem;">₱ <?php echo number_format($weekly, 2); ?></h2>
                    <span class="card-icon"><?php echo icon('coins', 22); ?></span>
                </div>
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">THIS MONTH</p>
                    <h2 style="color:var(--accent-blue); margin:12px 0; font-size:1.7rem;">₱ <?php echo number_format($monthly, 2); ?></h2>
                    <span class="card-icon"><?php echo icon('coins', 22); ?></span>
                </div>
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">THIS YEAR</p>
                    <h2 style="color:var(--accent-blue); margin:12px 0; font-size:1.7rem;">₱ <?php echo number_format($annual, 2); ?></h2>
                    <span class="card-icon"><?php echo icon('coins', 22); ?></span>
                </div>
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">LOW STOCK ALERTS</p>
                    <h2 style="color:var(--accent-red); margin:12px 0; font-size:1.7rem;"><?php echo $low_stock; ?> Items</h2>
                    <span class="card-icon"><?php echo icon('warning', 22); ?></span>
                </div>
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">TOTAL CATALOG</p>
                    <h2 style="color:var(--accent-blue); margin:12px 0; font-size:1.7rem;"><?php echo $catalog; ?> Parts</h2>
                    <span class="card-icon"><?php echo icon('database', 22); ?></span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 30px;">
                <div class="glass-card">
                    <h3 style="margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 15px;">
                        <?php echo icon('history', 18); ?> Recent Transactions
                    </h3>
                    <table class="table-glass">
                        <thead><tr><th>ID</th><th>Time</th><th>Total Amount</th></tr></thead>
                        <tbody>
                            <?php
                            $res = $conn->query("SELECT * FROM sales ORDER BY created_at DESC LIMIT 5");
                            if ($res->num_rows > 0): while ($r = $res->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-family: monospace; opacity: 0.5;">#<?php echo str_pad($r['sale_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo date('M d, h:i A', strtotime($r['created_at'])); ?></td>
                                    <td style="color:var(--accent-blue); font-weight: 700;">₱<?php echo number_format($r['total_amount'], 2); ?></td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="3" style="text-align: center; padding: 40px; opacity: 0.5;">No transactions yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="glass-card">
                    <h3 style="margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 15px;">
                        <?php echo icon('bell', 18); ?> Stock Critical
                    </h3>
                    <div style="margin-top:20px;">
                        <?php
                        $al = $conn->query("SELECT part_name, stock_quantity FROM inventory WHERE stock_quantity <= 5 LIMIT 4");
                        if ($al->num_rows > 0): while ($a = $al->fetch_assoc()): ?>
                            <div class="alert-item">
                                <small style="opacity:0.5; font-weight: 800; font-size: 0.6rem; text-transform: uppercase;">Needs Restock</small><br>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                    <strong><?php echo htmlspecialchars($a['part_name']); ?></strong>
                                    <span style="color: var(--accent-red); font-weight: 800;"><?php echo $a['stock_quantity']; ?> left</span>
                                </div>
                            </div>
                        <?php endwhile; else: ?>
                            <div style="text-align: center; padding: 40px; opacity: 0.5;">
                                <?php echo icon('check-circle', 32); ?><br><br>All stock levels healthy.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($role === 'Cashier'): ?>
            <?php
            $my_today = $conn->query("SELECT SUM(total_amount) t, COUNT(*) c FROM sales WHERE handled_by_user_id = $user_id AND DATE(created_at) = CURDATE()")->fetch_assoc();
            $my_week  = $conn->query("SELECT SUM(total_amount) t FROM sales WHERE handled_by_user_id = $user_id AND YEARWEEK(created_at,1) = YEARWEEK(CURDATE(),1)")->fetch_assoc()['t'] ?? 0;
            ?>
            <div class="dash-grid">
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">YOUR SALES TODAY</p>
                    <h2 style="color:var(--accent-green); margin:12px 0; font-size:1.7rem;">₱ <?php echo number_format($my_today['t'] ?? 0, 2); ?></h2>
                    <span class="card-icon"><?php echo icon('coins', 22); ?></span>
                </div>
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">TRANSACTIONS TODAY</p>
                    <h2 style="color:var(--accent-blue); margin:12px 0; font-size:1.7rem;"><?php echo $my_today['c'] ?? 0; ?></h2>
                    <span class="card-icon"><?php echo icon('cash-register', 22); ?></span>
                </div>
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">YOUR SALES THIS WEEK</p>
                    <h2 style="color:var(--accent-blue); margin:12px 0; font-size:1.7rem;">₱ <?php echo number_format($my_week, 2); ?></h2>
                    <span class="card-icon"><?php echo icon('coins', 22); ?></span>
                </div>
            </div>
            <a href="pos.php" class="dbtn dbtn-nav" style="padding:16px 30px; font-size:0.9rem;"><?php echo icon('cash-register', 16); ?> Go to POS System</a>

        <?php elseif ($role === 'Restocker'): ?>
            <?php
            $low_stock = $conn->query("SELECT COUNT(*) c FROM inventory WHERE stock_quantity <= 5")->fetch_assoc()['c'];
            $catalog   = $conn->query("SELECT COUNT(*) c FROM inventory")->fetch_assoc()['c'];
            ?>
            <div class="dash-grid">
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">LOW STOCK ALERTS</p>
                    <h2 style="color:var(--accent-red); margin:12px 0; font-size:1.7rem;"><?php echo $low_stock; ?> Items</h2>
                    <span class="card-icon"><?php echo icon('warning', 22); ?></span>
                </div>
                <div class="stat-card">
                    <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">TOTAL CATALOG</p>
                    <h2 style="color:var(--accent-blue); margin:12px 0; font-size:1.7rem;"><?php echo $catalog; ?> Parts</h2>
                    <span class="card-icon"><?php echo icon('database', 22); ?></span>
                </div>
            </div>
            <div class="glass-card">
                <h3 style="margin-top:0; font-size:1.1rem; border-bottom:1px solid var(--glass-border); padding-bottom:15px;"><?php echo icon('bell', 18); ?> Needs Restock</h3>
                <div style="margin-top:20px;">
                    <?php
                    $al = $conn->query("SELECT part_name, stock_quantity FROM inventory WHERE stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 8");
                    if ($al->num_rows > 0): while ($a = $al->fetch_assoc()): ?>
                        <div class="alert-item">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong><?php echo htmlspecialchars($a['part_name']); ?></strong>
                                <span style="color:var(--accent-red); font-weight:800;"><?php echo $a['stock_quantity']; ?> left</span>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                        <div style="text-align:center; padding:40px; opacity:0.5;"><?php echo icon('check-circle', 32); ?><br><br>All stock levels healthy.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('clock-time').innerText = now.toLocaleTimeString();
            document.getElementById('clock-date').innerText = now.toLocaleDateString(undefined, options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>