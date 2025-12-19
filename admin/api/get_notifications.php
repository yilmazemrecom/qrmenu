<?php
// Hata raporlamayı kapat ve JSON header ayarla
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    // Config dosyasını dahil et
    $configPath = '../../admin/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('Config dosyası bulunamadı.');
    }
    require_once $configPath;

    // 1. Bekleyen Siparişler (status = 'pending')
    $orderStmt = $db->query("SELECT * FROM orders WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
    $orderCount = count($orders); // Sadece son 5 değil toplam sayıyı da alabiliriz ama şimdilik yeterli

    // 2. Bekleyen Garson Çağrıları (status = 0)
    $callStmt = $db->query("SELECT * FROM waiter_calls WHERE status = 0 ORDER BY created_at DESC LIMIT 5");
    $calls = $callStmt->fetchAll(PDO::FETCH_ASSOC);
    $callCount = count($calls);

    // Detaylı liste formatı hazırlama
    $orderList = [];
    foreach ($orders as $order) {
        $orderList[] = [
            'id' => $order['id'],
            'table_no' => $order['table_no'],
            'total_amount' => $order['total_amount'] ?? 0, // Eğer sütun varsa
            'time' => date('H:i', strtotime($order['created_at']))
        ];
    }

    $callList = [];
    foreach ($calls as $call) {
        $callList[] = [
            'id' => $call['id'],
            'table_no' => $call['table_no'],
            'type' => $call['call_type'],
            'time' => date('H:i', strtotime($call['created_at']))
        ];
    }

    $totalCount = $orderCount + $callCount;

    echo json_encode([
        'success' => true,
        'total_count' => $totalCount,
        'orders' => $orderList,
        'calls' => $callList
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>