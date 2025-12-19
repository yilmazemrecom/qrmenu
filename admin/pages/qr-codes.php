<?php  
include "phpqrcode/qrlib.php";  

// QR kod oluşturma    
if(isset($_POST['generate_qr'])) {    
    try {  
        // QR kod içeriği direkt ana siteye yönlendirecek şekilde  
        $qr_content = SITE_URL;   

        // QR kod dosya adı    
        $qr_filename = 'qr_' . uniqid() . '.png';    
        $qr_file_path = '../' . UPLOAD_DIR . $qr_filename;  



        // QR kod oluştur  
        $size = 10; // QR kod boyutu (1-10 arası bir değer, 10 en büyük)
        $margin = 1; // QR kod kenar boşluğu
        QRcode::png($qr_content, $qr_file_path, QR_ECLEVEL_L, $size, $margin);  

        // QR kod dosya yolunu yazdır
        echo "QR kod dosya yolu: " . $qr_file_path;

        // Veritabanına kaydet  
        $stmt = $db->prepare("INSERT INTO qr_codes (filename, content) VALUES (?, ?)");    
        $stmt->execute([$qr_filename, $qr_content]);    

        $_SESSION['success'] = "QR kod başarıyla oluşturuldu.";    
    } catch(Exception $e) {    
        $_SESSION['error'] = "QR kod oluşturulurken bir hata oluştu: " . $e->getMessage();    
    }    

    header("Location: ?page=qr");    
    exit;    
}    

// En son oluşturulan QR kodu göster  
$qr_code = $db->query("SELECT * FROM qr_codes ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);    
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

    <!-- QR Kod Oluşturma Butonu -->    
    <div class="d-flex justify-content-between align-items-center mb-4">    
        <h4>QR Kod</h4>    
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateQRModal">    
            <i class="fas fa-qrcode me-2"></i>Yeni QR Kod Oluştur    
        </button>    
    </div>    

    <!-- Tek QR Kod Gösterimi -->    
    <?php if($qr_code && file_exists('../' . UPLOAD_DIR . $qr_code['filename'])): ?>  
    <div class="row justify-content-center">    
        <div class="col-md-6">    
            <div class="card">    
                <div class="card-body text-center">    
                    <img src="<?php echo '../' . UPLOAD_DIR . $qr_code['filename']; ?>"     
                         alt="QR Code" class="img-fluid mb-3" style="width: 500px; height: 500px;">    
                    <div class="btn-group">    
                        <a href="<?php echo '../' . UPLOAD_DIR . $qr_code['filename']; ?>"     
                           download="site_qr.png"     
                           class="btn btn-success btn-sm">    
                            <i class="fas fa-download me-2"></i>İndir    
                        </a>    
                    </div>    
                </div>    
            </div>    
        </div>    
    </div>  
    <?php else: ?>
    <div class="alert alert-warning">QR kod bulunamadı veya dosya mevcut değil.</div>
    <?php endif; ?>    
</div>    

<!-- QR Kod Oluşturma Modal -->    
<div class="modal fade" id="generateQRModal">    
    <div class="modal-dialog">    
        <div class="modal-content">    
            <div class="modal-header">    
                <h5 class="modal-title">Yeni QR Kod Oluştur</h5>    
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>    
            </div>    
            <form action="?page=qr" method="post">    
                <div class="modal-footer">    
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>    
                    <button type="submit" name="generate_qr" class="btn btn-primary">Oluştur</button>    
                </div>    
            </form>    
        </div>    
    </div>    
</div>