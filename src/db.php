<?php
$config = require __DIR__ . '/config.php';

$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $options);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Initialize schema
$schema = file_get_contents(__DIR__ . '/sql/schema.sql');
$pdo->exec($schema);

// Ensure initial admin user
$exists = $pdo->query("SELECT COUNT(*) AS c FROM users")->fetch()['c'];
if ((int)$exists === 0) {
    $adminUser = getenv('ADMIN_USER') ?: 'admin';
    $adminPass = getenv('ADMIN_PASS') ?: 'admin123';
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users(username, password_hash) VALUES(?,?)");
    $stmt->execute([$adminUser, $hash]);
}
