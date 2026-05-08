<?php
/**
 * Сервіс для керування файлами швейцарських ефемерід (.se1) 
 * та перевірки системних вимог (swetest).
 *
 * Version:     1.0.0
 * Date_update: 2026-05-07
 */

namespace DAstrolog\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EphemerisManager {

    /**
     * Шлях до директорії з ефемерідами.
     * @var string
     */
    private $ephe_dir;

    /**
     * Шлях до бінарного файлу swetest.
     * @var string
     */
    private $swetest_path;

    public function __construct() {
        $this->ephe_dir = DA_PLUGIN_DIR . 'ephe/';
        
        // Розумний пошук swetest: спочатку в папці плагіна /bin/, потім системний
        $local_bin = DA_PLUGIN_DIR . 'bin/swetest';
        if ( file_exists( $local_bin ) ) {
            $this->swetest_path = $local_bin;
        } else {
            $this->swetest_path = '/usr/local/bin/swetest'; 
        }
    }

    /**
     * Отримання абсолютного шляху до папки з ефемерідами.
     *
     * @return string
     */
    public function get_ephe_dir() {
        return $this->ephe_dir;
    }

    /**
     * Отримання шляху до swetest.
     *
     * @return string
     */
    public function get_swetest_path() {
        return $this->swetest_path;
    }

    /**
     * Перевірка наявності необхідних файлів ефемерід (.se1) у папці.
     *
     * @return bool|\WP_Error Повертає true, якщо файли є, або об'єкт помилки.
     */
    public function verify_ephemeris_files() {
        if ( ! is_dir( $this->ephe_dir ) ) {
            return new \WP_Error( 'dir_missing', 'Директорія /ephe/ не знайдена. Створіть її у корені плагіна.' );
        }

        // Перевіряємо, чи є хоча б один файл .se1 (наприклад, seas_18.se1)
        $files = glob( $this->ephe_dir . '*.se1' );
        if ( empty( $files ) ) {
            return new \WP_Error( 'files_missing', 'Файли швейцарських ефемерід (.se1) відсутні у папці /ephe/.' );
        }

        return true;
    }

    /**
     * Перевірка, чи встановлено swetest та чи має він права на виконання.
     *
     * @return bool|\WP_Error
     */
    public function verify_swetest_binary() {
        if ( ! file_exists( $this->swetest_path ) ) {
            return new \WP_Error( 'bin_missing', "Бінарний файл swetest не знайдено за шляхом: {$this->swetest_path}" );
        }

        if ( ! is_executable( $this->swetest_path ) ) {
            return new \WP_Error( 'bin_not_executable', "Файл swetest не має прав на виконання. Виконайте 'chmod +x {$this->swetest_path}' на сервері." );
        }

        return true;
    }
    
    /**
     * Комплексна перевірка готовності математичного ядра (для відображення в адмінці).
     * * @return array Асоціативний масив зі статусами перевірки.
     */
    public function get_system_status() {
        $ephe_check = $this->verify_ephemeris_files();
        $bin_check  = $this->verify_swetest_binary();
        
        return array(
            'ephemeris_ok' => ! is_wp_error( $ephe_check ),
            'ephemeris_msg'=> is_wp_error( $ephe_check ) ? $ephe_check->get_error_message() : 'Файли .se1 знайдено.',
            'swetest_ok'   => ! is_wp_error( $bin_check ),
            'swetest_msg'  => is_wp_error( $bin_check ) ? $bin_check->get_error_message() : 'swetest готовий до роботи.',
        );
    }
}