$(document).ready(function () {
    // Bildirimleri 5 saniyede bir kontrol et
    setInterval(checkNotifications, 5000);
    checkNotifications(); // Sayfa yüklendiğinde ilk kontrol

    // Önceki bildirim sayılarını tutmak için değişkenler
    let previousTotal = 0;
    let firstLoad = true;
    const audio = document.getElementById('notificationSound');

    function checkNotifications() {
        $.ajax({
            url: '../api/get_notifications.php', // admin dizininden çıktığımız için ../api
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    updateNotificationUI(response);
                    handleSound(response);
                }
            },
            error: function (err) {
                console.error('Bildirim kontrol hatası:', err);
            }
        });
    }

    function handleSound(data) {
        const currentCalls = data.calls ? data.calls.length : 0;
        const currentOrders = data.orders ? data.orders.length : 0;
        const currentTotal = currentCalls + currentOrders;

        // Eğer ilk yükleme değilse ve bildirim sayısı arttıysa ses çal
        if (!firstLoad && currentTotal > previousTotal) {
            playNotificationSound();
        }

        // Sayıları güncelle
        previousTotal = currentTotal;
        firstLoad = false;
    }

    function playNotificationSound() {
        if (audio) {
            audio.play().catch(function (error) {
                console.log('Ses çalma başarısız (Tarayıcı politikası olabilir):', error);
            });
        }
    }

    function updateNotificationUI(data) {
        const badge = $('#notification-badge');
        const list = $('#notification-list');
        const total = (data.calls ? data.calls.length : 0) + (data.orders ? data.orders.length : 0);

        // Badge Güncelleme
        badge.text(total);
        if (total > 0) {
            badge.show();
            badge.addClass('pulse-animation');
        } else {
            badge.hide();
            badge.removeClass('pulse-animation');
        }

        // Dropdown Listesi Güncelleme
        let html = '';

        if (total === 0) {
            html = `<li><a class="dropdown-item text-center text-muted" href="#">Bildirim yok</a></li>`;
        } else {
            html += `<li><h6 class="dropdown-header bg-light">Bildirimler</h6></li>`;

            // Garson Çağrıları
            if (data.calls && data.calls.length > 0) {
                html += `<li><span class="dropdown-header text-warning py-1"><i class="fas fa-concierge-bell me-1"></i>Garson Çağrıları</span></li>`;
                data.calls.forEach(call => {
                    let icon = 'fa-bell';
                    let bg = 'bg-warning';
                    if (call.type === 'Hesap') { icon = 'fa-file-invoice-dollar'; bg = 'bg-info'; }
                    if (call.type === 'Su') { icon = 'fa-glass-whiskey'; bg = 'bg-primary'; }

                    html += `
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="?page=waiter_calls">
                            <div class="${bg} rounded-circle p-2 me-2 text-white" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas ${icon} fa-sm"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-bold small">Masa ${call.table_no} - ${call.type}</p>
                                <small class="text-muted" style="font-size: 0.75rem;">${call.time}</small>
                            </div>
                        </a>
                    </li>`;
                });
            }

            // Siparişler
            if (data.orders && data.orders.length > 0) {
                html += `<li><div class="dropdown-divider my-1"></div></li>`;
                html += `<li><span class="dropdown-header text-primary py-1"><i class="fas fa-receipt me-1"></i>Yeni Siparişler</span></li>`;
                data.orders.forEach(order => {
                    html += `
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="?page=orders&status=pending">
                            <div class="bg-primary rounded-circle p-2 me-2 text-white" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-utensils fa-sm"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-bold small">Masa ${order.table_no}</p>
                                <small class="text-muted" style="font-size: 0.75rem;">${order.time}</small>
                            </div>
                        </a>
                    </li>`;
                });
            }
        }

        list.html(html);
    }
});
