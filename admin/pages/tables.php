<?php
// Masa Hesabını Kapat (Tüm Aktif Siparişleri Ödendi Yap)
if (isset($_POST['close_table'])) {
    validateCSRFToken($_POST['csrf_token']);
    $table_no = clean($_POST['table_no']);

    try {
        // Kontrol: Masada tamamlanmamış (pending/preparing) sipariş var mı?
        $check = $db->prepare("SELECT COUNT(*) FROM orders WHERE table_no = ? AND status IN ('pending', 'preparing')");
        $check->execute([$table_no]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Hata: Masada hazırlanmakta olan veya bekleyen siparişler var. Hesabı kapatmadan önce tüm siparişleri tamamlamalısınız.";
        } else {
            // İlgili masanın 'completed' durumundaki siparişlerini 'paid' yap
            // (Status check'ten geçtiği için sadece completed kalmış olmalı, ama yine de sorguyu sağlama alalım)
            $stmt = $db->prepare("UPDATE orders SET status = 'paid' WHERE table_no = ? AND status = 'completed'");
            $stmt->execute([$table_no]);

            // Masaya ait varsa garson çağrısını da tamamlandı yap
            $db->prepare("UPDATE waiter_calls SET status = 1 WHERE table_no = ?")->execute([$table_no]);

            $_SESSION['success'] = "Masa $table_no hesabı alındı ve kapatıldı.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Hata: " . $e->getMessage();
    }

    header("Location: ?page=tables");
    exit;
}

// Tüm aktif siparişleri çek (status != cancelled AND status != paid)
// Masaya göre gruplayacağız.
$sql = "
    SELECT 
        o.id as order_id, 
        o.table_no, 
        o.status, 
        o.created_at, 
        o.note,
        oi.product_name, 
        oi.quantity, 
        oi.price 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.status IN ('pending', 'preparing', 'completed')
    ORDER BY o.table_no ASC, o.created_at ASC
";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Veriyi işle ve masalara göre grupla
$tables = [];
foreach ($rows as $row) {
    $table_no = $row['table_no'];

    if (!isset($tables[$table_no])) {
        $tables[$table_no] = [
            'total_amount' => 0,
            'orders' => [],
            'start_time' => $row['created_at'], // İlk sipariş zamanı
            'has_waiter_call' => false,
            'has_pending' => false // Tamamlanmamış sipariş var mı?
        ];
    }

    if ($row['status'] != 'completed') {
        $tables[$table_no]['has_pending'] = true;
    }

    // Sipariş bazında gruplama (Aynı siparişin birden fazla kalemi olabilir, ama burada düz liste yapacağız)
    // Ya da sipariş ID'sine göre de alt gruplama yapılabilir. Basitlik için kalemleri listeyelim.

    $item_total = $row['price'] * $row['quantity'];
    $tables[$table_no]['total_amount'] += $item_total;

    $tables[$table_no]['items'][] = [
        'name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'status' => $row['status'],
        'note' => $row['note']
    ];
}

// Aktif garson çağrılarını kontrol et
$activeCalls = $db->query("SELECT table_no, call_type FROM waiter_calls WHERE status = 0")->fetchAll(PDO::FETCH_ASSOC);
foreach ($activeCalls as $call) {
    if (isset($tables[$call['table_no']])) {
        $tables[$call['table_no']]['has_waiter_call'] = true;
        $tables[$call['table_no']]['call_type'] = $call['call_type'];
    } else {
        // Eğer masada sipariş yok ama çağrı varsa, yine de masayı gösterelim mi?
        // Evet, boş masa da garson çağırmış olabilir.
        // Ancak bu yapı siparişler tablosu üzerine kurulu. Şimdilik sadece siparişi olan masaları yönetelim.
        // Kullanıcı isteği "masaya ürünler giriliyor... bu masada hangi ürünler var" şeklindeydi.
        // Boş masa çağrısı waiterr_calls sayfasında zaten var.
    }
}

ksort($tables, SORT_NUMERIC); // Masaları numaraya göre sırala
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Masa Yönetimi</h1>
        <a href="?page=tables" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-sync-alt"></i> Yenile
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <?php echo successMessage($_SESSION['success']);
        unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (empty($tables)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="fas fa-store-slash fa-3x mb-3 text-muted"></i>
            <h4>Şu an açık masa yok.</h4>
            <p class="text-muted">Aktif siparişi olan masalar burada listelenecektir.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-4">
            <?php foreach ($tables as $table_no => $data): ?>
                <div class="col">
                    <div
                        class="card h-100 shadow-sm border-0 <?php echo $data['has_waiter_call'] ? 'border-warning border-3' : ''; ?>">
                        <div
                            class="card-header d-flex justify-content-between align-items-center <?php echo $data['has_waiter_call'] ? 'bg-warning text-dark' : 'bg-white'; ?>">
                            <h4 class="mb-0 fw-bold">Masa <?php echo $table_no; ?></h4>
                            <?php if ($data['has_waiter_call']): ?>
                                <span class="badge bg-danger animate__animated animate__flash animate__infinite">
                                    <i class="fas fa-bell me-1"></i> <?php echo $data['call_type']; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo date('H:i', strtotime($data['start_time'])); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-borderless mb-0">
                                    <thead class="text-muted small border-bottom">
                                        <tr>
                                            <th>Ürün</th>
                                            <th class="text-end">Adet</th>
                                            <th class="text-end">Tutar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['items'] as $item):
                                            $status_color = match ($item['status']) {
                                                'completed' => 'text-success',
                                                'preparing' => 'text-info',
                                                default => 'text-muted'
                                            };
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="<?php echo $status_color; ?>">
                                                        <?php echo clean($item['name']); ?>
                                                    </span>
                                                    <?php if (!empty($item['note'])): ?>
                                                        <br><small class="text-muted fst-italic">-
                                                            <?php echo clean($item['note']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end fw-bold"><?php echo $item['quantity']; ?></td>
                                                <td class="text-end">
                                                    <?php echo number_format($item['price'] * $item['quantity'], 2); ?> ₺
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Toplam Tutar:</span>
                                <span
                                    class="h4 mb-0 fw-bold text-primary"><?php echo number_format($data['total_amount'], 2); ?>
                                    ₺</span>
                            </div>
                            <form method="POST"
                                onsubmit="return confirm('Masa <?php echo $table_no; ?> hesabını kapatmak ve masayı boşaltmak istediğinize emin misiniz?');">
                                <input type="hidden" name="csrf_token" value="<?php echo createCSRFToken(); ?>">
                                <input type="hidden" name="table_no" value="<?php echo $table_no; ?>">
                                <?php if ($data['has_pending']): ?>
                                    <div class="alert alert-warning py-2 mb-0 small text-center">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Sipariş tamamlanmalı
                                    </div>
                                <?php else: ?>
                                    <button type="submit" name="close_table" class="btn btn-success w-100 py-2">
                                        <i class="fas fa-cash-register me-2"></i>Hesabı Al ve Kapat
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Sayfayı her 30 saniyede bir yenile
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>