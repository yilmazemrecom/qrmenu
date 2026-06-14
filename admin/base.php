<?php
ob_start(); // Output buffering başlat
checkSession();


function isActive($page)
{
    return isset($_GET['page']) && $_GET['page'] === $page ? 'active' : '';
}

$page = $_GET['page'] ?? 'dashboard';
$title = ucfirst($page) . ' - QR Menü Admin';

// Sayfa içeriğini belirle  
switch ($page) {
    case 'categories':
        $content = 'pages/categories.php';
        break;
    case 'products':
        $content = 'pages/products.php';
        break;
    case 'orders':
        $content = 'pages/orders.php';
        break;
    case 'tables':
        $content = 'pages/tables.php';
        break;
    case 'slider':
        $content = 'pages/slider.php';
        break;
    case 'waiter_calls':
        $content = 'pages/waiter_calls.php';
        break;
    case 'qr':
        $content = 'pages/qr-codes.php';
        break;
    case 'settings':
        $content = 'pages/settings.php';
        break;
    case 'profile':
        $content = 'pages/profile.php';
        break;
    default:
        $content = 'pages/dashboard.php';
        break;


}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal2-apple-popup {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            border-radius: 18px !important;
            padding: 2rem !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1) !important;
        }
        .swal2-apple-popup .swal2-title {
            font-size: 1.3rem !important;
            font-weight: 600 !important;
            color: var(--text) !important;
            padding-top: 0.5rem !important;
        }
        .swal2-apple-popup .swal2-html-container {
            font-size: 0.95rem !important;
            color: var(--text-muted) !important;
        }
        .swal2-apple-popup .swal2-icon {
            border-width: 2px !important;
            margin-top: 0.5rem !important;
        }
        .swal2-apple-popup .swal2-actions {
            margin-top: 1.5rem !important;
            gap: 10px;
        }
    </style>
    <script>
        function confirmAction(element, message) {
            const evt = window.event || (arguments.callee.caller ? arguments.callee.caller.arguments[0] : null);
            if (evt) {
                evt.preventDefault();
                evt.stopPropagation();
            }

            Swal.fire({
                title: 'Emin misiniz?',
                text: message || 'Bu işlemi gerçekleştirmek istediğinize emin misiniz?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3b30', // Apple Danger
                cancelButtonColor: '#86868b',  // Apple Secondary
                confirmButtonText: 'Evet, Devam Et',
                cancelButtonText: 'İptal',
                background: '#ffffff',
                customClass: {
                    popup: 'swal2-apple-popup',
                    confirmButton: 'btn btn-danger px-4 py-2',
                    cancelButton: 'btn btn-light text-dark px-4 py-2 border'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    if (element.tagName === 'A') {
                        window.location.href = element.href;
                    } else if (element.form || element.closest('form')) {
                        const form = element.form || element.closest('form');
                        if (element.name) {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = element.name;
                            hiddenInput.value = element.value || '1';
                            form.appendChild(hiddenInput);
                        }
                        form.submit();
                    }
                }
            });
            return false;
        }
    </script>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0 d-none d-md-block">
                <div class="d-flex flex-column p-3">
                    <h4 class="text-center mb-4">QR Menü Admin</h4>
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo isActive('dashboard'); ?>" href="?page=dashboard">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                        <a class="nav-link collapsed" href="#ordersSubmenu" data-bs-toggle="collapse"
                            aria-expanded="false">
                            <i class="fas fa-receipt me-2"></i> Siparişler <i
                                class="fas fa-caret-down ms-auto float-end mt-1"></i>
                        </a>
                        <div class="collapse <?php echo (strpos($page, 'orders') !== false) ? 'show' : ''; ?>"
                            id="ordersSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isActive('orders') && !isset($_GET['status']) ? 'active' : ''; ?>"
                                        href="?page=orders" style="font-size: 0.9rem;">
                                        <i class="fas fa-list-ul me-2"></i>Tümü
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'active' : ''; ?>"
                                        href="?page=orders&status=pending" style="font-size: 0.9rem;">
                                        <i class="fas fa-clock me-2"></i>Bekleyen
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isset($_GET['status']) && $_GET['status'] == 'preparing' ? 'active' : ''; ?>"
                                        href="?page=orders&status=preparing" style="font-size: 0.9rem;">
                                        <i class="fas fa-fire me-2"></i>Hazırlanan
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'active' : ''; ?>"
                                        href="?page=orders&status=completed" style="font-size: 0.9rem;">
                                        <i class="fas fa-check-circle me-2"></i>Tamamlanan
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'active' : ''; ?>"
                                        href="?page=orders&status=cancelled" style="font-size: 0.9rem;">
                                        <i class="fas fa-times-circle me-2"></i>İptal Edilen
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <a class="nav-link <?php echo isActive('tables'); ?>" href="?page=tables">
                            <i class="fas fa-th me-2"></i> Masa Yönetimi
                        </a>
                        <a class="nav-link <?php echo isActive('categories'); ?>" href="?page=categories">
                            <i class="fas fa-list me-2"></i> Kategoriler
                        </a>
                        <a class="nav-link <?php echo isActive('products'); ?>" href="?page=products">
                            <i class="fas fa-utensils me-2"></i> Ürünler
                        </a>
                        <a class="nav-link <?php echo isActive('waiter_calls'); ?>" href="?page=waiter_calls">
                            <i class="fas fa-bell me-2"></i> Garson Çağrıları
                        </a>
                        <a class="nav-link <?php echo isActive('slider'); ?>" href="?page=slider">
                            <i class="fas fa-images me-2"></i> Slider Yönetimi
                        </a>
                        <a class="nav-link <?php echo isActive('qr'); ?>" href="?page=qr">
                            <i class="fas fa-qrcode me-2"></i> QR Kod
                        </a>
                        <a class="nav-link <?php echo isActive('settings'); ?>" href="?page=settings">
                            <i class="fas fa-cog me-2"></i> Ayarlar
                        </a>
                        <a class="nav-link <?php echo isActive('profile'); ?>" href="?page=profile">
                            <i class="fas fa-user me-2"></i> Profil
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Mobile Sidebar Overlay -->
            <div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="mobileSidebar"
                data-bs-backdrop="true" data-bs-keyboard="true">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title">QR Menü Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo isActive('dashboard'); ?>" href="?page=dashboard"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                        <a class="nav-link <?php echo isActive('orders'); ?>" href="?page=orders"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-receipt me-2"></i> Siparişler
                        </a>
                        <a class="nav-link <?php echo isActive('tables'); ?>" href="?page=tables"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-th me-2"></i> Masa Yönetimi
                        </a>
                        <a class="nav-link <?php echo isActive('categories'); ?>" href="?page=categories"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-list me-2"></i> Kategoriler
                        </a>
                        <a class="nav-link <?php echo isActive('products'); ?>" href="?page=products"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-utensils me-2"></i> Ürünler
                        </a>
                        <a class="nav-link <?php echo isActive('waiter_calls'); ?>" href="?page=waiter_calls"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-bell me-2"></i> Garson Çağrıları
                        </a>
                        <a class="nav-link <?php echo isActive('slider'); ?>" href="?page=slider"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-images me-2"></i> Slider Yönetimi
                        </a>
                        <a class="nav-link <?php echo isActive('qr'); ?>" href="?page=qr" data-bs-dismiss="offcanvas">
                            <i class="fas fa-qrcode me-2"></i> QR Kod
                        </a>
                        <a class="nav-link <?php echo isActive('settings'); ?>" href="?page=settings"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-cog me-2"></i> Ayarlar
                        </a>
                        <a class="nav-link <?php echo isActive('profile'); ?>" href="?page=profile"
                            data-bs-dismiss="offcanvas">
                            <i class="fas fa-user me-2"></i> Profil
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-9 col-lg-10 main-content">
                <!-- Top Navbar -->
                <nav class="navbar navbar-expand-lg mb-4">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#mobileSidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <span class="navbar-brand d-md-none mb-0 h1">QR Admin</span>
                        <div class="d-flex align-items-center ms-auto">
                            <!-- Bildirim Alanı -->
                            <div class="dropdown me-3">
                                <button class="btn btn-link text-dark position-relative" type="button"
                                    id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell fa-lg"></i>
                                    <span id="notification-badge"
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                        style="display: none;">
                                        0
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0" id="notification-list"
                                    aria-labelledby="notificationDropdown" style="width: 300px;">
                                    <li><a class="dropdown-item text-center text-muted" href="#">Yükleniyor...</a></li>
                                </ul>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-link dropdown-toggle text-dark" type="button" id="userDropdown"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-2"></i>
                                    <span
                                        class="d-none d-sm-inline"><?php echo $_SESSION['admin']['username']; ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="?page=profile"><i
                                                class="fas fa-user-cog me-2"></i>Profil</a></li>
                                    <li><a class="dropdown-item" href="logout.php"><i
                                                class="fas fa-sign-out-alt me-2"></i>Çıkış</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <?php
                if (file_exists($content)) {
                    include $content;
                } else {
                    echo errorMessage("Sayfa bulunamadı!");
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Bildirim Sesi -->
    <audio id="notificationSound" src="assets/audio/notification.mp3" preload="auto"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/notifications.js"></script>
    <?php if ($page === 'dashboard'): ?>
        <script src="assets/js/dashboard.js"></script>
    <?php endif; ?>
</body>

</html>
<?php ob_end_flush(); // Output buffering bitir ?>