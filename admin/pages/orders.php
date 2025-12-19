<?php
// Durum güncelleme işlemi
if (isset($_POST['status'])) {
    validateCSRFToken($_POST['csrf_token']);
    $order_id = (int) $_POST['order_id'];
    $status = clean($_POST['status']);

    $allowed_statuses = ['pending', 'preparing', 'completed', 'cancelled'];
    if (in_array($status, $allowed_statuses)) {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        $_SESSION['success'] = "Sipariş durumu güncellendi.";
    }

    header("Location: ?page=orders");
    exit;
}

// Sipariş Silme İşlemi
if (isset($_POST['delete_order'])) {
    try {
        validateCSRFToken($_POST['csrf_token']);
        $order_id = (int) $_POST['order_id'];

        // Sadece tamamlanan veya iptal edilen siparişler silinebilir (Güvenlik)
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ? AND status IN ('completed', 'cancelled')");
        $stmt->execute([$order_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Sipariş kalıcı olarak silindi.";
        } else {
            // Silinemedi
            $_SESSION['success'] = "Sipariş silinemedi veya zaten silinmiş.";
        }
    } catch (PDOException $e) {
        die("Veritabanı Hatası: " . $e->getMessage());
    } catch (Exception $e) {
        die("Hata: " . $e->getMessage());
    }

    header("Location: ?page=orders");
    exit;
}

// Toplu Silme İşlemi (Sadece İptal Edilenler)
if (isset($_POST['delete_all_cancelled'])) {
    validateCSRFToken($_POST['csrf_token']);
    try {
        $stmt = $db->prepare("DELETE FROM orders WHERE status = 'cancelled'");
        $stmt->execute();
        $_SESSION['success'] = "İptal edilen tüm siparişler silindi.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Hata: " . $e->getMessage();
    }
    header("Location: ?page=orders&status=cancelled");
    exit;
}

// Filtreleme
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';

// Sayfalama Ayarları
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int) $_GET['p'] : 1;
$limit = 20; // Sayfa başına sipariş sayısı
$offset = ($page - 1) * $limit;

// Toplam Kayıt Sayısı Sorgusu
$count_sql = "SELECT COUNT(*) FROM orders WHERE 1=1";
$params = [];

if ($status_filter) {
    $count_sql .= " AND status = ?";
    $params[] = $status_filter;
}

