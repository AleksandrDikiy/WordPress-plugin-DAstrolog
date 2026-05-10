<?php
/**
 * Клас обробки активації та імпорту даних.
 * Version:     1.2.0
 * Date_update: 2026-05-10
 * Зміни v1.1.7:
 * - Відновлено реєстрацію ролі DAstrolog User при активації плагіна.
 */

namespace DAstrolog\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Activator {

    public static function activate() {
        // Реєстрація кастомної ролі з базовими правами (читання)
        add_role( 'DAstrolog', __( 'DAstrolog User', 'dastrolog' ), array( 'read' => true ) );

        self::create_custom_tables();
        self::seed_default_data();
        update_option( 'da_db_version', DA_DB_VERSION );
 
        // ПРИМУСОВО ОЧИЩАЄМО КЕШ РОЗКЛАДІВ, щоб WordPress побачив нову назву без перекладу
        wp_clear_scheduled_hook( 'da_daily_telegram_broadcast' );
        wp_clear_scheduled_hook( 'da_telegram_broadcast' );
        delete_option( 'cron' ); 
        
        // Реєструємо новий хук, який працюватиме кожні 30 хвилин
        if ( ! wp_next_scheduled( 'da_telegram_broadcast' ) ) {
            wp_schedule_event( time(), 'da_half_hourly', 'da_telegram_broadcast' );
        }
        
        // Скидання rewrite rules для уникнення помилок маршрутизації
        flush_rewrite_rules();
    }

