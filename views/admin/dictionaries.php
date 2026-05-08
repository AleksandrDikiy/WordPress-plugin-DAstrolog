<?php
/**
 * Шаблон сторінки довідників (Аспекти, Доми, Планети).
 * Очікує змінні від AdminController: $active_tab, $columns, $items
 *
 * Version:     1.1.0
 * Date_update: 2026-05-07
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Довідники DAstrolog', 'dastrolog' ); ?></h1>
    <hr class="wp-header-end">

    <h2 class="nav-tab-wrapper">
        <a href="?page=da-dictionaries&tab=aspects" class="nav-tab <?php echo $active_tab === 'aspects' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Аспекти', 'dastrolog' ); ?>
        </a>
        <a href="?page=da-dictionaries&tab=houses" class="nav-tab <?php echo $active_tab === 'houses' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Системи Домів', 'dastrolog' ); ?>
        </a>
        <a href="?page=da-dictionaries&tab=planets" class="nav-tab <?php echo $active_tab === 'planets' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Планети', 'dastrolog' ); ?>
        </a>
    </h2>

    <div class="postbox" style="margin-top: 20px; padding: 20px;">
        <?php if ( ! empty( $items ) && is_array( $items ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php foreach ( $columns as $col_id => $col_name ) : ?>
                            <th scope="col" class="manage-column column-<?php echo esc_attr( $col_id ); ?>">
                                <?php echo esc_html( $col_name ); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $item ) : ?>
                        <tr>
                            <?php foreach ( $columns as $col_id => $col_name ) : ?>
                                <td>
                                    <?php 
                                    // ІНТЕРАКТИВНИЙ ВИБІР КОЛЬОРУ
                                    if ( $col_id === 'color_hex' && $active_tab === 'aspects' ) : ?>
                                        <input type="color" class="da-aspect-edit" data-id="<?php echo esc_attr( $item['id'] ); ?>" data-field="color_hex" value="<?php echo esc_attr( $item[$col_id] ); ?>" style="cursor: pointer; padding: 0; height: 28px; width: 40px; border: 1px solid #ccc; border-radius: 4px;">
                                        <span style="vertical-align: super; margin-left: 5px; font-family: monospace;"><?php echo esc_html( $item[$col_id] ); ?></span>
                                    
                                    <?php 
                                    // ІНТЕРАКТИВНИЙ ЧЕКБОКС (ОБРАНИЙ)
                                    elseif ( $col_id === 'is_major' && $active_tab === 'aspects' ) : ?>
                                        <input type="checkbox" class="da-aspect-edit" data-id="<?php echo esc_attr( $item['id'] ); ?>" data-field="is_major" <?php checked( $item[$col_id], 1 ); ?> style="width: 18px; height: 18px; cursor: pointer;">
                                        
                                    <?php 
                                    // Звичайний рендер для інших булевих значень (напр. is_fast_moving)
                                    elseif ( $col_id === 'is_fast_moving' ) : ?>
                                        <?php echo ! empty( $item[$col_id] ) ? '✅' : '❌'; ?>
                                    
                                    <?php 
                                    // Стандартний текстовий рендер з Late Escaping
                                    else : ?>
                                        <?php echo esc_html( $item[$col_id] ); ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'Немає даних для відображення. Будь ласка, запустіть імпорт у налаштуваннях плагіна.', 'dastrolog' ); ?></p>
        <?php endif; ?>
    </div>
</div>