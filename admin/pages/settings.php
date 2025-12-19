<?php
// Ayarları yükle  
$settings = $db->query("SELECT * FROM settings")->fetch(PDO::FETCH_ASSOC);

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
</div>