    private static function create_custom_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$wpdb->prefix}da_planet (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(50) NOT NULL,
            symbol varchar(10) NOT NULL,
            is_fast_moving tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;
        
        CREATE TABLE {$wpdb->prefix}da_aspect (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(50) NOT NULL,
            angle decimal(5,2) NOT NULL,
            type enum('positive', 'negative', 'neutral') NOT NULL DEFAULT 'neutral',
            color_hex varchar(7) NOT NULL DEFAULT '#000000',
            is_major tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY angle (angle)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}da_orb (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            planet_id bigint(20) unsigned NOT NULL,
            aspect_id bigint(20) unsigned NOT NULL,
            calc_type enum('natal', 'transit') NOT NULL,
            orb_value decimal(4,2) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}da_house_system (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(10) NOT NULL,
            name varchar(100) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}da_interpretations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            natal_planet_id bigint(20) unsigned NOT NULL,
            transit_planet_id bigint(20) unsigned NOT NULL,
            aspect_angle decimal(5,2) NOT NULL,
            category varchar(50) NOT NULL DEFAULT 'general',
            description longtext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}da_moon_days (
            day_number int(2) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            description longtext NOT NULL,
            PRIMARY KEY  (day_number)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}da_user_profiles (
            user_id bigint(20) unsigned NOT NULL,
            birth_date date NOT NULL,
            birth_time time NOT NULL,
            lat decimal(10,6) NOT NULL,
            lng decimal(10,6) NOT NULL,
            house_system_id bigint(20) unsigned NOT NULL,
            telegram_chat_id varchar(50) DEFAULT NULL,
            telegram_time time DEFAULT '07:30:00',
            last_tg_sent date DEFAULT NULL,
            settings_json longtext NOT NULL,
            PRIMARY KEY  (user_id)
        ) $charset_collate;";

        dbDelta( $sql );
    }

    private static function seed_default_data() {
        global $wpdb;
        $planets = [
            1=>['Сонце','☉',0], 2=>['Місяць','☽',1], 3=>['Меркурій','☿',0],
            4=>['Венера','♀',0], 5=>['Марс','♂',0], 6=>['Юпітер','♃',0],
            7=>['Сатурн','♄',0], 8=>['Уран','♅',0], 9=>['Нептун','♆',0], 10=>['Плутон','♇',0]
        ];
        foreach ($planets as $id => $d) {
            $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}da_planet (id, name, symbol, is_fast_moving) VALUES (%d, %s, %s, %d)", $id, $d[0], $d[1], $d[2]));
        }
    }

    public static function seed_house_systems($file_name) {
        global $wpdb;
        $path = DA_PLUGIN_DIR . 'assets/data/' . $file_name;
        if (!file_exists($path)) return ["❌ Файл {$file_name} не знайдено."];
        
        $content = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($path));
        if (preg_match('/HouseComboBox;(.*?)\r?\n/u', $content, $matches)) {
            $systems = explode(',', trim($matches[1]));
            foreach ($systems as $i => $name) {
                if ($name) $wpdb->insert("{$wpdb->prefix}da_house_system", ['code' => 'HS'.$i, 'name' => trim($name)]);
            }
        }
        return ["✅ Системи домів оновлено."];
    }

    public static function seed_aspects_and_orbs($file_name, $type) {
        global $wpdb;
        $path = DA_PLUGIN_DIR . 'assets/data/' . $file_name;
        if (!file_exists($path)) return ["❌ Файл {$file_name} не знайдено."];

        $lines = explode("\n", file_get_contents($path));
        $planet_map = [4=>1, 5=>2, 6=>3, 7=>4, 8=>5, 9=>6, 10=>7, 11=>8, 12=>9, 13=>10];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '//') === 0) continue;
            $parts = explode(';', $line);
            if (count($parts) < 15) continue;

            $angle = (float)str_replace(',', '.', $parts[3]);
            $is_major = (strtoupper($parts[2]) === 'M') ? 1 : 0;
            $coeff = (float)str_replace(',', '.', $parts[31] ?? 0);
            $a_type = ($coeff > 0) ? 'positive' : (($coeff < 0) ? 'negative' : 'neutral');

            $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO {$wpdb->prefix}da_aspect (name, angle, type, color_hex, is_major) VALUES (%s, %f, %s, %s, %d)",
                trim($parts[0]), $angle, $a_type, sprintf("#%06x", (int)($parts[27] ?? 0)), $is_major
            ));

            $aspect_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}da_aspect WHERE angle = %f", $angle));
            if ($aspect_id) {
                foreach ($planet_map as $idx => $p_id) {
                    $val = (float)str_replace(',', '.', trim($parts[$idx] ?? 0));
                    $wpdb->replace("{$wpdb->prefix}da_orb", ['planet_id'=>$p_id, 'aspect_id'=>$aspect_id, 'calc_type'=>$type, 'orb_value'=>$val]);
                }
            }
        }
        return ["✅ Аспекти з {$file_name} імпортовано."];
    }

    public static function parse_zet_interpretations($file_name, $category) {
        global $wpdb;
        $path = DA_PLUGIN_DIR . 'assets/data/' . $file_name;
        if (!file_exists($path)) return ["❌ Файл {$file_name} не знайдено."];

        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = str_replace("\r\n", "\n", $content);
        $content = preg_replace('/^\/\/.*$/m', '', $content);

        // ВИПРАВЛЕННЯ: Знаходимо перший реальний маркер
        if (preg_match('/\[\d{2}\.\d{3}\.\d{2}\]/u', $content, $m, PREG_OFFSET_CAPTURE)) {
            $content = substr($content, $m[0][1]);
        }

        $parts = preg_split('/\[(\d{2})\.(\d{3})\.(\d{2})\]/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        $inserted = 0;
        // Крок 4, бо preg_split повернув: 0=>TR, 1=>ANGLE, 2=>NAT, 3=>TEXT
        for ($i = 0; $i < count($parts); $i += 4) {
            if (!isset($parts[$i+3])) break;
            
            $wpdb->insert("{$wpdb->prefix}da_interpretations", [
                'transit_planet_id' => (int)$parts[$i],
                'aspect_angle'      => (float)$parts[$i+1],
                'natal_planet_id'   => (int)$parts[$i+2],
                'category'          => $category,
                'description'       => trim($parts[$i+3])
            ]);
            $inserted++;
        }
        return ["✅ {$category}: додано {$inserted} текстів."];
    }

    public static function parse_zet_moon_days($file_name, $keywords = []) {
        global $wpdb;
        $path = DA_PLUGIN_DIR . 'assets/data/' . $file_name;
        if (!file_exists($path)) return ["❌ Файл {$file_name} не знайдено."];

        $content = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($path));
        $content = str_replace("\r\n", "\n", $content);
        $content = preg_replace('/^\/\/.*$/m', '', $content);

        $parts = preg_split('/\n\s*\[(\d{2})\]\s*/u', "\n".$content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $inserted = 0;
        
        // Фолбек, якщо маніфест передав порожній масив
        if (empty($keywords)) {
            $keywords = [
                'Символическое соответствие', 'Действие', 'Названия', 'Символ', 
                'Влияние социальное', 'Влияние бытовое', 'Влияние мистическое', 
                'Влияние медицинское', 'Влияние на рожденных', 'Влияние на зачатие', 
                'Медитации', 'Сигнатуры', 'Камни', 'Камень'
            ];
        }

        for ($i = 0; $i < count($parts); $i += 2) {
            if (!isset($parts[$i+1])) break;
            
            $lines = explode("\n", trim($parts[$i+1]));
            $title = sanitize_text_field(array_shift($lines) ?? '');
            
            $parsed_data = ['general' => ''];
            $current_key = 'general';

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $matched = false;
                foreach ($keywords as $kw) {
                    // Шукаємо ключове слово з двокрапкою або тире
                    if (preg_match('/^' . preg_quote($kw, '/') . '\s*[:\-]\s*(.*)/iu', $line, $m)) {
                        $current_key = $kw;
                        $parsed_data[$current_key] = trim($m[1]);
                        $matched = true;
                        break;
                    }
                }
                
                if (!$matched) {
                    $parsed_data[$current_key] .= ($parsed_data[$current_key] === '' ? '' : ' ') . $line;
                }
            }

            // Перетворюємо масив у JSON
            $json_desc = wp_json_encode($parsed_data, JSON_UNESCAPED_UNICODE);

            $wpdb->insert("{$wpdb->prefix}da_moon_days", [
                'day_number'  => (int)$parts[$i],
                'title'       => $title,
                'description' => $json_desc
            ]);
            $inserted++;
        }
        return ["✅ Місячні дні: структуровано та додано {$inserted} днів."];
    }
}