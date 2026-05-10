(function($) {
    'use strict';

    $(document).ready(function() {
        
        // 1. Збереження профілю
        $('#da-profile-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $msg = $('#da-profile-msg');
            
            var data = {
                action: 'da_save_profile',
                nonce: da_vars.nonce,
                birth_date: $form.find('input[name="birth_date"]').val(),
                birth_time: $form.find('input[name="birth_time"]').val(),
                lat: $form.find('input[name="lat"]').val(),
                lng: $form.find('input[name="lng"]').val(),
                house_system_id: $form.find('select[name="house_system"]').val(),
                
                // НОВІ ПОЛЯ ТЕЛЕГРАМ:
                telegram_chat_id: $form.find('input[name="telegram_chat_id"]').val(),
                telegram_time: $form.find('input[name="telegram_time"]').val()
            };

            $.post(da_vars.ajax_url, data, function(response) {
                if (response.success) {
                    $msg.css('color', 'green').text(response.data.message);
                    setTimeout(function() { location.reload(); }, 1500); // Оновлюємо сторінку
                } else {
                    $msg.css('color', 'red').text(response.data.message);
                }
            });
        });

        // 2. Отримання прогнозу
        function loadForecast() {
            var $results = $('#da-forecast-results');
            var selectedDate = $('#da-forecast-date').val();
            
            if (!$results.length) return; // Якщо ми не на сторінці дашборду
            
            $results.html('<div class="da-loading">Розрахунок...</div>');

            $.post(da_vars.ajax_url, {
                action: 'da_get_forecast',
                nonce: da_vars.nonce,
                date: selectedDate
            }, function(response) {
                if (response.success) {
                    $('#da-moon-title').html(response.data.moon_day);
                    // Вставляємо згенерований PHP HTML напряму
                    $('#da-moon-desc').html(response.data.moon_desc);
                    $results.html(response.data.html);
                } else {
                    $results.html('<p style="color:red;">' + response.data.message + '</p>');
                }
            });
        }

        // Завантажуємо прогноз при старті та при зміні дати
        loadForecast();
        
        $('#da-update-btn').on('click', function() {
            loadForecast();
        });
        // Плавне відкриття/закриття панелі налаштувань
        $('#da-settings-toggle').on('click', function() {
            $('#da-settings-panel').slideToggle(250); // 250мс для плавної анімації
        });
        
        $('#da-forecast-date').on('change', function() {
            loadForecast();
        });
    });

})(jQuery);