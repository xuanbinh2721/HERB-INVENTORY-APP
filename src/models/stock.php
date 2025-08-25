<?php
require_once __DIR__ . '/../db.php';

function product_stock($product_id){
    global $pdo;
    $in = $pdo->prepare("SELECT COALESCE(SUM(qty),0) s FROM purchase_items WHERE product_id=?");
    $in->execute([$product_id]);
    $ins = (float)$in->fetch()['s'];

    $out = $pdo->prepare("SELECT COALESCE(SUM(qty),0) s FROM sale_items WHERE product_id=?");
    $out->execute([$product_id]);
    $outs = (float)$out->fetch()['s'];
    return $ins - $outs;
}

function product_avg_cost($product_id){
    global $pdo;
    $s = $pdo->prepare("SELECT CASE WHEN SUM(qty)=0 THEN 0 ELSE ROUND(SUM(qty*unit_cost)/SUM(qty),2) END avgc FROM purchase_items WHERE product_id=?");
    $s->execute([$product_id]);
    return (float)($s->fetch()['avgc'] ?? 0);
}
