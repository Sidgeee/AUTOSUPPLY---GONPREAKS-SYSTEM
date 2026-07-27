<?php
include 'db_connect.php';

if(isset($_POST['supplier'])) {
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier']);
    $query = "SELECT category_name FROM supplier_categories WHERE supplier_name = '$supplier'";
    $result = mysqli_query($conn, $query);

    echo '<option value="">-- Select Category --</option>';
    while($row = mysqli_fetch_assoc($result)) {
        echo '<option value="'.$row['category_name'].'">'.$row['category_name'].'</option>';
    }
}
?>