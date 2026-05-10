<?php 
/**
 * Частковий шаблон для відображення транзитів у фронтенді.
 * Виводить два блоки: позитивні та негативні аспекти з відповідними описами.
 * Version:     1.2.0
 * Date_update: 2026-05-10
 */
if ( ! defined( 'ABSPATH' ) ) exit; 

// Словник астрологічних символів для аспектів
// Словник астрологічних символів для аспектів (розширений)
$aspect_symbols = [
    'Соедин'        => '☌',
    'З\'єднан'      => '☌',
    'Секст'         => '⚹',
    'Квадрат'       => '□',
    'Тригон'        => '△',
    'Трігон'        => '△',
    'Оппоз'         => '☍',
    'Опоз'          => '☍',
    'Квинк'         => '⚻',
    'Квінк'         => '⚻',
    'Полусекст'     => '⚺',
    'Напівсекс'     => '⚺',
    'Полуквадрат'   => '∠',
    'Напівквад'     => '∠',
    'Полутораквадрат'=> '⚼',
];

// Функція-хелпер для швидкого отримання значка
$get_badge = function($name) use ($aspect_symbols) {
    // Шукаємо точний збіг назви (враховуючи регістр бази ZET)
    foreach ($aspect_symbols as $key => $symbol) {
        if (mb_stripos($name, $key) !== false) {
            // Повертаємо символ + назву (можете залишити тільки $symbol, якщо текст не потрібен)
            return '<span style="font-size: 1.2em; margin-right: 4px;">' . $symbol . '</span> ' . esc_html( mb_strtoupper($name) );
        }
    }
    return esc_html( mb_strtoupper($name) );
};
?>

<div class="da-aspects-grid">
    <div class="da-table-wrapper da-positive">
        <h4><?php esc_html_e( 'Гармонійні впливи (сприятливо)', 'dastrolog' ); ?></h4>
        <?php if ( ! empty( $transits['positive'] ) ) : ?>
            <div class="da-accordion">
                <?php foreach ( $transits['positive'] as $item ) : ?>
                    <details class="da-accordion-item">
                        <summary class="da-accordion-header">
                            <span class="da-planet-t"><?php echo esc_html( $item['transit_planet'] ); ?></span>
                            <span class="da-aspect-badge" style="color: <?php echo esc_attr( $item['color'] ); ?>;">
                                <?php echo wp_kses_post( $get_badge($item['aspect_name']) ); ?>
                            </span>
                            <span class="da-planet-n"><?php echo esc_html( $item['natal_planet'] ); ?></span>
                        </summary>
                        <div class="da-accordion-body">
                            <?php echo wp_kses_post( nl2br( $item['description'] ) ); ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'Значущих позитивних аспектів немає.', 'dastrolog' ); ?></p>
        <?php endif; ?>
    </div>

    <div class="da-table-wrapper da-negative">
        <h4><?php esc_html_e( 'Напружені впливи (обережність)', 'dastrolog' ); ?></h4>
        <?php if ( ! empty( $transits['negative'] ) ) : ?>
            <div class="da-accordion">
                <?php foreach ( $transits['negative'] as $item ) : ?>
                    <details class="da-accordion-item">
                        <summary class="da-accordion-header">
                            <span class="da-planet-t"><?php echo esc_html( $item['transit_planet'] ); ?></span>
                            <span class="da-aspect-badge" style="color: <?php echo esc_attr( $item['color'] ); ?>;">
                                <?php echo wp_kses_post( $get_badge($item['aspect_name']) ); ?>
                            </span>
                            <span class="da-planet-n"><?php echo esc_html( $item['natal_planet'] ); ?></span>
                        </summary>
                        <div class="da-accordion-body">
                            <?php echo wp_kses_post( nl2br( $item['description'] ) ); ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'Значущих напружених аспектів немає.', 'dastrolog' ); ?></p>
        <?php endif; ?>
    </div>
</div>