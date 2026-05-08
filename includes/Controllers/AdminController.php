<?php
/**
 * Контролер Адмін-панелі.
 * Version:     1.1.1
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AdminController {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function register_admin_menu() {
        add_menu_page(
            'DAstrolog: Імпорт Бази Даних ZET',
            'DAstrolog',
            'manage_options',
            'dastrolog',
            array( $this, 'render_settings_page' ),
            'dashicons-star-filled'
        );

        // ДОДАЄМО ПІДМЕНЮ "Довідники"
        add_submenu_page(
            'dastrolog',                    // Slug батьківського меню (має співпадати з 4-м параметром add_menu_page)
            __( 'Довідники', 'dastrolog' ), // Title сторінки
            __( 'Довідники', 'dastrolog' ), // Назва в меню зліва
            'manage_options',               // Права доступу
            'da-dictionaries',              // Slug нашої нової сторінки
            array( $this, 'render_dictionaries_page' ) // Метод-обробник, який ми створили раніше
        );
    }

    public function enqueue_admin_assets( $hook ) {
        // Підключаємо активи лише на сторінках нашого плагіна
        if ( strpos( $hook, 'dastrolog' ) === false ) {
            return;
        }

        wp_enqueue_style( 'da-admin-css', DA_PLUGIN_URL . 'assets/css/admin.css', array(), DA_VERSION );
        wp_enqueue_script( 'da-admin-js', DA_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), DA_VERSION, true );

        // Передаємо Nonce для AJAX в admin.js
        wp_localize_script( 'da-admin-js', 'da_admin_vars', array(
            'nonce' => wp_create_nonce( 'da_admin_action' )
        ) );
    }

    public function render_settings_page() {
        // Отримуємо статус ефемерід через менеджер
        $ephe_manager = new \DAstrolog\Services\EphemerisManager();
        $system_status = $ephe_manager->get_system_status();

        // Передаємо необхідні змінні у View
        $nonce = wp_create_nonce( 'da_admin_action' );
        require_once DA_PLUGIN_DIR . 'views/admin/settings-page.php';
    }
    /**
     * Рендер сторінки довідників
     */
    public function render_dictionaries_page() {
        global $wpdb;
        
        // Визначаємо активну вкладку
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'aspects';
        
        $items = [];
        $columns = [];

        // Роутинг даних залежно від вкладки
        if ( $active_tab === 'aspects' ) {
            $columns = [
                'id' => 'ID', 'name' => 'Назва', 'angle' => 'Кут (°)', 
                'type' => 'Тип', 'color_hex' => 'Колір', 'is_major' => 'Обраний'
            ];
            $items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}da_aspect ORDER BY angle ASC", ARRAY_A );
            
        } elseif ( $active_tab === 'houses' ) {
            $columns = [ 'id' => 'ID', 'code' => 'Код ZET', 'name' => 'Назва Системи' ];
            $items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}da_house_system", ARRAY_A );
            
        } elseif ( $active_tab === 'planets' ) {
            $columns = [ 'id' => 'ID', 'name' => 'Назва', 'symbol' => 'Символ', 'is_fast_moving' => 'Швидка' ];
            $items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}da_planet ORDER BY id ASC", ARRAY_A );
        }

        // Передаємо підготовлені змінні у View
        require DA_PLUGIN_DIR . 'views/admin/dictionaries.php';
    }
}