<?php
require_once __DIR__ . '/../lib/helpers.php'; // includes h(), redirect

$page = $_GET['page'] ?? 'login';

switch($page){
  case 'login':    require __DIR__ . '/../controllers/login.php'; break;
  case 'logout':   require __DIR__ . '/../controllers/logout.php'; break;
  case 'dashboard':require __DIR__ . '/../controllers/dashboard.php'; break;
  case 'products': require __DIR__ . '/../controllers/products.php'; break;
  case 'suppliers':require __DIR__ . '/../controllers/suppliers.php'; break;
  case 'customers':require __DIR__ . '/../controllers/customers.php'; break;
  case 'purchases':require __DIR__ . '/../controllers/purchases.php'; break;
  case 'sales':    require __DIR__ . '/../controllers/sales.php'; break;
  case 'revenue':  require __DIR__ . '/../controllers/revenue.php'; break;
  case 'export':   require __DIR__ . '/../controllers/export.php'; break;
  default:         http_response_code(404); echo "Not Found";
}
