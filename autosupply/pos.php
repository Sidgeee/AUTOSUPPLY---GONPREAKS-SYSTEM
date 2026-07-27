<?php
session_start();
include 'db_connect.php';
require_once 'role_guard.php';
guard(['Admin', 'Account Manager', 'Cashier']);
mysqli_set_charset($conn, "utf8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Line | GonPreaks AutoSupply</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
    :root {
        --accent-blue: #38bdf8;
        --bg: #020617;
        --panel: rgba(255, 255, 255, 0.02);
        --border: rgba(255, 255, 255, 0.08);
        --glass-bg: rgba(255, 255, 255, 0.03);
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
        --danger: #fb7185;
    }

    body {
        margin: 0;
        overflow: hidden;
        height: 100vh;
        background: var(--bg);
        font-family: 'Inter', sans-serif;
        color: var(--text-main);
        display: flex;
    }

    .main-content {
        flex-grow: 1;
        padding: 40px;
        display: flex;
        gap: 25px;
        height: 100vh;
        box-sizing: border-box;
    }

    .terminal-panel, .checkout-panel {
        background: var(--panel);
        backdrop-filter: blur(12px);
        border: 1px solid var(--border);
        border-radius: 28px;
        padding: 35px;
    }

    .terminal-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .checkout-panel {
        width: 420px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }

    .header-content { margin-bottom: 25px; }

    .breadcrumb {
        font-size: 0.7rem;
        opacity: 0.4;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 5px;
        display: block;
        color: #f8fafc;
    }

    .page-title {
        margin: 0;
        font-size: 2.8rem;
        font-weight: 900;
        letter-spacing: -1px;
        background: linear-gradient(to right, #fff 20%, var(--accent-blue) 80%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .nav-label {
        font-size: 0.7rem;
        color: var(--accent-blue);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 12px;
        display: block;
    }

    .search-input, .form-field {
        width: 100%;
        padding: 15px 20px;
        background: var(--glass-bg);
        border: 1px solid var(--border);
        border-radius: 15px;
        color: white;
        font-family: 'Inter', sans-serif;
        outline: none;
        margin-bottom: 15px;
        box-sizing: border-box;
        transition: 0.3s;
    }

    .search-input:focus, .form-field:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.1);
    }

    select.form-field { cursor: pointer; }
    select.form-field option { background: #0f172a; }

    .tab-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 20px;
    }

    .tab-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border);
        color: var(--text-muted);
        font-size: 0.75rem;
        font-weight: 600;
        padding: 10px 18px;
        border-radius: 12px;
        cursor: pointer;
        transition: 0.3s;
    }

    .tab-btn:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

    .tab-btn.active {
        background: var(--accent-blue) !important;
        color: #020617 !important;
        font-weight: 800;
        border-color: var(--accent-blue);
        box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2);
    }

    .inventory-scroll { flex-grow: 1; overflow-y: auto; }

    .item-row {
        display: grid;
        grid-template-columns: 1fr auto;
        padding: 18px 20px;
        background: rgba(255, 255, 255, 0.01);
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: 0.3s;
    }

    .item-row:hover {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 12px;
        transform: scale(1.01);
    }

    .item-row.out-of-stock { opacity: 0.35; cursor: not-allowed; }

    .item-price {
        font-family: 'JetBrains Mono', monospace;
        color: var(--accent-blue);
        font-weight: 700;
        font-size: 1.1rem;
    }

    #grand-total {
        font-family: 'JetBrains Mono', monospace;
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--accent-blue);
        margin: 15px 0;
    }

    .btn-pay {
        width: 100%;
        padding: 18px;
        background: var(--accent-blue);
        color: #020617;
        border: none;
        border-radius: 16px;
        font-weight: 900;
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        margin-top: 15px;
        box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2);
        transition: 0.3s;
    }

    .btn-pay:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(56, 189, 248, 0.3); }
    .btn-pay:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }

    .cart-row-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        font-size: 0.85rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 8px;
    }

    .qty-input {
        width: 45px;
        background: rgba(255,255,255,0.05);
        color: white;
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 4px;
        text-align: center;
    }

    .remove-btn {
        background: none;
        border: none;
        color: var(--danger);
        cursor: pointer;
        padding: 4px;
    }

    .section-divider {
        border-top: 1px solid var(--border);
        margin: 18px 0;
    }

    .customer-toggle {
        font-size: 0.7rem;
        color: var(--accent-blue);
        cursor: pointer;
        margin-bottom: 12px;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
    }

    #customer-fields { display: none; }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="terminal-panel">
        <div class="header-content">
            <span class="breadcrumb">SYSTEMS / ORDERING / LINE</span>
            <h1 class="page-title">ORDER LINE</h1>
        </div>

        <!-- This one input handles BOTH typed search AND barcode-scanner input.
             A scanner types the code fast and sends Enter - if what's typed
             exactly matches a part number/barcode, it's added to the cart
             immediately, scanner-style. -->
        <input type="text" id="productSearch" class="search-input"
               placeholder="Search product, or scan barcode..."
               oninput="handleFilters()"
               onkeypress="handleScanEnter(event)"
               autofocus>

        <div class="nav-section">
            <span class="nav-label">Ordering Method</span>
            <div class="tab-group" id="group-order-type">
                <button type="button" class="tab-btn active" onclick="setOrderType('Personal', this)">PERSONAL</button>
                <button type="button" class="tab-btn" onclick="setOrderType('Email', this)">EMAIL</button>
                <button type="button" class="tab-btn" onclick="setOrderType('Viber', this)">VIBER</button>
                <button type="button" class="tab-btn" onclick="setOrderType('Phone No.', this)">PHONE NO.</button>
                <button type="button" class="tab-btn" onclick="setOrderType('Tel. No.', this)">TEL. NO.</button>
            </div>
        </div>

        <div class="nav-section">
            <span class="nav-label">Filter by Shop</span>
            <div class="tab-group" id="group-suppliers" style="border:none;">
                <button type="button" class="tab-btn active" onclick="updateSupplierFilter('All', this)">ALL SHOPS</button>
                <?php
                $sup_query = mysqli_query($conn, "SELECT shop_name FROM suppliers ORDER BY shop_name ASC");
                if ($sup_query) {
                    while ($sup = mysqli_fetch_assoc($sup_query)):
                        $shop = trim($sup['shop_name']);
                        if ($shop === '') continue;
                ?>
                        <button type="button" class="tab-btn" onclick="updateSupplierFilter('<?php echo addslashes($shop); ?>', this)">
                            <?php echo strtoupper(htmlspecialchars($shop)); ?>
                        </button>
                <?php
                    endwhile;
                }
                ?>
            </div>
        </div>

        <div class="inventory-scroll" id="inventory-list"></div>
    </div>

    <div class="checkout-panel">
        <span class="nav-label">Current Basket</span>
        <div id="cart-items" style="flex-grow:1; overflow-y:auto; min-height: 80px;"></div>
        <div id="grand-total">₱ 0.00</div>

        <span class="nav-label" style="margin-top:5px;">Payment Method</span>
        <select id="payment-method" class="form-field">
            <option value="Cash">Cash</option>
            <option value="GCash">GCash</option>
            <option value="Bank_Transfer">Bank Transfer</option>
        </select>

        <span class="customer-toggle" onclick="toggleCustomerFields()">+ Add delivery / customer details</span>
        <div id="customer-fields">
            <input type="text" id="customer_name" class="form-field" placeholder="Customer name">
            <input type="text" id="customer_contact" class="form-field" placeholder="Contact number">
            <textarea id="delivery_address" class="form-field" placeholder="Delivery address" rows="2" style="resize:vertical;"></textarea>
        </div>

        <button class="btn-pay" id="checkoutBtn" onclick="processOrder()">Complete Order</button>
    </div>
