<?php

// Ürünleri ve önerilen durumlarını çek
function getRecommendedProducts($db) {
    $query = "
        SELECT p.*, 
               rp.is_new, 
               rp.is_vegan,
               rp.is_recommended,
               rp.id as recommend_id
               
        FROM products p
        LEFT JOIN recommended_products rp ON p.id = rp.product_id
        WHERE p.status = 1
        ORDER BY p.name ASC
    ";

    try {
        $stmt = $db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo errorMessage("Ürünler yüklenirken bir hata oluştu.");
        return [];
    }
}

// Önerilen ürün ekleme/güncelleme işlemi
function updateRecommendedProduct($db, $product_id, $is_new, $is_recommended, $is_vegan) {
    try {
        // Önce var olan kaydı kontrol et
        $check = $db->prepare("SELECT id FROM recommended_products WHERE product_id = ?");
        $check->execute([$product_id]);
        $exists = $check->fetch();

        if($exists) {
            // Güncelle
            $stmt = $db->prepare("UPDATE recommended_products SET is_new = ?, is_recommended = ?, is_vegan = ? WHERE product_id = ?");
            $stmt->execute([$is_new, $is_recommended, $is_vegan, $product_id]);
        } else {
            // Yeni kayıt ekle
            $stmt = $db->prepare("INSERT INTO recommended_products (product_id, is_new, is_recommended, is_vegan) VALUES (?, ?, ?, ?)");
            $stmt->execute([$product_id, $is_new, $is_recommended, $is_vegan]);
        }

        $_SESSION['success'] = "Ürün durumu başarıyla güncellendi.";
        header("Location: index.php?page=recommended_products");
        exit;
    } catch(PDOException $e) {
        echo errorMessage("Güncelleme sırasında bir hata oluştu.");
    }
}

$products = getRecommendedProducts($db);

if(isset($_POST['update_recommended'])) {
    $product_id = clean($_POST['product_id']);
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $is_recommended = isset($_POST['is_recommended']) ? 1 : 0;
    $is_vegan = isset($_POST['is_vegan']) ? 1 : 0; 

    updateRecommendedProduct($db, $product_id, $is_new, $is_recommended, $is_vegan);
}
?>

<!-- Sayfa İçeriği -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Yeni/Önerilen Yönetimi</h3>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Adı</th>
                                    <th>Fiyat</th>
                                    <th>Yeni</th>
                                    <th>Önerilen</th>
                                    <th>Vegan</th>
                                    <th>İşlemler</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo formatPrice($product['price']); ?> TL</td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['is_new'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $product['is_new'] ? 'Evet' : 'Hayır'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['is_recommended'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $product['is_recommended'] ? 'Evet' : 'Hayır'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['is_vegan'] ? 'success' : 'secondary'; ?>"> <!-- Vegan durumunu yazdırdım -->
                                                <?php echo $product['is_vegan'] ? 'Evet' : 'Hayır'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $product['id']; ?>">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Düzenleme Modal -->
                                    <div class="modal fade" id="editModal<?php echo $product['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Durumu Düzenle</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="" method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                                                        <div class="mb-3">
                                                            <label class="form-label">Adı</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" readonly>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" name="is_new" 
                                                                       id="isNew<?php echo $product['id']; ?>"
                                                                       <?php echo $product['is_new'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="isNew<?php echo $product['id']; ?>">
                                                                    Yeni
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" name="is_recommended"
                                                                       id="isRecommended<?php echo $product['id']; ?>"
                                                                       <?php echo $product['is_recommended'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="isRecommended<?php echo $product['id']; ?>">
                                                                    Önerilen
                                                                </label>
                                                            </div>
                                                        </div>


                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" name="is_vegan"
                                                                       id="isVegan<?php echo $product['id']; ?>"
                                                                       <?php echo $product['is_vegan'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="isVegan<?php echo $product['id']; ?>">
                                                                    Vegan
                                                                </label>
                                                            </div>
                                                        </div>

                                                        
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                        <button type="submit" name="update_recommended" class="btn btn-primary">Kaydet</button>
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
    </div>
</div>