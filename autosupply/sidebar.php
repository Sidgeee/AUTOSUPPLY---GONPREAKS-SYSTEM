<?php
// sidebar.php
if (!isset($conn)) {
    include 'db_connect.php';
}
require_once 'icons.php';

$current_role = $_SESSION['role'] ?? '';

// Which nav items each role can see. Adjust this array any time to change access.
$nav_access = [
    'Dashboard'   => ['Admin', 'Account Manager', 'Cashier', 'Driver', 'Restocker'],
    'POS'         => ['Admin', 'Account Manager', 'Cashier'],
    'Inventory'   => ['Admin', 'Account Manager', 'Restocker'],
    'Suppliers'   => ['Admin', 'Account Manager', 'Restocker'],
    'Employees'   => ['Admin', 'Account Manager'],
    'Attendance'  => ['Admin', 'Account Manager'],
    'Deliveries'  => ['Admin', 'Account Manager'],
    // Time Clock is only for the roles that actually punch in/out - not Admin/Account Manager
    'TimeClock'   => ['Cashier', 'Driver', 'Restocker'],
];
function can_see($section, $role, $nav_access) {
    return in_array($role, $nav_access[$section] ?? []);
}

$sidebar_result = null;
if (can_see('Suppliers', $current_role, $nav_access)) {
    $sidebar_query = "SELECT shop_name, supplier_id FROM suppliers ORDER BY shop_name ASC LIMIT 5";
    $sidebar_result = mysqli_query($conn, $sidebar_query);
}

$current_supplier_id = $_GET['id'] ?? null;
?>

<style>
    .sidebar {
        width: 280px; height: 94vh; margin: 3vh 0 3vh 20px;
        background: var(--sidebar-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border); border-radius: 2px;
        display: flex; flex-direction: column; flex-shrink: 0;
    }
    .sidebar-header { padding: 40px 25px; text-align: center; }
    .brand-name { margin: 0; font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; color: #fff; }
    .brand-role { font-size: 0.6rem; letter-spacing: 3px; color: var(--accent-blue); margin-top: 5px; opacity: 0.8; }
    .sidebar nav { flex: 1; padding: 20px 15px; overflow-y: auto; }
    .nav-item {
        display: flex; align-items: center; padding: 14px 25px; color: rgba(255, 255, 255, 0.6);
        text-decoration: none; transition: all 0.3s; font-size: 0.9rem; font-weight: 500; gap: 15px;
    }
    .nav-item .icon-slot { width: 20px; display:flex; justify-content:center; flex-shrink:0; }
    .nav-item:hover, .nav-item.active { color: #fff; background: rgba(56, 189, 248, 0.1); }
    .nav-item.active { color: var(--accent-blue); border-right: 3px solid var(--accent-blue); }
    .logout-box { margin-top: auto; padding: 20px 15px; }
    .logout-btn {
        display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px;
        background: rgba(251, 113, 133, 0.1); border: 1px solid rgba(251, 113, 133, 0.2); color: #fb7185 !important;
        text-decoration: none; border-radius: 12px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; transition: 0.3s;
    }
    .logout-btn:hover { background: rgba(251, 113, 133, 0.2); transform: translateY(-2px); }
    .sidebar-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 15px 25px; }
    .section-label { font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.3); margin: 20px 0 10px 25px; text-transform: uppercase; letter-spacing: 1.5px; }
    .sub-nav-item { display: flex; align-items: center; padding: 8px 25px 8px 35px; color: rgba(255,255,255,0.4); text-decoration: none; font-size: 0.75rem; transition: 0.3s; }
    .sub-nav-item:hover, .sub-nav-item.active { color: var(--accent-blue); padding-left: 40px; }
    .sub-nav-item.active { font-weight: 700; }
    .sub-dot { width:5px; height:5px; border-radius:50%; background:currentColor; margin-right:12px; flex-shrink:0; }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <h1 class="brand-name">GONPREAKS</h1>
        <div class="brand-role"><?php echo strtoupper($_SESSION['role'] ?? 'GUEST'); ?></div>
        <?php if (!empty($_SESSION['name'])): ?>
            <div style="font-size:0.7rem; color:rgba(255,255,255,0.4); margin-top:4px;"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
        <?php endif; ?>
    </div>

    <nav>
        <?php if (can_see('Dashboard', $current_role, $nav_access)): ?>
        <a href="dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <span class="icon-slot"><?php echo icon('dashboard'); ?></span> <span>Dashboard</span>
        </a>
        <?php endif; ?>

        <?php if (can_see('POS', $current_role, $nav_access)): ?>
        <a href="pos.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'pos.php') ? 'active' : ''; ?>">
            <span class="icon-slot"><?php echo icon('cash-register'); ?></span> <span>POS System</span>
        </a>
        <?php endif; ?>

        <?php if (can_see('Inventory', $current_role, $nav_access)): ?>
        <a href="inventory.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'inventory.php') ? 'active' : ''; ?>">
            <span class="icon-slot"><?php echo icon('boxes'); ?></span> <span>Inventory</span>
        </a>
        <?php endif; ?>

        <?php if (can_see('Deliveries', $current_role, $nav_access)): ?>
        <a href="deliveries.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'deliveries.php') ? 'active' : ''; ?>">
            <span class="icon-slot"><?php echo icon('route'); ?></span> <span>Deliveries</span>
        </a>
        <?php endif; ?>

        <?php if (can_see('Suppliers', $current_role, $nav_access)): ?>
        <div class="sidebar-divider"></div>
        <div class="section-label">Partners</div>
        <a href="suppliers.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'suppliers.php') ? 'active' : ''; ?>">
            <span class="icon-slot"><?php echo icon('truck'); ?></span> <span>Suppliers</span>
        </a>
        <div class="sub-menu">
            <?php if ($sidebar_result): ?>
                <?php while ($side_row = mysqli_fetch_assoc($sidebar_result)): ?>
                    <?php $active_sub = ($current_supplier_id == $side_row['supplier_id']) ? 'active' : ''; ?>
                    <a href="supplier_products.php?id=<?php echo $side_row['supplier_id']; ?>" class="sub-nav-item <?php echo $active_sub; ?>">
                        <span class="sub-dot"></span><span><?php echo htmlspecialchars($side_row['shop_name']); ?></span>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (can_see('Employees', $current_role, $nav_access)): ?>
        <div class="sidebar-divider"></div>
        <div class="section-label">Team</div>
        <a href="users.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">
            <span class="icon-slot"><?php echo icon('users-gear'); ?></span> <span>Employees</span>
        </a>
        <?php endif; ?>

        <?php if (can_see('Attendance', $current_role, $nav_access)): ?>
        <a href="attendance_overview.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance_overview.php') ? 'active' : ''; ?>">
            <span class="icon-slot"><?php echo icon('history'); ?></span> <span>Attendance Log</span>
        </a>
        <?php endif; ?>

        <?php if (can_see('TimeClock', $current_role, $nav_access)): ?>
        <a href="time_clock.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'time_clock.php') ? 'active' : ''; ?>">
            <span class="icon-slot"><?php echo icon('clock'); ?></span> <span>Time Clock</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="logout-box">
        <a href="logout.php" class="logout-btn">
            <?php echo icon('logout', 16); ?> <span>Logout Session</span>
        </a>
    </div>
</div>