<?php
header('Content-Type: application/json');
require_once '../admin/config.php';
require_once '../admin/functions.php';

try {
    // 1. Temel İstatistikler
    $today = date('Y-m-d');

    // Günlük Ciro
    $todayRevenue = $db->query("SELECT SUM(total_amount) FROM orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

    // Bugünkü Sipariş Sayısı
    $todayOrders = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

    // Bekleyen Siparişler
    $pendingOrdersCount = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

    // Aktif Garson Çağrıları
    $activeCallsCount = $db->query("SELECT COUNT(*) FROM waiter_calls WHERE status = 0")->fetchColumn();


    // 2. Son Siparişler HTML Oluşturma
    $latestOrders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $ordersHtml = '';

    if (empty($latestOrders)) {
        $ordersHtml = '<tr><td colspan="6" class="text-center py-4 text-muted">Henüz sipariş yok.</td></tr>';
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
            }

            $formattedPrice = formatPrice($order['total_amount']);
            $tableNo = htmlspecialchars($order['table_no']);
            $time = date('H:i', strtotime($order['created_at']));

            $ordersHtml .= "
            <tr>
                <td class=\"fw-bold\">#{$order['id']}</td>
                <td><span class=\"badge bg-light text-dark border\">Masa {$tableNo}</span></td>
                <td class=\"fw-bold text-success\">{$formattedPrice}</td>
                <td><span class=\"badge bg-{$statusBadge}\">{$statusText}</span></td>
                <td class=\"small text-muted\">{$time}</td>
                <td>
                    <a href=\"?page=orders&id={$order['id']}\" class=\"btn btn-sm btn-light border\" title=\"Detay\">
                        <i class=\"fas fa-eye text-secondary\"></i>
                    </a>
                </td>
            </tr>";
        }
    }


    // 3. Garson Çağrıları HTML Oluşturma
    $latestCalls = $db->query("SELECT * FROM waiter_calls WHERE status = 0 ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $callsHtml = '';

    if (empty($latestCalls)) {
        $callsHtml = '
        <div class="text-center py-5 text-muted opacity-75">
            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
            <p>Bekleyen çağrı yok!</p>
        </div>';
    } else {
        $callsHtml = '<div class="list-group list-group-flush">';
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

            $tableNo = htmlspecialchars($call['table_no']);
            $callType = htmlspecialchars($call['call_type']);
            $time = date('H:i', strtotime($call['created_at']));
            $fullTime = $call['created_at'];

            $callsHtml .= "
            <div class=\"list-group-item d-flex justify-content-between align-items-center py-3\">
                <div class=\"d-flex align-items-center\">
                    <div class=\"{$bgClass} rounded-circle p-2 me-3 d-flex align-items-center justify-content-center\" style=\"width: 40px; height: 40px;\">
                        <i class=\"fas {$icon}\"></i>
                    </div>
                    <div>
                        <h6 class=\"mb-0 fw-bold\">Masa {$tableNo}</h6>
                        <small class=\"text-muted\">{$callType} İstiyor</small>
                    </div>
                </div>
                <div class=\"text-end\">
                    <span class=\"badge bg-light text-dark border timer-badge\" data-time=\"{$fullTime}\">{$time}</span>
                </div>
            </div>";
        }
        $callsHtml .= '</div>';
    }

    echo json_encode([
        'success' => true,
        'todayRevenue' => formatPrice($todayRevenue),
        'todayOrders' => $todayOrders,
        'pendingOrders' => $pendingOrdersCount,
        'activeCalls' => $activeCallsCount,
        'ordersHtml' => $ordersHtml,
        'callsHtml' => $callsHtml
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
