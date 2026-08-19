<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$transaksi_id = isset($_GET['transaksi_id']) ? (int)$_GET['transaksi_id'] : 0;

if ($transaksi_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM item_transaksi WHERE transaksi_id = :tid ORDER BY id ASC");
$stmt->execute(['tid' => $transaksi_id]);
$items = $stmt->fetchAll();

echo json_encode($items);
exit;
