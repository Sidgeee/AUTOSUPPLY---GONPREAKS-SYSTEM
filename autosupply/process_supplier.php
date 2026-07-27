<?php
session_start();
include 'db_connect.php';

// 1. HANDLE BULK DELETE (GET Request)
if (isset($_GET['delete_ids'])) {
    $ids_string = $_GET['delete_ids'];
    // Validate that the string only contains numbers and commas for safety
    if (preg_match('/^[0-9,]+$/', $ids_string)) {
        $query = "DELETE FROM suppliers WHERE supplier_id IN ($ids_string)";
        if (mysqli_query($conn, $query)) {
            header("Location: suppliers.php?status=deleted");
        } else {
            header("Location: suppliers.php?status=error&msg=" . urlencode(mysqli_error($conn)));
        }
    }
    exit();
}

// 2. HANDLE ADD/EDIT (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if we are editing or adding
    $is_edit = isset($_POST['supplier_id']) && !empty($_POST['supplier_id']);
    
    // Sanitize inputs
    $shop_name        = mysqli_real_escape_string($conn, $_POST['shop_name']);
    $address_city     = mysqli_real_escape_string($conn, $_POST['address_city']);
    $address_province = mysqli_real_escape_string($conn, $_POST['address_province'] ?? '');
    $address_complete = mysqli_real_escape_string($conn, $_POST['address_complete'] ?? '');
    $contact_number   = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $email            = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $viber            = mysqli_real_escape_string($conn, $_POST['viber'] ?? '');

    if ($is_edit) {
        // UPDATE LOGIC
        $supplier_id = intval($_POST['supplier_id']);
        $query = "UPDATE suppliers SET 
                  shop_name=?, address_city=?, address_province=?, 
                  address_complete=?, contact_number=?, email=?, viber=? 
                  WHERE supplier_id=?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssi", 
            $shop_name, $address_city, $address_province, 
            $address_complete, $contact_number, $email, $viber, $supplier_id
        );
        $success_msg = "updated";
    } else {
        // INSERT LOGIC
        $query = "INSERT INTO suppliers (shop_name, address_city, address_province, address_complete, contact_number, email, viber) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssss", 
            $shop_name, $address_city, $address_province, 
            $address_complete, $contact_number, $email, $viber
        );
        $success_msg = "success";
    }

    if ($stmt->execute()) {
        header("Location: suppliers.php?status=" . $success_msg);
    } else {
        header("Location: suppliers.php?status=error&msg=" . urlencode($stmt->error));
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: suppliers.php");
    exit();
}
?>