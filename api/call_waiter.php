<?php
// Çıktı tamponlamasını başlat (Olası HTML hatalarını/uyarılarını yakalamak için)
ob_start();

// Hata raporlamayı kapat (JSON çıktısını bozmasını engelle)
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    // Config dosyasını dahil et
    if (!file_exists('../admin/config.php')) {
        throw new Exception('Config dosyası bulunamadı.');
    }
    require_once '../admin/config.php';

    // Functions dosyasını dahil et (clean fonksiyonu için)
    if (file_exists('../admin/functions.php')) {
        require_once '../admin/functions.php';
    }

    // clean fonksiyonu tanımlı değilse fallback bir fonksiyon tanımla
    if (!function_exists('clean')) {
        function clean($data)
        {
            return htmlspecialchars(strip_tags(trim($data)));
        }
    }

    // JSON verisini al
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Geçersiz JSON verisi gönderildi.');
    }

    $table_no = isset($data['table_no']) ? clean($data['table_no']) : '';
    $call_type = isset($data['call_type']) ? clean($data['call_type']) : 'Garson';

    if (empty($table_no)) {
        throw new Exception('Lütfen masa numaranızı giriniz.');
    }

    // Veritabanı bağlantısı kontrolü
    if (!isset($db)) {
        throw new Exception('Veritabanı bağlantısı kurulamadı.');
    }

    // Aynı masadan son 2 dakika içinde çağrı var mı kontrol et (Spam önleme)
    $stmt = $db->prepare("SELECT id FROM waiter_calls WHERE table_no = ? AND status = 0 AND created_at > (NOW() - INTERVAL 2 MINUTE)");
    $stmt->execute([$table_no]);

    if ($stmt->rowCount() > 0) {
        // Tamponu temizle ve JSON döndür
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Zaten aktif bir çağrınız var, lütfen bekleyiniz.']);
        exit;
    }

    // Çağrıyı veritabanına kaydet
    $stmt = $db->prepare("INSERT INTO waiter_calls (table_no, call_type) VALUES (?, ?)");
    if ($stmt->execute([$table_no, $call_type])) {
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Çağrınız başarıyla iletildi, yönlendiriyoruz.']);
    } else {
        throw new Exception('Kayıt sırasında bir hata oluştu.');
    }

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası oluştu.']);
}
?>