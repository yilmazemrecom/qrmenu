<?php
header('Content-Type: application/json');
require_once '../admin/config.php';

try {
    // 1. Bekleyen Siparişler
    // Son 24 saatteki bekleyen siparişleri al
    $order_stmt = $db->prepare("
        SELECT id, table_no, created_at 
        FROM orders 
        WHERE status = 'pending' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        ORDER BY created_at DESC
    ");
    $order_stmt->execute();
    $orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sipariş verilerini formatla
    $formatted_orders = array_map(function ($order) {
        return [
            'id' => $order['id'],
            'table_no' => $order['table_no'],
            'time' => date('H:i', strtotime($order['created_at'])),
            'type' => 'order'
        ];
    }, $orders);

    // 2. Garson Çağrıları
    // Tamamlanmamış (status = 0) çağrıları al
    $call_stmt = $db->prepare("
        SELECT id, table_no, call_type, created_at 
        FROM waiter_calls 
        WHERE status = 0 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC
    ");
    $call_stmt->execute();
    $calls = $call_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Çağrı verilerini formatla
    $formatted_calls = array_map(function ($call) {
        return [
            'id' => $call['id'],
            'table_no' => $call['table_no'],
            'type' => $call['call_type'] ?? 'Garson', // 'Hesap', 'Garson', 'Su' vb.
            'time' => date('H:i', strtotime($call['created_at']))
        ];
    }, $calls);

    echo json_encode([
        'success' => true,
        'orders' => $formatted_orders,
        'calls' => $formatted_calls,
        'timestamp' => time()
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
