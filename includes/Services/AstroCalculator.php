<?php
/**
 * Ядро астрологічних обчислень через бінарник swetest.
 * Version:     1.2.0
 * Date_update: 2026-05-10
 */

namespace DAstrolog\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use DAstrolog\Models\PlanetModel;
use DAstrolog\Models\AspectModel;
use DAstrolog\Models\OrbModel;
use DAstrolog\Models\InterpretationModel;
use DAstrolog\Services\EphemerisManager;

class AstroCalculator {

    private $ephe_path;
    private $planet_model;
    private $aspect_model;
    private $orb_model;
    private $interp_model;
    private $ephe_manager;

    public function __construct() {
        // 1. СПОЧАТКУ ініціалізуємо менеджер ефемерід
        $this->ephe_manager = new EphemerisManager();
        
        // 2. ПОТІМ отримуємо з нього шлях
        $this->ephe_path    = $this->ephe_manager->get_ephe_dir();
        
        // 3. Ініціалізуємо інші моделі
        $this->planet_model = new PlanetModel();
        $this->aspect_model = new AspectModel();
        $this->orb_model    = new OrbModel();
        $this->interp_model = new InterpretationModel();
    }

    public function get_planet_positions( $date, $time ) {
        $planet_map = [
            'Sun' => 1, 'Moon' => 2, 'Mercury' => 3, 'Venus' => 4,
            'Mars' => 5, 'Jupiter' => 6, 'Saturn' => 7, 'Uranus' => 8,
            'Neptune' => 9, 'Pluto' => 10,
        ];

        // Додаємо 2>&1, щоб перехопити текст помилки операційної системи (наприклад, command not found)
        $cmd = sprintf(
            '%s -edir%s -b%s -ut%s -p0123456789 -eswe -fPl -g";" 2>&1',
            escapeshellarg( $this->ephe_manager->get_swetest_path() ),
            escapeshellarg( $this->ephe_path ),
            escapeshellarg( $date ),
            escapeshellarg( $time )
        );

        $output = shell_exec( $cmd );
        $positions = [];

        // Якщо swetest не встановлено, він поверне "command not found" або null
        if ( $output === null || strpos( $output, 'command not found' ) !== false || strpos( $output, 'not found' ) !== false ) {
            error_log( "[DAstrolog Critical] swetest не знайдено! Output: " . print_r($output, true) );
            return []; // Повертаємо порожній масив
        }

        $lines = explode( "\n", trim( $output ) );
        foreach ( $lines as $line ) {
            $parts = explode( ';', $line );
            if ( count( $parts ) >= 2 ) {
                $planet_name = trim( $parts[0] );
                $longitude   = (float) trim( $parts[1] );

                if ( isset( $planet_map[ $planet_name ] ) ) {
                    $db_id = $planet_map[ $planet_name ];
                    $positions[ $db_id ] = $longitude;
                }
            }
        }

        return $positions;
    }

