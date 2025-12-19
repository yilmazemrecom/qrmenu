<?php

include_once 'config.php';

// Güvenli string temizleme fonksiyonu  
if (!function_exists('clean')) {
    function clean($string)
    {
        return htmlspecialchars(trim($string ?? ''), ENT_QUOTES, 'UTF-8');
    }
}



// Başarı mesajı oluşturma
if (!function_exists('successMessage')) {
    function successMessage($message)
    {
        return '<div class="alert alert-success">' . clean($message) . '</div>';
    }
}

// Hata mesajı oluşturma
if (!function_exists('errorMessage')) {
    function errorMessage($message)
    {
        return '<div class="alert alert-danger">' . clean($message) . '</div>';
    }
}

// Oturum kontrolü
if (!function_exists('checkSession')) {
    function checkSession()
    {
        if (!isset($_SESSION['admin'])) {
            header('Location: login.php');
            exit;
        }
    }
}

// Para formatı
if (!function_exists('formatPrice')) {
    function formatPrice($price)
    {
        return number_format($price, 2, ',', '.');
    }
}




// CSRF Token Oluştur
function createCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token Doğrula
function validateCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('CSRF Hatası: Geçersiz istek!');
    }
    return true;
}

// Güvenli Dosya Yükleme Fonksiyonu
function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'webp', 'avif'])
{
    if ($file['error'] === 0) {
        // 1. Uzantı Kontrolü
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_types)) {
            echo "Geçersiz dosya uzantısı.";
            return false;
        }

        // 2. MIME Type Kontrolü (finfo)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
        ];

        if (!in_array($mime_type, $allowed_mimes)) {
            echo "Geçersiz dosya içeriği (MIME).";
            return false;
        }

        // 3. Resim Boyutu/Doğrulama (getimagesize)
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            echo "Dosya geçerli bir resim değil.";
            return false;
        }

        // Benzersiz dosya adı oluştur
        $filename = uniqid() . '.' . $ext;
        $upload_path = SYSTEM_UPLOAD_DIR . $filename;

        // Yükleme dizini yoksa oluştur
        if (!file_exists(SYSTEM_UPLOAD_DIR)) {
            mkdir(SYSTEM_UPLOAD_DIR, 0777, true);
            // Klasör güvenliği için boş index.php
            file_put_contents(SYSTEM_UPLOAD_DIR . 'index.php', '');
        }

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            return $filename;
        } else {
            echo "Dosya yüklenirken bir hata oluştu.";
        }
    } else {
        echo "Dosya yükleme hatası: " . $file['error'];
    }
    return false;
}

// Ziyaretçi Sayacı
function incrementPageViews($db)
{
    $today = date('Y-m-d');

    // Tablo var mı kontrol et (İlk kurulum için basit çözüm)
    try {
        $db->query("SELECT 1 FROM page_views LIMIT 1");
    } catch (PDOException $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS page_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            view_date DATE NOT NULL,
            views INT DEFAULT 1,
            UNIQUE KEY unique_date (view_date)
        )");
    }

    // Bugünü kontrol et
    $stmt = $db->prepare("INSERT INTO page_views (view_date, views) VALUES (?, 1) ON DUPLICATE KEY UPDATE views = views + 1");
    $stmt->execute([$today]);
}


// Veritabanından ayarları çek
$settingsQuery = $db->query("SELECT * FROM settings LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

// Kategori bilgilerini al
$category = $db->prepare("SELECT * FROM categories WHERE id = ?");
$category = $category->fetch(PDO::FETCH_ASSOC);






// Ürünleri listele
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$searchQuery = $search ? "AND p.name LIKE '%$search%'" : '';

$products = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE 1=1 $searchQuery
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);


// Önerilen ürünleri çek
$recommended_query = "
    SELECT p.* 
    FROM products p
    INNER JOIN recommended_products rp ON p.id = rp.product_id
    WHERE p.status = 1 AND rp.is_recommended = 1
";

// Yeni ürünleri çek
$new_products_query = "
    SELECT p.* 
    FROM products p
    INNER JOIN recommended_products rp ON p.id = rp.product_id
    WHERE p.status = 1 AND rp.is_new = 1
";