$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Ana Sipariş Sorgusu (Limit ve Offset ile)
$sql = "SELECT * FROM orders WHERE 1=1";
if ($status_filter) {
    $sql .= " AND status = ?";
}
$sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sipariş detaylarını al
function getOrderItems($db, $order_id)
{
    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <?php
            switch ($status_filter) {
                case 'pending':
                    echo 'Bekleyen Siparişler';
                    break;
                case 'preparing':
                    echo 'Hazırlanan Siparişler';
                    break;
                case 'completed':
                    echo 'Tamamlanan Siparişler';
                    break;
                case 'cancelled':
                    echo 'İptal Edilen Siparişler';
                    break;
                default:
                    echo 'Tüm Siparişler';
                    break;
            }
            ?>
        </h4>
        <div class="d-flex gap-2">
            <?php if ($status_filter == 'cancelled' && count($orders) > 0): ?>
                <form action="?page=orders" method="POST"
                    onsubmit="return confirm('İptal edilen TÜM siparişleri silmek istediğinize emin misiniz? Bu işlem geri alınamaz!');">
                    <input type="hidden" name="csrf_token" value="<?php echo createCSRFToken(); ?>">
                    <input type="hidden" name="delete_all_cancelled" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Tümünü Sil
                    </button>
                </form>
            <?php endif; ?>
            <div class="btn-group">
                <a href="?page=orders"
                    class="btn btn-outline-secondary <?php echo $status_filter == '' ? 'active' : ''; ?>">Tümü</a>
                <a href="?page=orders&status=pending"
                    class="btn btn-outline-warning <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Bekleyen</a>
                <a href="?page=orders&status=preparing"
                    class="btn btn-outline-info <?php echo $status_filter == 'preparing' ? 'active' : ''; ?>">Hazırlanıyor</a>
                <a href="?page=orders&status=completed"
                    class="btn btn-outline-success <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Tamamlanan</a>
                <a href="?page=orders&status=cancelled"
                    class="btn btn-outline-danger <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">İptal
                    Edilen</a>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <?php echo successMessage($_SESSION['success']);
        unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
            <h5>
                <?php
                switch ($status_filter) {
                    case 'pending':
                        echo 'Şu an bekleyen yeni sipariş yok.';
                        break;
                    case 'preparing':
                        echo 'Hazırlanma aşamasında olan sipariş yok.';
                        break;
                    case 'completed':
                        echo 'Henüz tamamlanmış bir sipariş bulunmuyor.';
                        break;
                    case 'cancelled':
                        echo 'İptal edilen sipariş bulunmuyor.';
                        break;
                    default:
                        echo 'Sistemde kayıtlı sipariş bulunmuyor.';
                        break;
                }
                ?>
            </h5>
            <p class="mb-0 text-muted">Yeni siparişler geldiğinde burada görüntülenecektir.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3 text-sm" id="orders-container">
            <?php foreach ($orders as $order):
                $items = getOrderItems($db, $order['id']);
                $status_class = [
                    'pending' => 'warning',
                    'preparing' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    'paid' => 'secondary'
                ][$order['status']];

                $status_text = [
                    'pending' => 'Bekliyor',
                    'preparing' => 'Hazırlanıyor',
                    'completed' => 'Tamamlandı',
                    'cancelled' => 'İptal',
                    'paid' => 'Ödendi'
                ][$order['status']];
                ?>
                <div class="col">
                    <div class="card h-100 border-<?php echo $status_class; ?>">
                        <div
                            class="card-header bg-<?php echo $status_class; ?> text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Masa: <?php echo clean($order['table_no']); ?></h5>
                            <span class="badge bg-white text-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 text-muted small">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                            <?php if (!empty($order['note'])): ?>
                                <div class="alert alert-secondary py-2 px-3 mb-3 small">
                                    <strong>Not:</strong> <?php echo clean($order['note']); ?>
                                </div>
                            <?php endif; ?>

                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($items as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <span
                                                class="badge bg-secondary rounded-pill me-2"><?php echo $item['quantity']; ?>x</span>
                                            <?php echo clean($item['product_name']); ?>
                                        </div>
                                        <span><?php echo number_format($item['price'] * $item['quantity'], 2); ?> ₺</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                <h5 class="mb-0">Toplam: <?php echo number_format($order['total_amount'], 2); ?> ₺</h5>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <form action="?page=orders" method="POST" class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?php echo createCSRFToken(); ?>">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">

                                <?php if ($order['status'] == 'pending'): ?>
                                    <button type="submit" name="status" value="preparing"
                                        class="btn btn-sm btn-info w-100 text-white">
                                        <i class="fas fa-fire me-1"></i>Hazırla
                                    </button>
                                    <button type="submit" name="status" value="cancelled" class="btn btn-sm btn-danger w-100">
                                        <i class="fas fa-times me-1"></i>İptal
                                    </button>
                                <?php elseif ($order['status'] == 'preparing'): ?>
                                    <button type="submit" name="status" value="completed" class="btn btn-sm btn-success w-100">
                                        <i class="fas fa-check me-1"></i>Tamamla
                                    </button>
                                <?php endif; ?>

                                <?php if ($order['status'] == 'completed' || $order['status'] == 'cancelled'): ?>
                                    <button type="submit" name="delete_order" value="1" class="btn btn-sm btn-outline-danger w-100"
                                        onclick="return confirm('Bu siparişi kalıcı olarak silmek istediğinize emin misiniz?');">
                                        <i class="fas fa-trash-alt me-1"></i>Sil
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Sayfalama -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Sayfalama" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Önceki Sayfa -->
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link"
                        href="?page=orders<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&p=<?php echo $page - 1; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>

                <!-- Sayfa Numaraları -->
                <?php
                $start = max(1, $page - 2);
                $end = min($start + 4, $total_pages);
                if ($end - $start < 4) {
                    $start = max(1, $end - 4);
                }

                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link"
                            href="?page=orders<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&p=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <!-- Sonraki Sayfa -->
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link"
                        href="?page=orders<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&p=<?php echo $page + 1; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="text-center text-muted small mt-2">
            Toplam <?php echo $total_orders; ?> siparişten <?php echo ($offset + 1); ?> -
            <?php echo min($offset + $limit, $total_orders); ?> arası gösteriliyor.
        </div>
    <?php endif; ?>
</div>