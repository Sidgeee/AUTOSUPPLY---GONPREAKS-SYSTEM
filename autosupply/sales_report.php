<?php 
session_start();
include 'db_connect.php'; 

// Protection check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report | AutoSupply Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { padding: 25px; text-align: center; border-radius: 15px; }
        .stat-value { font-size: 2rem; font-weight: bold; margin-top: 10px; color: #38bdf8; }
        .status-voided { color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div style="margin-left: 260px; padding: 20px;">
        <h2 style="color: #38bdf8;"><i class="fas fa-chart-line"></i> Sales Analytics</h2>

        <?php
        // Updated Summary SQL to only count "Completed" sales for revenue/profit
        $summary_sql = "SELECT 
                            SUM(si.subtotal) as total_revenue,
                            SUM(si.subtotal - (sp.cost_price * si.quantity)) as total_profit,
                            COUNT(DISTINCT s.sale_id) as total_orders
                        FROM sale_items si
                        JOIN sales s ON si.sale_id = s.sale_id
                        JOIN supplier_products sp ON si.supplier_part_number = sp.product_part_number
                        WHERE s.status = 'Completed'";
        $summary = $conn->query($summary_sql)->fetch_assoc();
        ?>

        <div class="stats-grid">
            <div class="glass-card stat-card">
                <h3>Total Revenue</h3>
                <div class="stat-value">₱<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></div>
            </div>
            <div class="glass-card stat-card">
                <h3>Total Profit</h3>
                <div class="stat-value" style="color: #22c55e;">₱<?php echo number_format($summary['total_profit'] ?? 0, 2); ?></div>
            </div>
            <div class="glass-card stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?php echo $summary['total_orders'] ?? 0; ?></div>
            </div>
        </div>

        <div class="glass-card">
            <h3>Recent Transactions</h3>
            <table class="table-glass" style="width: 100%; margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date/Time</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sales_list = $conn->query("SELECT * FROM sales ORDER BY sale_date DESC LIMIT 15");
                    while($sale = $sales_list->fetch_assoc()):
                    ?>
                    <tr>
                        <td>#<?php echo $sale['sale_id']; ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($sale['sale_date'])); ?></td>
                        <td><span class="badge"><?php echo $sale['payment_method']; ?></span></td>
                        <td>₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                        <td>
                            <?php if($sale['status'] == 'Voided'): ?>
                                <span class="status-voided">VOIDED</span>
                            <?php else: ?>
                                <span style="color: #22c55e;">Completed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($sale['status'] == 'Completed'): ?>
                                <button class="btn-pos" style="padding: 5px 10px; font-size: 0.8rem; background: #ef4444;" 
                                        onclick="voidSale(<?php echo $sale['sale_id']; ?>)">
                                    <i class="fas fa-undo"></i> VOID
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function voidSale(saleId) {
        if (confirm("Are you sure you want to VOID Sale #" + saleId + "?\nThis will return the items to stock and remove this from your revenue.")) {
            fetch('void_sale.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sale_id: saleId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("Sale Voided Successfully. Inventory has been updated.");
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("An error occurred while voiding the sale.");
            });
        }
    }
    </script>
</body>
</html>