    public function get_moon_day( $current_date_utc, $current_time_utc ) {
        $positions = $this->get_planet_positions( $current_date_utc, $current_time_utc );
        
        if ( empty( $positions[1] ) || empty( $positions[2] ) ) {
            return 0; // 0 - це наш код критичної помилки
        }

        $sun_lon = $positions[1];
        $moon_lon = $positions[2];

        $phase = $moon_lon - $sun_lon;
        if ( $phase < 0 ) {
            $phase += 360;
        }

        $moon_day = floor( $phase / 12 ) + 1;
        return (int) $moon_day;
    }
    // Додаткові методи для розрахунку транзитів, описів місячних днів та фаз можна додати тут 
    public function get_moon_day_description( $moon_day ) {
        if ( $moon_day === 0 ) {
            return array( 'title' => 'Помилка розрахунку', 'description' => 'Математичне ядро (swetest) не відповідає.' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'da_moon_days';
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE day_number = %d", $moon_day ), ARRAY_A );
        
        return $result ? $result : array( 'title' => 'Місячний день ' . $moon_day, 'description' => 'Опис відсутній.' );
    }
    /**
     * Розрахунок фази Місяця та дати наступної зміни.
     */
    public function get_moon_phase_info( $date, $time = '12:00:00' ) {
        $pos = $this->get_planet_positions( $date, $time );
        if ( empty($pos[1]) || empty($pos[2]) ) return null;

        $sun  = $pos[1];
        $moon = $pos[2];
        
        // Кут між Сонцем та Місяцем (від 0 до 360)
        $angle = fmod( $moon - $sun + 360, 360 );

        // 1. Зростаючий чи спадний
        $is_waxing = $angle < 180;
        $trend = $is_waxing ? 'Зростаючий 🌔' : 'Спадний 🌖';

        // 2. Поточна фаза (назви з орбісом в 10 градусів)
        if ( $angle <= 10 || $angle >= 350 ) {
            $phase_name = 'Молодик 🌑';
        } elseif ( $angle >= 80 && $angle <= 100 ) {
            $phase_name = 'Перша чверть 🌓';
        } elseif ( $angle >= 170 && $angle <= 190 ) {
            $phase_name = 'Повня 🌕';
        } elseif ( $angle >= 260 && $angle <= 280 ) {
            $phase_name = 'Остання чверть 🌗';
        } else {
            $phase_name = $trend; // Якщо проміжна фаза - виводимо просто тренд
        }

        // 3. Знаходимо найближчу цільову точку зміни (0, 90, 180, 270, 360)
        $next_target = ceil( $angle / 90 ) * 90;
        
        $target_names = [
            0   => 'Молодика 🌑',
            90  => 'Першої чверті 🌓',
            180 => 'Повні 🌕',
            270 => 'Останньої чверті 🌗',
            360 => 'Молодика 🌑'
        ];
        $next_phase_name = $target_names[$next_target] ?? 'зміни';

        // 4. Крокуємо вперед по днях, щоб знайти дату перетину фази
        $current_datetime = \DateTime::createFromFormat('d.m.Y H:i:s', $date . ' ' . $time);
        if ( ! $current_datetime ) $current_datetime = new \DateTime(); 
        
        $next_date = '';
        $prev_angle = $angle;

        // Фаза змінюється в середньому раз на 7.4 дня, тому 10 днів циклу вистачить з запасом
        for ( $i = 1; $i <= 10; $i++ ) { 
            $current_datetime->modify('+1 day');
            $check_date = $current_datetime->format('d.m.Y');
            
            $check_pos = $this->get_planet_positions( $check_date, '12:00:00' );
            if ( empty($check_pos[1]) || empty($check_pos[2]) ) continue;

            $check_angle = fmod( $check_pos[2] - $check_pos[1] + 360, 360 );

            $crossed = false;
            // Обробка переходу через 360 градусів (Новий цикл)
            if ( $next_target == 360 || $next_target == 0 ) {
                if ( $prev_angle > 270 && $check_angle < 90 ) {
                    $crossed = true;
                }
            } else {
                if ( $prev_angle <= $next_target && $check_angle >= $next_target ) {
                    $crossed = true;
                }
            }

            if ( $crossed ) {
                $next_date = $check_date;
                break;
            }
            $prev_angle = $check_angle;
        }

        return [
            'trend'           => $trend,
            'phase_name'      => $phase_name,
            'next_phase_name' => $next_phase_name,
            'next_phase_date' => $next_date
        ];
    }
    // Розрахунок транзитів для конкретної дати та натальної карти
    public function calculate_daily_transits( $n_date, $n_time, $t_date, $t_time = '12:00:00' ) {
        // Додаємо можливість вимкнути кешування через опцію (для розробки або при нестабільності даних) 
        // Перед заливкою на робочий сервер просто змініть дефолтне значення на 'no'
        $disable_cache = get_option( 'da_disable_cache', 'yes' );
        // Генеруємо унікальний ключ для кешу на основі вхідних параметрів
        $cache_key = 'da_transits_v10_' . md5( $n_date . $n_time . $t_date . $t_time );

        if ( $disable_cache !== 'yes' ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) return $cached;
        }

        $results = array( 'positive' => array(), 'negative' => array(), 'neutral' => array() );

        $natal_positions   = $this->get_planet_positions( $n_date, $n_time );
        $transit_positions = $this->get_planet_positions( $t_date, $t_time );

        if ( empty( $natal_positions ) || empty( $transit_positions ) ) {
            return $results; // Повертаємо пусті результати, якщо ядро swetest не відповіло
        }

        // Отримуємо дані через моделі
        $aspects      = $this->aspect_model->get_major_aspects();
        $transit_orbs = $this->orb_model->get_transit_orbs();
        $planets_raw  = $this->planet_model->get_all();

        $planet_names = array();
        foreach ( $planets_raw as $p ) {
            $planet_names[ $p['id'] ] = $p['name'] . ' (' . $p['symbol'] . ')';
        }

        $cat_labels = [
            'health'   => '⚕️ <b>Здоров\'я:</b>',
            'business' => '💼 <b>Бізнес:</b>',
            'love'     => '❤️ <b>Кохання та сім\'я:</b>',
            'general'  => '🔮 <b>Загальне:</b>'
        ];

        foreach ( $transit_positions as $t_id => $t_lon ) {
            foreach ( $natal_positions as $n_id => $n_lon ) {
                
                $diff = abs( $t_lon - $n_lon );
                if ( $diff > 180 ) $diff = 360 - $diff;

                foreach ( $aspects as $aspect ) {
                    $target_angle = (float) $aspect['angle'];
                    $current_orb = isset( $transit_orbs[ $t_id ][ $aspect['id'] ] ) ? $transit_orbs[ $t_id ][ $aspect['id'] ] : 1.0;

                    if ( abs( $diff - $target_angle ) <= $current_orb ) {
                        
                        // Отримуємо інтерпретації через модель
                        $interps = $this->interp_model->get_interpretations( $t_id, $n_id, $target_angle );

                        $desc_html = '';
                        if ( ! empty( $interps ) ) {
                            foreach ( $interps as $row ) {
                                $label = $cat_labels[ $row['category'] ] ?? "<b>{$row['category']}:</b>";
                                $desc_html .= $label . " " . trim( $row['description'] ) . "\n\n";
                            }
                        } else {
                            $desc_html = 'Опис для цієї комбінації відсутній.';
                        }

                        $results[ $aspect['type'] ][] = array(
                            'transit_planet' => $planet_names[$t_id] ?? 'Планета '.$t_id,
                            'transit_p_id'   => $t_id,
                            'natal_planet'   => $planet_names[$n_id] ?? 'Планета '.$n_id,
                            'natal_p_id'     => $n_id,
                            'aspect_name'    => $aspect['name'],
                            'color'          => $aspect['color_hex'],
                            'description'    => $desc_html
                        );
                    }
                }
            }
        }

        if ( $disable_cache !== 'yes' ) {
            set_transient( $cache_key, $results, DAY_IN_SECONDS );
        }
        return $results;
    }
    /**
     * Формування короткого тексту для Telegram
     */
    public function get_telegram_forecast_text( $birth_date, $birth_time, $target_date = null ) {
        if ( ! $target_date ) $target_date = date('d.m.Y');
        
        $moon_day_num = $this->get_moon_day( $target_date, '12:00:00' );
        $moon_info    = $this->get_moon_day_description( $moon_day_num );
        $moon_data    = json_decode( $moon_info['description'], true );
        $phase_info   = $this->get_moon_phase_info( $target_date, '12:00:00' );
        $transits     = $this->calculate_daily_transits( $birth_date, $birth_time, $target_date );

        // Значки аспектів (розширений словник для будь-якої мови)
        $symbols = [
            'Соедин' => '☌', 'З\'єднан' => '☌', 
            'Секст'  => '⚹', 
            'Квадрат'=> '□', 
            'Тригон' => '△', 'Трігон' => '△', 
            'Оппоз'  => '☍', 'Опоз' => '☍',
            'Квинк'  => '⚻', 'Квінк' => '⚻'
        ];
        // Значки планет
        $planets = [1=>'☉', 2=>'☽', 3=>'☿', 4=>'♀', 5=>'♂', 6=>'♃', 7=>'♄', 8=>'♅', 9=>'♆', 10=>'♇'];

        $text = "<b>" . $phase_info['phase_name'] . "</b> " . $moon_day_num . " - " . ($moon_data['Дія'] ?? '') . "\n\n";

        if ( ! empty($transits['positive']) ) {
            $text .= "🟢 <b>Сприятливо:</b>\n";
            foreach ($transits['positive'] as $tr) {
                $s = '';
                foreach($symbols as $k=>$v) { if(mb_stripos($tr['aspect_name'], $k)!==false) $s = $v; }
                $text .= $planets[$tr['transit_p_id']] . " " . $s . " " . $planets[$tr['natal_p_id']] . "\n";
            }
            $text .= "\n";
        }

        if ( ! empty($transits['negative']) ) {
            $text .= "🔴 <b>Обережність:</b>\n";
            foreach ($transits['negative'] as $tr) {
                $s = '';
                foreach($symbols as $k=>$v) { if(mb_stripos($tr['aspect_name'], $k)!==false) $s = $v; }
                $text .= $planets[$tr['transit_p_id']] . " " . $s . " " . $planets[$tr['natal_p_id']] . "\n";
            }
            $text .= "\n";
        }

        if ( $phase_info ) {
            $text .= "✨ Зміна фази: " . $phase_info['next_phase_date'] . " (" . $phase_info['next_phase_name'] . ")\n";
        }

        return $text;
    }
    // Додаткові методи для розрахунку прогресій, солярів та інших астрологічних технік можна додати тут
}