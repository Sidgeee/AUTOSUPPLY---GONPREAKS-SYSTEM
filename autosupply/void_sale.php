<?php
include 'db_connect.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$sale_id = $data['sale_id'];

if ($sale_id) {
    $conn->begin_transaction();

    try {
        // 1. Get all items from that sale
        $items_query = $conn->prepare("SELECT supplier_part_number, quantity FROM sale_items WHERE sale_id = ?");
        $items_query->bind_param("i", $sale_id);
        $items_query->execute();
        $items = $items_query->get_result();

        while ($item = $items->fetch_assoc()) {
            // 2. Put stock back into supplier_products
            // Note: We use the part number to find the supplier record
            $update_stock = $conn->prepare("UPDATE supplier_products SET stock_quantity = stock_quantity + ? WHERE product_part_number = ?");
            $update_stock->bind_param("is", $item['quantity'], $item['supplier_part_number']);
            $update_stock->execute();

            // 3. Record the "RETURN" movement
            $movement = $conn->prepare("INSERT INTO inventory_movements (sup_prod_id, movement_type, quantity, reference_id, notes) 
                                        SELECT sup_prod_id, 'RETURN', ?, ?, 'Customer Return/Void' 
                                        FROM supplier_products WHERE product_part_number = ? LIMIT 1");
            $movement->bind_param("iis", $item['quantity'], $sale_id, $item['supplier_part_number']);
            $movement->execute();
        }

        // 4. Mark sale as Voided
        $void_sale = $conn->prepare("UPDATE sales SET status = 'Voided' WHERE sale_id = ?");
        $void_sale->bind_param("i", $sale_id);
        $void_sale->execute();

        $conn->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>