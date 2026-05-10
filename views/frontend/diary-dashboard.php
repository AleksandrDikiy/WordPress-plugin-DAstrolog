<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="da-forecast-app" class="da-container">
    
    <div class="da-sticky-wrapper">
        
        <div class="da-forecast-bar">
            <div id="da-moon-title" class="da-moon-title-inline"></div>
            
            <div class="da-forecast-controls">
                <label for="da-forecast-date" class="da-forecast-label">📅 <?php esc_html_e('Прогноз на дату:', 'dastrolog'); ?></label>
                <input type="date" id="da-forecast-date" value="<?php echo date('Y-m-d'); ?>" class="da-forecast-input">
                <button type="button" id="da-update-btn" class="da-btn da-update-btn"><?php esc_html_e('Оновити', 'dastrolog'); ?></button>
                
                <button type="button" id="da-settings-toggle" class="da-btn da-btn-icon" title="<?php esc_attr_e('Налаштування даних народження', 'dastrolog'); ?>">⚙️</button>
            </div>
        </div>

        <div id="da-settings-panel" class="da-settings-panel" style="display: <?php echo empty($data['birth_date']) ? 'block' : 'none'; ?>;">
            <form id="da-profile-form" class="da-profile-form">
                <input type="text" name="da_website" class="da-hidden-honeypot" tabindex="-1" autocomplete="off">
                
                <div class="da-field da-date-field">
                    <label><?php esc_html_e( 'Дата народження', 'dastrolog' ); ?> <span class="da-req">*</span></label>
                    <input type="date" name="birth_date" value="<?php echo esc_attr( $data['birth_date'] ?? '' ); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="da-field da-time-field">
                    <label><?php esc_html_e( 'Час', 'dastrolog' ); ?> <span class="da-req">*</span></label>
                    <input type="time" step="1" name="birth_time" value="<?php echo esc_attr( $data['birth_time'] ?? '12:00:00' ); ?>" required>
                </div>
                
                <div class="da-field da-coord-field">
                    <label><?php esc_html_e( 'Широта', 'dastrolog' ); ?> <span class="da-req">*</span></label>
                    <input type="number" step="0.000001" min="-90" max="90" name="lat" value="<?php echo esc_attr( $data['lat'] ?? '' ); ?>" placeholder="50.45" required>
                </div>
                
                <div class="da-field da-coord-field">
                    <label><?php esc_html_e( 'Довгота', 'dastrolog' ); ?> <span class="da-req">*</span></label>
                    <input type="number" step="0.000001" min="-180" max="180" name="lng" value="<?php echo esc_attr( $data['lng'] ?? '' ); ?>" placeholder="30.52" required>
                </div>
                
                <div class="da-field da-house-field">
                    <label><?php esc_html_e( 'Система домів', 'dastrolog' ); ?> <span class="da-req">*</span></label>
                    <select name="house_system" required>
                        <?php if(!empty($house_systems)): foreach ( $house_systems as $hs ) : ?>
                            <option value="<?php echo esc_attr( $hs['id'] ); ?>" <?php selected( ($data['house_system_id'] ?? 1), $hs['id'] ); ?>>
                                <?php echo esc_html( $hs['name'] ); ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="da-field da-tg-field">
                    <label><?php esc_html_e( 'Telegram ID', 'dastrolog' ); ?></label>
                    <input type="text" name="telegram_chat_id" value="<?php echo esc_attr( $data['telegram_chat_id'] ?? '' ); ?>" placeholder="123456789">
                </div>
                
                <div class="da-field da-time-field">
                    <label><?php esc_html_e( 'Час розсилки', 'dastrolog' ); ?></label>
                    <input type="time" name="telegram_time" value="<?php echo esc_attr( $data['telegram_time'] ?? '07:30' ); ?>">
                </div>

                <div class="da-field da-btn-field">
                    <button type="submit" class="da-btn da-save-btn">
                        <?php esc_html_e( 'Зберегти', 'dastrolog' ); ?>
                    </button>
                </div>
                
                <div class="da-msg-wrapper">
                    <span id="da-profile-msg"></span>
                </div>
            </form>
        </div>
    </div> <div id="da-moon-container" class="da-moon-container">
        <div id="da-moon-desc"></div>
    </div>

    <div id="da-forecast-results">
        <div class="da-loading">
            <?php esc_html_e( 'Завантаження прогнозу...', 'dastrolog' ); ?>
        </div>
    </div>
</div>