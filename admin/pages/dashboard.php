<?php
// İstatistikleri ve Verileri Çekme

// 1. Temel İstatistikler
try {
    // Bugünün Tarihi
    $today = date('Y-m-d');

    // Toplam Sipariş
    $totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    // Bugünkü Sipariş Sayısı
    $todayOrders = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

    // Bekleyen Siparişler
    $pendingOrdersCount = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

    // Aktif Garson Çağrıları
    $activeCallsCount = $db->query("SELECT COUNT(*) FROM waiter_calls WHERE status = 0")->fetchColumn();

    // Toplam Ciro (Tamamlanan + Ödenen Siparişler)
    $totalRevenue = $db->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('completed', 'paid')")->fetchColumn() ?: 0;

    // Günlük Ciro
    $todayRevenue = $db->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('completed', 'paid') AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

    // Grafik Verileri (Son 7 Gün)
    $last7Days = [];
    $revenueData = [];
    $orderCountData = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = date('d.m', strtotime("-$i days")); // Örn: 14.12

        // O günün cirosu
        $dayRevenue = $db->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('completed', 'paid') AND DATE(created_at) = '$date'")->fetchColumn() ?: 0;

        // O günün sipariş sayısı
        $dayOrderCount = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('completed', 'paid') AND DATE(created_at) = '$date'")->fetchColumn() ?: 0;

        $last7Days[] = $dayName;
        $revenueData[] = $dayRevenue;
        $orderCountData[] = $dayOrderCount;
    }

} catch (PDOException $e) {
    // Hata durumunda varsayılan değerler
    $totalOrders = 0;
    $todayOrders = 0;
    $pendingOrdersCount = 0;
    $activeCallsCount = 0;
    $totalRevenue = 0;
    $todayRevenue = 0;
    $last7Days = [];
    $revenueData = [];
    $orderCountData = []; // Added this for consistency
}

// 2. Son Siparişler (Limit 10)
$latestOrders = $db->query("
    SELECT * FROM orders 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Son Garson Çağrıları (Limit 10)
$latestCalls = $db->query("
    SELECT * FROM waiter_calls 
    WHERE status = 0
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <!-- Günlük Ciro -->
        <div class="col-md-3">
            <div class="stats-card bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 opacity-75">Günlük Ciro</h6>
                        <h3 class="mb-0 fw-bold" id="stat-today-revenue"><?= formatPrice($todayRevenue); ?></h3>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <small class="opacity-75">Toplam: <?= formatPrice($totalRevenue); ?></small>
            </div>
        </div>

        <!-- Bugün Gelen Sipariş -->
        <div class="col-md-3">
            <div class="stats-card bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 opacity-75">Bugünkü Sipariş</h6>
                        <h3 class="mb-0 fw-bold" id="stat-today-orders"><?= $todayOrders; ?></h3>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
                <small class="opacity-75">Toplam Sipariş: <?= $totalOrders; ?></small>
            </div>
        </div>

        <!-- Bekleyen Sipariş -->
        <div class="col-md-3">
            <div class="stats-card bg-warning text-dark">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 opacity-75">Bekleyen Sipariş</h6>
                        <h3 class="mb-0 fw-bold" id="stat-pending-orders"><?= $pendingOrdersCount; ?></h3>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <a href="?page=orders&status=pending"
                    class="text-dark small text-decoration-none stretched-link">Görüntüle <i
                        class="fas fa-arrow-right input-group-text-sm"></i></a>
            </div>
        </div>

        <!-- Aktif Garson Çağrısı -->
        <div class="col-md-3">
            <div class="stats-card bg-danger text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 opacity-75">Aktif Çağrı</h6>
                        <h3 class="mb-0 fw-bold" id="stat-active-calls"><?= $activeCallsCount; ?></h3>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-bell"></i>
                    </div>
                </div>
                <a href="?page=waiter_calls" class="text-dark small text-decoration-none stretched-link">Görüntüle <i
                        class="fas fa-arrow-right input-group-text-sm"></i></a>

                <a href="?page=waiter_calls" class="text-white small text-decoration-none stretched-link">Yönet <i
                        class="fas fa-arrow-right input-group-text-sm"></i></a>
            </div>
        </div>
    </div>

    <!-- Grafik Alanı -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-info"></i>Haftalık Satış
                        Grafiği</h5>
                </div>
                <div class="card-body">
                    <canvas id="weeklySalesChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Scripti -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('weeklySalesChart').getContext('2d');

            // Gradient oluştur
            let gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(46, 204, 113, 0.5)'); // Yeşil opak
            gradient.addColorStop(1, 'rgba(46, 204, 113, 0.05)'); // Yeşil transparan

            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($last7Days); ?>,
                    datasets: [{
                        label: 'Ciro (TL)',
                        data: <?= json_encode($revenueData); ?>,
                        backgroundColor: gradient,
                        borderColor: '#2ecc71',
                        borderWidth: 2,
                        pointBackgroundColor: '#2ecc71',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [2, 2]
                            },
                            ticks: {
                                callback: function (value, index, values) {
                                    return value + ' TL';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>

    <div class="row">
        <!-- Son Siparişler Tablosu -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-receipt me-2 text-primary"></i>Son Siparişler
                    </h5>
                    <a href="?page=orders" class="btn btn-sm btn-outline-primary rounded-pill">Tümünü Gör</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Sipariş No</th>
                                <th>Masa</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>Zaman</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="recent-orders-body">
                            <?php if (empty($latestOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Henüz sipariş yok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latestOrders as $order): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $order['id']; ?></td>
                                        <td><span class="badge bg-light text-dark border">Masa
                                                <?= htmlspecialchars($order['table_no']); ?></span></td>
                                        <td class="fw-bold text-success"><?= formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <?php
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
                                            <span class="badge bg-<?= $statusBadge; ?>"><?= $statusText; ?></span>
                                        </td>
                                        <td class="small text-muted"><?= date('H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="?page=orders&id=<?= $order['id']; ?>" class="btn btn-sm btn-light border"
                                                title="Detay">
                                                <i class="fas fa-eye text-secondary"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Son Garson Çağrıları -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold"><i class="fas fa-concierge-bell me-2 text-warning"></i>Bekleyen
                        Çağrılar</h5>
                    <a href="?page=waiter_calls" class="btn btn-sm btn-outline-warning rounded-pill">Yönet</a>
                </div>
                <div class="card-body p-0" id="waiter-calls-list">
                    <?php if (empty($latestCalls)): ?>
                        <div class="text-center py-5 text-muted opacity-75">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <p>Bekleyen çağrı yok!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($latestCalls as $call): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <?php
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
                                        <div class="<?= $bgClass; ?> rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                                            style="width: 40px; height: 40px;">
                                            <i class="fas <?= $icon; ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Masa <?= htmlspecialchars($call['table_no']); ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($call['call_type']); ?>
                                                İstiyor</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark border timer-badge"
                                            data-time="<?= $call['created_at']; ?>">
                                            <?= date('H:i', strtotime($call['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Dashboard Kart Stilleri */
    .stats-card {
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
        height: 100%;
    }

    .stats-card:hover {
        transform: translateY(-3px);
    }
</style>