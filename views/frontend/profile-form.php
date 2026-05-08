<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="da-container">
    <h2><?php esc_html_e( 'Налаштування Натальної Карти', 'dastrolog' ); ?></h2>
    <p><?php esc_html_e( 'Для отримання індивідуального прогнозу, будь ласка, введіть свої дані народження.', 'dastrolog' ); ?></p>
    
    <form id="da-profile-form" class="da-form">
        <div class="da-form-group">
            <label><?php esc_html_e( 'Дата народження', 'dastrolog' ); ?></label>
            <input type="date" name="birth_date" required>
        </div>
        <div class="da-form-group">
            <label><?php esc_html_e( 'Час народження (ГГ:ХХ)', 'dastrolog' ); ?></label>
            <input type="time" name="birth_time" step="1" required>
        </div>
        <div class="da-form-group">
            <label><?php esc_html_e( 'Широта (напр. 48.4500)', 'dastrolog' ); ?></label>
            <input type="number" step="0.000001" name="lat" required>
        </div>
        <div class="da-form-group">
            <label><?php esc_html_e( 'Довгота (напр. 34.9800)', 'dastrolog' ); ?></label>
            <input type="number" step="0.000001" name="lng" required>
        </div>
        <div class="da-form-group">
            <label><?php esc_html_e( 'Система Домів', 'dastrolog' ); ?></label>
            <select name="house_system" required>
                <?php foreach ( $house_systems as $hs ) : ?>
                    <option value="<?php echo esc_attr( $hs['id'] ); ?>"><?php echo esc_html( $hs['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="da-btn da-btn-primary"><?php esc_html_e( 'Зберегти', 'dastrolog' ); ?></button>
        <div id="da-profile-msg"></div>
    </form>
</div>