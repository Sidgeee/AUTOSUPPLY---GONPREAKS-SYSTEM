<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize inputs
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $shop_name = mysqli_real_escape_string($conn, $_POST['shop_name']);
    
    // Optional: Add logic for city/province if you add those fields to the modal
    // For now, focusing on the core shop_name to get it working
    
    if ($id > 0) {
        // UPDATE existing partner
        $query = "UPDATE suppliers SET shop_name = '$shop_name' WHERE supplier_id = $id";
    } else {
        // INSERT new partner
        $query = "INSERT INTO suppliers (shop_name) VALUES ('$shop_name')";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: suppliers.php?status=success");
    } else {
        header("Location: suppliers.php?status=error");
    }
}
?>