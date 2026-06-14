<?php

// Slider ekleme
if (isset($_POST['add_slider'])) {
    $title = clean($_POST['title']);
    $subtitle = clean($_POST['subtitle']);

    // Link oluşturma
    $category_id = (int) $_POST['category_id'];
    $link_url = $category_id > 0 ? "category.php?id=$category_id" : '';

    $button_text = clean($_POST['button_text']);
    $sort_order = (int) $_POST['sort_order'];
    $status = isset($_POST['status']) ? 1 : 0;

    // Resim yükleme
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $image = uploadFile($_FILES['image']);
    }

    if ($image) {
        try {
            $stmt = $db->prepare("INSERT INTO slider_images (title, subtitle, image, link_url, button_text, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $image, $link_url, $button_text, $sort_order, $status]);
            $_SESSION['success'] = "Slider başarıyla eklendi.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Slider eklenirken bir hata oluştu: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Slider resmi yüklenemedi. Dosya boyutu büyük olabilir veya desteklenmeyen format.";
    }

    header("Location: ?page=slider");
    exit;
}

// Slider düzenleme
if (isset($_POST['edit_slider'])) {
    $id = (int) $_POST['id'];
    $title = clean($_POST['title']);
    $subtitle = clean($_POST['subtitle']);

    // Link oluşturma
    $category_id = (int) $_POST['category_id'];
    $link_url = $category_id > 0 ? "category.php?id=$category_id" : '';

    $button_text = clean($_POST['button_text']);
    $sort_order = (int) $_POST['sort_order'];
    $status = isset($_POST['status']) ? 1 : 0;

    // Resim yükleme kontrolü
    $image_update = '';
    $new_image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $new_image = uploadFile($_FILES['image']);
        if ($new_image) {
            // Eski resmi sil
            $old_image = $db->prepare("SELECT image FROM slider_images WHERE id = ?");
            $old_image->execute([$id]);
            $old_image_data = $old_image->fetch(PDO::FETCH_ASSOC);
            if ($old_image_data && file_exists(UPLOAD_DIR . $old_image_data['image'])) {
                unlink(UPLOAD_DIR . $old_image_data['image']);
            }
            $image_update = ', image = ?';
        }
    }

    try {
        if ($image_update) {
            $stmt = $db->prepare("UPDATE slider_images SET title = ?, subtitle = ?, link_url = ?, button_text = ?, sort_order = ?, status = ? $image_update WHERE id = ?");
            $stmt->execute([$title, $subtitle, $link_url, $button_text, $sort_order, $status, $new_image, $id]);
        } else {
            $stmt = $db->prepare("UPDATE slider_images SET title = ?, subtitle = ?, link_url = ?, button_text = ?, sort_order = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $subtitle, $link_url, $button_text, $sort_order, $status, $id]);
        }
        $_SESSION['success'] = "Slider başarıyla güncellendi.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Slider güncellenirken bir hata oluştu: " . $e->getMessage();
    }

    header("Location: ?page=slider");
    exit;
}

// Slider silme
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    try {
        // Önce resmi sil
        $slider = $db->query("SELECT image FROM slider_images WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        if ($slider && $slider['image'] && file_exists(UPLOAD_DIR . $slider['image'])) {
            unlink(UPLOAD_DIR . $slider['image']);
        }

        // Sonra kaydı sil
        $db->exec("DELETE FROM slider_images WHERE id = $id");
        $_SESSION['success'] = "Slider başarıyla silindi.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Slider silinirken bir hata oluştu.";
    }

    header("Location: ?page=slider");
    exit;
}

