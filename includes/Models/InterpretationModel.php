<?php
/**
 * Модель для роботи з таблицею wp_da_interpretations.
 * Version:     1.1.0
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class InterpretationModel {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'da_interpretations';
    }

    /**
     * Отримання текстів для конкретного аспекту
     */
    public function get_interpretations( $transit_id, $natal_id, $angle ) {
        global $wpdb;
        
        $transit_id = absint( $transit_id );
        $natal_id   = absint( $natal_id );
        $angle      = (float) $angle;

        $sql = "SELECT category, description FROM {$this->table_name} 
                WHERE transit_planet_id = %d AND natal_planet_id = %d AND aspect_angle = %f";
                
        return $wpdb->get_results( $wpdb->prepare( $sql, $transit_id, $natal_id, $angle ), ARRAY_A );
    }

    /**
     * Безпечне оновлення запису з використанням Whitelist (згідно зі стандартами AGENTS.md)
     */
    public function update_interpretation( $id, $data ) {
        global $wpdb;
        
        $id = absint( $id );
        if ( ! $id ) return false;

        // Жорстко захардкоджені дозволені колонки
        $column_map = array(
            'category'    => 'category',
            'description' => 'description'
        );

        $update_parts  = array();
        $update_values = array();

        foreach ( $column_map as $input_key => $column_name ) {
            if ( isset( $data[ $input_key ] ) ) {
                $update_parts[] = "{$column_name} = %s";
                
                // Санітизація залежно від поля
                if ( $input_key === 'description' ) {
                    $update_values[] = sanitize_textarea_field( wp_unslash( $data[ $input_key ] ) );
                } else {
                    $update_values[] = sanitize_text_field( wp_unslash( $data[ $input_key ] ) );
                }
            }
        }

        if ( empty( $update_parts ) ) return false;

        $update_values[] = $id; // Додаємо ID для WHERE
        $set_clause = implode( ', ', $update_parts );

        $sql = "UPDATE {$this->table_name} SET {$set_clause} WHERE id = %d";
        return $wpdb->query( $wpdb->prepare( $sql, ...$update_values ) );
    }
}