<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = intval($_POST['item_id']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);

    // Make sure 'part_id' matches your actual primary key column name!
    $query = "UPDATE inventory SET category = '$category' WHERE part_id = $item_id";

    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        // This will help us see the error in the browser console if it fails
        http_response_code(500);
        echo mysqli_error($conn);
    }
}
?>