<?php

require_once 'admin/functions.php';
incrementPageViews($db);

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="<?php echo $settings['site_description'] ?? 'Restoran ve kafeler için dijital QR menü sistemi. Kolay menü yönetimi ve modern tasarım.'; ?>">
    <meta name="keywords" content="qr menü, dijital menü, restoran menüsü, kafe menüsü, online menü">
    <meta name="author" content="<?php echo $settings['site_title'] ?? 'QR Menü Sistemi'; ?>">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?php echo $settings['site_title'] ?? 'QR Menü Sistemi'; ?>">
    <meta property="og:description"
        content="<?php echo $settings['site_description'] ?? 'Restoran ve kafeler için dijital QR menü sistemi'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:image"
        content="<?php echo isset($settings['logo']) ? $settings['logo'] : 'admin/assets/img/no-image.jpg'; ?>">
    <link rel="canonical"
        href="<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <title><?php echo $settings['site_title'] ?? 'QR Menü Sistemi'; ?></title>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap"
            rel="stylesheet">
    </noscript>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"
        media="print" onload="this.media='all'">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin/assets/css/front.css?v=<?= time(); ?>" rel="stylesheet">
    <link href="admin/assets/css/animation.css" rel="stylesheet">
    <link href="admin/assets/css/modal-custom.css?v=<?= time(); ?>" rel="stylesheet">

    <?php
    $settingsQuery = $db->query("SELECT * FROM settings");
    $settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);
    ?>
    <style>
        :root {
            --primary:
                <?php echo !empty($settings['color_primary']) ? $settings['color_primary'] : '#34495e'; ?>
            ;
            --secondary:
                <?php echo !empty($settings['color_secondary']) ? $settings['color_secondary'] : '#e67e22'; ?>
            ;
            --light:
                <?php echo !empty($settings['color_bg']) ? $settings['color_bg'] : '#f8f9fa'; ?>
            ;
            --text:
                <?php echo !empty($settings['color_text']) ? $settings['color_text'] : '#2c3e50'; ?>
            ;
        }

        /* Arka plan rengini body'ye de uygula */
        body {
            background-color: var(--light);
            color: var(--text);
        }
    </style>
    <link rel="icon" href="<?php echo htmlspecialchars($settings['logo']); ?>" type="image/x-icon">
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top glass-navbar">
        <div class="container position-relative d-flex justify-content-center align-items-center">

            <!-- Logo / Brand -->
            <a class="navbar-brand m-0 fw-bold text-uppercase warning-text" href="index.php"
                style="color: var(--primary);">
                <?php echo $settings['site_title'] ?? 'QR Menü'; ?>
            </a>

            <!-- Sağ Üst Arama İkonu -->
            <div class="position-absolute end-0 top-50 translate-middle-y me-3">
                <button type="button" class="btn btn-link link-dark p-0 text-decoration-none" data-bs-toggle="modal"
                    data-bs-target="#searchModal">
                    <i class="fas fa-search fa-lg" style="color: var(--primary);"></i>
                </button>
            </div>

        </div>
    </nav>

    <!-- Mobil Yatay Kategori Menüsü -->
    <div class="mobile-category-nav glass-category-nav">
        <div class="container">
            <div class="scroll-menu">
                <a href="index.php"
                    class="scroll-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Tümü
                </a>
                <?php
                // Kategorileri tekrar sorgulamak yerine yukarıdaki sorguyu resetleyip kullanalım yada yeniden sorgulayalım
                $categoriesNav = $db->query("SELECT * FROM categories WHERE status = 1");
                $current_cat_id = isset($_GET['id']) ? $_GET['id'] : 0;
                while ($cat = $categoriesNav->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <a href="category.php?id=<?php echo $cat['id']; ?>"
                        class="scroll-menu-item <?php echo $current_cat_id == $cat['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>



    <script>window.gtranslateSettings = { "default_language": "tr", "detect_browser_language": true, "languages": ["tr", "en", "de", "ar"], "wrapper_selector": ".gtranslate_wrapper" }</script>
    <script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>