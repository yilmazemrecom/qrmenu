document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('globalSearchInput');
    const searchResults = document.getElementById('globalSearchResults');
    const searchModal = document.getElementById('searchModal');
    let searchTimeout;

    // Modal açıldığında input'a focuslan
    if (searchModal) {
        searchModal.addEventListener('shown.bs.modal', function () {
            if (searchInput) searchInput.focus();
        });
    }

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();

            clearTimeout(searchTimeout);

            if (query.length < 3) {
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
                return;
            }

            // Loading göster
            searchResults.style.display = 'block';
            searchResults.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin text-primary"></i></div>';

            searchTimeout = setTimeout(() => {
                fetch(`api/search.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = ''; // Temizle

                        if (data.success && data.products.length > 0) {
                            data.products.forEach(product => {
                                const item = document.createElement('div');
                                item.className = 'search-item p-3 border-bottom d-flex align-items-center';
                                item.style.cursor = 'pointer';

                                // Price formatting fallback
                                let priceVal = 0;
                                if (typeof product.price === 'string') priceVal = parseFloat(product.price);
                                else priceVal = product.price;

                                item.innerHTML = `
                                    <img src="${product.image_url}" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-dark fw-bold">${product.name}</h6>
                                        <div class="text-primary fw-bold">${priceVal.toFixed(2)} ₺</div>
                                    </div>
                                    <button class="btn btn-sm btn-primary rounded-pill px-3">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                `;

                                // Click handler
                                item.onclick = function () {
                                    // 1. Öncemevcut search modalı kapat
                                    const modalInstance = bootstrap.Modal.getInstance(searchModal);
                                    modalInstance.hide();

                                    // 2. Product Modal verilerini doldur
                                    const pName = document.getElementById('modalProductName');
                                    const pImg = document.getElementById('modalProductImage');
                                    const pPrice = document.getElementById('modalProductPrice');
                                    const pDesc = document.getElementById('modalProductDescription');

                                    if (pName) pName.textContent = product.name;
                                    if (pImg) pImg.src = product.image_url;
                                    if (pPrice) pPrice.textContent = priceVal.toFixed(2);
                                    if (pDesc) pDesc.textContent = product.description || '';

                                    // 3. Sepet butonu ayarla
                                    const addToCartBtn = document.getElementById('modalAddToCartBtn');
                                    if (addToCartBtn) {
                                        // Clone to clear listeners
                                        const newBtn = addToCartBtn.cloneNode(true);
                                        addToCartBtn.parentNode.replaceChild(newBtn, addToCartBtn);

                                        newBtn.onclick = function () {
                                            if (typeof addToCart === 'function') {
                                                addToCart(product.id, product.name, priceVal, product.image_url);
                                            }

                                            // Product Modal kapat, Cart Modal aç
                                            const pmEl = document.getElementById('productModal');
                                            const pmInstance = bootstrap.Modal.getInstance(pmEl);
                                            pmInstance.hide();

                                            setTimeout(() => {
                                                const cmEl = document.getElementById('cartModal');
                                                const cmInstance = new bootstrap.Modal(cmEl);
                                                cmInstance.show();
                                            }, 300);
                                        };
                                    }

                                    // 4. Product Modal aç
                                    setTimeout(() => {
                                        const pmEl = document.getElementById('productModal');
                                        const pmInstance = new bootstrap.Modal(pmEl);
                                        pmInstance.show();
                                    }, 200);
                                };

                                searchResults.appendChild(item);
                            });
                        } else {
                            searchResults.innerHTML = `
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                                    <p class="mb-0">"${query}" için sonuç bulunamadı.</p>
                                </div>
                            `;
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        searchResults.innerHTML = '<div class="text-center text-danger py-2">Bir hata oluştu.</div>';
                    });
            }, 300); // 300ms debounce
        });
    }
});
