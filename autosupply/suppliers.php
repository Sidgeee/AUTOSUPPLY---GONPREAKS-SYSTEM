<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
guard(['Admin', 'Account Manager', 'Restocker']);
require_once 'icons.php';

// Fetch all suppliers with a sub-query to get their unique product categories
$query = "SELECT s.*, 
          (SELECT GROUP_CONCAT(DISTINCT category SEPARATOR ', ') 
           FROM inventory 
           WHERE supplier_name = s.shop_name) as shop_categories
          FROM suppliers s 
          ORDER BY s.shop_name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers | GonPreaks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
    :root {
        --accent-blue: #38bdf8;
        --glass-bg: rgba(15, 23, 42, 0.7);
        --glass-border: rgba(255, 255, 255, 0.08);
        --input-bg: rgba(0, 0, 0, 0.3);
        --card-hover: rgba(56, 189, 248, 0.05);
        --danger-red: #fb7185;
    }

    body { 
        background: #020617; 
        color: #f8fafc; 
        margin: 0; 
        font-family: 'Inter', sans-serif;
        overflow-x: hidden;
        /* Added Flex to snap the content to the sidebar */
        display: flex;
    }

    .main-content { 
        /* Removed margin-left and calc width */
        flex-grow: 1; 
        padding: 40px; 
        min-height: 100vh;
        box-sizing: border-box;
        background: radial-gradient(circle at top right, rgba(56, 189, 248, 0.05), transparent);
        position: relative;
    }

    .header-section {
        display: flex; 
        justify-content: space-between; 
        align-items: flex-end; 
        margin-bottom: 50px;
    }

    .breadcrumb { font-size: 0.7rem; opacity: 0.4; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 5px; }

    .page-title {
        margin: 0; 
        font-size: 2.8rem; 
        font-weight: 900;
        letter-spacing: -1px;
        background: linear-gradient(to right, #fff, #94a3b8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .search-container { position: relative; margin-right: 15px; }

    .search-input {
        padding: 14px 20px 14px 45px;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 14px;
        color: white;
        font-size: 0.85rem;
        outline: none;
        width: 250px;
        transition: 0.3s;
    }

    .search-input:focus {
        border-color: var(--accent-blue);
        width: 300px;
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.1);
    }

    .register-btn {
        padding: 16px 32px; 
        background: var(--accent-blue); 
        color: #0f172a; 
        border: none;
        border-radius: 16px; 
        font-weight: 900; 
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2);
    }

    .supplier-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
        gap: 25px; 
    }

    .supplier-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 30px;
        backdrop-filter: blur(16px);
        transition: all 0.4s ease;
        position: relative;
    }

    .supplier-card:hover { border-color: rgba(56, 189, 248, 0.4); transform: translateY(-8px); background: var(--card-hover); }

    .card-actions {
        position: absolute;
        top: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        background: rgba(0, 0, 0, 0.3);
        padding: 6px;
        border-radius: 12px;
        border: 1px solid var(--glass-border);
    }

    .action-icon {
        cursor: pointer;
        font-size: 1rem;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(255, 255, 255, 0.03);
    }

    .edit-icon { 
        color: var(--accent-blue); 
        border: 1px solid rgba(56, 189, 248, 0.2);
    }
    .edit-icon:hover { 
        background: var(--accent-blue); 
        color: #0f172a; 
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.4);
        transform: scale(1.1);
    }

    .delete-icon { 
        color: var(--danger-red); 
        border: 1px solid rgba(251, 113, 133, 0.2);
    }
    .delete-icon:hover { 
        background: var(--danger-red); 
        color: #fff; 
        box-shadow: 0 0 15px rgba(251, 113, 133, 0.4);
        transform: scale(1.1);
    }

    .card-id { font-size: 0.7rem; font-weight: 800; color: var(--accent-blue); opacity: 0.8; letter-spacing: 1px; }
    
    .category-box {
        margin: 15px 0;
        padding: 12px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 12px;
        border-left: 3px solid var(--accent-blue);
    }
    .category-label { font-size: 0.6rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent-blue); opacity: 0.7; display: block; margin-bottom: 4px; }
    .category-list { font-size: 0.8rem; color: #cbd5e1; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .contact-info-box { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 18px; margin: 20px 0; border: 1px solid rgba(255,255,255,0.03); }
    .info-row { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; margin-bottom: 10px; color: rgba(255,255,255,0.7); }
    .info-row i { color: var(--accent-blue); width: 18px; text-align: center; }

    .manage-link {
        display: block; text-align: center; padding: 14px; background: rgba(56, 189, 248, 0.1); color: var(--accent-blue); 
        text-decoration: none; border-radius: 14px; font-size: 0.8rem; font-weight: 800; border: 1px solid rgba(56, 189, 248, 0.2); transition: 0.3s;
    }

    .manage-link:hover { background: var(--accent-blue); color: #0f172a; }

    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(12px); z-index: 2000; }
    .modal-content { background: #0f172a; border: 1px solid var(--glass-border); border-radius: 28px; width: 600px; margin: 8vh auto; padding: 45px; box-shadow: 0 30px 60px rgba(0,0,0,0.6); }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .full-width { grid-column: span 2; }
    .form-label { display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--accent-blue); margin-bottom: 8px; font-weight: 800; }
    .form-input { width: 100%; padding: 14px 18px; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 14px; color: white; font-size: 0.95rem; outline: none; box-sizing: border-box; }

    .toast-container { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
    .toast {
        background: var(--glass-bg); border: 1px solid var(--accent-blue); backdrop-filter: blur(20px);
        color: white; padding: 16px 24px; border-radius: 16px; display: flex; align-items: center; gap: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5); transform: translateX(120%); transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); margin-top: 10px;
    }
    .toast.show { transform: translateX(0); }
    .toast-icon { color: var(--accent-blue); font-size: 1.2rem; }
    .toast-error { border-color: var(--danger-red); }
    .toast-error .toast-icon { color: var(--danger-red); }
