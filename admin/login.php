<?php
require_once 'config.php';
require_once 'functions.php';


if (isset($_POST['login'])) {
    // CSRF Kontrolü
    validateCSRFToken($_POST['csrf_token']);

    $username = clean($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Şifre Doğrulama (Modern yöntem veya Eski yöntem)
            $loginSuccess = false;

            // 1. Modern Yöntem (Bcrypt)
            if (password_verify($password, $user['password'])) {
                $loginSuccess = true;
            }
            // 2. Eski Yöntem (SHA256) - Geçiş için
            else if (hash_equals($user['password'], hash('sha256', $password))) {
                $loginSuccess = true;
                // Şifreyi güncelle (Rehash)
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$newHash, $user['id']]);
            }

            if ($loginSuccess) {
                // Giriş başarılı  
                $_SESSION['admin'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'name' => $user['name']
                ];

                // Beni hatırla  
                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 gün  

                    $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);
                }

                header('Location: index.php');
                exit;
            } else {
                $_SESSION['error'] = "Kullanıcı adı veya şifre hatalı!";
            }
        } else {
            $_SESSION['error'] = "Kullanıcı adı veya şifre hatalı!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Giriş yapılırken bir hata oluştu.";
    }

    header('Location: login.php');
    exit;
}

// Remember me kontrolü  
if (!isset($_SESSION['admin']) && isset($_COOKIE['remember_token'])) {
    $token = clean($_COOKIE['remember_token']);

    $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ? AND status = 1 LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['admin'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name']
        ];

        header('Location: index.php');
        exit;
    }
}

// Zaten giriş yapmışsa ana sayfaya yönlendir  
if (isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo img {
            max-height: 80px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-logo">
            <a href="https://yilmazemre.tr" target="_blank"><img src="assets/img/ey-logo.png" alt="Logo"></a>

        </div>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        <form action="login.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo createCSRFToken(); ?>">
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" class="form-control" name="username" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" name="password" required>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Beni Hatırla</label>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>