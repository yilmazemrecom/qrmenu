<?php
require_once '../config.php';
require_once '../functions.php';

// Oturum kontrolü (Global checkSession fonksiyonuna uygun olarak)
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $productId = (int) $_POST['product_id'];

    // CSRF Kontrolü
    if (!validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz CSRF token']);
        exit;
    }

    $field = clean($_POST['field']);
    $value = (int) $_POST['value'];

    // Güvenlik kontrolü - sadece izin verilen alanlar
    $allowedFields = ['is_recommended', 'is_new', 'is_vegan'];

    if (!in_array($field, $allowedFields)) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz alan']);
        exit;
    }

    try {
        // Önce kayıt var mı kontrol et
        $checkStmt = $db->prepare("SELECT id FROM recommended_products WHERE product_id = ?");
        $checkStmt->execute([$productId]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            // Kayıt varsa güncelle
            $stmt = $db->prepare("UPDATE recommended_products SET {$field} = ? WHERE product_id = ?");
            $result = $stmt->execute([$value, $productId]);
        } else {
            // Kayıt yoksa yeni ekle
            $stmt = $db->prepare("INSERT INTO recommended_products (product_id, {$field}, created_at) VALUES (?, ?, NOW())");
            $result = $stmt->execute([$productId, $value]);
        }
        echo json_encode(['success' => $result]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek']);
    exit;
}
?>