</div>

<script id="products-json" type="application/json">
    <?php
        // Pulling from the real `inventory` table - this is the fix.
        // (The old query hit `products`, which only has 1 dummy test row.)
        $res = mysqli_query($conn, "SELECT part_id, part_number, part_name, price, stock_quantity, supplier_name FROM inventory ORDER BY part_name ASC");
        $data = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $data[] = [
                    'part_id'     => (int)$row['part_id'],
                    'part_number' => $row['part_number'],
                    'name'        => htmlspecialchars($row['part_name'], ENT_QUOTES, 'UTF-8'),
                    'price'       => (float)$row['price'],
                    'stock'       => (int)$row['stock_quantity'],
                    'supplier'    => trim($row['supplier_name'])
                ];
            }
        }
        echo json_encode($data);
    ?>
</script>

<script>
    let products = [];
    let cart = [];
    let currentSupplier = 'All';
    let currentOrderType = 'Personal';

    window.addEventListener('DOMContentLoaded', () => {
        try {
            const dataText = document.getElementById('products-json').textContent;
            products = JSON.parse(dataText);
            renderProducts(products);
        } catch (e) {
            console.error("Failed to load products:", e);
        }
    });

    function setOrderType(type, btn) {
        document.querySelectorAll('#group-order-type .tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentOrderType = type;
    }

    function updateSupplierFilter(shop, btn) {
        document.querySelectorAll('#group-suppliers .tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentSupplier = shop;
        handleFilters();
    }

    function toggleCustomerFields() {
        const el = document.getElementById('customer-fields');
        el.style.display = (el.style.display === 'block') ? 'none' : 'block';
    }

    function handleFilters() {
        const query = document.getElementById('productSearch').value.toLowerCase().trim();
        const filtered = products.filter(p => {
            const matchName = p.name.toLowerCase().includes(query) || p.part_number.toLowerCase().includes(query);
            const matchSup = (currentSupplier === 'All' || p.supplier === currentSupplier);
            return matchName && matchSup;
        });
        renderProducts(filtered);
    }

    // Barcode-scanner support: scanners act like fast typing + Enter.
    // If the current text is an EXACT match to a part number, add it straight
    // to the cart and clear the box, same as a scan.
    function handleScanEnter(e) {
        if (e.key !== 'Enter') return;
        const query = document.getElementById('productSearch').value.trim().toLowerCase();
        if (!query) return;

        const exact = products.find(p => p.part_number.toLowerCase() === query);
        if (exact) {
            addToCart(exact.part_id, exact.name, exact.price, exact.part_number, exact.stock);
            document.getElementById('productSearch').value = '';
            handleFilters();
        }
    }

    function renderProducts(list) {
        const container = document.getElementById('inventory-list');
        if (!container) return;

        if (list.length === 0) {
            container.innerHTML = '<div style="padding: 20px; color: var(--text-muted);">No products found matching your search.</div>';
            return;
        }

        container.innerHTML = list.map(p => {
            const oos = p.stock <= 0;
            return `
            <div class="item-row ${oos ? 'out-of-stock' : ''}"
                 onclick="${oos ? '' : `addToCart(${p.part_id}, '${p.name.replace(/'/g, "\\'")}', ${p.price}, '${p.part_number}', ${p.stock})`}">
                <div>
                    <div class="item-name" style="font-weight:700;">${p.name}</div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">
                        ${p.part_number} • ${oos ? 'OUT OF STOCK' : p.stock + ' units'} • ${p.supplier}
                    </div>
                </div>
                <div class="item-price">₱ ${p.price.toLocaleString(undefined, {minimumFractionDigits:2})}</div>
            </div>`;
        }).join('');
    }

    function addToCart(part_id, name, price, part_number, stock) {
        const item = cart.find(i => i.part_id === part_id);
        const currentQty = item ? item.qty : 0;

        if (currentQty + 1 > stock) {
            alert(`Only ${stock} unit(s) of "${name}" available.`);
            return;
        }

        if (item) item.qty++;
        else cart.push({ part_id, name, price, part_number, qty: 1, maxStock: stock });

        updateCartUI();
    }

    function updateQty(index, newQty) {
        newQty = parseInt(newQty);
        const item = cart[index];
        if (isNaN(newQty) || newQty < 1) newQty = 1;
        if (newQty > item.maxStock) {
            alert(`Only ${item.maxStock} unit(s) of "${item.name}" available.`);
            newQty = item.maxStock;
        }
        item.qty = newQty;
        updateCartUI();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        updateCartUI();
    }

    function updateCartUI() {
        let total = 0;
        const container = document.getElementById('cart-items');

        if (cart.length === 0) {
            container.innerHTML = '<div style="color:var(--text-muted); font-size:0.85rem;">Basket is empty.</div>';
        } else {
            container.innerHTML = cart.map((item, index) => {
                total += item.price * item.qty;
                return `
                <div class="cart-row-item">
                    <span>${item.name}</span>
                    <input type="number" class="qty-input" min="1" max="${item.maxStock}" value="${item.qty}"
                           onchange="updateQty(${index}, this.value)">
                    <span style="font-family:'JetBrains Mono'; min-width:70px; text-align:right;">
                        ₱${(item.price * item.qty).toLocaleString(undefined, {minimumFractionDigits:2})}
                    </span>
                    <button class="remove-btn" title="Remove item" onclick="removeItem(${index})"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg></button>
                </div>`;
            }).join('');
        }

        document.getElementById('grand-total').innerText = `₱ ${total.toLocaleString(undefined, {minimumFractionDigits:2})}`;
    }

    function processOrder() {
        if (cart.length === 0) return alert("Basket is empty!");

        const btn = document.getElementById('checkoutBtn');
        btn.disabled = true;
        btn.innerText = 'Processing...';

        const payload = {
            cart: cart.map(i => ({ part_id: i.part_id, qty: i.qty })),
            payment_method: document.getElementById('payment-method').value,
            order_type: currentOrderType,
            customer_name: document.getElementById('customer_name').value.trim(),
            customer_contact: document.getElementById('customer_contact').value.trim(),
            delivery_address: document.getElementById('delivery_address').value.trim()
        };

        fetch('complete_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'Complete Order';

            if (data.success) {
                // Opens the 3-in-1 receipt (Invoice + Delivery + Packing List) and auto-prints it
                window.open(`receipt.php?sale_id=${data.sale_id}`, '_blank', 'width=420,height=650');
                alert(`Order #${data.order_number} completed!`);
                window.location.reload();
            } else {
                alert("Could not complete order: " + (data.message || "Unknown error."));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'Complete Order';
            alert("Connection error - could not reach the server.");
            console.error(err);
        });
    }
</script>
</body>
</html>