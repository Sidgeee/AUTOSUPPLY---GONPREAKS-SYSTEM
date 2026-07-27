<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
require_once 'icons.php';
@include_once 'activity_logger.php';
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {}
}
guard(['Admin', 'Account Manager', 'Restocker']);

$actor_id = $_SESSION['user_id'] ?? null;

// --- 1. HANDLE POST ACTIONS (Register & Update) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $part_number = mysqli_real_escape_string($conn, $_POST['part_number']);
    $part_name = mysqli_real_escape_string($conn, $_POST['part_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock_quantity']);
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier_name']);

    if (isset($_POST['register_part'])) {
        $sql = "INSERT INTO inventory (part_number, part_name, category, price, stock_quantity, supplier_name) 
                VALUES ('$part_number', '$part_name', '$category', $price, $stock, '$supplier')";
        $status = mysqli_query($conn, $sql) ? "success" : "error";
        if ($status === "success") {
            log_activity($conn, $actor_id, 'STOCK_ADDED', "Registered new part \"$part_name\" ($part_number), qty $stock.", $conn->insert_id);
        }
    } elseif (isset($_POST['update_part'])) {
        $id = intval($_POST['part_id']);
        $sql = "UPDATE inventory SET part_number='$part_number', part_name='$part_name', category='$category',
                price=$price, stock_quantity=$stock, supplier_name='$supplier' WHERE part_id=$id";
        $status = mysqli_query($conn, $sql) ? "updated" : "error";
        if ($status === "updated") {
            log_activity($conn, $actor_id, 'STOCK_UPDATED', "Updated part \"$part_name\" ($part_number) - now qty $stock.", $id);
        }
    }
    header("Location: inventory.php?status=$status");
    exit();
}

// --- 2. HANDLE DELETE ACTION ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $name_res = mysqli_query($conn, "SELECT part_name, part_number FROM inventory WHERE part_id = $delete_id");
    $deleted_part = mysqli_fetch_assoc($name_res);

    $status = mysqli_query($conn, "DELETE FROM inventory WHERE part_id = $delete_id") ? "deleted" : "error";
    if ($status === "deleted" && $deleted_part) {
        log_activity($conn, $actor_id, 'STOCK_DELETED', "Removed part \"{$deleted_part['part_name']}\" ({$deleted_part['part_number']}).", $delete_id);
    }
    header("Location: inventory.php?status=$status");
    exit();
}

// --- 3. SEARCH & FETCH LOGIC ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if (!empty($search)) {
    $where_clause = "WHERE (part_name LIKE '%$search%' OR part_number LIKE '%$search%' OR supplier_name LIKE '%$search%' OR category LIKE '%$search%')";
}

$result = mysqli_query($conn, "SELECT * FROM inventory $where_clause ORDER BY stock_quantity ASC");

// --- 4. DATA FOR DYNAMIC DROPDOWNS ---
$suppliers_query = mysqli_query($conn, "SELECT shop_name FROM suppliers ORDER BY shop_name ASC");

