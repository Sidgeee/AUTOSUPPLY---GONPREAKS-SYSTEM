<?php
// receipt.php?sale_id=X
// Prints all 3 documents back-to-back (page-break between each) so a single
// "Print" triggers Invoice Receipt + Delivery Receipt + Packing List together,
// formatted to a narrow/short paper width.

session_start();
include 'db_connect.php';

$sale_id = intval($_GET['sale_id'] ?? 0);
if (!$sale_id) { die("Missing sale ID."); }

$order_number = str_pad($sale_id, 5, '0', STR_PAD_LEFT);

$stmt = $conn->prepare("SELECT * FROM sales WHERE sale_id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
if (!$sale) { die("Sale #$sale_id not found."); }

// Join back to inventory so we can show the product name, not just the part number
$stmt = $conn->prepare("
    SELECT si.*, i.part_name
    FROM sale_items si
    LEFT JOIN inventory i ON si.supplier_part_number = i.part_number
    WHERE si.sale_id = ?
");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT * FROM deliveries WHERE sale_id = ? ORDER BY delivery_id DESC LIMIT 1");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();

$date_str = date('M d, Y h:i A', strtotime($sale['created_at']));
$order_type = $sale['order_type'] ?? 'Personal';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt #<?php echo $order_number; ?></title>
<style>
    /* Adjust to 58mm if your printer/paper cut is narrower */
    @page { size: 80mm auto; margin: 4mm; }
    * { box-sizing: border-box; }
    body {
        font-family: 'Courier New', monospace;
        color: #000;
        width: 76mm;
        margin: 0 auto;
        font-size: 11px;
        line-height: 1.45;
    }
    .receipt { padding: 6px 0 18px 0; page-break-after: always; }
    .receipt:last-child { page-break-after: auto; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .line { border-top: 1px dashed #000; margin: 6px 0; }
    .row { display: flex; justify-content: space-between; gap: 8px; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th, td { text-align: left; padding: 2px 0; font-size: 10px; vertical-align: top; }
    th { border-bottom: 1px solid #000; }
    .doc-title { font-size: 13px; font-weight: bold; text-transform: uppercase; margin: 6px 0 4px; text-align: center; letter-spacing: 1px; }
    .totals-row { font-weight: bold; font-size: 12px; }
    .footer { text-align: center; font-size: 9px; margin-top: 10px; }
    .sig-line { border-top: 1px solid #000; margin-top: 22px; padding-top: 2px; font-size: 9px; }

    @media screen {
        body { background: #ccc; }
        .receipt { background: #fff; max-width: 320px; margin: 20px auto; padding: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.25); }
    }
</style>
</head>
<body>

<!-- ===================== 1. INVOICE RECEIPT ===================== -->
<div class="receipt">
    <div class="center bold">GONPREAKS AUTOSUPPLY</div>
    <div class="center">Quezon City, Philippines</div>
    <div class="doc-title">Invoice Receipt</div>
    <div class="row"><span>OR#:</span><span class="bold"><?php echo $order_number; ?></span></div>
    <div class="row"><span>Date:</span><span><?php echo $date_str; ?></span></div>
    <div class="row"><span>Cashier:</span><span><?php echo htmlspecialchars($sale['cashier_name'] ?? 'System Admin'); ?></span></div>
    <div class="row"><span>Order Via:</span><span><?php echo htmlspecialchars($order_type); ?></span></div>
    <?php if (!empty($delivery['customer_name'])): ?>
    <div class="row"><span>Customer:</span><span><?php echo htmlspecialchars($delivery['customer_name']); ?></span></div>
    <?php endif; ?>
    <div class="line"></div>
    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Amt</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?php echo htmlspecialchars($it['part_name'] ?: $it['supplier_part_number']); ?></td>
                <td><?php echo (int)$it['quantity']; ?></td>
                <td>₱<?php echo number_format($it['subtotal'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="line"></div>
    <div class="row totals-row"><span>TOTAL:</span><span>₱<?php echo number_format($sale['total_amount'], 2); ?></span></div>
    <div class="row"><span>Payment:</span><span><?php echo htmlspecialchars($sale['payment_method']); ?></span></div>
    <div class="footer">Thank you for choosing Gonpreaks!</div>
</div>

<!-- ===================== 2. DELIVERY RECEIPT ===================== -->
<div class="receipt">
    <div class="center bold">GONPREAKS AUTOSUPPLY</div>
    <div class="doc-title">Delivery Receipt</div>
    <div class="row"><span>DR#:</span><span class="bold"><?php echo $order_number; ?></span></div>
    <div class="row"><span>Date:</span><span><?php echo $date_str; ?></span></div>
    <div class="line"></div>
    <div class="row"><span>Customer:</span><span><?php echo htmlspecialchars($delivery['customer_name'] ?? 'Walk-in'); ?></span></div>
    <div class="row"><span>Contact:</span><span><?php echo htmlspecialchars($delivery['contact_number'] ?? '-'); ?></span></div>
    <div>Address:<br><?php echo nl2br(htmlspecialchars($delivery['delivery_address'] ?? '-')); ?></div>
    <div class="line"></div>
    <table>
        <thead><tr><th>Item</th><th>Qty</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?php echo htmlspecialchars($it['part_name'] ?: $it['supplier_part_number']); ?></td>
                <td><?php echo (int)$it['quantity']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="line"></div>
    <div class="sig-line">Driver / Rider Signature</div>
    <div class="sig-line">Received By (Customer Signature)</div>
</div>

<!-- ===================== 3. PACKING LIST ===================== -->
<div class="receipt">
    <div class="center bold">GONPREAKS AUTOSUPPLY</div>
    <div class="doc-title">Packing List</div>
    <div class="row"><span>Ref#:</span><span class="bold"><?php echo $order_number; ?></span></div>
    <div class="row"><span>Date:</span><span><?php echo $date_str; ?></span></div>
    <div class="line"></div>
    <table>
        <thead><tr><th>Item</th><th>Part #</th><th>Qty</th><th>[ ]</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?php echo htmlspecialchars($it['part_name'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($it['supplier_part_number']); ?></td>
                <td><?php echo (int)$it['quantity']; ?></td>
                <td>[&nbsp;&nbsp;]</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="line"></div>
    <div>Total line items: <?php echo count($items); ?></div>
    <div class="sig-line">Packed By</div>
    <div class="sig-line">Checked By</div>
</div>

<script>
    // Auto-triggers the print dialog for all 3 documents as one job.
    window.onload = function () { window.print(); };
</script>
</body>
</html>