<?php
$config = require __DIR__ . '/../../config.php';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Hệ thống quản lý kho và bán hàng">
  <meta name="author" content="Herb Inventory System">
  <title><?= h($config['app_name']) . (isset($title) ? (' - ' . h($title)) : '') ?></title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link href="/assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-light">
