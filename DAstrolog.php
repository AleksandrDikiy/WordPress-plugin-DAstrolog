<?php
/**
 * Plugin Name: DAstrolog - Personal Astrology Forecast
 * Plugin URI:  https://fbudget.pp.ua/DAstrolog/
 * Description: Еталонний Enterprise-плагін для щоденного астрологічного прогнозу на основі транзитів до натальної карти. Використовує швейцарські ефемеріди (swetest).
 * Version:     1.3.2
 * Date_update: 2026-05-10
 * Author:      Senior PHP Developer
 * Text Domain: dastrolog
 *
 * @package DAstrolog
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Захист від прямого доступу
}

/**
 * 1. ВИЗНАЧЕННЯ КОНСТАНТ
 */
define( 'DA_VERSION', '1.3.2' );
define( 'DA_DB_VERSION', '1.2.0' );
define( 'DA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * 2. ПІДКЛЮЧЕННЯ ЯДРА ТА СЕРВІСІВ
 */
// Core
require_once DA_PLUGIN_DIR . 'includes/Core/Loader.php';
require_once DA_PLUGIN_DIR . 'includes/Core/Activator.php';
require_once DA_PLUGIN_DIR . 'includes/Core/Deactivator.php';

// Models
require_once DA_PLUGIN_DIR . 'includes/Models/PlanetModel.php';
require_once DA_PLUGIN_DIR . 'includes/Models/AspectModel.php';
require_once DA_PLUGIN_DIR . 'includes/Models/OrbModel.php';
require_once DA_PLUGIN_DIR . 'includes/Models/InterpretationModel.php';
require_once DA_PLUGIN_DIR . 'includes/Models/UserProfileModel.php';

// Services
require_once DA_PLUGIN_DIR . 'includes/Services/EphemerisManager.php';
require_once DA_PLUGIN_DIR . 'includes/Services/AstroCalculator.php';

// Controllers
require_once DA_PLUGIN_DIR . 'includes/Controllers/AdminController.php';
require_once DA_PLUGIN_DIR . 'includes/Controllers/AjaxController.php';
require_once DA_PLUGIN_DIR . 'includes/Controllers/FrontendController.php';

/**
 * 3. РЕЄСТРАЦІЯ ХУКІВ ЖИТТЄВОГО ЦИКЛУ
 * Викликаються БЕЗПОСЕРЕДНЬО у головному файлі згідно з AGENTS.md.
 */
register_activation_hook( __FILE__, array( 'DAstrolog\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DAstrolog\\Core\\Deactivator', 'deactivate' ) );

/**
 * 4. ЗАПУСК ПЛАГІНА
 * Ініціалізація компонентів та реєстрація їхніх хуків через Loader.
 */
function run_dastrolog() {
    // Відкладаємо запуск всього плагіна до повного завантаження ядра WordPress
    add_action( 'plugins_loaded', function() {
        // МІГРАЦІЯ БАЗИ ДАНИХ
        if ( get_option( 'da_db_version' ) !== DA_DB_VERSION ) {
            require_once DA_PLUGIN_DIR . 'includes/Core/Activator.php';
            \DAstrolog\Core\Activator::activate(); 
        }

        $loader = new \DAstrolog\Core\Loader();

        // Ініціалізація контролерів
        $admin_controller    = new \DAstrolog\Controllers\AdminController();
        $ajax_controller     = new \DAstrolog\Controllers\AjaxController();
        $frontend_controller = new \DAstrolog\Controllers\FrontendController();

        $loader->run();
    });
}

run_dastrolog();