<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
require_once 'icons.php';
guard(['Admin', 'Account Manager']);

$roles = ['Cashier', 'Restocker', 'Driver', 'Account Manager', 'Admin'];

// Prevent acting on your own account for destructive actions
$self_id = $_SESSION['user_id'];

// Hard-delete only allowed if the account has no sales history tied to it
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    if ($id !== $self_id) {
        $check = $conn->prepare("SELECT COUNT(*) as c FROM sales WHERE handled_by_user_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $has_sales = $check->get_result()->fetch_assoc()['c'] > 0;

        if (!$has_sales) {
            $conn->query("DELETE FROM users WHERE user_id = $id");
        }
    }
    header("Location: users.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employees | GonPreaks AutoSupply</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --accent-blue: #38bdf8; --accent-green: #4ade80; --danger: #fb7185;
            --glass-bg: rgba(255,255,255,0.03); --glass-border: rgba(255,255,255,0.1);
            --panel: rgba(255,255,255,0.02);
        }
        body { margin:0; background:#020617; color:#f1f5f9; font-family:'Inter',sans-serif; display:flex; }
        .main-content { flex-grow:1; padding:50px 40px; height:100vh; overflow-y:auto; box-sizing:border-box; }
        .page-title { font-size:2.2rem; font-weight:900; margin:0 0 5px; background:linear-gradient(to right,#fff,var(--accent-blue)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .breadcrumb { font-size:0.75rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:1.5px; }
        .panel { background:var(--panel); border:1px solid var(--glass-border); border-radius:24px; padding:30px; margin-top:25px; backdrop-filter:blur(10px); }
        .panel h3 { margin:0 0 18px; font-size:1rem; color:var(--accent-blue); text-transform:uppercase; letter-spacing:1px; }
        .form-field { padding:13px 16px; background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:12px; color:white; outline:none; }
        select.form-field option { background:#0f172a; }
        .btn { padding:13px 26px; border:none; border-radius:12px; font-weight:800; cursor:pointer; text-transform:uppercase; font-size:0.8rem; letter-spacing:0.5px; }
        .btn-primary { background:var(--accent-blue); color:#020617; }
        table { width:100%; border-collapse:separate; border-spacing:0 8px; }
        td, th { padding:14px 16px; text-align:left; }
        th { font-size:0.7rem; color:var(--accent-blue); text-transform:uppercase; letter-spacing:1px; }
        td { background:rgba(255,255,255,0.02); }
        td:first-child { border-radius:12px 0 0 12px; }
        td:last-child { border-radius:0 12px 12px 0; }
        .badge { padding:5px 12px; border-radius:50px; font-size:0.7rem; font-weight:800; text-transform:uppercase; }
        .badge-role { background:rgba(56,189,248,0.15); color:var(--accent-blue); border:1px solid rgba(56,189,248,0.3); }
        .badge-active { background:rgba(74,222,128,0.15); color:var(--accent-green); border:1px solid rgba(74,222,128,0.3); }
        .badge-inactive { background:rgba(251,113,133,0.15); color:var(--danger); border:1px solid rgba(251,113,133,0.3); }
        .action-group { display:flex; gap:8px; flex-wrap:wrap; }
        .icon-btn { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); cursor:pointer; text-decoration:none; color:inherit; }
        .icon-btn.blue:hover { background:rgba(56,189,248,0.2); color:var(--accent-blue); }
        .icon-btn.green:hover { background:rgba(74,222,128,0.2); color:var(--accent-green); }
        .icon-btn.red:hover { background:rgba(251,113,133,0.2); color:var(--danger); }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(2,6,23,0.85); backdrop-filter:blur(10px); z-index:999; }
        .modal-content { background:#0f172a; border:1px solid var(--glass-border); border-radius:24px; width:420px; margin:12vh auto; padding:35px; }
        .modal-content label { font-size:0.7rem; color:var(--accent-blue); text-transform:uppercase; letter-spacing:1px; font-weight:800; display:block; margin-bottom:6px; }
        .modal-content .form-field { width:100%; box-sizing:border-box; margin-bottom:16px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <span class="breadcrumb">SYSTEMS / TEAM / EMPLOYEES</span>
        <h1 class="page-title">Employee Management</h1>

        <div class="panel">
            <h3>Add New Employee</h3>
            <form action="save_user.php" method="POST" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start;">
                <input type="hidden" name="action" value="add">
                <input type="text" name="full_name" placeholder="Full Name" required class="form-field" style="flex:1; min-width:160px;">
                <input type="text" name="username" placeholder="Username" required class="form-field" style="flex:1; min-width:140px;">
                <input type="password" name="password" placeholder="Password" required class="form-field" style="flex:1; min-width:140px;">
                <select name="role" class="form-field" style="flex:1; min-width:140px;">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Add Employee</button>
            </form>
        </div>

        <div class="panel">
            <h3>Staff Directory</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Today</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $today = date('Y-m-d');
                $res = $conn->query("SELECT * FROM users ORDER BY status DESC, role ASC, full_name ASC");
                while ($u = $res->fetch_assoc()):
                    $uid = $u['user_id'];

                    // Today's clock status
                    $att_stmt = $conn->prepare("SELECT time_in, time_out FROM attendance_logs WHERE user_id=? AND log_date=? ORDER BY attendance_id DESC LIMIT 1");
                    $att_stmt->bind_param("is", $uid, $today);
                    $att_stmt->execute();
                    $att = $att_stmt->get_result()->fetch_assoc();

                    if (!$att) $clock_status = '<span style="color:#64748b; font-size:0.75rem;">Not clocked in</span>';
                    elseif (!$att['time_out']) $clock_status = '<span style="color:var(--accent-green); font-size:0.75rem; font-weight:700;">● On duty since ' . date('h:i A', strtotime($att['time_in'])) . '</span>';
                    else $clock_status = '<span style="color:#64748b; font-size:0.75rem;">Out at ' . date('h:i A', strtotime($att['time_out'])) . '</span>';

                    // Can this account be hard-deleted?
                    $sc = $conn->prepare("SELECT COUNT(*) c FROM sales WHERE handled_by_user_id = ?");
                    $sc->bind_param("i", $uid);
                    $sc->execute();
                    $has_history = $sc->get_result()->fetch_assoc()['c'] > 0;
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><span class="badge badge-role"><?php echo htmlspecialchars($u['role']); ?></span></td>
                        <td>
                            <span class="badge <?php echo $u['status'] === 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $u['status']; ?>
                            </span>
                        </td>
                        <td><?php echo $clock_status; ?></td>
                        <td>
                            <div class="action-group">
                                <a class="icon-btn blue" title="Edit"
                                   onclick='openEditModal(<?php echo json_encode($u); ?>); return false;' href="#">
                                    <?php echo icon('edit', 16); ?>
                                </a>
                                <a class="icon-btn blue" title="View Activity" href="employee_activity.php?id=<?php echo $uid; ?>">
                                    <?php echo icon('history', 16); ?>
                                </a>

                                <?php if ($uid !== $self_id): ?>
                                    <form action="save_user.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
                                        <button type="submit" class="icon-btn <?php echo $u['status'] === 'Active' ? 'red' : 'green'; ?>"
                                                title="<?php echo $u['status'] === 'Active' ? 'Deactivate' : 'Activate'; ?>">
                                            <?php echo $u['status'] === 'Active' ? icon('user-slash', 16) : icon('user-check', 16); ?>
                                        </button>
                                    </form>

                                    <?php if (!$has_history): ?>
                                        <a class="icon-btn red" title="Delete permanently"
                                           href="users.php?delete_id=<?php echo $uid; ?>"
                                           onclick="return confirm('Permanently delete this employee? This cannot be undone.');">
                                            <?php echo icon('trash', 16); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small style="opacity:0.5; align-self:center;">(You)</small>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit / Reset Password Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color:#fff; margin-bottom:20px;">Edit Employee</h2>
            <form action="save_user.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">

                <label>Full Name</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-field" required>

                <label>Username</label>
                <input type="text" name="username" id="edit_username" class="form-field" required>

                <label>Role</label>
                <select name="role" id="edit_role" class="form-field">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>New Password <span style="opacity:0.5; text-transform:none; font-weight:400;">(leave blank to keep current password)</span></label>
                <input type="password" name="password" class="form-field" placeholder="••••••••">

                <div style="display:flex; gap:12px; margin-top:10px;">
                    <button type="button" onclick="closeEditModal()" style="flex:1; padding:14px; background:rgba(255,255,255,0.05); color:white; border:1px solid var(--glass-border); border-radius:12px; cursor:pointer;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(u) {
            document.getElementById('edit_user_id').value = u.user_id;
            document.getElementById('edit_full_name').value = u.full_name;
            document.getElementById('edit_username').value = u.username;
            document.getElementById('edit_role').value = u.role;
            document.getElementById('editModal').style.display = 'block';
        }
        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
        window.onclick = (e) => { if (e.target.id === 'editModal') closeEditModal(); };
    </script>
</body>
</html>