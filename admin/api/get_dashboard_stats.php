<?php
require_once '../../admin/config.php';
require_once '../../admin/functions.php';

header('Content-Type: application/json');

try {
    // 1. İstatistikler
    $todayOrders = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $activeCalls = $db->query("SELECT COUNT(*) FROM waiter_calls WHERE status = 0")->fetchColumn();

    // Ciro Hesaplama (Completed + Paid) - GÜNCELLENDİ
    $todayRevenue = $db->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('completed', 'paid') AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

    // 2. Son Siparişler HTML
    $latestOrders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    ob_start();
    if (empty($latestOrders)) {
        echo '<tr><td colspan="6" class="text-center py-4 text-muted">Henüz sipariş yok.</td></tr>';
    } else {
        foreach ($latestOrders as $order) {
            $statusBadge = 'secondary';
            $statusText = 'Bilinmiyor';
            switch ($order['status']) {
                case 'pending':
                    $statusBadge = 'warning text-dark';
                    $statusText = 'Bekliyor';
                    break;
                case 'preparing':
                    $statusBadge = 'info text-dark';
                    $statusText = 'Hazırlanıyor';
                    break;
                case 'completed':
                    $statusBadge = 'success';
                    $statusText = 'Tamamlandı';
                    break;
                case 'cancelled':
                    $statusBadge = 'danger';
                    $statusText = 'İptal';
                    break;
                case 'paid':
                    $statusBadge = 'secondary';
                    $statusText = 'Ödendi';
                    break;
            }
            ?>
            <tr>
                <td class="fw-bold">#<?= $order['id']; ?></td>
                <td><span class="badge bg-light text-dark border">Masa <?= htmlspecialchars($order['table_no']); ?></span></td>
                <td class="fw-bold text-success"><?= formatPrice($order['total_amount']); ?></td>
                <td><span class="badge bg-<?= $statusBadge; ?>"><?= $statusText; ?></span></td>
                <td class="small text-muted"><?= date('H:i', strtotime($order['created_at'])); ?></td>
                <td>
                    <a href="?page=orders&id=<?= $order['id']; ?>" class="btn btn-sm btn-light border" title="Detay">
                        <i class="fas fa-eye text-secondary"></i>
                    </a>
                </td>
            </tr>
            <?php
        }
    }
    $ordersHtml = ob_get_clean();

    // 3. Garson Çağrıları HTML
    $latestCalls = $db->query("SELECT * FROM waiter_calls WHERE status = 0 ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    ob_start();
    if (empty($latestCalls)) {
        echo '<div class="text-center py-5 text-muted opacity-75"><i class="fas fa-check-circle fa-3x mb-3 text-success"></i><p>Bekleyen çağrı yok!</p></div>';
    } else {
        echo '<div class="list-group list-group-flush">';
        foreach ($latestCalls as $call) {
            $icon = 'fa-bell';
            $bgClass = 'bg-warning text-dark';
            if ($call['call_type'] == 'Hesap') {
                $icon = 'fa-file-invoice-dollar';
                $bgClass = 'bg-info text-dark';
            }
            if ($call['call_type'] == 'Su') {
                $icon = 'fa-glass-whiskey';
                $bgClass = 'bg-primary text-white';
            }
            ?>
            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center">
                    <div class="<?= $bgClass; ?> rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px;">
                        <i class="fas <?= $icon; ?>"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Masa <?= htmlspecialchars($call['table_no']); ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($call['call_type']); ?> İstiyor</small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark border"><?= date('H:i', strtotime($call['created_at'])); ?></span>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
    $callsHtml = ob_get_clean();

    echo json_encode([
        'success' => true,
        'todayRevenue' => formatPrice($todayRevenue),
        'todayOrders' => $todayOrders,
        'pendingOrders' => $pendingOrders,
        'activeCalls' => $activeCalls,
        'ordersHtml' => $ordersHtml,
        'callsHtml' => $callsHtml
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>