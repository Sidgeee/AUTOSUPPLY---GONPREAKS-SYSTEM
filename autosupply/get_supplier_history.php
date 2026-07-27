<?php
include 'db_connect.php';

if(isset($_GET['id'])) {
    // Sanitize the ID to prevent SQL injection
    $supplier_id = intval($_GET['id']);
    
    /**
     * We join the inventory table (i) with inventory_movements (m)
     * to get the product names and the specific quantities added.
     */
    $query = "SELECT 
                m.created_at as date, 
                i.product_name as item_name, 
                m.quantity, 
                m.movement_type as status
              FROM inventory_movements m
              JOIN inventory i ON m.supplier_product_id = i.product_id
              WHERE i.supplier_id = $supplier_id 
              AND m.movement_type IN ('PURCHASE', 'INITIAL_STOCK')
              ORDER BY m.created_at DESC 
              LIMIT 15";

    $result = mysqli_query($conn, $query);
    $history = [];

    if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
            // Format the date for the UI (e.g., Mar 15, 2026)
            $row['date'] = date("M d, Y", strtotime($row['date']));
            
            // Normalize status labels for the frontend badges
            $row['status'] = ($row['status'] === 'INITIAL_STOCK') ? 'INITIAL' : 'RECEIVED';
            
            $history[] = $row;
        }
    }

    // Return as JSON for the AJAX call in suppliers.php
    header('Content-Type: application/json');
    echo json_encode($history);
}
?>