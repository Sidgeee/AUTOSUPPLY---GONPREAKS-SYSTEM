<?php
session_start();
include 'db_connect.php'; 
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
        width: 400px; 
        flex-shrink: 0; 
        display: flex; 
        flex-direction: column; 
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

    .search-input { 
        width: 100%; 
        padding: 15px 20px; 
        background: var(--glass-bg); 
        border: 1px solid var(--border); 
        border-radius: 15px; 
        color: white; 
        font-family: 'Inter', sans-serif;
        outline: none; 
        margin-bottom: 25px; 
        box-sizing: border-box; 
        transition: 0.3s;
    }

    .search-input:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.1);
    }

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
        margin: 20px 0; 
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
        margin-top: auto;
        box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2);
        transition: 0.3s;
    }

    .btn-pay:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 15px 30px rgba(56, 189, 248, 0.3); 
    }
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

        <input type="text" id="productSearch" class="search-input" placeholder="Search product..." oninput="handleFilters()">

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
                if($sup_query) {
                    while($sup = mysqli_fetch_assoc($sup_query)): ?>
                        <button type="button" class="tab-btn" onclick="updateSupplierFilter('<?php echo addslashes($sup['shop_name']); ?>', this)">
                            <?php echo strtoupper($sup['shop_name']); ?>
                        </button>
                    <?php endwhile; 
                } ?>
            </div>
        </div>

        <div class="inventory-scroll" id="inventory-list"></div>
    </div>

    <div class="checkout-panel">
        <span class="nav-label">Current Basket</span>
        <div id="cart-items" style="flex-grow:1; overflow-y:auto;"></div>
        <div id="grand-total">₱ 0.00</div>
        <button class="btn-pay" onclick="processOrder()">Complete Order</button>
    </div>
</div>

<script id="products-json" type="application/json">
    <?php 
        $res = mysqli_query($conn, "SELECT id, product_name, price, stock, shop_name FROM products");
        $data = [];
        if($res) {
            while($row = mysqli_fetch_assoc($res)) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'name' => htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8'),
                    'price' => (float)$row['price'],
                    'stock' => (int)$row['stock'],
                    'supplier' => $row['shop_name']
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
            console.log("Loaded products:", products); // Debugging
            renderProducts(products); 
        } catch (e) {
            console.error("Failed to load products:", e);
        }
    });

    function setOrderType(type, btn) {
        const btns = document.querySelectorAll('#group-order-type .tab-btn');
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentOrderType = type;
    }

    function updateSupplierFilter(brand, btn) {
        const btns = document.querySelectorAll('#group-suppliers .tab-btn');
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentSupplier = brand;
        handleFilters(); 
    }

    function handleFilters() {
        const searchInput = document.getElementById('productSearch');
        const query = searchInput.value.toLowerCase().trim();
        
        const filtered = products.filter(p => {
            const matchName = p.name.toLowerCase().includes(query);
            const matchSup = (currentSupplier === 'All' || p.supplier === currentSupplier);
            return matchName && matchSup;
        });
        
        renderProducts(filtered);
    }

    function renderProducts(list) {
        const container = document.getElementById('inventory-list');
        if (!container) return;
        
        if (list.length === 0) {
            container.innerHTML = '<div style="padding: 20px; color: var(--text-muted);">No products found matching your search.</div>';
            return;
        }

        container.innerHTML = list.map(p => `
            <div class="item-row" onclick="addToCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price})">
                <div>
                    <div class="item-name" style="font-weight:700;">${p.name}</div>
                    <div style="font-size:0.7rem; color:var(--text-muted);">${p.stock} units • ${p.supplier}</div>
                </div>
                <div class="item-price">₱ ${p.price.toLocaleString(undefined, {minimumFractionDigits:2})}</div>
            </div>
        `).join('');
    }

    function addToCart(id, name, price) {
        const item = cart.find(i => i.id === id);
        if (item) item.qty++;
        else cart.push({ id, name, price, qty: 1 });
        updateCartUI();
    }

    function updateCartUI() {
        let total = 0;
        const container = document.getElementById('cart-items');
        container.innerHTML = cart.map(item => {
            total += item.price * item.qty;
            return `<div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:0.85rem; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                        <span>${item.name} <strong style="color:var(--accent-blue)">x${item.qty}</strong></span>
                        <span style="font-family:'JetBrains Mono'">₱${(item.price * item.qty).toLocaleString(undefined, {minimumFractionDigits:2})}</span>
                    </div>`;
        }).join('');
        document.getElementById('grand-total').innerText = `₱ ${total.toLocaleString(undefined, {minimumFractionDigits:2})}`;
    }

    function processOrder() {
        if(cart.length === 0) return alert("Basket is empty!");
        alert(`Order Confirmed!\nMethod: ${currentOrderType}\nItems: ${cart.length}\nTotal: ${document.getElementById('grand-total').innerText}`);
    }
</script>
</body>
</html>