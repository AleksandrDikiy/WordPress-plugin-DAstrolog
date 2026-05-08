<?php
/**
 * Модель для роботи з таблицею wp_da_orb.
 * Version:     1.1.0
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OrbModel {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'da_orb';
    }

    /**
     * Отримання орбісів для транзитів (згрупованих за планетою та аспектом)
     */
    public function get_transit_orbs() {
        global $wpdb;
        $results = $wpdb->get_results( 
            "SELECT planet_id, aspect_id, orb_value FROM {$this->table_name} WHERE calc_type = 'transit'", 
            ARRAY_A 
        );

        $mapped = array();
        if ( $results ) {
            foreach ( $results as $row ) {
                $mapped[ $row['planet_id'] ][ $row['aspect_id'] ] = (float) $row['orb_value'];
            }
        }
        return $mapped;
    }
}