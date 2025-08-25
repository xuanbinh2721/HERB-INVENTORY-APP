<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../models/stock.php';

$type = $_GET['type'] ?? '';
switch($type){
  case 'products':
    $rows = $pdo->query("SELECT id, sku, name, unit, cost_hint, price_hint FROM products ORDER BY id DESC")->fetchAll();
    $data = array_map(fn($r)=>[$r['id'],$r['sku'],$r['name'],$r['unit'],$r['cost_hint'],$r['price_hint']], $rows);
    csv_download('products.csv',['ID','SKU','Name','Unit','CostHint','PriceHint'],$data);
    break;
  case 'purchases':
    $rows = $pdo->query("SELECT p.id, p.purchase_date, s.name supplier, p.ref_no, SUM(pi.qty*pi.unit_cost) total
                         FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id
                         LEFT JOIN purchase_items pi ON pi.purchase_id=p.id
                         GROUP BY p.id ORDER BY p.id DESC")->fetchAll();
    $data = array_map(fn($r)=>[$r['id'],$r['purchase_date'],$r['supplier'],$r['ref_no'],$r['total']], $rows);
    csv_download('purchases.csv',['ID','Date','Supplier','RefNo','Total'],$data);
    break;
  case 'sales':
    $rows = $pdo->query("SELECT s.id, s.sale_date, c.name customer, s.invoice_no, SUM(si.qty*si.unit_price) total
                         FROM sales s LEFT JOIN customers c ON c.id=s.customer_id
                         LEFT JOIN sale_items si ON si.sale_id=s.id
                         GROUP BY s.id ORDER BY s.id DESC")->fetchAll();
    $data = array_map(fn($r)=>[$r['id'],$r['sale_date'],$r['customer'],$r['invoice_no'],$r['total']], $rows);
    csv_download('sales.csv',['ID','Date','Customer','Invoice','Total'],$data);
    break;
  case 'stock':
    $rows = $pdo->query("SELECT id, sku, name, unit FROM products ORDER BY name")->fetchAll();
    $data = []; foreach($rows as $r){ $stock=product_stock($r['id']); $avg=product_avg_cost($r['id']); $data[] = [$r['id'],$r['sku'],$r['name'],$r['unit'],$stock,$avg]; }
    csv_download('stock.csv',['ID','SKU','Name','Unit','Stock','AvgCost'],$data);
    break;
  case 'revenue':
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-d');
    
    // Get sales data
    $sales = $pdo->prepare("
        SELECT s.id, s.sale_date, s.invoice_no, c.name as customer_name, 
               COALESCE(SUM(si.qty*si.unit_price),0) total, COUNT(si.id) as item_count
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN sale_items si ON si.sale_id=s.id
        WHERE s.sale_date BETWEEN ? AND ?
        GROUP BY s.id 
        ORDER BY s.sale_date DESC
    ");
    $sales->execute([$from, $to]);
    $sales = $sales->fetchAll();
    
    // Get detailed items for COGS calculation
    $itemsStmt = $pdo->prepare("
        SELECT si.product_id, si.qty, si.unit_price, p.name as product_name, p.sku
        FROM sale_items si 
        JOIN sales s ON s.id=si.sale_id
        JOIN products p ON p.id=si.product_id
        WHERE s.sale_date BETWEEN ? AND ?
        ORDER BY s.sale_date DESC
    ");
    $itemsStmt->execute([$from, $to]);
    $items = $itemsStmt->fetchAll();
    
    // Calculate totals with accurate COGS
    $rev_total = array_sum(array_map(fn($r)=>(float)$r['total'], $sales));
    $cogs = 0.0;
    foreach($items as $it) {
        // Get the sale date for this item
        $sale_date_stmt = $pdo->prepare("
            SELECT s.sale_date 
            FROM sales s 
            JOIN sale_items si ON s.id = si.sale_id 
            WHERE si.product_id = ? AND si.qty = ? AND si.unit_price = ?
            ORDER BY s.sale_date DESC 
            LIMIT 1
        ");
        $sale_date_stmt->execute([$it['product_id'], $it['qty'], $it['unit_price']]);
        $sale_date = $sale_date_stmt->fetch()['sale_date'] ?? date('Y-m-d');
        
        // Calculate average cost at the time of sale
        $avg_cost = product_avg_cost($it['product_id'], $sale_date);
        $cogs += (float)$it['qty'] * (float)$avg_cost;
    }
    $gross_profit = $rev_total - $cogs;
    $gross_margin = $rev_total > 0 ? ($gross_profit / $rev_total) * 100 : 0;
    
    // Prepare data for CSV
    $data = [];
    
    // Summary section
    $data[] = ['BÁO CÁO DOANH THU', '', '', '', '', ''];
    $data[] = ['Từ ngày:', $from, 'Đến ngày:', $to, '', ''];
    $data[] = ['', '', '', '', '', ''];
    $data[] = ['Tổng doanh thu:', number_format($rev_total, 0) . ' VNĐ', '', '', '', ''];
    $data[] = ['Giá vốn hàng bán:', number_format($cogs, 0) . ' VNĐ', '', '', '', ''];
    $data[] = ['Lợi nhuận gộp:', number_format($gross_profit, 0) . ' VNĐ', '', '', '', ''];
    $data[] = ['Tỷ suất lợi nhuận:', number_format($gross_margin, 1) . '%', '', '', '', ''];
    $data[] = ['', '', '', '', '', ''];
    
    // Sales details
    $data[] = ['CHI TIẾT HÓA ĐƠN', '', '', '', '', ''];
    $data[] = ['Ngày', 'Số HĐ', 'Khách hàng', 'Số SP', 'Tổng tiền', 'Ghi chú'];
    
    foreach($sales as $r) {
        $data[] = [
            date('d/m/Y', strtotime($r['sale_date'])),
            $r['invoice_no'] ?: '-',
            $r['customer_name'] ?: 'Khách lẻ',
            $r['item_count'],
            number_format($r['total'], 0) . ' VNĐ',
            ''
        ];
    }
    
    $data[] = ['', '', '', '', '', ''];
    
    // COGS details
    $data[] = ['CHI TIẾT GIÁ VỐN', '', '', '', '', ''];
    $data[] = ['Sản phẩm', 'SKU', 'Số lượng bán', 'Giá vốn TB', 'Tổng giá vốn', ''];
    
    foreach($items as $it) {
        // Get the sale date for this item
        $sale_date_stmt = $pdo->prepare("
            SELECT s.sale_date 
            FROM sales s 
            JOIN sale_items si ON s.id = si.sale_id 
            WHERE si.product_id = ? AND si.qty = ? AND si.unit_price = ?
            ORDER BY s.sale_date DESC 
            LIMIT 1
        ");
        $sale_date_stmt->execute([$it['product_id'], $it['qty'], $it['unit_price']]);
        $sale_date = $sale_date_stmt->fetch()['sale_date'] ?? date('Y-m-d');
        
        // Calculate average cost at the time of sale
        $avg_cost = product_avg_cost($it['product_id'], $sale_date);
        $item_cogs = (float)$it['qty'] * (float)$avg_cost;
        $data[] = [
            $it['product_name'],
            $it['sku'],
            number_format($it['qty'], 3),
            number_format($avg_cost, 0) . ' VNĐ',
            number_format($item_cogs, 0) . ' VNĐ',
            ''
        ];
    }
    
    csv_download('revenue_report_' . $from . '_to_' . $to . '.csv', 
                ['Cột 1', 'Cột 2', 'Cột 3', 'Cột 4', 'Cột 5', 'Cột 6'], $data);
    break;
  case 'sale_detail':
    $sale_id = (int)($_GET['id'] ?? 0);
    if(!$sale_id) {
        http_response_code(400); echo "Invalid sale ID";
        exit;
    }
    
    // Get sale header
    $sale_stmt = $pdo->prepare("
        SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $sale_stmt->execute([$sale_id]);
    $sale = $sale_stmt->fetch();
    
    if(!$sale) {
        http_response_code(404); echo "Sale not found";
        exit;
    }
    
    // Get sale items
    $items_stmt = $pdo->prepare("
        SELECT si.*, p.name as product_name, p.sku, p.unit
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
        ORDER BY si.id
    ");
    $items_stmt->execute([$sale_id]);
    $sale_items = $items_stmt->fetchAll();
    
    // Prepare data for CSV
    $data = [];
    
    // Header
    $data[] = ['CHI TIẾT HÓA ĐƠN BÁN HÀNG', '', '', '', '', '', ''];
    $data[] = ['Số hóa đơn:', $sale['invoice_no'] ?: 'Không có', 'Ngày bán:', date('d/m/Y', strtotime($sale['sale_date'])), '', '', ''];
    $data[] = ['Khách hàng:', $sale['customer_name'] ?: 'Khách lẻ', 'Điện thoại:', $sale['customer_phone'] ?: 'Không có', '', '', ''];
    $data[] = ['Địa chỉ:', $sale['customer_address'] ?: 'Không có', '', '', '', '', ''];
    if($sale['notes']) {
        $data[] = ['Ghi chú:', $sale['notes'], '', '', '', '', ''];
    }
    $data[] = ['', '', '', '', '', '', ''];
    
    // Items table
    $data[] = ['STT', 'Sản phẩm', 'SKU', 'Đơn vị', 'Số lượng', 'Đơn giá', 'Thành tiền'];
    
    $total_amount = 0;
    foreach($sale_items as $index => $item) {
        $item_total = $item['qty'] * $item['unit_price'];
        $total_amount += $item_total;
        $data[] = [
            $index + 1,
            $item['product_name'],
            $item['sku'],
            $item['unit'],
            number_format($item['qty'], 3),
            number_format($item['unit_price'], 0) . ' VNĐ',
            number_format($item_total, 0) . ' VNĐ'
        ];
    }
    
    $data[] = ['', '', '', '', '', '', ''];
    $data[] = ['', '', '', '', '', 'Tổng cộng:', number_format($total_amount, 0) . ' VNĐ'];
    
    csv_download('sale_detail_' . $sale_id . '.csv', 
                ['Cột 1', 'Cột 2', 'Cột 3', 'Cột 4', 'Cột 5', 'Cột 6', 'Cột 7'], $data);
    break;
  default:
    http_response_code(400); echo "Invalid export type";
}
