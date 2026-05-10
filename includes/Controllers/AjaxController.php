<?php
/**
 * Контролер обробки AJAX-запитів (Бекенд та Фронтенд).
 * Version:     1.5.0
 * Date_update: 2026-05-10
 */

namespace DAstrolog\Controllers;

use DAstrolog\Core\Activator;
use DAstrolog\Models\UserProfileModel;
use DAstrolog\Services\AstroCalculator;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AjaxController {

    // Додаємо новий інтервал для Cron (кожні 30 хвилин)
    public function add_cron_intervals( $schedules ) {
        if ( ! is_array( $schedules ) ) {
            $schedules = array();
        }
        $schedules['da_half_hourly'] = array(
            'interval' => 1800, // 30 хвилин у секундах
            'display'  => 'Кожні 30 хвилин' // Прибрали функцію перекладу, яка викликала помилку у WP Crontrol
        );
        return $schedules;
    }

    public function send_daily_forecasts() {
        $bot_token = get_option('da_tg_bot_token'); 
        if ( ! $bot_token ) return;

        global $wpdb;
        // Отримуємо поточний час та дату сервера WordPress
        $current_time = current_time('H:i:s');
        $current_date = current_time('Y-m-d');
        
        // ФІНАЛЬНА ВЕРСІЯ: Відправляємо лише тим, у кого настав час і сьогодні ще не було відправки
        $query = $wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}da_user_profiles 
            WHERE telegram_chat_id IS NOT NULL 
              AND telegram_chat_id != ''
              AND telegram_time <= %s
              AND (last_tg_sent IS NULL OR last_tg_sent < %s)
        ", $current_time, $current_date);
        
        $users = $wpdb->get_results( $query );
        if ( empty($users) ) {
            error_log('[DAstrolog TG] Користувачів для відправки не знайдено (перевірте час).');
            return;
        }
        
        $calculator = new \DAstrolog\Services\AstroCalculator();

        foreach ( $users as $user ) {
            $text = $calculator->get_telegram_forecast_text( date('d.m.Y', strtotime($user->birth_date)), $user->birth_time );
            
            $response = wp_remote_post( "https://api.telegram.org/bot{$bot_token}/sendMessage", [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode([
                    'chat_id'    => trim($user->telegram_chat_id),
                    'text'       => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '📖 Читати детальний прогноз',
                                    'url'  => 'https://fbudget.pp.ua/dastrolog/'
                                ]
                            ]
                        ]
                    ]
                ])
            ]);

            // Діагностика у лог-файл (debug.log)
            if ( is_wp_error( $response ) ) {
                error_log('[DAstrolog TG Error] Помилка з\'єднання з Telegram: ' . $response->get_error_message());
            } else {
                $status_code = wp_remote_retrieve_response_code( $response );
                $body = wp_remote_retrieve_body( $response );
                error_log("[DAstrolog TG] Юзер: {$user->user_id} | Статус: {$status_code} | Відповідь Telegram: {$body}");

                // Записуємо успіх ТІЛЬКИ якщо Telegram підтвердив доставку (200 OK)
                if ( $status_code == 200 ) {
                    $wpdb->update(
                        "{$wpdb->prefix}da_user_profiles",
                        ['last_tg_sent' => $current_date],
                        ['user_id' => $user->user_id],
                        ['%s'],
                        ['%d']
                    );
                }
            }
        }
    }
    
    public function __construct() {
        // Реєструємо наш кастомний 30-хвилинний розклад та подію розсилки
        add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
        add_action( 'da_telegram_broadcast', array( $this, 'send_daily_forecasts' ) );
        // Адмінські дії
        add_action( 'wp_ajax_da_import_zet_data', array( $this, 'handle_import_zet_data' ) );
        add_action( 'wp_ajax_da_update_aspect', array( $this, 'handle_update_aspect' ) );
        add_action( 'wp_ajax_da_test_tg_send', array( $this, 'handle_test_tg_send' ) );
        // Фронтенд дії
        add_action( 'wp_ajax_da_save_profile', array( $this, 'handle_save_profile' ) );
        add_action( 'wp_ajax_da_get_forecast', array( $this, 'handle_get_forecast' ) );
    }

    /**
     * Перевірка прав для АДМІНІСТРАТОРА
     */
    private function verify_admin_request( $nonce_action ) {
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Немає прав доступу.' ), 403 );
        }
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
            wp_send_json_error( array( 'message' => 'Помилка безпеки (Nonce).' ), 403 );
        }
    }

    /**
     * Перевірка прав для КОРИСТУВАЧА ФРОНТЕНДУ
     */
    private function verify_frontend_request( $nonce_action ) {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Не авторизовано.' ), 401 );
        }
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
            wp_send_json_error( array( 'message' => 'Помилка безпеки (Nonce).' ), 403 );
        }
    }

    // --- ОБРОБНИКИ ---

    public function handle_test_tg_send() {
        $this->verify_admin_request( 'da_admin_action' );
        
        $bot_token = get_option('da_tg_bot_token');
        if ( ! $bot_token ) wp_send_json_error(['message' => 'Токен не вказано.']);

        $user_id = get_current_user_id();
        $model = new \DAstrolog\Models\UserProfileModel();
        $user = $model->get_profile( $user_id );

        if ( ! $user || empty($user['telegram_chat_id']) ) {
            wp_send_json_error(['message' => 'Вкажіть ваш Telegram ID у профілі на сайті!']);
        }

        $calculator = new AstroCalculator();
        $text = $calculator->get_telegram_forecast_text( date('d.m.Y', strtotime($user['birth_date'])), $user['birth_time'] );
        
        $response = wp_remote_post( "https://api.telegram.org/bot{$bot_token}/sendMessage", [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'chat_id'    => trim($user['telegram_chat_id']),
                'text'       => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => [
                    'inline_keyboard' => [[['text' => '📖 Читати детальний прогноз', 'url' => home_url('/dastrolog/')]]]
                ]
            ])
        ]);

        if ( is_wp_error( $response ) ) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status == 200 ) {
            wp_send_json_success(['message' => 'Надіслано! Перевірте Telegram.']);
        } else {
            $body = json_decode(wp_remote_retrieve_body( $response ), true);
            wp_send_json_error(['message' => 'Помилка Telegram: ' . ($body['description'] ?? 'невідомо')]);
        }
    }

    public function handle_import_zet_data() {
        $this->verify_admin_request( 'da_admin_action' );
        global $wpdb;

        $manifest_path = DA_PLUGIN_DIR . 'assets/data/manifest.json';
        if ( !file_exists($manifest_path) ) wp_send_json_error(['message' => 'manifest.json не знайдено.']);
        $config = json_decode(file_get_contents($manifest_path), true);

        $type = sanitize_text_field( wp_unslash( $_POST['import_type'] ?? '' ) );
        $log = [];

        if ( $type === 'dictionaries' ) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}da_house_system");
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}da_aspect");
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}da_orb");

            $log = array_merge($log, Activator::seed_house_systems($config['dictionaries']['house_systems']));
            $log = array_merge($log, Activator::seed_aspects_and_orbs($config['dictionaries']['natal'], 'natal'));
            $log = array_merge($log, Activator::seed_aspects_and_orbs($config['dictionaries']['transit'], 'transit'));
        } elseif ( $type === 'transits' ) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}da_interpretations");
            foreach ($config['interpretations'] as $item) {
                $log = array_merge($log, Activator::parse_zet_interpretations($item['file'], $item['category']));
            }
        } elseif ( $type === 'moon_days' ) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}da_moon_days");
            $keywords = isset($config['moon_day_keywords']) ? $config['moon_day_keywords'] : [];
            $log = Activator::parse_zet_moon_days($config['moon_days'], $keywords);
        }

        wp_send_json_success(['message' => implode("\n", $log)]);
    }

    public function handle_save_profile() {
        $this->verify_frontend_request( 'da_forecast_nonce' );

        $user_id = get_current_user_id();
        $model = new UserProfileModel();

        if ( ! empty( $_POST['da_website'] ) ) {
            wp_send_json_error( array( 'message' => 'Bot detected.' ), 400 );
        }

        $result = $model->save_profile( $user_id, $_POST );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        if ( $result !== false ) {
            // Очищаємо кеш прогнозів цього юзера
            global $wpdb;
            $wpdb->query( $wpdb->prepare( 
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 
                '_transient_da_transits_' . md5($user_id) . '%' 
            ) );
            wp_send_json_success( array( 'message' => 'Профіль успішно оновлено!' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Помилка при збереженні даних у БД.' ) );
        }
    }
    // Фронтенд обробник для отримання прогнозу (транзити + місячний день)
    public function handle_get_forecast() {
        $this->verify_frontend_request( 'da_forecast_nonce' );

        $user_id = get_current_user_id();
        $model = new UserProfileModel();
        $profile = $model->get_profile( $user_id );
        
        if ( ! $profile ) {
            wp_send_json_error( array( 'message' => 'Профіль не знайдено.' ) );
        }

        $transit_date = sanitize_text_field( wp_unslash( $_POST['date'] ?? date('Y-m-d') ) );
        $transit_date_formatted = date('d.m.Y', strtotime($transit_date));
        $n_date = date( 'd.m.Y', strtotime( $profile['birth_date'] ) );
        $n_time = $profile['birth_time'];
        
        $calculator = new AstroCalculator();
        $moon_day_num = $calculator->get_moon_day( $transit_date_formatted, '12:00:00' );
        $moon_info    = $calculator->get_moon_day_description( $moon_day_num );
        $phase_info   = $calculator->get_moon_phase_info( $transit_date_formatted, '12:00:00' );
        $transits     = $calculator->calculate_daily_transits( $n_date, $n_time, $transit_date_formatted );

        ob_start();
        require DA_PLUGIN_DIR . 'views/frontend/tables-partial.php';
        $html = ob_get_clean();

        $moon_data = json_decode( $moon_info['description'], true );
        $moon_html = '';

        if ( is_array( $moon_data ) ) {
            // Зчитуємо налаштування з manifest.json
            $manifest_path = DA_PLUGIN_DIR . 'assets/data/manifest.json';
            $manifest_data = file_exists( $manifest_path ) ? json_decode( file_get_contents( $manifest_path ), true ) : [];
            $main_sections = isset( $manifest_data['moon_day_main_sections'] ) ? $manifest_data['moon_day_main_sections'] : ['general', 'Символ', 'Дія'];

            $moon_html .= '<div class="da-moon-main">';
            
            // Динамічний вивід головних секцій
            foreach ( $main_sections as $section ) {
                if ( ! empty( $moon_data[ $section ] ) ) {
                    if ( $section === 'general' ) {
                        $moon_html .= '<p>' . esc_html( $moon_data[ $section ] ) . '</p>';
                    } else {
                        $moon_html .= '<p><strong>' . esc_html( $section ) . ':</strong> ' . esc_html( $moon_data[ $section ] ) . '</p>';
                    }
                }
            }
            $moon_html .= '</div>';

            // Акордеон з іншими характеристиками
            $moon_html .= '<details class="da-accordion-item da-moon-details">';
            $moon_html .= '<summary class="da-accordion-header">Всі характеристики Місячного дня</summary>';
            $moon_html .= '<div class="da-accordion-body">';
            foreach ( $moon_data as $key => $val ) {
                // Пропускаємо те, що вже вивели вище, та порожні значення
                if ( in_array( $key, $main_sections ) || empty( $val ) ) continue;
                $moon_html .= '<p><strong>' . esc_html( $key ) . ':</strong> ' . esc_html( $val ) . '</p>';
            }
            $moon_html .= '</div></details>';
        } else {
            $moon_html = nl2br( esc_html( $moon_info['description'] ) );
        }

        // Формуємо заголовок Місячного дня разом із фазою в один рядок для Sticky Bar
        $moon_title_str = esc_html( $moon_info['title'] );
        if ( isset($phase_info) && $phase_info ) {
            $moon_title_str .= '<span class="da-phase-inline">';
            $moon_title_str .= 'Фаза: <b>' . esc_html( $phase_info['phase_name'] ) . '</b>';
            if ( $phase_info['phase_name'] !== $phase_info['trend'] ) {
                $moon_title_str .= ' (' . esc_html( $phase_info['trend'] ) . ')';
            }
            $moon_title_str .= ' | ⏳ Зміна: <b>' . esc_html( $phase_info['next_phase_date'] ) . '</b> (' . esc_html( $phase_info['next_phase_name'] ) . ')';
            $moon_title_str .= '</span>';
        }

        wp_send_json_success( array(
            'html'      => $html,
            'moon_day'  => $moon_title_str,
            'moon_desc' => $moon_html
        ) );
    }
    /**
     * AJAX обробник для інлайн-редагування аспектів (колір та статус "Обраний").
     */
    public function handle_update_aspect() {
        $this->verify_admin_request( 'da_admin_action' );
        global $wpdb;

        $id    = absint( $_POST['aspect_id'] ?? 0 );
        $field = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
        $value = sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) );

        if ( ! $id || ! in_array( $field, ['color_hex', 'is_major'] ) ) {
            wp_send_json_error( array( 'message' => 'Невірні дані.' ) );
        }

        // Санітизація залежно від типу поля
        if ( $field === 'color_hex' ) {
            // Перевіряємо, чи це дійсно HEX колір (напр. #ff0000)
            if ( ! preg_match( '/^#[a-f0-9]{6}$/i', $value ) ) {
                wp_send_json_error( array( 'message' => 'Невірний формат кольору.' ) );
            }
        } elseif ( $field === 'is_major' ) {
            $value = absint( $value ) === 1 ? 1 : 0;
        }

        // Оновлюємо базу даних
        $updated = $wpdb->update(
            $wpdb->prefix . 'da_aspect',
            array( $field => $value ), // Дані
            array( 'id' => $id ),      // WHERE
            array( $field === 'is_major' ? '%d' : '%s' ), // Формат даних
            array( '%d' )              // Формат WHERE
        );

        if ( $updated !== false ) {
            wp_send_json_success( array( 'message' => 'Збережено' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Помилка БД.' ) );
        }
    }
    // Додайте інші обробники AJAX, якщо потрібно
}