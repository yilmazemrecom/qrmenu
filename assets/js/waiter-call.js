document.addEventListener('DOMContentLoaded', function () {
    // SessionStorage'dan masa numarasını al (Daha önce girildiyse)
    let savedTableNo = localStorage.getItem('qr_menu_table_no') || '';
    const waiterTableInput = document.getElementById('waiterTableNo');

    if (waiterTableInput && savedTableNo) {
        waiterTableInput.value = savedTableNo;
    }

    // Modal açıldığında focus
    const waiterModal = document.getElementById('waiterModal');
    if (waiterModal) {
        waiterModal.addEventListener('shown.bs.modal', function () {
            if (!waiterTableInput.value) {
                waiterTableInput.focus();
            }
        });
    }
});

function callWaiter(type) {
    const tableInput = document.getElementById('waiterTableNo');
    const tableNo = tableInput.value.trim();
    const feedbackMsg = tableInput.nextElementSibling; // invalid-feedback div

    if (!tableNo) {
        // alert('Lütfen masa numaranızı giriniz.'); // Kaldırıldı
        tableInput.classList.add('is-invalid');
        if (feedbackMsg) feedbackMsg.textContent = 'Lütfen masa numaranızı boş bırakmayınız.';
        tableInput.focus();

        // Kullanıcı yazmaya başlayınca hatayı kaldır
        tableInput.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        }, { once: true });

        return;
    }

    // Masa numarasını kaydet
    localStorage.setItem('qr_menu_table_no', tableNo);
    tableInput.classList.remove('is-invalid');

    // Butonları pasif yap
    const btns = document.querySelectorAll('.waiter-option-btn');
    btns.forEach(btn => btn.disabled = true);

    // API isteği
    fetch('api/call_waiter.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            table_no: tableNo,
            call_type: type
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Modal kapat
                const waiterModalEl = document.getElementById('waiterModal');
                const modal = bootstrap.Modal.getInstance(waiterModalEl);
                modal.hide();

                // Başarılı mesajı (SweetAlert varsa)
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Çağrı Alındı',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert(data.message);
                }
            } else {
                // Hata durumu (Örn: Zaten aktif çağrı var)
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Bekleyiniz',
                        text: data.error,
                        confirmButtonText: 'Tamam',
                        confirmButtonColor: '#ffca2c'
                    });
                } else {
                    alert(data.error);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // alert('Bir hata oluştu.'); // Sessiz kalması daha iyi olabilir veya tost mesajı
        })
        .finally(() => {
            btns.forEach(btn => btn.disabled = false);
        });
}
