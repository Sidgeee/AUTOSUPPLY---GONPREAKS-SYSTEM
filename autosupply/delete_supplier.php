<?php
// delete_supplier.php
session_start();
include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // Warning: This will permanently remove the supplier from the database
    $query = "DELETE FROM suppliers WHERE supplier_id = '$id'";

    if (mysqli_query($conn, $query)) {
        header("Location: suppliers.php?status=deleted");
    } else {
        header("Location: suppliers.php?status=error");
    }
} else {
    // If no ID is provided, just go back to the list
    header("Location: suppliers.php");
}
?>