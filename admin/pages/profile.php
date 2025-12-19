<?php
require_once 'config.php';

// Oturum kontrolü
if (!isset($_SESSION['admin']['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['admin']['username'];

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Kullanıcı bulunamadı!');
}

// Profil güncelleme işlemi
if (isset($_POST['update_profile'])) {
    $username = clean($_POST['username']);
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Kullanıcı adı kontrolü
    if ($username !== $user['username']) {
        $check_username = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $check_username->execute([$username]);
        if ($check_username->fetchColumn() > 0) {
            $errors[] = "Bu kullanıcı adı zaten kullanılıyor!";
        }
    }

    // Mevcut şifreyi kontrol et
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Mevcut şifre yanlış!";
        } else if ($new_password !== $confirm_password) {
            $errors[] = "Yeni şifreler eşleşmiyor!";
        }
    }

    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Şifre değişikliği varsa
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $name, $email, $password_hash, $user['id']]);
            } else {
                // Sadece kullanıcı bilgilerini güncelle
                $stmt = $db->prepare("UPDATE users SET username = ?, name = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $name, $email, $user['id']]);
            }

            // Session'ı güncelle
            $_SESSION['admin']['username'] = $username;

            $_SESSION['success'] = "Profil başarıyla güncellendi!";
            header("Location: profile.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Güncelleme sırasında bir hata oluştu!";
        }
    }
}
?>



<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profil Ayarları</h3>
                </div>
                <div class="card-body">
                    <?php if(isset($errors)): ?>
                        <?php foreach($errors as $error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post">
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <hr>
                        <h5 class="mb-3">Şifre Değiştir</h5>

                        <div class="mb-3">
                            <label class="form-label">Mevcut Şifre</label>
                            <input type="password" class="form-control" name="current_password">
                            <small class="text-muted">Şifrenizi değiştirmek istemiyorsanız boş bırakın</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" name="new_password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" name="confirm_password">
                        </div>

                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Değişiklikleri Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>