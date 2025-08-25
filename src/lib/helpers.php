<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect($to){ header("Location: $to"); exit; }

function csv_download($filename, $headers, $rows){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output','w');
    
    // Add BOM for UTF-8 to ensure Excel recognizes the encoding
    fwrite($out, "\xEF\xBB\xBF");
    
    fputcsv($out, $headers);
    foreach($rows as $r) fputcsv($out, $r);
    fclose($out); exit;
}
