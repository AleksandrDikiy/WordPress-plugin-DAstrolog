<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

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
                                <?php echo esc_html( $item['aspect_name'] ); ?>
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
                                <?php echo esc_html( $item['aspect_name'] ); ?>
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