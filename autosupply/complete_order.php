<?php
// complete_order.php
// Replaces the broken save_sale.php / process_checkout.php.
// - Reads from the `inventory` table (your real product data)
// - Recalculates prices server-side (never trusts the browser's numbers)
// - Locks rows during the transaction so stock can't go negative from
//   two sales happening at the same time
// - Saves every line item into sale_items (this is what makes receipts possible)
// - Optionally saves delivery info if the cashier filled it in

session_start();
include 'db_connect.php';
@include_once 'activity_logger.php';
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {}
}
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Account Manager', 'Cashier'])) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to complete orders.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit;
}

$cart              = $data['cart'];
$payment_method    = $data['payment_method']    ?? 'Cash';       // must match enum: Cash, GCash, Bank_Transfer
$order_type        = $data['order_type']        ?? 'Personal';   // Personal, Email, Viber, Phone No., Tel. No.
$customer_name     = trim($data['customer_name']     ?? '');
$customer_contact  = trim($data['customer_contact']  ?? '');
$delivery_address  = trim($data['delivery_address']  ?? '');

$cashier_name = $_SESSION['name'] ?? 'System Admin';
$actor_id = $_SESSION['user_id'] ?? null;

$conn->begin_transaction();

try {
    $total = 0;
    $validated_items = [];

    foreach ($cart as $item) {
        $part_id = intval($item['part_id'] ?? 0);
        $qty     = intval($item['qty'] ?? 0);
        if ($part_id <= 0 || $qty < 1) continue;

        // FOR UPDATE locks this row until commit/rollback, preventing overselling
        // if two cashiers check out the same part at the same time.
        $stmt = $conn->prepare("SELECT part_id, part_number, part_name, price, stock_quantity FROM inventory WHERE part_id = ? FOR UPDATE");
        $stmt->bind_param("i", $part_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) throw new Exception("Product ID $part_id no longer exists.");
        if ($row['stock_quantity'] < $qty) {
            throw new Exception("Not enough stock for \"{$row['part_name']}\". Only {$row['stock_quantity']} left.");
        }

        $subtotal = $row['price'] * $qty;
        $total += $subtotal;

        $validated_items[] = [
            'part_id'     => $row['part_id'],
            'part_number' => $row['part_number'],
            'part_name'   => $row['part_name'],
            'price'       => $row['price'],
            'qty'         => $qty,
            'subtotal'    => $subtotal
        ];
    }

    if (empty($validated_items)) {
        throw new Exception("No valid items in the cart.");
    }

    // 1. Sale header
    $stmt = $conn->prepare("INSERT INTO sales (cashier_name, handled_by_user_id, total_amount, payment_method, order_type, created_at, status) VALUES (?, ?, ?, ?, ?, NOW(), 'Completed')");
    $stmt->bind_param("sidss", $cashier_name, $actor_id, $total, $payment_method, $order_type);
    $stmt->execute();
    $sale_id = $conn->insert_id;
    $stmt->close();

    log_activity($conn, $actor_id, 'SALE_COMPLETED', "Completed sale #" . str_pad($sale_id, 5, '0', STR_PAD_LEFT) . " - ₱" . number_format($total, 2), $sale_id);

    // 2. Line items + stock deduction
    foreach ($validated_items as $item) {
        $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, supplier_part_number, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isidd", $sale_id, $item['part_number'], $item['qty'], $item['price'], $item['subtotal']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE inventory SET stock_quantity = stock_quantity - ? WHERE part_id = ?");
        $stmt->bind_param("ii", $item['qty'], $item['part_id']);
        $stmt->execute();
        $stmt->close();
    }

    // 3. Optional delivery record (only if the cashier filled in customer info)
    if ($customer_name !== '' || $delivery_address !== '') {
        $stmt = $conn->prepare("INSERT INTO deliveries (sale_id, customer_name, contact_number, delivery_address, delivery_status, delivery_date) VALUES (?, ?, ?, ?, 'Pending', CURDATE())");
        $stmt->bind_param("isss", $sale_id, $customer_name, $customer_contact, $delivery_address);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success'      => true,
        'sale_id'      => $sale_id,
        'order_number' => str_pad($sale_id, 5, '0', STR_PAD_LEFT) // 00001, 00002, ...
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();