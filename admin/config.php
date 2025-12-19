<?php
// Veritabanı bağlantı bilgileri  
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qr_menu');

// Veritabanı bağlantısı  
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
    exit;
}

// Oturum başlat  
session_start();

// Zaman dilimi ayarı  
date_default_timezone_set('Europe/Istanbul');

// Site ayarları    
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$server_name = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);
// Admin klasöründe olduğumuz için bir üst dizine çıkalım (bu dosya admin/functions.php üzerinden çağrılıyor olabilir ama config direkt admin/config.php)
// Eğer bu dosya admin/ altında ise dirname /admin döndürür.
// Proje kök dizinine ulaşmak için /admin'i kaldırmayı deneyelim.
$base_path = str_replace(['/admin', '\\admin'], '', $script_path);
$base_url = rtrim($base_path, '/\\') . '/';

define('SITE_URL', $protocol . '://' . $server_name . $base_url);
define('UPLOAD_DIR', 'admin/uploads/'); // Web URL'leri için    
define('SYSTEM_UPLOAD_DIR', __DIR__ . '/uploads/'); // Dosya sistemi işlemleri için



// Görsel URL'si oluşturma  
$image = !empty($product['image'])
    ? SITE_URL . UPLOAD_DIR . $product['image']
    : SITE_URL . 'assets/img/no-image.jpg';
?>