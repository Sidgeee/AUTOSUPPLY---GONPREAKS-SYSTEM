<?php
session_start();
include 'db_connect.php';

// 1. Get Part Data
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM inventory WHERE part_id = $id";
    $result = mysqli_query($conn, $query);
    $item = mysqli_fetch_assoc($result);

    if (!$item) {
        header("Location: inventory.php?status=error");
        exit();
    }
} else {
    header("Location: inventory.php");
    exit();
}

// 2. Handle Update Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = mysqli_real_escape_string($conn, $_POST['part_name']);
    $cat      = mysqli_real_escape_string($conn, $_POST['category']);
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $price    = floatval($_POST['price']);
    $stock    = intval($_POST['stock']);

    $update_sql = "UPDATE inventory SET 
                   part_name = '$name', 
                   category = '$cat', 
                   supplier_name = '$supplier', 
                   price = '$price', 
                   stock_quantity = '$stock' 
                   WHERE part_id = $id";

    if (mysqli_query($conn, $update_sql)) {
        header("Location: inventory.php?status=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Part | GonPreaks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --accent-blue: #00d2ff;
            --accent-green: #00ff88;
        }
        body { 
            background: #0f172a; 
            color: white; 
            font-family: 'Segoe UI', sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .edit-container { 
            background: var(--glass-bg); 
            backdrop-filter: blur(20px); 
            border: 1px solid var(--glass-border); 
            padding: 40px; 
            border-radius: 25px; 
            width: 400px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; opacity: 0.6; font-size: 0.8rem; margin-bottom: 5px; }
        input, select { 
            width: 100%; 
            padding: 12px; 
            background: rgba(255,255,255,0.05); 
            border: 1px solid var(--glass-border); 
            color: white; 
            border-radius: 10px; 
            box-sizing: border-box; 
            outline: none;
        }
        input:focus { border-color: var(--accent-blue); }
        .btn-update { 
            width: 100%; 
            padding: 15px; 
            background: var(--accent-blue); 
            border: none; 
            border-radius: 10px; 
            font-weight: bold; 
            cursor: pointer; 
            color: #000;
            transition: 0.3s;
        }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0, 210, 255, 0.3); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: white; opacity: 0.4; text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { opacity: 1; }
    </style>
</head>
<body>

    <div class="edit-container">
        <h2 style="margin-top: 0; color: var(--accent-blue);">Edit Part #<?php echo $id; ?></h2>
        <form method="POST">
            <div class="form-group">
                <label>Part Name</label>
                <input type="text" name="part_name" value="<?php echo htmlspecialchars($item['part_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Supplier / Shop Name</label>
                <input type="text" name="supplier_name" value="<?php echo htmlspecialchars($item['supplier_name'] ?? ''); ?>" placeholder="Enter Supplier">
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="Engine" <?php if($item['category'] == 'Engine') echo 'selected'; ?>>Engine</option>
                    <option value="Brakes" <?php if($item['category'] == 'Brakes') echo 'selected'; ?>>Brakes</option>
                    <option value="Suspension" <?php if($item['category'] == 'Suspension') echo 'selected'; ?>>Suspension</option>
                    <option value="Electrical" <?php if($item['category'] == 'Electrical') echo 'selected'; ?>>Electrical</option>
                    <option value="Tires" <?php if($item['category'] == 'Tires') echo 'selected'; ?>>Tires</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Price (₱)</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $item['price']; ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Stock Qty</label>
                    <input type="number" name="stock" value="<?php echo $item['stock_quantity']; ?>" required>
                </div>
            </div>

            <button type="submit" class="btn-update">UPDATE PRODUCT</button>
            <a href="inventory.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Inventory</a>
        </form>
    </div>

</body>
</html>