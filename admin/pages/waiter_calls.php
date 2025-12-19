<?php
// İşlem: Durum Güncelleme
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $id = $_GET['complete'];
    $update = $db->prepare("UPDATE waiter_calls SET status = 1 WHERE id = ?");
    if ($update->execute([$id])) {
        echo '<div class="alert alert-success">Çağrı tamamlandı olarak işaretlendi.</div>';
    }
}

// İşlem: Silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $delete = $db->prepare("DELETE FROM waiter_calls WHERE id = ?");
    if ($delete->execute([$id])) {
        echo '<div class="alert alert-success">Çağrı silindi.</div>';
    }
}

// Aktif Çağrılar
$activeCalls = $db->query("SELECT * FROM waiter_calls WHERE status = 0 ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);

// Tamamlanan Çağrılar (Son 10)
$completedCalls = $db->query("SELECT * FROM waiter_calls WHERE status = 1 ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Garson Çağrıları</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?page=waiter_calls" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-sync"></i> Yenile
        </a>
    </div>
</div>

<!-- Aktif Çağrılar -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Bekleyen Çağrılar</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activeCalls)): ?>
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                        <p class="mb-0">Harika! Bekleyen çağrı yok.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Masa</th>
                                    <th>Talep</th>
                                    <th>Süre</th>
                                    <th>Zaman</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeCalls as $call): ?>
                                    <tr class="table-danger">
                                        <td><span class="badge bg-dark fs-6">Masa
                                                <?= htmlspecialchars($call['table_no']); ?></span></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'bg-secondary';
                                            $icon = 'fa-bell';
                                            if ($call['call_type'] == 'Hesap') {
                                                $badgeClass = 'bg-info text-dark';
                                                $icon = 'fa-file-invoice-dollar';
                                            }
                                            if ($call['call_type'] == 'Su') {
                                                $badgeClass = 'bg-primary';
                                                $icon = 'fa-tint';
                                            }
                                            if ($call['call_type'] == 'Garson') {
                                                $badgeClass = 'bg-warning text-dark';
                                                $icon = 'fa-user-tie';
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass; ?> p-2">
                                                <i class="fas <?= $icon; ?> me-1"></i>
                                                <?= htmlspecialchars($call['call_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $createTime = strtotime($call['created_at']);
                                            $diff = time() - $createTime;
                                            $min = floor($diff / 60);
                                            if ($min < 1)
                                                echo '<span class="text-success fw-bold">Yeni</span>';
                                            else
                                                echo '<span class="text-danger fw-bold">' . $min . ' dk önce</span>';
                                            ?>
                                        </td>
                                        <td class="text-muted small"><?= date('H:i', strtotime($call['created_at'])); ?></td>
                                        <td class="text-end">
                                            <a href="?page=waiter_calls&complete=<?= $call['id']; ?>"
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i> Tamamlandı
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Geçmiş Çağrılar -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light">
                <h5 class="mb-0 text-muted"><i class="fas fa-history me-2"></i>Tamamlananlar (Son 10)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Masa</th>
                                <th>Talep</th>
                                <th>Zaman</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completedCalls as $call): ?>
                                <tr>
                                    <td>Masa <?= htmlspecialchars($call['table_no']); ?></td>
                                    <td><?= htmlspecialchars($call['call_type']); ?></td>
                                    <td class="text-muted small"><?= date('d.m.Y H:i', strtotime($call['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?page=waiter_calls&delete=<?= $call['id']; ?>"
                                            class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Silmek istediğinize emin misiniz?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Otomatik Yenileme Scripti -->
<script>
    // Sayfayı her 30 saniyede bir yenile (Yeni çağrıları görmek için)
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>