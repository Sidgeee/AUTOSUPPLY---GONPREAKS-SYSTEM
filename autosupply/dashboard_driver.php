<?php
// dashboard_driver.php - included by dashboard.php only when role === 'Driver'.
// Deliberately does NOT query sales totals or inventory - drivers don't see
// company revenue or stock levels, only their own assigned deliveries.

$driver = $conn->query("SELECT driver_status FROM users WHERE user_id = $user_id")->fetch_assoc();
$current_driver_status = $driver['driver_status'] ?? 'Off-Duty';

$pending_count = $conn->query("SELECT COUNT(*) c FROM deliveries WHERE driver_user_id = $user_id AND delivery_status NOT IN ('Delivered','Cancelled')")->fetch_assoc()['c'];
$completed_today = $conn->query("SELECT COUNT(*) c FROM deliveries WHERE driver_user_id = $user_id AND delivery_status = 'Delivered' AND DATE(delivered_at) = CURDATE()")->fetch_assoc()['c'];

$active_deliveries = $conn->query("
    SELECT d.*, s.total_amount, s.payment_method
    FROM deliveries d
    LEFT JOIN sales s ON d.sale_id = s.sale_id
    WHERE d.driver_user_id = $user_id AND d.delivery_status NOT IN ('Delivered','Cancelled')
    ORDER BY FIELD(d.delivery_status, 'Issue','En Route','Assigned','Pending'), d.delivery_id ASC
");

$status_badge_map = [
    'Pending' => 'status-pending', 'Assigned' => 'status-assigned',
    'En Route' => 'status-enroute', 'Issue' => 'status-issue'
];
?>

<div class="dash-grid">
    <div class="stat-card">
        <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">ASSIGNED DELIVERIES</p>
        <h2 style="color:var(--accent-blue); margin:12px 0; font-size:1.7rem;"><?php echo $pending_count; ?> Pending</h2>
        <span class="card-icon"><?php echo icon('route', 22); ?></span>
    </div>
    <div class="stat-card">
        <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">COMPLETED TODAY</p>
        <h2 style="color:var(--accent-green); margin:12px 0; font-size:1.7rem;"><?php echo $completed_today; ?> Stops</h2>
        <span class="card-icon"><?php echo icon('check-circle', 22); ?></span>
    </div>
    <div class="stat-card">
        <p style="margin:0; opacity:0.5; font-size:0.7rem; font-weight:700; letter-spacing:1px;">DRIVER STATUS</p>
        <select id="driverStatusSelect" class="driver-status-select" style="margin-top:12px; width:100%;" onchange="setDriverStatus(this.value)">
            <?php foreach (['Off-Duty', 'Available', 'In-Transit', 'On-Site'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $s === $current_driver_status ? 'selected' : ''; ?>><?php echo $s; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="glass-card">
    <h3 style="margin-top:0; font-size:1.1rem; border-bottom:1px solid var(--glass-border); padding-bottom:15px;">
        <?php echo icon('route', 18); ?> Active Assigned Deliveries
    </h3>

    <div style="margin-top:20px;">
        <?php if ($active_deliveries->num_rows === 0): ?>
            <div style="text-align:center; padding:50px; opacity:0.4;">
                <?php echo icon('check-circle', 40); ?><br><br>No deliveries assigned right now.
            </div>
        <?php else: while ($d = $active_deliveries->fetch_assoc()):
            $badge = $status_badge_map[$d['delivery_status']] ?? 'status-pending';
            $maps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($d['delivery_address'] ?: '');

            // Items for this delivery's order
            $items = $conn->query("
                SELECT si.quantity, COALESCE(i.part_name, si.supplier_part_number) AS part_name
                FROM sale_items si
                LEFT JOIN inventory i ON si.supplier_part_number = i.part_number
                WHERE si.sale_id = " . intval($d['sale_id'])
            );
        ?>
            <div class="delivery-card">
                <div class="top-row">
                    <div>
                        <div style="font-weight:800; font-size:1.05rem;">
                            #<?php echo str_pad($d['sale_id'], 5, '0', STR_PAD_LEFT); ?> - <?php echo htmlspecialchars($d['customer_name'] ?: 'Walk-in Customer'); ?>
                        </div>
                        <div style="font-size:0.8rem; opacity:0.6; margin-top:4px;">
                            <?php echo icon('location', 13); ?> <?php echo htmlspecialchars($d['delivery_address'] ?: 'No address on file'); ?>
                        </div>
                        <?php if (!empty($d['contact_number'])): ?>
                        <div style="font-size:0.8rem; opacity:0.6; margin-top:2px;">
                            <?php echo icon('phone', 13); ?> <?php echo htmlspecialchars($d['contact_number']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <span class="delivery-status-pill <?php echo $badge; ?>"><?php echo htmlspecialchars($d['delivery_status']); ?></span>
                </div>

                <div style="margin-top:14px; padding:14px; background:rgba(255,255,255,0.02); border-radius:12px;">
                    <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:1px; opacity:0.5; margin-bottom:8px;">Items to Deliver</div>
                    <?php if ($items->num_rows === 0): ?>
                        <div style="opacity:0.4; font-size:0.85rem;">No item details found.</div>
                    <?php else: while ($it = $items->fetch_assoc()): ?>
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem; padding:4px 0;">
                            <span><?php echo htmlspecialchars($it['part_name']); ?></span>
                            <span style="font-weight:700; color:var(--accent-blue);">x<?php echo $it['quantity']; ?></span>
                        </div>
                    <?php endwhile; endif; ?>
                    <div style="margin-top:10px; padding-top:10px; border-top:1px solid var(--glass-border); display:flex; justify-content:space-between; font-weight:800;">
                        <span>Total (<?php echo htmlspecialchars($d['payment_method'] ?? 'COD'); ?>)</span>
                        <span style="color:var(--accent-blue);">₱<?php echo number_format($d['total_amount'] ?? 0, 2); ?></span>
                    </div>
                </div>

                <div class="delivery-actions">
                    <a href="<?php echo $maps_url; ?>" target="_blank" class="dbtn dbtn-nav"><?php echo icon('location', 14); ?> Open Navigation</a>

                    <?php if ($d['delivery_status'] !== 'En Route'): ?>
                        <button class="dbtn dbtn-enroute" onclick="updateDelivery(<?php echo $d['delivery_id']; ?>, 'En Route')"><?php echo icon('route', 14); ?> Mark En Route</button>
                    <?php endif; ?>

                    <button class="dbtn dbtn-delivered" onclick="updateDelivery(<?php echo $d['delivery_id']; ?>, 'Delivered')"><?php echo icon('check-circle', 14); ?> Mark Delivered</button>
                    <button class="dbtn dbtn-issue" onclick="updateDelivery(<?php echo $d['delivery_id']; ?>, 'Issue')"><?php echo icon('warning', 14); ?> Report Issue</button>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>
</div>

<script>
    function setDriverStatus(status) {
        fetch('driver_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=set_status&status=${encodeURIComponent(status)}`
        })
        .then(res => res.json())
        .then(data => { if (!data.success) alert(data.message); })
        .catch(() => alert('Connection error - could not update status.'));
    }

    function updateDelivery(deliveryId, newStatus) {
        if (newStatus === 'Delivered' && !confirm('Confirm this delivery is complete?')) return;
        if (newStatus === 'Issue') {
            const note = prompt('Briefly describe the issue (optional):') || '';
        }

        fetch('driver_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_delivery&delivery_id=${deliveryId}&new_status=${encodeURIComponent(newStatus)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) window.location.reload();
            else alert(data.message);
        })
        .catch(() => alert('Connection error - could not update delivery.'));
    }
</script>