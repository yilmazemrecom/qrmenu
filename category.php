<?php
require_once 'admin/functions.php';
require_once 'header.php';

list($category, $products) = fetchCategoryAndProducts($db);
// ID'leri dizi olarak al (renderProductCard için)
$recommended_ids = $db->query("SELECT product_id FROM recommended_products WHERE is_recommended = 1")->fetchAll(PDO::FETCH_COLUMN);
$new_ids = $db->query("SELECT product_id FROM recommended_products WHERE is_new = 1")->fetchAll(PDO::FETCH_COLUMN);
$vegan_ids = $db->query("SELECT product_id FROM recommended_products WHERE is_vegan = 1")->fetchAll(PDO::FETCH_COLUMN);


?>




<div class="hero-section" style="<?php
if (!empty($category['image'])) {
    echo 'background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url(\'' . SITE_URL . UPLOAD_DIR . $category['image'] . '\') center/cover no-repeat;';
}
?>">
    <div class="hero-content">
        <h1 class="hero-title">
            <?php echo !empty($category['name']) ? clean($category['name']) : ($settings['site_title'] ?? 'QR Menü Sistemi'); ?>
        </h1>
        <p class="hero-subtitle">
            <?php echo !empty($category['description']) ? clean($category['description']) : ($settings['site_description'] ?? 'Dijital menü yönetim sistemi'); ?>
        </p>
    </div>
</div>

<div class="category-container">
    <div class="category-header">
        <h2 class="category-title"><?php echo $category['name']; ?></h2>
    </div>

    <!-- Filtreleme Butonları -->
    <div class="filter-container d-flex gap-2 mb-4 overflow-auto pb-2" style="white-space: nowrap;">
        <button class="btn btn-sm btn-outline-dark active filter-btn rounded-pill px-3" data-filter="all">Tümü</button>
        <button class="btn btn-sm btn-outline-success filter-btn rounded-pill px-3" data-filter="vegan">
            <i class="fas fa-leaf me-1"></i> Vegan
        </button>
        <button class="btn btn-sm btn-outline-primary filter-btn rounded-pill px-3" data-filter="new">
            <i class="fas fa-star me-1"></i> Yeni
        </button>
        <button class="btn btn-sm btn-outline-warning filter-btn rounded-pill px-3" data-filter="recommended">
            <i class="fas fa-thumbs-up me-1"></i> Önerilen
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const products = document.querySelectorAll('.row.g-2 > [class*="col-"]'); // Card'ı kapsayan tüm sütunlar

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    // Active class yönetimi
                    filterBtns.forEach(b => b.classList.remove('active', 'btn-dark', 'text-white'));
                    filterBtns.forEach(b => b.classList.add('btn-outline-dark')); // Hepsi outline'a dön

                    // Seçili butonu boya (renk koruyarak)
                    this.classList.add('active');
                    this.classList.remove('btn-outline-dark');

                    // Buton tipine göre renk ver (Bootstrap sınıfları ezilmesin diye manuel style veya class toggle)
                    if (this.dataset.filter === 'all') this.classList.add('btn-dark', 'text-white');
                    else if (this.dataset.filter === 'vegan') this.classList.replace('btn-outline-success', 'btn-success');
                    else if (this.dataset.filter === 'new') this.classList.replace('btn-outline-primary', 'btn-primary');
                    else if (this.dataset.filter === 'recommended') this.classList.replace('btn-outline-warning', 'btn-warning');

                    // Diğerlerini eski haline getir logic'i biraz karışık, basit tutalım:
                    // Basit Class Toggle Yöntemi:
                    // 1. Reset all to outline defaults
                    filterBtns.forEach(b => {
                        b.className = b.className.replace(/btn-(success|primary|warning|dark)/g, 'btn-outline-$1');
                        b.classList.remove('text-white');
                    });
                    // 2. Set active to solid
                    this.className = this.className.replace('btn-outline-', 'btn-');
                    this.classList.add('text-white');


                    const filter = this.getAttribute('data-filter');

                    products.forEach(col => {
                        const card = col.querySelector('.product-card, .product-card-slider, .product-card-vertical, .product-card-horizontal');
                        if (!card) return;

                        let show = false;
                        if (filter === 'all') {
                            show = true;
                        } else if (filter === 'vegan' && card.dataset.isVegan === '1') {
                            show = true;
                        } else if (filter === 'new' && card.dataset.isNew === '1') {
                            show = true;
                        } else if (filter === 'recommended' && card.dataset.isRecommended === '1') {
                            show = true;
                        }

                        if (show) {
                            col.style.display = 'block';
                            col.classList.add('fade-in'); // (animation.css varsa)
                        } else {
                            col.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>

    <div class="row g-2">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <p class="text-center">Şu anda bu kategori boş.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-12 col-md-6 col-lg-4"> <!-- Mobilde 1 sütun dikey sıralı yatay kartlar -->
                    <?php echo renderProductCard($product, $settings, $recommended_ids, $new_ids, $vegan_ids); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>



</div>














<?php require_once 'footer.php'; ?>