// Sliderları listele
$sliders = $db->query("SELECT * FROM slider_images ORDER BY sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Kategorileri çek (Modal içinde kullanmak için)
$categories = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
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

    <!-- Slider Ekleme Butonu -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-2 mb-md-0">Slider Yönetimi</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSliderModal">
            <i class="fas fa-plus me-2"></i><span class="d-none d-sm-inline">Yeni </span>Slider Ekle
        </button>
    </div>

    <!-- Sliderlar Tablosu -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Görsel</th>
                            <th>Başlık</th>
                            <th class="d-none d-md-table-cell">Alt Başlık</th>
                            <th class="d-none d-lg-table-cell">Buton</th>
                            <th class="d-none d-sm-table-cell">Sıra</th>
                            <th class="d-none d-md-table-cell">Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sliders as $slider): ?>
                            <tr>
                                <td data-label="Görsel">
                                    <img src="<?php echo SITE_URL . UPLOAD_DIR . $slider['image']; ?>"
                                        alt="<?php echo clean($slider['title']); ?>" width="80" height="45" class="rounded"
                                        style="object-fit: cover;">
                                </td>
                                <td data-label="Başlık"><?php echo clean($slider['title']); ?></td>
                                <td data-label="Alt Başlık" class="d-none d-md-table-cell">
                                    <?php
                                    $subtitle = clean($slider['subtitle']);
                                    echo strlen($subtitle) > 50 ? substr($subtitle, 0, 50) . '...' : $subtitle;
                                    ?>
                                </td>
                                <td data-label="Buton" class="d-none d-lg-table-cell">
                                    <?php echo clean($slider['button_text']); ?>
                                </td>
                                <td data-label="Sıra" class="d-none d-sm-table-cell"><?php echo $slider['sort_order']; ?>
                                </td>
                                <td data-label="Durum" class="d-none d-md-table-cell">
                                    <span class="badge bg-<?php echo $slider['status'] ? 'success' : 'danger'; ?>">
                                        <?php echo $slider['status'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td data-label="İşlemler">
                                    <div class="btn-group-vertical-mobile d-md-inline">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#editSliderModal<?php echo $slider['id']; ?>">
                                            <i class="fas fa-edit"></i><span class="d-none d-lg-inline"> Düzenle</span>
                                        </button>
                                        <a href="?page=slider&action=delete&id=<?php echo $slider['id']; ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirmAction(this, 'Bu slider\'ı silmek istediğinizden emin misiniz?');">
                                            <i class="fas fa-trash"></i><span class="d-none d-lg-inline"> Sil</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Düzenleme Modal -->
                            <div class="modal fade" id="editSliderModal<?php echo $slider['id']; ?>">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Slider Düzenle</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="?page=slider" method="post" enctype="multipart/form-data">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Başlık *</label>
                                                            <input type="text" class="form-control" name="title"
                                                                value="<?php echo clean($slider['title']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Alt Başlık</label>
                                                            <textarea class="form-control" name="subtitle"
                                                                rows="2"><?php echo clean($slider['subtitle']); ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Yönlendirilecek Kategori</label>
                                                            <?php
                                                                // Mevcut linkten kategori ID'sini bul
                                                                $current_cat_id = 0;
                                                                if (preg_match('/category\.php\?id=(\d+)/', $slider['link_url'], $matches)) {
                                                                    $current_cat_id = (int)$matches[1];
                                                                }
                                                            ?>
                                                            <select class="form-select" name="category_id">
                                                                <option value="0">Link Yok (Yönlendirme Yapma)</option>
                                                                <?php foreach($categories as $cat): ?>
                                                                <option value="<?php echo $cat['id']; ?>" 
                                                                        <?php echo $current_cat_id == $cat['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo clean($cat['name']); ?>
                                                                </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Buton Metni</label>
                                                            <input type="text" class="form-control" name="button_text"
                                                                value="<?php echo clean($slider['button_text']); ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Sıra</label>
                                                            <input type="number" class="form-control" name="sort_order"
                                                                value="<?php echo $slider['sort_order']; ?>" min="0">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Yeni Görsel</label>
                                                            <input type="file" class="form-control" name="image" accept="image/png, image/jpeg, image/webp, image/avif">
                                                            <small class="text-muted">Mevcut:
                                                                <?php echo $slider['image']; ?></small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="status" <?php echo $slider['status'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label">Aktif</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">İptal</button>
                                                <button type="submit" name="edit_slider"
                                                    class="btn btn-primary">Güncelle</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>




<!-- Yeni Slider Ekleme Modal -->
<div class="modal fade" id="addSliderModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Slider Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="?page=slider" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Başlık *</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alt Başlık</label>
                                <textarea class="form-control" name="subtitle" rows="2"
                                    placeholder="İsteğe bağlı açıklama..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yönlendirilecek Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="0">Link Yok (Yönlendirme Yapma)</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo clean($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Buton Metni</label>
                                <input type="text" class="form-control" name="button_text"
                                    placeholder="Örn: Menüyü İncele">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sıra</label>
                                <input type="number" class="form-control" name="sort_order" value="0" min="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Görsel *</label>
                                <input type="file" class="form-control" name="image" accept="image/*" required>
                                <small class="text-muted">Önerilen boyut: 1200x600 piksel</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status" checked>
                                    <label class="form-check-label">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_slider" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>