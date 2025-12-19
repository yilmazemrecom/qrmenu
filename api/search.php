<?php
header('Content-Type: application/json');
require_once '../admin/config.php';

$keyword = isset($_GET['q']) ? $_GET['q'] : '';

if (strlen($keyword) < 3) {
    echo json_encode(['success' => false, 'error' => 'En az 3 karakter giriniz.']);
    exit;
}

try {
    // Ürünleri ara (isim veya açıklama)
    $stmt = $db->prepare("
        SELECT id, name, description, price, image 
        FROM products 
        WHERE status = 1 
        AND (name LIKE :keyword OR description LIKE :keyword)
        LIMIT 10
    ");
    $stmt->execute(['keyword' => "%$keyword%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Resim yollarını düzelt (Relative path kullan, config'deki SITE_URL hatalı olsa bile çalışsın)
    foreach ($products as &$product) {
        // Eğer resim varsa 'admin/uploads/' (UPLOAD_DIR config'deki path ama string olarak yazalım garanti olsun)
        // Config'de UPLOAD_DIR 'admin/uploads/' olarak tanımlı.
        $product['image_url'] = !empty($product['image']) ? 'admin/uploads/' . $product['image'] : 'admin/assets/img/no-image.jpg';
    }

    echo json_encode(['success' => true, 'products' => $products]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası']);
}
?>