<?php
// Ayarları yükle  
$settings = $db->query("SELECT * FROM settings")->fetch(PDO::FETCH_ASSOC);

// Veritabanı Temizleme İşlemi
if (isset($_POST['db_cleanup'])) {
    validateCSRFToken($_POST['csrf_token']);
    
    $clean_range = clean($_POST['clean_range']);
    $clear_orders = isset($_POST['clear_orders']) ? 1 : 0;
    $clear_waiter = isset($_POST['clear_waiter']) ? 1 : 0;
    
    if (!$clear_orders && !$clear_waiter) {
        $_SESSION['error'] = "Lütfen temizlemek istediğiniz veri türlerinden en az birini seçin.";
        header("Location: ?page=settings");
        exit;
    }
    
    // Zaman filtresi koşulunu oluştur
    $date_condition = "";
    if ($clean_range === 'week') {
        $date_condition = "AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $date_condition_waiter = "WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($clean_range === 'month') {
        $date_condition = "AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $date_condition_waiter = "WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    } else {
        // 'all' - tümü
        $date_condition = "";
        $date_condition_waiter = "";
    }
    
    try {
        $db->beginTransaction();
        $deleted_count = 0;
        
        // 1. Siparişleri temizle
        if ($clear_orders) {
            // Önce ilişkisel order_items kayıtlarını sil (yabancı anahtar çakışmasını veya artık veriyi önlemek için)
            $subquery = "SELECT id FROM orders WHERE status IN ('completed', 'cancelled') $date_condition";
            $stmtItems = $db->query($subquery);
            $order_ids = $stmtItems->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($order_ids)) {
                $in_clause = implode(',', array_map('intval', $order_ids));
                $db->exec("DELETE FROM order_items WHERE order_id IN ($in_clause)");
                $stmtOrders = $db->exec("DELETE FROM orders WHERE id IN ($in_clause)");
                $deleted_count += $stmtOrders;
            }
        }
        
        // 2. Garson Çağrılarını temizle
        if ($clear_waiter) {
            $stmtWaiter = $db->exec("DELETE FROM waiter_calls $date_condition_waiter");
            $deleted_count += $stmtWaiter;
        }
        
        $db->commit();
        $_SESSION['success'] = "Veritabanı temizleme işlemi başarıyla tamamlandı. Toplam $deleted_count kayıt silindi.";
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Veritabanı temizleme sırasında hata oluştu: " . $e->getMessage();
    }
    
    header("Location: ?page=settings");
    exit;
}

// Ayarları güncelle  
if (isset($_POST['update_settings'])) {
    $site_title = clean($_POST['site_title']);
    $site_description = clean($_POST['site_description']);
    $contact_email = clean($_POST['contact_email']);
    $contact_phone = clean($_POST['contact_phone']);
    $address = clean($_POST['address']);
    $footer_text = clean($_POST['footer_text']);

    // Renkleri al
    $color_primary = clean($_POST['color_primary']);
    $color_secondary = clean($_POST['color_secondary']);
    $color_bg = clean($_POST['color_bg']);
    $color_text = clean($_POST['color_text']);

    try {
        // Logo güncelleme  
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $logo = uploadFile($_FILES['logo'], ['jpg', 'jpeg', 'png', 'webp', 'avif']);
            if ($logo) {
                // Eski logoyu sil  
                if ($settings['logo'] && file_exists(UPLOAD_DIR . $settings['logo'])) {
                    unlink(UPLOAD_DIR . $settings['logo']);
                }

                $stmt = $db->prepare("UPDATE settings SET logo = ?");
                $stmt->execute([$logo]);
            }
        }

        // Diğer ayarları güncelle  
        $stmt = $db->prepare("UPDATE settings SET   
            site_title = ?,   
            site_description = ?,   
            contact_email = ?,  
            contact_phone = ?,  
            address = ?,  
            footer_text = ?,
            color_primary = ?,
            color_secondary = ?,
            color_bg = ?,
            color_text = ?
        ");
        $stmt->execute([
            $site_title,
            $site_description,
            $contact_email,
            $contact_phone,
            $address,
            $footer_text,
            $color_primary,
            $color_secondary,
            $color_bg,
            $color_text
        ]);

        $_SESSION['success'] = "Ayarlar başarıyla güncellendi.";

        // Ayarları yeniden yükle  
        $settings = $db->query("SELECT * FROM settings")->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Ayarlar güncellenirken bir hata oluştu.";
    }
}
?>

