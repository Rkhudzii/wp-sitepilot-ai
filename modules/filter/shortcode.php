<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'recrm_filter', 'recrm_filter_shortcode' );
add_shortcode( 'recrm_properties', 'recrm_filter_shortcode' );

function recrm_filter_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'id'           => '',
            'limit'        => 12,
            'fields'       => '',
            'show_form'    => 'yes',
            'show_results' => 'yes',
            'show_reset'   => '1',
            'action_url'   => '',
            'instance'     => '',
            'title'        => '',
            'subtitle'     => '',
        ),
        $atts,
        'recrm_filter'
    );

    $preset = array();

    if ( ! empty( $atts['id'] ) ) {
        $preset = recrm_filter_get_preset( $atts['id'] );
    }

    if ( ! empty( $preset['id'] ) ) {
        $atts['title']        = '' !== $atts['title'] ? $atts['title'] : $preset['title'];
        $atts['subtitle']     = '' !== $atts['subtitle'] ? $atts['subtitle'] : $preset['subtitle'];
        $atts['action_url']   = '' !== $atts['action_url'] ? $atts['action_url'] : $preset['action_url'];
        $atts['limit']        = ! empty( $atts['limit'] ) && 12 !== (int) $atts['limit'] ? $atts['limit'] : $preset['posts_per_page'];
        $atts['show_form']    = ( '' !== $atts['show_form'] && 'yes' !== $atts['show_form'] ) ? $atts['show_form'] : $preset['show_form'];
        $atts['show_results'] = ( '' !== $atts['show_results'] && 'yes' !== $atts['show_results'] ) ? $atts['show_results'] : $preset['show_results'];
        $atts['show_reset']   = $preset['show_reset'];

        // 🔥 Передаємо повну конфігурацію полів
        $atts['field_configs'] = isset( $preset['fields'] ) && is_array( $preset['fields'] ) ? $preset['fields'] : array();

        // 🔥 А enabled_fields робимо як список тільки enabled-полів
        $atts['enabled_fields'] = array();

        foreach ( $atts['field_configs'] as $field_key => $field_config ) {
            if ( is_array( $field_config ) && isset( $field_config['enabled'] ) && '1' === (string) $field_config['enabled'] ) {
                $atts['enabled_fields'][] = $field_key;
            }
        }
    }

    if ( empty( $atts['enabled_fields'] ) ) {
        $atts['enabled_fields'] = recrm_filter_parse_fields_list( $atts['fields'] );
    }

    $instance = ! empty( $atts['instance'] ) ? sanitize_title( $atts['instance'] ) : 'filter-' . wp_rand( 1000, 9999 );
    $source   = recrm_filter_get_effective_source( $_GET, $preset );

    ob_start();
    ?>
    <div class="recrm-archive-page recrm-filter-builder-instance" data-instance="<?php echo esc_attr( $instance ); ?>">
        <?php if ( ! empty( $atts['title'] ) || ! empty( $atts['subtitle'] ) ) : ?>
            <div class="recrm-archive-top">
                <div class="recrm-archive-heading">
                    <?php if ( ! empty( $atts['title'] ) ) : ?>
                        <h2 class="recrm-archive-title"><?php echo esc_html( $atts['title'] ); ?></h2>
                    <?php endif; ?>
                    <?php if ( ! empty( $atts['subtitle'] ) ) : ?>
                        <p class="recrm-archive-subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( in_array( strtolower( (string) $atts['show_form'] ), array( '1', 'yes', 'true' ), true ) ) : ?>
            <?php recrm_filter_render_form( $atts, $source, $instance ); ?>
        <?php endif; ?>

        <?php if ( in_array( strtolower( (string) $atts['show_results'] ), array( '1', 'yes', 'true' ), true ) ) : ?>
            <div class="recrm-results-wrap" data-instance="<?php echo esc_attr( $instance ); ?>">
                <?php echo recrm_filter_render_results( $source, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}