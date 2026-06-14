<?php
ob_start(); // Çıktı tamponlamayı etkinleştir

// Kategori ekleme  
if(isset($_POST['add_category'])) {  
    $name = clean($_POST['name']);  
    $description = clean($_POST['description']);
    $status = isset($_POST['status']) ? 1 : 0;  

    // Resim yükleme
    $image = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $image = uploadFile($_FILES['image']);
    }

    try {  
        $stmt = $db->prepare("INSERT INTO categories (name, description, image, status) VALUES (?, ?, ?, ?)");  
        $stmt->execute([$name, $description, $image, $status]);  
        $_SESSION['success'] = "Kategori başarıyla eklendi.";  
    } catch(PDOException $e) {  
        $_SESSION['error'] = "Kategori eklenirken bir hata oluştu: " . $e->getMessage();  
    }  

    header("Location: ?page=categories");  
    exit;  
}  

// Kategorileri listele  
$categories = $db->query("  
    SELECT c.*, COUNT(p.id) as product_count   
    FROM categories c   
    LEFT JOIN products p ON c.id = p.category_id   
    GROUP BY c.id  
    ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);  
?>  

<div class="container-fluid">  
    <?php  
    if(isset($_SESSION['success'])) {  
        echo successMessage($_SESSION['success']);  
        unset($_SESSION['success']);  
    }  
    if(isset($_SESSION['error'])) {  
        echo errorMessage($_SESSION['error']);  
        unset($_SESSION['error']);  
    }  
    ?>  

    <!-- Kategori Ekleme Butonu -->  
    <div class="d-flex justify-content-between align-items-center mb-4">  
        <h4>Kategoriler</h4>  
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">  
            <i class="fas fa-plus me-2"></i>Yeni Kategori Ekle  
        </button>  
    </div>  

    <!-- Kategoriler Tablosu -->  
    <div class="card">  
        <div class="card-body">  
            <div class="table-responsive">
                <table class="table">  
                    <thead>  
                        <tr>  
                            <th>Görsel</th>
                            <th>Kategori Adı</th>  
                            <th class="d-none d-md-table-cell">Açıklama</th>
                            <th>Ürün Sayısı</th>  
                            <th>Durum</th>  
                            <th>İşlemler</th>  
                        </tr>  
                    </thead>  
                    <tbody>  
                        <?php foreach($categories as $category): ?>  
                        <tr>  
                            <td>
                                <?php if($category['image']): ?>
                                    <img src="<?php echo SITE_URL . UPLOAD_DIR . $category['image']; ?>" 
                                         alt="<?php echo clean($category['name']); ?>" 
                                         width="50" height="50" class="rounded object-fit-cover">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo clean($category['name']); ?></td>  
                            <td class="d-none d-md-table-cell"><?php echo mb_substr(clean($category['description']), 0, 50) . (mb_strlen($category['description'] ?? '') > 50 ? '...' : ''); ?></td>
                            <td><?php echo $category['product_count']; ?></td>  
                            <td>  
                                <span class="badge bg-<?php echo $category['status'] ? 'success' : 'danger'; ?>">  
                                    <?php echo $category['status'] ? 'Aktif' : 'Pasif'; ?>  
                                </span>  
                            </td>  
                            <td>  
                                <button class="btn btn-sm btn-primary"   
                                        data-bs-toggle="modal"   
                                        data-bs-target="#editCategoryModal<?php echo $category['id']; ?>">  
                                    <i class="fas fa-edit"></i>  
                                </button>  
                                <a href="?page=categories&action=delete&id=<?php echo $category['id']; ?>"   
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirmAction(this, 'Bu kategoriyi silmek istediğinize emin misiniz? Altındaki ürünler silinmeyecektir.');">  
                                    <i class="fas fa-trash"></i>  
                                </a>  
                            </td>  
                        </tr>  

                        <!-- Düzenleme Modal -->  
                        <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>">  
                            <div class="modal-dialog">  
                                <div class="modal-content">  
                                    <div class="modal-header">  
                                        <h5 class="modal-title">Kategori Düzenle</h5>  
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>  
                                    </div>  
                                    <form action="?page=categories&action=edit" method="post" enctype="multipart/form-data">  
                                        <div class="modal-body">  
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">  
                                            <div class="mb-3">  
                                                <label class="form-label">Kategori Adı</label>  
                                                <input type="text" class="form-control" name="name"   
                                                       value="<?php echo clean($category['name']); ?>" required>  
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Açıklama</label>
                                                <textarea class="form-control" name="description" rows="3"><?php echo clean($category['description'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Görsel</label>
                                                <input type="file" class="form-control" name="image" accept="image/png, image/jpeg, image/webp, image/avif">
                                                <?php if($category['image']): ?>
                                                    <div class="mt-2">
                                                        <img src="<?php echo SITE_URL . UPLOAD_DIR . $category['image']; ?>" width="100" class="rounded">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-3">  
                                                <div class="form-check">  
                                                    <input class="form-check-input" type="checkbox" name="status"   
                                                           <?php echo $category['status'] ? 'checked' : ''; ?>>  
                                                    <label class="form-check-label">Aktif</label>  
                                                </div>  
                                            </div>  
                                        </div>  
                                        <div class="modal-footer">  
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>  
                                            <button type="submit" name="edit_category" class="btn btn-primary">Güncelle</button>  
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

<!-- Yeni Kategori Ekleme Modal -->  
<div class="modal fade" id="addCategoryModal">  
    <div class="modal-dialog">  
        <div class="modal-content">  
            <div class="modal-header">  
                <h5 class="modal-title">Yeni Kategori Ekle</h5>  
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>  
            </div>  
            <form action="?page=categories" method="post" enctype="multipart/form-data">  
                <div class="modal-body">  
                    <div class="mb-3">  
                        <label class="form-label">Kategori Adı</label>  
                        <input type="text" class="form-control" name="name" required>  
                    </div> 
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Görsel</label>
                        <input type="file" class="form-control" name="image" accept="image/png, image/jpeg, image/webp, image/avif">
                    </div> 
                    <div class="mb-3">  
                        <div class="form-check">  
                            <input class="form-check-input" type="checkbox" name="status" checked>  
                            <label class="form-check-label">Aktif</label>  
                        </div>  
                    </div>  
                </div>  
                <div class="modal-footer">  
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>  
                    <button type="submit" name="add_category" class="btn btn-primary">Ekle</button>  
                </div>  
            </form>  
        </div>  
    </div>  
</div>  

<?php  
// Kategori silme işlemi  
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {  
    $id = (int)$_GET['id'];  

    try {  
        // Önce resmi sil
        $cat = $db->query("SELECT image FROM categories WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        if ($cat && $cat['image'] && file_exists(UPLOAD_DIR . $cat['image'])) {
            unlink(UPLOAD_DIR . $cat['image']);
        }

        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");  
        $stmt->execute([$id]);  
        $_SESSION['success'] = "Kategori başarıyla silindi.";  
    } catch(PDOException $e) {  
        $_SESSION['error'] = "Kategori silinirken bir hata oluştu: " . $e->getMessage();  
    }  

    header("Location: ?page=categories");  
    exit;  
}  

// Kategori düzenleme işlemi  
if(isset($_POST['edit_category'])) {  
    $id = (int)$_POST['id'];  
    $name = clean($_POST['name']);
    $description = clean($_POST['description']);  
    $status = isset($_POST['status']) ? 1 : 0;  

    try {
        // Resim güncelleme
        if(isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $image = uploadFile($_FILES['image']);
            if($image) {
                // Eski resmi sil
                $old_image = $db->query("SELECT image FROM categories WHERE id = $id")->fetchColumn();
                if($old_image && file_exists(UPLOAD_DIR . $old_image)) {
                    unlink(UPLOAD_DIR . $old_image);
                }
                
                $stmt = $db->prepare("UPDATE categories SET image = ? WHERE id = ?");
                $stmt->execute([$image, $id]);
            }
        }
 
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");  
        $stmt->execute([$name, $description, $status, $id]);  
        $_SESSION['success'] = "Kategori başarıyla güncellendi.";  
    } catch(PDOException $e) {  
        $_SESSION['error'] = "Kategori güncellenirken bir hata oluştu: " . $e->getMessage();  
    }  

    header("Location: ?page=categories");  
    exit;  
}  

ob_end_flush(); // Çıktı tamponlamayı kapat
?>