$cat_mapping_query = mysqli_query($conn, "SELECT supplier_name, category_name FROM supplier_categories");
$supplier_mapping = [];
while($row = mysqli_fetch_assoc($cat_mapping_query)) {
    $clean_name = trim($row['supplier_name']);
    $supplier_mapping[$clean_name][] = $row['category_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | GonPreaks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --accent-blue: #38bdf8; --accent-green: #22c55e; --accent-red: #fb7185;
            --glass-bg: rgba(255, 255, 255, 0.03); --glass-border: rgba(255, 255, 255, 0.1);
            --primary-dark: #0f172a;
            --text-main: #e2e8f0; 
            --text-muted: #94a3b8;
        }

        body { 
    background: #020617; 
    color: #f8fafc; 
    font-family: 'Inter', sans-serif; 
    display: flex;
    margin: 0;
}
        .main-content { 
    flex-grow: 1;
    padding: 50px 40px;
    height: 100vh;
    overflow-y: auto;
    margin-left: 0;
}

        .sortable { cursor: pointer; transition: 0.2s; }
        .sortable:hover { color: #fff !important; }
        .sortable i { font-size: 0.6rem; margin-left: 5px; opacity: 0.5; }

        .dropdown-fix { background-color: #1e293b !important; color: white !important; border: 1px solid var(--glass-border) !important; }
        .dropdown-fix option { background-color: #0f172a; color: white; padding: 10px; }

        .modal-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(12px); }
        .modal-content { background: var(--primary-dark); border: 1px solid var(--glass-border); width: 550px; margin: 5vh auto; padding: 40px; border-radius: 28px; animation: modalSlideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes modalSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .inventory-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header-titles h1 { margin: 0; font-size: 2.2rem; font-weight: 800; background: linear-gradient(to right, #fff, var(--accent-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .premium-table tr td { background: rgba(255, 255, 255, 0.02); padding: 18px 20px; border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border); transition: all 0.3s ease; }
        .premium-table tr:hover td { background: rgba(255, 255, 255, 0.05); border-color: rgba(56, 189, 248, 0.3); }

        .status-pill { padding: 6px 14px; border-radius: 50px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .status-stocked { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .status-low { background: rgba(251, 113, 133, 0.1); color: #fb7185; border: 1px solid rgba(251, 113, 133, 0.3); }

        .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.05); transition: 0.2s; text-decoration: none; cursor: pointer; }
        .action-btn:hover { background: rgba(255,255,255,0.15); transform: translateY(-2px); }
        label { color: var(--accent-blue); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .search-input { width: 100%; padding: 15px; border-radius: 15px; border: 1px solid var(--glass-border); background: var(--glass-bg); color: white; box-sizing: border-box;}
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="inventory-header">
            <div class="header-titles">
                <div class="breadcrumb" style="font-size: 0.75rem; color: rgba(255,255,255,0.4); text-transform: uppercase;">Systems / Core / Inventory</div>
                <h1>Inventory Catalog</h1>
            </div>
            <button id="openModalBtn" style="cursor: pointer; padding: 14px 28px; background: var(--accent-blue); color: #000; border: none; font-weight: 800; border-radius: 14px;">
                <?php echo icon('plus', 16); ?> REGISTER NEW PART
            </button>
        </div>

        <form method="GET" action="inventory.php" style="margin-bottom: 30px; position: relative;">
            <span style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--accent-blue);"><?php echo icon('search', 16); ?></span>
            <input type="text" name="search" class="search-input" style="padding-left: 50px;" placeholder="Search by name, part #, or supplier..." value="<?php echo htmlspecialchars($search); ?>">
        </form>

        <table class="premium-table" id="inventoryTable">
            <thead>
                <tr style="color: var(--accent-blue); font-size: 0.7rem; text-transform: uppercase; text-align: left;">
                    <th>Part Number</th>
                    <th class="sortable" onclick="sortTable(1)">Product Details <?php echo icon("sort", 11); ?></th>
                    <th class="sortable" onclick="sortTable(2)">Category <?php echo icon("sort", 11); ?></th>
                    <th class="sortable" onclick="sortTable(3)">Supplier <?php echo icon("sort", 11); ?></th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class="sortable" onclick="sortTable(6)">Status <?php echo icon("sort", 11); ?></th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): $is_low = $row['stock_quantity'] <= 5; ?>
                    <tr class="inventory-row">
                        <td style="font-family: monospace; font-weight: 700; color: var(--accent-blue);"><?php echo htmlspecialchars($row['part_number'] ?? '---'); ?></td>
                        <td>
                            <div style="font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($row['part_name']); ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">ID: #<?php echo $row['part_id']; ?></div>
                        </td>
                        <td style="color: var(--accent-blue); font-size: 0.8rem; font-weight: 600;"><?php echo htmlspecialchars($row['category'] ?? 'General'); ?></td>
                        <td style="color: var(--text-muted);"><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td style="font-weight: 600; color: var(--text-main);">₱ <?php echo number_format($row['price'], 2); ?></td>
                        <td style="font-weight: 800; color: var(--text-main);"><?php echo $row['stock_quantity']; ?></td>
                        <td><span class="status-pill <?php echo $is_low ? 'status-low' : 'status-stocked'; ?>"><?php echo $is_low ? "Low Stock" : "In Stock"; ?></span></td>
                        <td style="text-align: right;">
                            <a href="javascript:void(0)" class="action-btn" style="color: var(--accent-blue);" title="Edit Part" onclick='openEditModal(<?php echo json_encode($row); ?>)'><?php echo icon('edit', 16); ?></a>
                            <a href="javascript:void(0)" class="action-btn" style="color: var(--accent-red);" title="Delete Part" onclick="confirmDelete(<?php echo $row['part_id']; ?>)"><?php echo icon('trash', 16); ?></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="partModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modalTitle" style="color: #fff; margin-bottom: 25px;">Register New Part</h2>
            <form action="inventory.php" method="POST">
                <input type="hidden" name="part_id" id="part_id">
                
                <label>Part Number</label>
                <input type="text" name="part_number" id="form_part_number" class="search-input" style="margin-bottom: 15px;" required>
                
                <label>Part Name</label>
                <input type="text" name="part_name" id="form_part_name" class="search-input" style="margin-bottom: 15px;" required>
                
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label>Supplier</label>
                        <select name="supplier_name" id="form_supplier" class="search-input dropdown-fix" onchange="updateCategories(this.value)" required>
                            <option value="">Select Supplier...</option>
                            <?php 
                            mysqli_data_seek($suppliers_query, 0);
                            while($s = mysqli_fetch_assoc($suppliers_query)): ?>
                                <option value="<?php echo htmlspecialchars($s['shop_name']); ?>">
                                    <?php echo htmlspecialchars($s['shop_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Category</label>
                        <select name="category" id="form_category" class="search-input dropdown-fix" required>
                            <option value="">Select Supplier First...</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-bottom: 25px;">
                    <div style="flex: 1;">
                        <label>Price (₱)</label>
                        <input type="number" step="0.01" name="price" id="form_price" class="search-input" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Stock Qty</label>
                        <input type="number" name="stock_quantity" id="form_stock" class="search-input" required>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeModal()" style="flex: 1; padding: 15px; background: rgba(255,255,255,0.05); color: white; border: none; border-radius: 12px; cursor: pointer;">CANCEL</button>
                    <button type="submit" id="submitBtn" name="register_part" style="flex: 2; padding: 15px; background: var(--accent-blue); color: #000; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">SAVE PART</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const supplierMapping = <?php echo json_encode($supplier_mapping); ?>;

        function updateCategories(supplierName) {
            const categorySelect = document.getElementById('form_category');
            categorySelect.innerHTML = '<option value="">Select Category...</option>';
            const cleanName = supplierName.trim();

            if (cleanName && supplierMapping[cleanName]) {
                const cats = [...new Set(supplierMapping[cleanName])];
                cats.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat;
                    option.text = cat;
                    categorySelect.appendChild(option);
                });
            } else if (cleanName) {
                const option = document.createElement('option');
                option.text = "No custom categories found";
                option.disabled = true;
                categorySelect.appendChild(option);
            }
        }

        const modal = document.getElementById('partModal');
        const openBtn = document.getElementById('openModalBtn');

        openBtn.onclick = () => {
            document.getElementById('modalTitle').innerText = "Register New Part";
            document.getElementById('submitBtn').name = "register_part";
            document.querySelectorAll('.modal-content input:not([type="hidden"])').forEach(i => i.value = '');
            document.getElementById('form_supplier').value = '';
            document.getElementById('form_category').innerHTML = '<option value="">Select Supplier First...</option>';
            modal.style.display = 'block';
        };

        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = "Update Part Info";
            document.getElementById('submitBtn').name = "update_part";
            document.getElementById('part_id').value = data.part_id;
            document.getElementById('form_part_number').value = data.part_number;
            document.getElementById('form_part_name').value = data.part_name;
            document.getElementById('form_supplier').value = data.supplier_name;
            
            updateCategories(data.supplier_name);
            setTimeout(() => {
                document.getElementById('form_category').value = data.category;
            }, 50);

            document.getElementById('form_price').value = data.price;
            document.getElementById('form_stock').value = data.stock_quantity;
            modal.style.display = 'block';
        }

        function closeModal() { modal.style.display = 'none'; }

        function confirmDelete(id) {
            if(confirm("Confirm deletion of this part?")) {
                window.location.href = "inventory.php?delete_id=" + id;
            }
        }

        let sortDirections = [true, true, true, true, true, true, true];
        function sortTable(columnIndex) {
            const table = document.getElementById("inventoryTable");
            const tbody = table.tBodies[0];
            const rows = Array.from(tbody.rows);
            const direction = sortDirections[columnIndex] ? 1 : -1;

            rows.sort((a, b) => {
                const cellA = a.cells[columnIndex].innerText.toLowerCase().trim();
                const cellB = b.cells[columnIndex].innerText.toLowerCase().trim();
                return isNaN(cellA) ? cellA.localeCompare(cellB) * direction : (parseFloat(cellA) - parseFloat(cellB)) * direction;
            });

            sortDirections[columnIndex] = !sortDirections[columnIndex];
            tbody.append(...rows);
        }

        window.onclick = (event) => { if (event.target == modal) closeModal(); }
    </script>
</body>
</html>