<?php
/**
 * Рендер сторінки налаштувань плагіна в адмінці.
 * Виводить статус математичного ядра та форми для імпорту баз даних ZET.
 * Version:     1.2.0
 * Date_update: 2026-05-10
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'DAstrolog: Імпорт Бази Даних ZET', 'dastrolog' ); ?></h1>

    <div class="da-system-status postbox" style="margin-top: 20px; padding: 15px; border-left: 4px solid <?php echo $system_status['swetest_ok'] && $system_status['ephemeris_ok'] ? '#00a32a' : '#d63638'; ?>;">
        <h3 style="margin-top: 0;">Статус математичного ядра:</h3>
        <ul style="margin-bottom: 0;">
            <li>
                <?php echo $system_status['swetest_ok'] ? '✅' : '❌'; ?> 
                <strong>Бінарний файл (swetest):</strong> <?php echo esc_html( $system_status['swetest_msg'] ); ?>
            </li>
            <li>
                <?php echo $system_status['ephemeris_ok'] ? '✅' : '❌'; ?> 
                <strong>Файли ефемерід (.se1):</strong> <?php echo esc_html( $system_status['ephemeris_msg'] ); ?>
            </li>
        </ul>
    </div>
    
    <div class="notice notice-info">
        <p><?php esc_html_e( 'Переконайтеся, що файли збережені у кодуванні UTF-8 та завантажені у папку /assets/data/ вашого плагіна.', 'dastrolog' ); ?></p>
    </div>

    <form method="post" action="options.php" style="background: #fff; padding: 15px 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-bottom: 20px;">
        <?php settings_fields( 'da_settings_group' ); ?>
        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;"><?php esc_html_e( 'Налаштування Telegram-бота', 'dastrolog' ); ?></h2>
        <table class="form-table" style="margin-top: 0;">
            <tr valign="top">
                <th scope="row" style="width: 200px; padding: 15px 10px 15px 0;">
                    <label for="da_tg_bot_token"><?php esc_html_e( 'Telegram API Token', 'dastrolog' ); ?></label>
                </th>
                <td style="padding: 15px 10px;">
                    <input type="text" id="da_tg_bot_token" name="da_tg_bot_token" value="<?php echo esc_attr( get_option('da_tg_bot_token') ); ?>" style="width: 400px;" placeholder="Напр. 1234567890:AAHqwertyuiop..." />
                    <p class="description"><?php esc_html_e( 'Вставте сюди API Token, який ви отримали від @BotFather.', 'dastrolog' ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Зберегти токен', 'primary', 'submit', false ); ?>
        &nbsp;
<button type="button" id="da-test-tg-btn" class="button button-secondary">
    <?php esc_html_e( 'Надіслати мені тестовий прогноз', 'dastrolog' ); ?>
</button>
<span id="da-tg-test-status" style="margin-left: 10px;"></span>
    </form>

    <h2 style="margin-top: 30px;"><?php esc_html_e( 'Імпорт баз даних ZET', 'dastrolog' ); ?></h2>
    <table class="form-table" style="background: #fff; padding: 15px 20px; border: 1px solid #ccd0d4; display: block;">
        <tr>
            <th scope="row"><?php esc_html_e( '1. Імпорт Довідників (Аспекти, Орбіси, Доми)', 'dastrolog' ); ?></th>
            <td>
                <button type="button" class="button button-primary" id="da-import-dictionaries">
                    <?php esc_html_e( 'Запустити імпорт', 'dastrolog' ); ?>
                </button>
                <span class="spinner" id="da-dict-spinner"></span>
                <p class="description">Бере дані з файлів: Natal.a2, Transit.a2, Interface.txt</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( '2. Імпорт Інтерпретацій (Транзити Здоров\'я)', 'dastrolog' ); ?></th>
            <td>
                <button type="button" class="button button-primary" id="da-import-transits">
                    <?php esc_html_e( 'Запустити імпорт', 'dastrolog' ); ?>
                </button>
                <span class="spinner" id="da-transit-spinner"></span>
                <p class="description">Бере дані з 4 файлів: Transit - Health.txt, Transit - Business.txt, Transit - Love and family.txt, Transit Conjunctions.txt</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( '3. Імпорт Місячних Днів (Глоба)', 'dastrolog' ); ?></th>
            <td>
                <button type="button" class="button button-primary" id="da-import-moon">
                    <?php esc_html_e( 'Запустити імпорт', 'dastrolog' ); ?>
                </button>
                <span class="spinner" id="da-moon-spinner"></span>
                <p class="description">Бере дані з файлу: Moon_Days.txt</p>
            </td>
        </tr>
    </table>
</div>

<script>
(function($) {
    'use strict';
    
    function triggerImport(type, buttonId, spinnerId) {
        var $btn = $(buttonId);
        var $spinner = $(spinnerId);
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'da_import_zet_data',
                import_type: type,
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Помилка: ' + response.data.message);
                }
            },
            error: function() {
                alert('Помилка сервера.');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    }

    $('#da-import-dictionaries').on('click', function() {
        triggerImport('dictionaries', '#da-import-dictionaries', '#da-dict-spinner');
    });

    $('#da-import-transits').on('click', function() {
        triggerImport('transits', '#da-import-transits', '#da-transit-spinner');
    });

    $('#da-import-moon').on('click', function() {
        triggerImport('moon_days', '#da-import-moon', '#da-moon-spinner');
    });

    // Обробник для тестової відправки в Telegram
    $('#da-test-tg-btn').on('click', function() {
        var $btn = $(this);
        var $status = $('#da-tg-test-status');
        
        $btn.prop('disabled', true);
        $status.text('Відправка...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'da_test_tg_send',
                nonce: '<?php echo esc_js( $nonce ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $status.css('color', 'green').text('✅ ' + response.data.message);
                } else {
                    $status.css('color', 'red').text('❌ ' + response.data.message);
                }
            },
            error: function() {
                $status.css('color', 'red').text('Помилка сервера.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

})(jQuery);
</script>