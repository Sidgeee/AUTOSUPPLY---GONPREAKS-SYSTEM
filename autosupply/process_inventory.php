<?php
session_start();
include 'db_connect.php';

// Check if the request is a POST and the action is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    // --- CASE 1: UPDATING AN EXISTING COMPONENT ---
    if ($action == 'update') {
        $part_id      = intval($_POST['part_id']);
        $part_number  = mysqli_real_escape_string($conn, $_POST['part_number']);
        $part_name    = mysqli_real_escape_string($conn, $_POST['part_name']);
        $supplier     = mysqli_real_escape_string($conn, $_POST['supplier_name']);
        $price        = floatval($_POST['price']);
        $stock        = intval($_POST['stock']);

        // Updated query to include supplier_name so edits actually save the vendor change
        $stmt = $conn->prepare("UPDATE inventory SET part_number = ?, part_name = ?, supplier_name = ?, price = ?, stock_quantity = ? WHERE part_id = ?");
        $stmt->bind_param("sssdii", $part_number, $part_name, $supplier, $price, $stock, $part_id);

        if ($stmt->execute()) {
            header("Location: inventory.php?status=updated");
        } else {
            header("Location: inventory.php?status=error&msg=" . urlencode($stmt->error));
        }
        $stmt->close();
    }
    
    // --- CASE 2: ADDING A NEW COMPONENT ---
    elseif ($action == 'add') {
        $part_number  = mysqli_real_escape_string($conn, $_POST['part_number']);
        $part_name    = mysqli_real_escape_string($conn, $_POST['part_name']);
        $supplier     = mysqli_real_escape_string($conn, $_POST['supplier_name']);
        
        // FIX: Use null coalescing to prevent "Undefined array key" warning
        // If your form doesn't have a category input, it will default to 'General'
        $category     = mysqli_real_escape_string($conn, $_POST['category'] ?? 'General');
        
        $price        = floatval($_POST['price']);
        $stock        = intval($_POST['stock']);

        // IMPORTANT: Ensure 'part_number' exists in your MySQL table. 
        // If the error persists, check if the column is actually named 'part_no'
        $stmt = $conn->prepare("INSERT INTO inventory (part_number, part_name, supplier_name, category, price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdi", $part_number, $part_name, $supplier, $category, $price, $stock);

        if ($stmt->execute()) {
            header("Location: inventory.php?status=success");
        } else {
            // Added error message to the URL for easier debugging
            header("Location: inventory.php?status=error&msg=" . urlencode($stmt->error));
        }
        $stmt->close();
    }
    
    $conn->close();
    exit();
} else {
    header("Location: inventory.php");
    exit();
}
?>