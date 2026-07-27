SELECT 
    p.name, 
    p.category, 
    p.brand, 
    s.shop_name, 
    sp.cost_price, 
    sp.selling_price, 
    sp.stock_quantity
FROM supplier_products sp
JOIN products p ON sp.product_part_number = p.part_number
JOIN suppliers s ON sp.supplier_id = s.supplier_id
ORDER BY p.name ASC;