<?php
/**
 * Клас, що виконується під час деактивації плагіна.
 * * Цей клас відповідає за очищення тимчасових даних, видалення ролей 
 * та оновлення правил перенаправлення.
 *
 * Version:     1.0.0
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Deactivator {

    /**
     * Основний метод, що викликається при деактивації.
     * * Згідно зі стандартами AGENTS.md:
     * 1. Видаляємо кастомну роль DAstrolog.
     * 2. Скидаємо правила Rewrite Rules.
     */
    public static function deactivate() {
        // Видаляємо кастомну роль (користувачі автоматично отримають статус "Без ролі") 
        remove_role( 'DAstrolog' );

        // Очищаємо правила перенаправлення (потрібно, якщо в майбутньому додамо CPT) 
        flush_rewrite_rules();
        
        // Очищаємо астрологічний кеш (transients), щоб при повторній активації дані були актуальними
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_da_transits_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_da_transits_%'" );
    }
}