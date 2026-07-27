<?php
session_start();
include 'db_connect.php';

// 1. Get Supplier Details
if (!isset($_GET['id'])) { header("Location: suppliers.php"); exit(); }
$supplier_id = intval($_GET['id']);

$sup_res = mysqli_query($conn, "SELECT * FROM suppliers WHERE supplier_id = $supplier_id");
$supplier = mysqli_fetch_assoc($sup_res);

if (!$supplier) { die("Supplier not found."); }

// 2. Fetch Products matching this supplier's name
$supplier_name = mysqli_real_escape_string($conn, $supplier['shop_name']);
$query = "SELECT * FROM inventory WHERE supplier_name = '$supplier_name' ORDER BY stock_quantity ASC, part_name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $supplier['shop_name']; ?> | Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --accent-blue: #38bdf8;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
            --input-bg: rgba(0, 0, 0, 0.3);
            --danger-red: #fb7185;
            --success-green: #4ade80;
        }

        .supplier-banner {
            background: linear-gradient(to right, rgba(56, 189, 248, 0.1), transparent);
            padding: 40px; border-radius: 20px; border: 1px solid var(--glass-border);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .action-group { display: flex; gap: 12px; }

        .btn-base {
            cursor: pointer; padding: 12px 24px; 
            border-radius: 12px; font-weight: 700; transition: 0.3s;
            display: flex; align-items: center; gap: 8px;
        }

        .print-btn {
            background: rgba(255,255,255,0.05); 
            color: #fff; border: 1px solid var(--glass-border); 
        }

        .print-btn:hover { background: var(--accent-blue); color: #000; }

        .category-btn {
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent-blue);
            border: 1px solid var(--accent-blue);
        }
        
        .category-btn:hover {
            background: var(--accent-blue);
            color: #0f172a;
        }

        .category-select {
            background: #000;
            color: #fff;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 8px;
            cursor: pointer;
            width: 100%;
            outline: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .category-select:focus { border-color: var(--accent-blue); }
        
        .save-success { border-color: var(--success-green) !important; }

        .stock-warning {
            color: var(--danger-red) !important;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2, 6, 23, 0.85); backdrop-filter: blur(12px); z-index: 2000; }
        .modal-content { background: #0f172a; border: 1px solid var(--glass-border); border-radius: 28px; width: 450px; margin: 15vh auto; padding: 40px; box-shadow: 0 30px 60px rgba(0,0,0,0.6); }
        .form-label { display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--accent-blue); margin-bottom: 8px; font-weight: 800; }
        .form-input { width: 100%; padding: 14px 18px; background: var(--input-bg); border: 1px solid var(--glass-border); border-radius: 14px; color: white; font-size: 0.95rem; outline: none; box-sizing: border-box; }

        @media print {
            .sidebar, .breadcrumb, .print-btn, .category-btn, i, .category-select { display: none !important; }
            body { background: white !important; color: black !important; }
            .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .supplier-banner { 
                background: none !important; border: none !important; 
                border-bottom: 2px solid #000 !important; border-radius: 0 !important;
                padding: 0 0 20px 0 !important;
            }
            .supplier-banner h1, .supplier-banner p { color: black !important; }
            .premium-table th, .premium-table td { 
                color: black !important; border: 1px solid #ddd !important; 
                background: white !important; padding: 10px !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="supplier-banner">
            <div>
                <div class="breadcrumb" style="font-size: 0.7rem; opacity: 0.5; text-transform: uppercase; margin-bottom: 10px;">
                    <a href="suppliers.php" style="color: #fff; text-decoration: none;">Suppliers</a> / Partner Details
                </div>
                <h1 style="color: #fff; margin: 0; font-size: 2.5rem;"><?php echo $supplier['shop_name']; ?></h1>
                <p style="color: var(--accent-blue); font-weight: 600; margin-top: 5px;">
                    <i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($supplier['address_city']); ?> | 
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($supplier['contact_number']); ?>
                </p>
            </div>
            
            <div class="action-group">
                <button onclick="openCategoryModal()" class="btn-base category-btn">
                    <i class="fas fa-tags"></i> ADD CATEGORY
                </button>
                <button onclick="window.print()" class="btn-base print-btn">
                    <i class="fas fa-print"></i> PRINT CATALOG
                </button>
            </div>
        </div>

        <h3 style="color: #fff; opacity: 0.8; margin-bottom: 20px;">Partner Inventory Report</h3>
        
        <div class="glass-card" style="padding: 0; background: transparent; border: none;">
            <table class="premium-table">
                <thead>
                    <tr style="color: var(--accent-blue); font-size: 0.7rem; text-transform: uppercase;">
                        <th>Part Number</th>
                        <th>Product Name</th>
                        <th style="width: 200px;">Category</th>
                        <th>Price</th>
                        <th>Stock Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): 
                            $is_low = ($row['stock_quantity'] < 10);
                        ?>
                        <tr style="<?php echo $is_low ? 'background: rgba(251, 113, 133, 0.05);' : ''; ?>">
                            <td style="color: var(--accent-blue); font-family: monospace; font-weight: 700;">
                                <?php echo htmlspecialchars($row['part_number']); ?>
                            </td>
                            <td style="color: #fff; font-weight: 700;">
                                <?php echo htmlspecialchars($row['part_name']); ?>
                            </td>
                            <td>
                                <select class="category-select" onchange="updateItemCategory(<?php echo $row['part_id']; ?>, this)">
                                    <option value="">Uncategorized</option>
                                    <?php
                                    $cat_list_query = mysqli_query($conn, "SELECT * FROM supplier_categories ORDER BY category_name ASC");
                                    while($cat_row = mysqli_fetch_assoc($cat_list_query)) {
                                        $selected = ($row['category'] == $cat_row['category_name']) ? 'selected' : '';
                                        echo "<option value='".htmlspecialchars($cat_row['category_name'])."' $selected>".htmlspecialchars($cat_row['category_name'])."</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td style="color: #fff;">
                                ₱ <?php echo number_format($row['price'], 2); ?>
                            </td>
                            <td style="color: #fff; font-weight: 800;">
                                <?php if($is_low): ?>
                                    <span class="stock-warning">
                                        <i class="fas fa-arrow-trend-down"></i> <?php echo $row['stock_quantity']; ?> (CRITICAL)
                                    </span>
                                <?php else: ?>
                                    <?php echo $row['stock_quantity']; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 50px; opacity: 0.3;">THIS SUPPLIER HAS NO REGISTERED PRODUCTS</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="categoryModal" class="modal-overlay">
        <div class="modal-content">
            <h2 style="margin-bottom: 25px; font-weight: 900; font-size: 1.8rem; color: var(--accent-blue);">Register Category</h2>
            <form action="process_category.php" method="POST">
                <label class="form-label">Category Name</label>
                <input type="text" name="category_name" class="form-input" placeholder="e.g. Engine Parts" required autofocus>
                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <button type="button" onclick="closeCategoryModal()" style="flex:1; padding:16px; background:none; color:#fff; border:1px solid var(--glass-border); border-radius:14px; cursor:pointer;">CANCEL</button>
                    <button type="submit" style="flex:2; padding:16px; background:var(--accent-blue); color:#0f172a; border:none; border-radius:14px; font-weight:900; cursor:pointer;">SAVE CATEGORY</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCategoryModal() { document.getElementById('categoryModal').style.display = 'block'; }
        function closeCategoryModal() { document.getElementById('categoryModal').style.display = 'none'; }

        function updateItemCategory(itemId, selectElement) {
            const newCategory = selectElement.value;
            fetch('update_item_category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `item_id=${itemId}&category=${encodeURIComponent(newCategory)}`
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "success") {
                    // Success flash effect
                    selectElement.classList.add('save-success');
                    setTimeout(() => selectElement.classList.remove('save-success'), 1000);
                } else {
                    alert("Update failed: " + data);
                }
            })
            .catch(err => console.error(err));
        }

        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            if(params.has('status')) {
                if(params.get('status') === 'cat_success') alert("NEW CATEGORY SYNCHRONIZED.");
                if(params.get('status') === 'exists') alert("CATEGORY ALREADY REGISTERED.");
            }
        });

        window.onclick = function(e) { if (e.target.className === 'modal-overlay') closeCategoryModal(); }
    </script>
</body>
</html>