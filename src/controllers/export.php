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
  default:
    http_response_code(400); echo "Invalid export type";
}
