<?php
// update_supplier.php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize data to prevent SQL Injection
    $id = mysqli_real_escape_string($conn, $_POST['supplier_id']);
    $name = mysqli_real_escape_string($conn, $_POST['shop_name']);
    $city = mysqli_real_escape_string($conn, $_POST['address_city']);
    $province = mysqli_real_escape_string($conn, $_POST['address_province']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Execute Update
    $query = "UPDATE suppliers SET 
              shop_name = '$name', 
              address_city = '$city', 
              address_province = '$province', 
              contact_number = '$contact', 
              email = '$email' 
              WHERE supplier_id = '$id'";

    if (mysqli_query($conn, $query)) {
        header("Location: suppliers.php?status=updated");
    } else {
        header("Location: suppliers.php?status=error");
    }
}
?>