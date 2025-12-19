<?php
header('Content-Type: application/json');
require_once '../admin/config.php';
require_once '../admin/functions.php';

// Önceki çıktıları temizle (Bazen config dosyalarından boşluk/hata gelebilir)
if (ob_get_length())
    ob_clean();


// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek metodu']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz veri']);
    exit;
}

$table_no = clean($input['table_no'] ?? '');
$note = clean($input['note'] ?? '');
$items = $input['items'] ?? [];

// Validation
if (empty($table_no)) {
    echo json_encode(['success' => false, 'error' => 'Masa numarası gereklidir']);
    exit;
}

if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Sepet boş']);
    exit;
}

try {
    $db->beginTransaction();

    // Calculate total
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += ($item['price'] * $item['quantity']);
    }

    // Create Order
    $stmt = $db->prepare("INSERT INTO orders (table_no, total_amount, note, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$table_no, $total_amount, $note]);
    $order_id = $db->lastInsertId();

    // Create Order Items
    $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");

    foreach ($items as $item) {
        $itemStmt->execute([
            $order_id,
            $item['id'],
            $item['name'],
            $item['quantity'],
            $item['price']
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Hata: ' . $e->getMessage()]);
}
