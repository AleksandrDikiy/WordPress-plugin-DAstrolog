<?php
/**
 * Контролер для відображення фронтенд-частини (шорткод).
 *
 * Version:     1.2.0
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Controllers;

use DAstrolog\Models\UserProfileModel;

if ( ! defined( 'ABSPATH' ) ) exit;

class FrontendController {

    private $profile_model;
    // Додайте інші необхідні моделі або сервіси, якщо потрібно
    public function __construct() {
        $this->profile_model = new UserProfileModel();
        
        add_shortcode( 'DAstrolog', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }
    // Метод для підключення CSS та JS файлів на фронтенді
    public function enqueue_assets() {
        wp_enqueue_style( 'da-frontend-css', DA_PLUGIN_URL . 'assets/css/frontend.css', array(), DA_VERSION );
        wp_enqueue_script( 'da-frontend-js', DA_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), DA_VERSION, true );
        
        wp_localize_script( 'da-frontend-js', 'da_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'da_forecast_nonce' )
        ) );
    }
    // Метод для рендерингу шорткоду
    public function render_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Будь ласка, авторизуйтесь для перегляду щоденника.', 'dastrolog' ) . '</p>';
        }

        $user_id = get_current_user_id();
        $profile = $this->profile_model->get_profile( $user_id );

        // Якщо профіль є - беремо його, якщо ні - ставимо пусті значення
        $data = $profile ? $profile : array(
            'birth_date'      => '',
            'birth_time'      => '12:00:00',
            'lat'             => '',
            'lng'             => '',
            'house_system_id' => 1
        );

        global $wpdb;
        $house_systems = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}da_house_system", ARRAY_A );

        ob_start();
        require DA_PLUGIN_DIR . 'views/frontend/diary-dashboard.php';
        return ob_get_clean();
    }
}