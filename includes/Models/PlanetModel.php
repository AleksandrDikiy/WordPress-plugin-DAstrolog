<?php
/**
 * Модель для роботи з таблицею wp_da_planet.
 * Version:     1.1.0
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PlanetModel {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'da_planet';
    }

    /**
     * Отримання списку всіх планет
     */
    public function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY id ASC", ARRAY_A );
    }

    /**
     * Отримання планети за ID
     */
    public function get_by_id( $id ) {
        global $wpdb;
        $id = absint( $id );
        $sql = "SELECT * FROM {$this->table_name} WHERE id = %d";
        return $wpdb->get_row( $wpdb->prepare( $sql, $id ), ARRAY_A );
    }
}