<div class="container-fluid">
    <?php
    if (isset($_SESSION['success'])) {
        echo successMessage($_SESSION['success']);
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo errorMessage($_SESSION['error']);
        unset($_SESSION['error']);
    }
    ?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Site Ayarları</h5>
        </div>
        <div class="card-body">
            <form action="?page=settings" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Site Başlığı</label>
                            <input type="text" class="form-control" name="site_title"
                                value="<?php echo clean($settings['site_title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site Açıklaması</label>
                            <textarea class="form-control" name="site_description"
                                rows="3"><?php echo clean($settings['site_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" name="logo"
                                accept="image/png, image/jpeg, image/jpg, image/webp, image/avif">
                            <?php if ($settings['logo']): ?>
                                <br>
                                <p> Yüklü Logo</p>
                                <img src="<?php echo SITE_URL . UPLOAD_DIR . $settings['logo']; ?>" alt="Logo" class="mt-2"
                                    height="50">
                            <?php endif; ?>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">Görünüm Ayarları (Renkler)</h5>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Ana Renk (Primary)</label>
                                <input type="color" class="form-control form-control-color w-100" name="color_primary"
                                    value="<?php echo !empty($settings['color_primary']) ? $settings['color_primary'] : '#34495e'; ?>"
                                    title="Ana renk seçin">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">İkincil Renk (Secondary)</label>
                                <input type="color" class="form-control form-control-color w-100" name="color_secondary"
                                    value="<?php echo !empty($settings['color_secondary']) ? $settings['color_secondary'] : '#e67e22'; ?>"
                                    title="İkincil renk seçin">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Arka Plan (Background)</label>
                                <input type="color" class="form-control form-control-color w-100" name="color_bg"
                                    value="<?php echo !empty($settings['color_bg']) ? $settings['color_bg'] : '#f8f9fa'; ?>"
                                    title="Arka plan rengi seçin">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Metin Rengi (Text)</label>
                                <input type="color" class="form-control form-control-color w-100" name="color_text"
                                    value="<?php echo !empty($settings['color_text']) ? $settings['color_text'] : '#2c3e50'; ?>"
                                    title="Metin rengi seçin">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">İletişim E-posta</label>
                            <input type="email" class="form-control" name="contact_email"
                                value="<?php echo clean($settings['contact_email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">İletişim Telefon</label>
                            <input type="text" class="form-control" name="contact_phone"
                                value="<?php echo clean($settings['contact_phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adres</label>
                            <textarea class="form-control" name="address"
                                rows="3"><?php echo clean($settings['address']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Footer Metni</label>
                            <textarea class="form-control" name="footer_text"
                                rows="3"><?php echo clean($settings['footer_text']); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Ayarları Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Veritabanı Bakımı ve Temizliği Bölümü -->
    <div class="card mt-4 mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="card-title mb-0"><i class="fas fa-database me-2"></i>Veritabanı Bakımı ve Temizliği</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                <div>
                    <strong>DİKKAT:</strong> Bu işlem seçtiğiniz kriterlere uyan verileri <strong>kalıcı olarak silecektir</strong> ve bu işlem geri alınamaz! Lütfen silme kriterlerini doğru seçtiğinizden emin olun.
                </div>
            </div>

            <form action="?page=settings" method="post" id="cleanupForm" onsubmit="return confirmCleanup();">
                <input type="hidden" name="csrf_token" value="<?php echo createCSRFToken(); ?>">
                <input type="hidden" name="db_cleanup" value="1">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Zaman Aralığı</label>
                        <select class="form-select" name="clean_range" id="clean_range" required>
                            <option value="week">1 Haftadan Eski Veriler</option>
                            <option value="month" selected>1 Aydan Eski Veriler</option>
                            <option value="all">Tüm Zamanlar (Her Şeyi Sil)</option>
                        </select>
                        <div class="form-text">Seçilen zaman diliminden daha eski olan kayıtlar silinecektir.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold d-block">Silinecek Veri Tipleri</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="clear_orders" id="clear_orders" value="1" checked>
                            <label class="form-check-label" for="clear_orders">
                                Tamamlanan ve İptal Edilen Siparişler <span class="text-muted">(Sipariş Ürünleriyle Birlikte)</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="clear_waiter" id="clear_waiter" value="1" checked>
                            <label class="form-check-label" for="clear_waiter">
                                Garson Çağrı Geçmişi
                            </label>
                        </div>
                    </div>
                </div>

                <div class="text-end border-top pt-3 mt-3">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Temizlemeyi Başlat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmCleanup() {
    const clearOrders = document.getElementById('clear_orders').checked;
    const clearWaiter = document.getElementById('clear_waiter').checked;
    const cleanRange = document.getElementById('clean_range');
    const rangeText = cleanRange.options[cleanRange.selectedIndex].text;

    if (!clearOrders && !clearWaiter) {
        alert("Lütfen temizlenecek veri türlerinden en az birini seçin!");
        return false;
    }

    let selectedData = [];
    if (clearOrders) selectedData.push("Siparişler");
    if (clearWaiter) selectedData.push("Garson Çağrıları");

    // Birinci aşama onay
    const firstConfirm = confirm(
        "Kritik Uyarı:\n\n" +
        "Kapsam: " + selectedData.join(" ve ") + "\n" +
        "Zaman Kriteri: " + rangeText + "\n\n" +
        "Bu verilere uyan kayıtlar kalıcı olarak silinecektir! Devam etmek istiyor musunuz?"
    );

    if (!firstConfirm) return false;

    // İkinci aşama kesin onay (Eğer 'Tüm Zamanlar' seçildiyse)
    if (cleanRange.value === 'all') {
        const secondConfirm = confirm(
            "SON UYARI:\n\n" +
            "Sistemdeki seçilen tüm veriler (Tüm Zamanlar) geri getirilemez şekilde silinecektir!\n" +
            "Devam etmek için onaylıyor musunuz?"
        );
        return secondConfirm;
    }

    return true;
}
</script>