</style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header-section">
            <div>
                <div class="breadcrumb">Systems / Network / Partners</div>
                <h1 class="page-title">Supplier Network</h1>
            </div>
            
            <div style="display: flex; align-items: center;">
                <div class="search-container">
                    <span style="position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.3);"><?php echo icon('search', 16); ?></span>
                    <input type="text" id="supplierSearch" class="search-input" placeholder="Search partners..." onkeyup="filterSuppliers()">
                </div>
                <button onclick="openSupplierModal()" class="register-btn">
                    <?php echo icon('plus', 16); ?> REGISTER NEW PARTNER
                </button>
            </div>
        </div>

        <div class="supplier-grid" id="supplierGrid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="supplier-card">
                        <span class="card-id">PARTNER #<?php echo $row['supplier_id']; ?></span>
                        
                        <div class="card-actions">
                            <span class="action-icon edit-icon" 
                               onclick='openEditModal(<?php echo json_encode($row); ?>)' 
                               title="Edit Partner Details"><?php echo icon('edit', 16); ?></span>
                            
                            <span class="action-icon delete-icon" 
                               onclick="confirmDelete(<?php echo $row['supplier_id']; ?>, '<?php echo addslashes($row['shop_name']); ?>')" 
                               title="Remove Partner"><?php echo icon('trash', 16); ?></span>
                        </div>
                        
                        <h3 style="margin: 15px 0 8px 0; font-size: 1.4rem; font-weight: 800; color: #fff;">
                            <?php echo htmlspecialchars($row['shop_name']); ?>
                        </h3>
                        
                        <div style="font-size: 0.8rem; color: var(--accent-blue); font-weight: 600; margin-bottom: 10px;" class="location-text">
                            <?php echo icon('location', 14); ?> 
                            <?php echo htmlspecialchars($row['address_city'] . ", " . $row['address_province']); ?>
                        </div>

                        <div class="category-box">
                            <span class="category-label">Line of Business</span>
                            <div class="category-list">
                                <?php echo !empty($row['shop_categories']) ? htmlspecialchars($row['shop_categories']) : 'No active inventory'; ?>
                            </div>
                        </div>

                        <div class="contact-info-box">
                            <div class="info-row"><?php echo icon('phone', 14); ?> <?php echo htmlspecialchars($row['contact_number']); ?></div>
                            <div class="info-row"><?php echo icon('envelope', 14); ?> <?php echo htmlspecialchars($row['email']); ?></div>
                        </div>

                        <a href="supplier_products.php?id=<?php echo $row['supplier_id']; ?>" class="manage-link">
                            MANAGE INVENTORY <?php echo icon('arrow-right', 12); ?>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 100px; opacity: 0.3;">
                    <div style="display:flex; justify-content:center;"><?php echo icon('truck', 56); ?></div>
                    <h3>No Partners Registered Yet</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="supplierModal" class="modal-overlay">
        <div class="modal-content">
            <h2 style="margin-bottom: 30px; font-weight: 900; font-size: 1.8rem;">Partner Registration</h2>
            <form action="process_supplier.php" method="POST">
                <div class="form-grid">
                    <div class="full-width">
                        <label class="form-label">Shop Name</label>
                        <input type="text" name="shop_name" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">City</label>
                        <input type="text" name="address_city" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Province</label>
                        <input type="text" name="address_province" class="form-input">
                    </div>
                    <div class="full-width">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-input">
                    </div>
                    <div class="full-width">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input">
                    </div>
                </div>
                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <button type="button" onclick="closeSupplierModal()" style="flex:1; padding:16px; background:none; color:#fff; border:1px solid var(--glass-border); border-radius:14px; cursor:pointer;">CANCEL</button>
                    <button type="submit" style="flex:2; padding:16px; background:var(--accent-blue); color:#0f172a; border:none; border-radius:14px; font-weight:900; cursor:pointer;">REGISTER</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <h2 style="margin-bottom: 30px; font-weight: 900; font-size: 1.8rem; color: var(--accent-blue);">Update Partner Details</h2>
            <form action="update_supplier.php" method="POST">
                <input type="hidden" name="supplier_id" id="edit_id">
                <div class="form-grid">
                    <div class="full-width">
                        <label class="form-label">Shop Name</label>
                        <input type="text" name="shop_name" id="edit_name" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">City</label>
                        <input type="text" name="address_city" id="edit_city" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Province</label>
                        <input type="text" name="address_province" id="edit_province" class="form-input">
                    </div>
                    <div class="full-width">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" id="edit_contact" class="form-input">
                    </div>
                    <div class="full-width">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-input">
                    </div>
                </div>
                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <button type="button" onclick="closeEditModal()" style="flex:1; padding:16px; background:none; color:#fff; border:1px solid var(--glass-border); border-radius:14px; cursor:pointer;">CANCEL</button>
                    <button type="submit" style="flex:2; padding:16px; background:var(--accent-blue); color:#0f172a; border:none; border-radius:14px; font-weight:900; cursor:pointer;">SAVE CHANGES</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <script>
        function openSupplierModal() { document.getElementById('supplierModal').style.display = 'block'; }
        function closeSupplierModal() { document.getElementById('supplierModal').style.display = 'none'; }

        function openEditModal(supplier) {
            document.getElementById('edit_id').value = supplier.supplier_id;
            document.getElementById('edit_name').value = supplier.shop_name;
            document.getElementById('edit_city').value = supplier.address_city;
            document.getElementById('edit_province').value = supplier.address_province;
            document.getElementById('edit_contact').value = supplier.contact_number;
            document.getElementById('edit_email').value = supplier.email;
            document.getElementById('editModal').style.display = 'block';
        }
        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type === 'error' ? 'toast-error' : ''}`;
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `<i class="fas ${icon} toast-icon"></i><div style="font-size: 0.85rem; font-weight: 600;">${message}</div>`;
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }

        function confirmDelete(id, name) {
            if (confirm(`REMOVE PARTNER: "${name.toUpperCase()}"?\nThis action cannot be undone.`)) {
                window.location.href = "delete_supplier.php?id=" + id;
            }
        }

        function filterSuppliers() {
            const query = document.getElementById('supplierSearch').value.toLowerCase();
            const cards = document.getElementsByClassName('supplier-card');
            Array.from(cards).forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const loc = card.querySelector('.location-text').textContent.toLowerCase();
                const cats = card.querySelector('.category-list').textContent.toLowerCase();
                card.style.display = (name.includes(query) || loc.includes(query) || cats.includes(query)) ? "block" : "none";
            });
        }

        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status')) {
                const status = urlParams.get('status');
                if (status === 'updated') showToast("PARTNER INFORMATION SYNCHRONIZED.");
                if (status === 'deleted') showToast("PARTNER SUCCESSFULLY REMOVED.", "error");
                if (status === 'error') showToast("SYSTEM ERROR: PLEASE TRY AGAIN.", "error");
            }
        });

        window.onclick = function(e) {
            if (e.target.className === 'modal-overlay') {
                closeSupplierModal();
                closeEditModal();
            }
        }
    </script>
</body>
</html>