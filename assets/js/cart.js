document.addEventListener('DOMContentLoaded', function () {
    // Sepet verisini localStorage'dan al veya boş başlat
    let cart = JSON.parse(localStorage.getItem('qr_menu_cart')) || [];

    // UI Güncelleme
    updateCartUI();

    // Global fonksiyonlar (onclick eventleri için window'a atıyoruz)
    // Global fonksiyonlar (onclick eventleri için window'a atıyoruz)
    window.addToCart = function (id, name, price, image) {
        // ID'yi string'e çevir (Tür uyuşmazlığını önlemek için)
        id = String(id);

        console.log('Adding to cart:', id, name); // Debug için

        const existingItem = cart.find(item => item.id === id);

        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({
                id: id,
                name: name,
                price: parseFloat(price),
                image: image,
                quantity: 1
            });
        }

        saveCart();
        showToast(`"${name}" sepete eklendi`);
        updateCartUI();
    };

    window.removeFromCart = function (index) {
        cart.splice(index, 1);
        saveCart();
        updateCartUI();
    };

    window.increaseQuantity = function (index) {
        cart[index].quantity++;
        saveCart();
        updateCartUI();
    };

    window.decreaseQuantity = function (index) {
        if (cart[index].quantity > 1) {
            cart[index].quantity--;
        } else {
            cart.splice(index, 1);
        }
        saveCart();
        updateCartUI();
    };

    window.clearCart = function () {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Sepetinizdeki tüm ürünler silinecek!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                cart = [];
                saveCart();
                updateCartUI();
                Swal.fire(
                    'Silindi!',
                    'Sepetiniz başarıyla boşaltıldı.',
                    'success'
                );
            }
        });
    };

    // Helper functions
    function saveCart() {
        localStorage.setItem('qr_menu_cart', JSON.stringify(cart));
    }

    function updateCartUI() {
        const cartCount = document.getElementById('cart-count');
        const cartTotal = document.getElementById('cart-total');
        const cartItemsContainer = document.getElementById('cart-items');
        const cartFooter = document.getElementById('cartFooter');
        const cartEmpty = document.getElementById('cart-empty');
        const floatingBtn = document.getElementById('floating-cart-btn');

        // Toplam ürün sayısı ve tutar
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        // Badge güncelle
        if (cartCount) cartCount.textContent = totalItems;

        // Floating button göster/gizle
        // Floating button animasyon
        if (floatingBtn) {
            if (totalItems > 0) {
                floatingBtn.classList.add('bounce');
                setTimeout(() => floatingBtn.classList.remove('bounce'), 1000);
            }
            floatingBtn.style.display = 'block';
        }

        // Modal içeriği güncelle (Eğer modal açıksa veya açılacaksa)
        if (cartItemsContainer) {
            cartItemsContainer.innerHTML = '';

            if (cart.length === 0) {
                cartItemsContainer.style.display = 'none';
                if (cartFooter) cartFooter.style.display = 'none';
                if (cartEmpty) cartEmpty.style.display = 'block';
            } else {
                if (cartEmpty) cartEmpty.style.display = 'none';
                cartItemsContainer.style.display = 'block';
                if (cartFooter) cartFooter.style.display = 'block';

                cart.forEach((item, index) => {
                    const itemHtml = `
                        <div class="cart-item d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                <img src="${item.image}" alt="${item.name}" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-0">${item.name}</h6>
                                    <small class="text-muted">${item.price.toFixed(2)} ₺</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <button class="btn btn-sm btn-outline-secondary me-2" onclick="decreaseQuantity(${index})">-</button>
                                <span class="fw-bold me-2">${item.quantity}</span>
                                <button class="btn btn-sm btn-outline-secondary me-2" onclick="increaseQuantity(${index})">+</button>
                                <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    cartItemsContainer.insertAdjacentHTML('beforeend', itemHtml);
                });

                if (cartTotal) cartTotal.textContent = totalPrice.toFixed(2);
            }
        }
    }

    window.showToast = function (message) {
        // Basit bir toast mesajı
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    };
});

// Sipariş Gönderme
// Sipariş Gönderme
async function submitOrder() {
    const tableNo = document.getElementById('table-no').value;
    const note = document.getElementById('order-note').value;
    const cart = JSON.parse(localStorage.getItem('qr_menu_cart')) || [];
    const submitBtn = document.querySelector('button[onclick="submitOrder()"]');
    const originalBtnText = submitBtn.innerHTML;

    if (cart.length === 0) {
        showToast('Sepetiniz boş!');
        return;
    }

    const tableInput = document.getElementById('table-no');

    if (!tableNo) {
        showToast('Lütfen masa numaranızı giriniz.');
        tableInput.classList.add('is-invalid', 'shake-animation'); // Hata sınıfı ve animasyon
        tableInput.focus();

        // Kullanıcı yazmaya başlayınca hatayı kaldır
        tableInput.addEventListener('input', function () {
            this.classList.remove('is-invalid', 'shake-animation');
        }, { once: true });

        return;
    }

    // Butonu pasif yap
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gönderiliyor...';

    const orderData = {
        table_no: tableNo,
        note: note,
        items: cart
    };

    try {
        const response = await fetch('api/place_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(orderData)
        });

        // Yanıtın JSON olup olmadığını kontrol et
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Sunucudan geçersiz yanıt alındı (JSON değil).");
        }

        const result = await response.json();

        if (result.success) {
            // Modal'ı kapat (Bootstrap 5)
            const modalEl = document.getElementById('cartModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            // Başarılı mesajı
            Swal.fire({
                icon: 'success',
                title: 'Sipariş Alındı!',
                text: 'Siparişiniz başarıyla iletildi. Afiyet olsun!',
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                localStorage.removeItem('qr_menu_cart');
                window.location.reload();
            });

            // Eğer SweetAlert2 yüklü değilse fallback
            if (typeof Swal === 'undefined') {
                alert('Siparişiniz alındı! Teşekkür ederiz.');
                localStorage.removeItem('qr_menu_cart');
                window.location.reload();
            }

        } else {
            console.error('Sipariş hatası:', result);
            alert('Sipariş verilirken bir hata oluştu: ' + (result.error || 'Bilinmeyen hata'));
        }
    } catch (error) {
        console.error('Connection Error:', error);
        alert('Bir bağlantı hatası oluştu: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}
