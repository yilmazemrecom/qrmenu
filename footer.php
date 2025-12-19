<?php

// Veritabanından ayarları çek
$settingsQuery = $db->query("SELECT * FROM settings LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

?>
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h4 class="footer-title"><?= $settings['site_title'] ?? 'QR Menü Sistemi'; ?></h4>
                <p><?= $settings['footer_text'] ?? ''; ?></p>
            </div>
            <div class="col-md-6">
                <h4 class="footer-title">İletişim</h4>
                <ul class="footer-contact">
                    <?php if (!empty($settings['contact_phone'])): ?>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span><?= $settings['contact_phone']; ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['contact_email'])): ?>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span><?= $settings['contact_email']; ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['address'])): ?>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= $settings['address']; ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>© <?= date('Y'); ?> <?= $settings['site_title'] ?? 'QR Menü Sistemi'; ?>. Tüm hakları saklıdır. <br>
                Yapım: <a href="https://yilmazemre.tr" target="_blank">EY</a></p>

        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Floating Action Buttons Container -->
<div class="position-fixed end-0 bottom-0 m-3 z-3 d-flex flex-column gap-3"
    style="bottom: 20px !important; right: 20px !important;">

    <!-- Garson Çağırma Butonu -->
    <button
        class="btn btn-warning rounded-circle shadow-lg d-flex align-items-center justify-content-center shake-hover"
        style="width: 60px; height: 60px;" data-bs-toggle="modal" data-bs-target="#waiterModal">
        <i class="fas fa-bell fa-lg text-white"></i>
    </button>

    <!-- Sepet Butonu -->
    <div id="floating-cart-btn" class="position-relative" data-bs-toggle="modal" data-bs-target="#cartModal"
        style="cursor: pointer;">
        <button class="btn btn-success rounded-circle shadow-lg d-flex align-items-center justify-content-center"
            style="width: 60px; height: 60px;">
            <i class="fas fa-shopping-basket fa-lg text-white"></i>
        </button>
        <span id="cart-count"
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light"
            style="font-size: 0.8rem;">
            0
        </span>
    </div>

</div>

<!-- Garson Çağırma Modal -->
<div class="modal fade" id="waiterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-concierge-bell me-2"></i>Garson Çağır</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted mb-4">Lütfen isteğinizi seçiniz, hemen ilgilenelim.</p>

                <div class="mb-4">
                    <input type="text" id="waiterTableNo" class="form-control form-control-lg text-center fw-bold"
                        placeholder="Masa Numaranız" required>
                    <div class="invalid-feedback">Lütfen masa numaranızı giriniz.</div>
                </div>

                <div class="d-grid gap-3">
                    <button class="btn btn-outline-warning btn-lg waiter-option-btn" onclick="callWaiter('Garson')">
                        <i class="fas fa-user-tie me-2"></i> Garson Bakabilir mi?
                    </button>
                    <button class="btn btn-outline-info btn-lg waiter-option-btn" onclick="callWaiter('Hesap')">
                        <i class="fas fa-file-invoice-dollar me-2"></i> Hesap Lütfen
                    </button>
                    <button class="btn btn-outline-primary btn-lg waiter-option-btn" onclick="callWaiter('Su')">
                        <i class="fas fa-glass-whiskey me-2"></i> Su İstiyorum
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sepet Modal (Global) -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="cartModalLabel"><i
                        class="fas fa-shopping-basket me-2 text-primary"></i>Sepetim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="cart-empty" class="text-center py-5" style="display: none;">
                    <div class="mb-3">
                        <i class="fas fa-shopping-cart fa-4x text-muted opacity-50"></i>
                    </div>
                    <h5 class="text-muted mb-3">Sepetinizde henüz ürün yok.</h5>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-utensils me-2"></i>Menüye Göz At
                    </button>
                </div>

                <div id="cart-items" class="mt-2">
                    <!-- Javascript ile doldurulacak -->
                </div>
            </div>

            <div class="modal-footer flex-column border-top-0 pt-0" id="cartFooter" style="display: none;">
                <div class="d-flex justify-content-between w-100 mb-3 p-3 bg-light rounded-3">
                    <span class="fs-5 fw-bold">Toplam Tutar:</span>
                    <span class="fs-4 fw-bold text-primary"><span id="cart-total">0.00</span> ₺</span>
                </div>

                <div class="w-100 mb-4">
                    <label class="form-label fw-bold">Masa Numaranız</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white"><i class="fas fa-chair text-muted"></i></span>
                        <input type="text" id="table-no" class="form-control" placeholder="Örn: 5">
                    </div>
                </div>

                <div class="w-100 mb-4">
                    <label class="form-label fw-bold">Sipariş Notu <small class="text-muted fw-normal">(İsteğe
                            bağlı)</small></label>
                    <textarea id="order-note" class="form-control" rows="2"
                        placeholder="Örn: Acısız olsun, buzlu olsun..."></textarea>
                </div>

                <div class="d-flex w-100 gap-2">
                    <button type="button" class="btn btn-outline-danger btn-lg" onclick="clearCart()">
                        <i class="fas fa-trash me-2"></i>Temizle
                    </button>
                    <button type="button" class="btn btn-success btn-lg flex-grow-1 shadow-sm" onclick="submitOrder()">
                        <i class="fas fa-check-circle me-2"></i>Siparişi Tamamla
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/cart.js?v=<?= time(); ?>"></script>
<script src="assets/js/waiter-call.js?v=<?= time(); ?>"></script>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="globalSearchInput" class="form-control bg-light border-0 shadow-none py-2"
                        placeholder="Menüde ara..." autocomplete="off">
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="globalSearchResults" style="min-height: 100px;">
                    <div class="text-center text-muted py-4 opacity-75">
                        <i class="fas fa-utensils fa-2x mb-2"></i>
                        <p>Lezzetli bir şeyler arayın...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/search.js"></script>
</body>

</html>