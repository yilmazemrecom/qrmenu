$(document).ready(function () {
    // 5 saniyede bir dashboard verilerini güncelle
    setInterval(updateDashboard, 5000);

    function updateDashboard() {
        $.ajax({
            url: '../api/get_dashboard_stats.php', // admin/pages/dashboard.php context'i için ../ gerekli mi? 
            // Hayır, dashboard.php admin/index.php üzerinden yükleniyor. Yani base URL admin/.
            // Bu yüzden api/get_dashboard_stats.php veya ../api/ çalışabilir. 
            // index.php view'ı admin klasöründe. api klasörü bir üstte ../api doğru.
            // Fakat ajax isteği browser'dan yapılıyor. Browser URL'i http://host/admin/?page=dashboard
            // Bu durumda relative path '../api/...' -> http://host/api/... olur. Doğru.
            url: 'api/get_dashboard_stats.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    // İstatistikleri Güncelle
                    $('#stat-today-revenue').text(data.todayRevenue);
                    $('#stat-today-orders').text(data.todayOrders);
                    $('#stat-pending-orders').text(data.pendingOrders);
                    $('#stat-active-calls').text(data.activeCalls);

                    // Son Siparişler Tablosunu Güncelle (Sadece içerik değiştiyse güncelleme eklenebilir ama şimdilik direkt basıyoruz)
                    $('#recent-orders-body').html(data.ordersHtml);

                    // Aktif Çağrıları Güncelle
                    $('#waiter-calls-list').html(data.callsHtml);
                }
            },
            error: function (err) {
                console.error('Dashboard update error:', err);
            }
        });
    }
});
