$(document).ready(function() {  
    // Sidebar toggle for mobile  
    $('.navbar-toggler').on('click', function() {  
        $('.sidebar').toggleClass('show');  
    });  

    // File input preview  
    $('input[type="file"]').on('change', function(e) {  
        var file = e.target.files[0];  
        var reader = new FileReader();  
        reader.onload = function(e) {  
            $('#imagePreview').attr('src', e.target.result);  
        }  
        reader.readAsDataURL(file);  
    });  

    // Delete confirmation  
    $('.delete-btn').on('click', function(e) {  
        e.preventDefault();  
        if(confirm('Bu öğeyi silmek istediğinizden emin misiniz?')) {  
            window.location = $(this).attr('href');  
        }  
    });  

    // Form validation  
    $('form').on('submit', function(e) {  
        var required = $(this).find('[required]');  
        var valid = true;  

        required.each(function() {  
            if(!$(this).val()) {  
                valid = false;  
                $(this).addClass('is-invalid');  
            } else {  
                $(this).removeClass('is-invalid');  
            }  
        });  

        if(!valid) {  
            e.preventDefault();  
            alert('Lütfen tüm zorunlu alanları doldurun.');  
        }  
    });  
});  


