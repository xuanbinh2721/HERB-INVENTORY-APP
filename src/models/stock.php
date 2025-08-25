<?php
require_once __DIR__ . '/../db.php';

/**
 * Tính tồn kho hiện tại của sản phẩm
 * @param int $product_id ID sản phẩm
 * @return float Số lượng tồn kho
 */
function product_stock($product_id){
    global $pdo;
    
    if(!$product_id || $product_id <= 0) {
        return 0;
    }
    
    // Tính tổng nhập
    $in = $pdo->prepare("SELECT COALESCE(SUM(qty), 0) as total_in FROM purchase_items WHERE product_id = ?");
    $in->execute([$product_id]);
    $total_in = (float)$in->fetch()['total_in'];

    // Tính tổng xuất
    $out = $pdo->prepare("SELECT COALESCE(SUM(qty), 0) as total_out FROM sale_items WHERE product_id = ?");
    $out->execute([$product_id]);
    $total_out = (float)$out->fetch()['total_out'];
    
    return max(0, $total_in - $total_out);
}

/**
 * Tính giá vốn trung bình của sản phẩm (tính đến thời điểm hiện tại)
 * @param int $product_id ID sản phẩm
 * @param string $as_of_date Ngày tính đến (mặc định là ngày hiện tại)
 * @return float Giá vốn trung bình
 */
function product_avg_cost($product_id, $as_of_date = null){
    global $pdo;
    
    if(!$product_id || $product_id <= 0) {
        return 0;
    }
    
    // Nếu không có ngày cụ thể, lấy ngày hiện tại
    if(!$as_of_date) {
        $as_of_date = date('Y-m-d');
    }
    
    // Tính giá vốn trung bình dựa trên lịch sử nhập hàng đến ngày cụ thể
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN SUM(pi.qty) = 0 THEN 0 
                ELSE ROUND(SUM(pi.qty * pi.unit_cost) / SUM(pi.qty), 2) 
            END as avg_cost 
        FROM purchase_items pi
        JOIN purchases p ON pi.purchase_id = p.id
        WHERE pi.product_id = ? AND p.purchase_date <= ?
    ");
    $stmt->execute([$product_id, $as_of_date]);
    return (float)($stmt->fetch()['avg_cost'] ?? 0);
}

/**
 * Tính giá vốn trung bình của sản phẩm tại thời điểm bán hàng
 * @param int $product_id ID sản phẩm
 * @param string $sale_date Ngày bán hàng
 * @return float Giá vốn trung bình tại thời điểm bán
 */
function product_avg_cost_at_sale($product_id, $sale_date){
    return product_avg_cost($product_id, $sale_date);
}

/**
 * Kiểm tra tồn kho có đủ để bán không
 * @param int $product_id ID sản phẩm
 * @param float $qty Số lượng muốn bán
 * @return bool True nếu đủ tồn kho
 */
function check_stock_available($product_id, $qty){
    $current_stock = product_stock($product_id);
    return $current_stock >= $qty;
}

/**
 * Lấy thông tin tồn kho chi tiết của sản phẩm
 * @param int $product_id ID sản phẩm
 * @return array Thông tin tồn kho
 */
function get_product_stock_info($product_id){
    global $pdo;
    
    if(!$product_id || $product_id <= 0) {
        return [
            'stock' => 0,
            'avg_cost' => 0,
            'total_value' => 0,
            'last_purchase' => null,
            'last_sale' => null
        ];
    }
    
    $stock = product_stock($product_id);
    $avg_cost = product_avg_cost($product_id);
    
    // Lấy thông tin lần nhập cuối
    $last_purchase = $pdo->prepare("
        SELECT pi.*, p.purchase_date, s.name as supplier_name
        FROM purchase_items pi
        JOIN purchases p ON pi.purchase_id = p.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        WHERE pi.product_id = ?
        ORDER BY p.purchase_date DESC
        LIMIT 1
    ");
    $last_purchase->execute([$product_id]);
    $last_purchase_data = $last_purchase->fetch();
    
    // Lấy thông tin lần bán cuối
    $last_sale = $pdo->prepare("
        SELECT si.*, s.sale_date, c.name as customer_name
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE si.product_id = ?
        ORDER BY s.sale_date DESC
        LIMIT 1
    ");
    $last_sale->execute([$product_id]);
    $last_sale_data = $last_sale->fetch();
    
    return [
        'stock' => $stock,
        'avg_cost' => $avg_cost,
        'total_value' => $stock * $avg_cost,
        'last_purchase' => $last_purchase_data,
        'last_sale' => $last_sale_data
    ];
}

/**
 * Lấy danh sách sản phẩm có tồn kho thấp
 * @param float $threshold Ngưỡng cảnh báo (mặc định 10)
 * @return array Danh sách sản phẩm
 */
function get_low_stock_products($threshold = 10){
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.sku,
            p.unit,
            COALESCE(SUM(pi.qty), 0) - COALESCE(SUM(si.qty), 0) as current_stock
        FROM products p
        LEFT JOIN purchase_items pi ON p.id = pi.product_id
        LEFT JOIN sale_items si ON p.id = si.product_id
        GROUP BY p.id, p.name, p.sku, p.unit
        HAVING current_stock <= ?
        ORDER BY current_stock ASC
    ");
    $stmt->execute([$threshold]);
    return $stmt->fetchAll();
}
