<?php
header('Content-Type: application/json');
require_once '../admin/config.php';

// Son kontrol zamanı (client'tan gelir)
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-1 minute'));

try {
    // Yeni sipariş var mı?
    // status = 'pending' olan ve created_at > last_check olanlar
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending' AND created_at > ?");
    $stmt->execute([$last_check]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $count = (int) $result['count'];

    echo json_encode([
        'success' => true,
        'has_new' => $count > 0,
        'count' => $count,
        'timestamp' => date('Y-m-d H:i:s') // Client bu zamanı bir sonraki istekte kullanacak
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
