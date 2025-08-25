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
    
    // Calculate totals
    $rev_total = array_sum(array_map(fn($r)=>(float)$r['total'], $sales));
    $cogs = 0.0;
    foreach($items as $it) {
        $avg_cost = product_avg_cost($it['product_id']);
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
        $avg_cost = product_avg_cost($it['product_id']);
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
  default:
    http_response_code(400); echo "Invalid export type";
}
