<?php
/**
 * Модель для роботи з таблицею профілів користувачів (wp_da_user_profiles).
 *
 * Version:     1.1.0
 * Date_update: 2026-05-07
 * * Зміни v1.1.0:
 * - Реалізовано UPSERT (INSERT ... ON DUPLICATE KEY UPDATE).
 * - Додано сувору валідацію дат згідно з AGENTS.md.
 */

namespace DAstrolog\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UserProfileModel {

    /**
     * Назва таблиці з префіксом.
     */
    private $table_name;

    /**
     * Ініціалізація моделі.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'da_user_profiles';
    }

    /**
     * Отримання профілю користувача за його ID.
     * * @param int $user_id ID користувача WordPress.
     * @return array|null Масив даних або null, якщо не знайдено.
     */
    public function get_profile( $user_id ) {
        global $wpdb;
        $user_id = absint( $user_id );
        
        if ( ! $user_id ) return null;

        $sql = $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE user_id = %d", $user_id );
        return $wpdb->get_row( $sql, ARRAY_A );
    }

    /**
     * Створення або оновлення профілю (UPSERT).
     * * @param int   $user_id ID користувача.
     * @param array $data    Вхідні дані ($_POST).
     * @return int|bool      Кількість змінених рядків або false при помилці.
     */
    public function save_profile( $user_id, $data ) {
        global $wpdb;
        $user_id = absint( $user_id );
        if ( ! $user_id ) return false;

        // 1. Санітизація вхідних даних (Суворе правило AGENTS.md)
        $birth_date      = sanitize_text_field( wp_unslash( $data['birth_date'] ?? '' ) );
        $birth_time      = sanitize_text_field( wp_unslash( $data['birth_time'] ?? '12:00:00' ) );
        $lat             = (float) ( $data['lat'] ?? 0 );
        $lng             = (float) ( $data['lng'] ?? 0 );
        $house_system_id = absint( $data['house_system_id'] ?? 1 );
        
        // Обробка JSON налаштувань
        $settings = isset( $data['settings_json'] ) ? json_decode( wp_unslash( $data['settings_json'] ), true ) : array();
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        $settings_json = wp_json_encode( $settings, JSON_UNESCAPED_UNICODE );

        // 2. Валідація (Суворе правило AGENTS.md: Санітизація ДО валідації)
        if ( ! $this->is_valid_date( $birth_date ) ) {
            error_log( "[DAstrolog] Спроба збереження невірної дати: " . $birth_date );
            return new \WP_Error( 'invalid_date', 'Невірний формат або значення дати народження.' );
        }

        // 3. Використання Prepared Statement для безпеки
        $sql = "INSERT INTO {$this->table_name} 
                (user_id, birth_date, birth_time, lat, lng, house_system_id, settings_json) 
                VALUES (%d, %s, %s, %f, %f, %d, %s) 
                ON DUPLICATE KEY UPDATE 
                birth_date = VALUES(birth_date), 
                birth_time = VALUES(birth_time), 
                lat = VALUES(lat), 
                lng = VALUES(lng), 
                house_system_id = VALUES(house_system_id), 
                settings_json = VALUES(settings_json)";

        return $wpdb->query( $wpdb->prepare( 
            $sql, 
            $user_id, 
            $birth_date, 
            $birth_time, 
            $lat, 
            $lng, 
            $house_system_id, 
            $settings_json 
        ) );
    }

    /**
     * Перевірка реального існування дати (напр. 2026-02-30 — false).
     * Використовує вбудовану функцію PHP checkdate.
     */
    private function is_valid_date( $date ) {
        if ( empty( $date ) ) return false;
        
        // Перевірка формату YYYY-MM-DD
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) return false;
        
        $parts = explode( '-', $date );
        // checkdate(month, day, year)
        return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
    }

    /**
     * Видалення профілю.
     */
    public function delete_profile( $user_id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_name, array( 'user_id' => absint( $user_id ) ), array( '%d' ) );
    }
}