// kategorileri çek
$categories = $db->query("SELECT * FROM categories WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);

// ayarları çek
$settingsQuery = $db->query("SELECT * FROM settings LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);


// Önerilen ürünleri göster
$recommended_products = $db->query($recommended_query)->fetchAll(PDO::FETCH_ASSOC);

function getCategoryAndProducts($db, $category_id)
{
    // Kategori bilgilerini al
    $category = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $category->execute([$category_id]);
    $category = $category->fetch(PDO::FETCH_ASSOC);

    // Kategoriye ait ürünleri al
    $products = $db->prepare("SELECT * FROM products WHERE category_id = ? AND status = 1");
    $products->execute([$category_id]);
    $products = $products->fetchAll(PDO::FETCH_ASSOC);

    return [$category, $products];
}

function fetchCategoryAndProducts($db)
{
    // Kategori ID'sini URL'den al
    $category_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    list($category, $products) = getCategoryAndProducts($db, $category_id);

    if (!$category) {
        echo "Kategori bulunamadı.";
        exit;
    }

    return [$category, $products];
}

function getVeganProducts($db)
{
    $vegan_query = "
        SELECT p.* 
        FROM products p
        INNER JOIN recommended_products rp ON p.id = rp.product_id
        WHERE p.status = 1 AND rp.is_vegan = 1
    ";
    return $db->query($vegan_query)->fetchAll(PDO::FETCH_ASSOC);
}




// Ürün Kartı Render Fonksiyonu
function renderProductCard($product, $settings, $recommended_ids = [], $new_ids = [], $vegan_ids = [], $is_slider = false)
{
    // Resim yolunu belirle
    $image_path = !empty($product['image']) ? UPLOAD_DIR . $product['image'] : (isset($settings['logo']) ? UPLOAD_DIR . $settings['logo'] : 'admin/assets/img/no-image.jpg');

    // Ürün özelliklerini kontrol et
    // ID kontrolü için array_map ile int dönüşümü gerekebilir veya direkt kontrol
    // Burada product['id'] string gelebilir, o yüzden loose comparison veya dönüşüm faydalı olabilir
    $p_id = $product['id'];

    $is_recommended = in_array($p_id, $recommended_ids) || (isset($product['is_recommended']) && $product['is_recommended'] == 1);
    $is_new = in_array($p_id, $new_ids) || (isset($product['is_new']) && $product['is_new'] == 1);
    $is_vegan = in_array($p_id, $vegan_ids) || (isset($product['is_vegan']) && $product['is_vegan'] == 1);

    // HTML sınıfları ve yapıları
    $card_class = $is_slider ? 'product-card-slider' : 'product-card';
    $image_container_class = $is_slider ? 'product-image-container-slider' : 'product-image-container';
    $image_class = $is_slider ? 'product-image-slider' : 'product-image';
    $price_class = $is_slider ? 'price-tag-slider' : 'price-tag';
    $badge_container_class = $is_slider ? 'badge-container-slider' : 'badge-container';
    $info_class = $is_slider ? 'product-info-slider' : 'product-info';
    $title_class = $is_slider ? 'product-title-slider' : 'product-title';

    // Açıklama kısaltma
    $description = !empty($product['description']) ? htmlspecialchars($product['description']) : ' ';
    $short_desc = '';
    if (!$is_slider && !empty($product['description'])) {
        $short_desc = strlen($product['description']) > 20 ? substr(htmlspecialchars($product['description']), 0, 20) . '...' : htmlspecialchars($product['description']);
    }

    // İsim kısaltma (Slider için)
    $name = htmlspecialchars($product['name']);
    $display_name = $name;
    if ($is_slider && strlen($name) > 18) {
        $display_name = substr($name, 0, 18) . '...';
    }

    ob_start();
    ?>
    <div class="<?php echo $card_class; ?>">

        <!-- Görsel Alanı - Modalı Tetikler -->
        <div class="<?php echo $image_container_class; ?>" style="cursor: pointer;" data-bs-toggle="modal"
            data-bs-target="#productModal" data-id="<?php echo $p_id; ?>" data-name="<?php echo $name; ?>"
            data-image="<?php echo $image_path; ?>" data-price="<?php echo number_format($product['price'], 2); ?>"
            data-description="<?php echo $description; ?>" data-is-new="<?php echo $is_new ? '1' : '0'; ?>"
            data-is-recommended="<?php echo $is_recommended ? '1' : '0'; ?>"
            data-is-vegan="<?php echo $is_vegan ? '1' : '0'; ?>">

            <img src="<?php echo $image_path; ?>" class="<?php echo $image_class; ?>" alt="<?php echo $name; ?>"
                loading="lazy">
            <div class="<?php echo $price_class; ?>"><?php echo number_format($product['price'], 2); ?> ₺</div>
            <div class="<?php echo $badge_container_class; ?>">
                <?php if ($is_recommended): ?>
                    <div class="badge recommended-badge">Önerilen</div>
                <?php endif; ?>
                <?php if ($is_new): ?>
                    <div class="badge new-badge">Yeni</div>
                <?php endif; ?>
                <?php if ($is_vegan): ?>
                    <div class="badge vegan-badge">Vegan</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bilgi Alanı -->
        <div class="<?php echo $info_class; ?>">
            <!-- Başlık ve Açıklama - Modalı Tetikler -->
            <div style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#productModal"
                data-id="<?php echo $p_id; ?>" data-name="<?php echo $name; ?>" data-image="<?php echo $image_path; ?>"
                data-price="<?php echo number_format($product['price'], 2); ?>"
                data-description="<?php echo $description; ?>" data-is-new="<?php echo $is_new ? '1' : '0'; ?>"
                data-is-recommended="<?php echo $is_recommended ? '1' : '0'; ?>"
                data-is-vegan="<?php echo $is_vegan ? '1' : '0'; ?>">

                <h3 class="<?php echo $title_class; ?>"><?php echo $display_name; ?></h3>
                <?php if (!$is_slider && !empty($short_desc)): ?>
                    <p class="product-description"><?php echo $short_desc; ?></p>
                <?php endif; ?>
            </div>

            <!-- Buton - Modalı Tetiklemez -->
            <button class="btn btn-sm btn-outline-primary mt-2 w-100"
                onclick="addToCart(<?php echo $p_id; ?>, '<?php echo addslashes($display_name); ?>', <?php echo $product['price']; ?>, '<?php echo $image_path; ?>')">
                <i class="fas fa-shopping-basket me-1"></i> Sepete Ekle
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>