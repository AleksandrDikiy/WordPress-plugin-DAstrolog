<?php
/**
 * Модель для роботи з таблицею wp_da_aspect.
 * Version:     1.1.0
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AspectModel {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'da_aspect';
    }

    /**
     * Отримання тільки мажорних аспектів
     */
    public function get_major_aspects() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE is_major = 1 ORDER BY angle ASC", ARRAY_A );
    }

    /**
     * Отримання аспекту за ID
     */
    public function get_by_id( $id ) {
        global $wpdb;
        $id = absint( $id );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ), ARRAY_A );
    }
}