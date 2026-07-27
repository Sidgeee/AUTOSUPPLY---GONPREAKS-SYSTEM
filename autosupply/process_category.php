<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Capture the category and the shop name
    // Using 'shop_name' here to stay consistent with your suppliers table
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $shop_name = mysqli_real_escape_string($conn, $_POST['shop_name']); 

    // 2. Check if this specific combination already exists in supplier_categories
    // Note: In your screenshot, the column in 'supplier_categories' is 'supplier_name'
    $check_query = "SELECT * FROM supplier_categories 
                    WHERE category_name = '$category_name' 
                    AND supplier_name = '$shop_name'";
    
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) == 0) {
        // 3. Insert into supplier_categories
        // We map the 'shop_name' from your form to the 'supplier_name' column in this table
        $insert_query = "INSERT INTO supplier_categories (supplier_name, category_name) 
                        VALUES ('$shop_name', '$category_name')";
        
        if (mysqli_query($conn, $insert_query)) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&status=cat_success");
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "&status=exists");
        exit();
    }
} else {
    header("Location: suppliers.php");
    exit();
}
?>