<?php

require_once 'header.php';


?>

<!-- Hero Slider -->
<?php
$slider_query = $db->query("SELECT * FROM slider_images WHERE status = 1 ORDER BY sort_order ASC");
$slider_images = $slider_query->fetchAll(PDO::FETCH_ASSOC);
if (count($slider_images) > 0):
    ?>
    <div class="hero-slider-container">
        <div class="hero-slider" id="heroSlider">
            <?php foreach ($slider_images as $index => $slide): ?>
                <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>"
                    style="background-image: url('<?php echo UPLOAD_DIR . $slide['image']; ?>')">
                    <div class="hero-overlay"></div>
                    <div class="hero-content">
                        <div class="container">
                            <h1 class="hero-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                            <?php if (!empty($slide['subtitle'])): ?>
                                <p class="hero-subtitle"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($slide['button_text'])): ?>
                                <a href="<?php echo !empty($slide['link_url']) ? htmlspecialchars($slide['link_url']) : '#'; ?>"
                                    class="hero-button"><?php echo htmlspecialchars($slide['button_text']); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($slider_images) > 1): ?>
            <!-- Slider Controls -->
            <div class="hero-slider-controls">
                <button class="hero-slider-btn hero-prev" onclick="changeSlide(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="hero-slider-btn hero-next" onclick="changeSlide(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Slider Dots -->
            <div class="hero-slider-dots">
                <?php foreach ($slider_images as $index => $slide): ?>
                    <span class="hero-dot <?php echo $index === 0 ? 'active' : ''; ?>"
                        onclick="currentSlide(<?php echo $index + 1; ?>)"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Fallback hero section -->
    <div class="hero-section">
        <div class="hero-content">
            <?php if (!empty($settings['logo'])): ?>
                <img src="<?php echo UPLOAD_DIR . $settings['logo']; ?>" alt="Site Logo" class="hero-logo">
            <?php endif; ?>
            <h1 class="hero-title"><?php echo $settings['site_title'] ?? 'QR Menü Sistemi'; ?></h1>
            <p class="hero-subtitle mb-4"><?php echo $settings['site_description'] ?? 'Dijital menü yönetim sistemi'; ?></p>


        </div>
    </div>
<?php endif; ?>







<?php
// Önerilen ürünleri göster - Slider (badge bilgisi ile)
$recommended_products = $db->query("
    SELECT p.*, rp.is_recommended, rp.is_new, rp.is_vegan
    FROM products p
    INNER JOIN recommended_products rp ON p.id = rp.product_id
    WHERE p.status = 1 AND rp.is_recommended = 1
")->fetchAll(PDO::FETCH_ASSOC);
if (count($recommended_products) > 0):
    ?>
    <div class="category-container">
        <div class="category-header">
            <h2 class="category-title">Önerilen Ürünler</h2>
        </div>
        <div class="recommended-slider-container">
            <div class="products-carousel">
                <?php foreach ($recommended_products as $product):
                    echo renderProductCard($product, $settings, [], [], [], true);
                endforeach; ?>
            </div>
            <div class="carousel-controls">
                <button class="carousel-btn carousel-btn-prev" onclick="scrollCarousel(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="carousel-btn carousel-btn-next" onclick="scrollCarousel(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="category-container">
    <div class="category-header">
        <h2 class="category-title">Kategoriler</h2>
    </div>

    <div class="categories-container">
        <?php foreach ($categories as $category): ?>
            <a href="category.php?id=<?php echo $category['id']; ?>" class="category-box">
                <div class="category-name"><?php echo $category['name']; ?></div>
            </a>
        <?php endforeach; ?>
    </div>

</div>






<?php
// Yeni ürünleri göster (badge bilgisi ile)
$new_products = $db->query("
            SELECT p.*, rp.is_recommended, rp.is_new, rp.is_vegan
            FROM products p
            INNER JOIN recommended_products rp ON p.id = rp.product_id
            WHERE p.status = 1 AND rp.is_new = 1
        ")->fetchAll(PDO::FETCH_ASSOC);
if (count($new_products) > 0):
    ?>
    <div class="category-container">
        <div class="category-header">
            <h2 class="category-title">Yeni</h2>
        </div>
        <div class="row g-2">
            <?php foreach ($new_products as $product): ?>
                <div class="col-lg-4 col-md-6 col-6">
                    <?php echo renderProductCard($product, $settings); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
<?php
// Normal kategorileri göster

$categories = $db->query("SELECT * FROM categories WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as $category):
    $products = $db->prepare("
                SELECT p.*, 
                       (SELECT 1 FROM recommended_products rp WHERE rp.product_id = p.id AND rp.is_recommended = 1) as is_recommended,
                       (SELECT 1 FROM recommended_products rp WHERE rp.product_id = p.id AND rp.is_new = 1) as is_new,
                       (SELECT 1 FROM recommended_products rp WHERE rp.product_id = p.id AND rp.is_vegan = 1) as is_vegan
                FROM products p 
                WHERE p.status = 1 AND p.category_id = ?
            ");
    $products->execute([$category['id']]);
    $products = $products->fetchAll(PDO::FETCH_ASSOC);

    if (count($products) > 0):
        ?>
        <div class="category-container" id="category-<?php echo $category['id']; ?>">
            <div class="category-header">
                <h2 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h2>
                <a href="category.php?id=<?php echo $category['id']; ?>" class="view-all-btn">
                    Tümünü Gör <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <div class="row g-2">
                <?php foreach ($products as $product): ?>
                    <div class="col-lg-4 col-md-6 col-6">
                        <?php echo renderProductCard($product, $settings); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    endif;
endforeach;
?>
</div>
<!-- Ürün Detay Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Ürün Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="modalProductImage" src="" alt="Ürün Resmi">
                <h4 id="modalProductName"></h4>
                <p class="modal-price"><strong><span id="modalProductPrice"></span> ₺</strong></p>
                <p id="modalProductDescription"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="modalAddToCartBtn">Sepete Ekle</button>
            </div>
        </div>
    </div>
</div>








<?php
require_once 'footer.php';

?>






<script>
    document.addEventListener("DOMContentLoaded", function () {
        var productModal = document.getElementById('productModal');

        productModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;  // Tıklanan ürün kartı
            if (!button) return; // Fix: Check if button exists (for manual JS calls)

            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var image = button.getAttribute('data-image');
            var price = button.getAttribute('data-price');
            var description = button.getAttribute('data-description');

            // Modal içindeki öğeleri güncelle
            document.getElementById('modalProductName').textContent = name;
            document.getElementById('modalProductImage').src = image;
            document.getElementById('modalProductPrice').textContent = price;
            document.getElementById('modalProductDescription').textContent = description;

            // Sepete Ekle Butonunu Ayarla
            var addToCartBtn = document.getElementById('modalAddToCartBtn');
            addToCartBtn.onclick = function () {
                addToCart(id, name, price, image);

                // Modalları Yönet
                var productModalEl = document.getElementById('productModal');
                var productModalInstance = bootstrap.Modal.getInstance(productModalEl);
                productModalInstance.hide();

                // Sepet modalını aç
                setTimeout(function () {
                    var cartModalEl = document.getElementById('cartModal');
                    var cartModalInstance = new bootstrap.Modal(cartModalEl);
                    cartModalInstance.show();
                }, 300);
            };
        });
    });
</script>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        const carousel = document.querySelector(".products-carousel");
        if (!carousel) return;

        let isDown = false;
        let startX, scrollLeft, scrollAmount = 0;
        let autoScrollInterval;

        // Mouse sürükleme
        carousel.addEventListener("mousedown", (e) => {
            isDown = true;
            startX = e.pageX - carousel.offsetLeft;
            scrollLeft = carousel.scrollLeft;
            carousel.style.cursor = "grabbing";
            clearInterval(autoScrollInterval);
        });

        carousel.addEventListener("mouseleave", () => {
            isDown = false;
            carousel.style.cursor = "grab";
            startAutoScroll();
        });

        carousel.addEventListener("mouseup", () => {
            isDown = false;
            carousel.style.cursor = "grab";
        });

        carousel.addEventListener("mousemove", (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - carousel.offsetLeft;
            const walk = (x - startX) * 2;
            carousel.scrollLeft = scrollLeft - walk;
        });

        // Dokunmatik cihazlar
        let touchStartX = 0;
        carousel.addEventListener("touchstart", (e) => {
            touchStartX = e.touches[0].clientX;
            clearInterval(autoScrollInterval);
        });

        carousel.addEventListener("touchmove", (e) => {
            const touchX = e.touches[0].clientX;
            const walk = touchStartX - touchX;
            carousel.scrollLeft += walk * 2;
            touchStartX = touchX;
        });

        carousel.addEventListener("touchend", () => {
            startAutoScroll();
        });

        // Otomatik kaydırma
        function autoScroll() {
            const scrollMax = carousel.scrollWidth - carousel.clientWidth;
            if (scrollAmount >= scrollMax) {
                scrollAmount = 0;
                carousel.scrollTo({ left: 0, behavior: "smooth" });
            } else {
                scrollAmount += 150;
                carousel.scrollBy({ left: 150, behavior: "smooth" });
            }
        }

        function startAutoScroll() {
            autoScrollInterval = setInterval(autoScroll, 3000);
        }

        startAutoScroll();
    });

    // Slider kontrol fonksiyonları
    function scrollCarousel(direction) {
        const carousel = document.querySelector(".products-carousel");
        if (!carousel) return;

        const scrollAmount = 200;
        const currentScroll = carousel.scrollLeft;
        const targetScroll = currentScroll + (direction * scrollAmount);

        carousel.scrollTo({
            left: targetScroll,
            behavior: 'smooth'
        });
    }

    // Hero Slider JavaScript
    let currentSlideIndex = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    let slideInterval;

    function showSlide(index) {
        // Tüm slide'ları gizle
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        // Belirtilen slide'ı göster
        if (slides[index]) {
            slides[index].classList.add('active');
            if (dots[index]) {
                dots[index].classList.add('active');
            }
        }

        currentSlideIndex = index;
    }

    function nextSlide() {
        currentSlideIndex++;
        if (currentSlideIndex >= slides.length) {
            currentSlideIndex = 0;
        }
        showSlide(currentSlideIndex);
    }

    function prevSlide() {
        currentSlideIndex--;
        if (currentSlideIndex < 0) {
            currentSlideIndex = slides.length - 1;
        }
        showSlide(currentSlideIndex);
    }

    function changeSlide(direction) {
        if (direction > 0) {
            nextSlide();
        } else {
            prevSlide();
        }
        resetAutoSlide();
    }

    function currentSlide(index) {
        showSlide(index - 1);
        resetAutoSlide();
    }

    function startAutoSlide() {
        if (slides.length > 1) {
            slideInterval = setInterval(nextSlide, 5000); // 5 saniyede bir
        }
    }

    function resetAutoSlide() {
        clearInterval(slideInterval);
        startAutoSlide();
    }

    // Slider'ı başlat
    document.addEventListener('DOMContentLoaded', function () {
        if (slides.length > 0) {
            showSlide(0);
            startAutoSlide();

            // Mouse hover durdurmak için
            const sliderContainer = document.querySelector('.hero-slider-container');
            if (sliderContainer) {
                sliderContainer.addEventListener('mouseenter', () => clearInterval(slideInterval));
                sliderContainer.addEventListener('mouseleave', startAutoSlide);
            }
        }
    });
</script>