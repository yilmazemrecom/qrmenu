<?php
// Oturumu başlat
session_start();

// Tüm oturum değişkenlerini temizle
session_unset();

// Oturumu tamamen sonlandır
session_destroy();

// Kullanıcıyı ana sayfaya veya giriş sayfasına yönlendir
header("Location: login.php"); // "login.php" yerine istediğiniz sayfanın yolunu yazabilirsiniz.
exit();
?>
