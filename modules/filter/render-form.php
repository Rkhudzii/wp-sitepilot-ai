<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function recrm_filter_render_form_field( $field_key, $field, $source, $field_config = array(), $instance = '' ) {
    $default_value = isset( $field_config['default'] ) ? (string) $field_config['default'] : '';
    $value         = recrm_filter_get_request_value( $source, $field_key, '' );

    if ( '' === (string) $value ) {
        $value = $default_value;
    }

    $visible = ! isset( $field_config['visible'] ) || '1' === (string) $field_config['visible'];

    if ( ! $visible ) {
        echo '<input type="hidden" name="' . esc_attr( $field_key ) . '" value="' . esc_attr( $value ) . '">';
        return;
    }

    $input_id = 'recrm-filter-' . sanitize_html_class( $instance ) . '-' . sanitize_html_class( $field_key );
    ?>
    <div class="recrm-filter-field">
        <label for="<?php echo esc_attr( $input_id ); ?>">
            <?php echo esc_html( $field['label'] ); ?>
        </label>

        <?php if ( isset( $field['type'] ) && 'select' === $field['type'] ) : ?>
            <select name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $input_id ); ?>">
                <?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
                    <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( (string) $value, (string) $option_value ); ?>>
                        <?php echo esc_html( $option_label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else : ?>
            <input
                type="number"
                step="any"
                min="0"
                name="<?php echo esc_attr( $field_key ); ?>"
                id="<?php echo esc_attr( $input_id ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
            >
        <?php endif; ?>
    </div>
    <?php
}

function recrm_filter_render_form( $atts, $source, $instance ) {
    $fields_schema = recrm_filter_get_fields_schema( $source );
    $field_configs = isset( $atts['field_configs'] ) && is_array( $atts['field_configs'] ) ? $atts['field_configs'] : array();

    $enabled_fields = isset( $atts['enabled_fields'] ) && is_array( $atts['enabled_fields'] )
        ? $atts['enabled_fields']
        : recrm_filter_parse_fields_list( isset( $atts['fields'] ) ? $atts['fields'] : '' );

    if ( ! is_array( $enabled_fields ) ) {
        $enabled_fields = array();
    }

    $action_url  = recrm_filter_get_form_action_url( $atts );
    $limit       = ! empty( $atts['limit'] ) ? absint( $atts['limit'] ) : 12;
    $fields_attr = ! empty( $enabled_fields ) ? implode( ',', $enabled_fields ) : '';
    $layout      = ! empty( $atts['layout'] ) ? sanitize_key( $atts['layout'] ) : 'default';

    $form_classes = array( 'recrm-filter' );

    if ( 'sidebar' === $layout ) {
        $form_classes[] = 'recrm-filter-sidebar';
        $form_classes[] = 'recrm-sidebar-filter';
    } elseif ( 'header' === $layout ) {
        $form_classes[] = 'recrm-filter-header';
    }

    $form_classes = array_unique( array_filter( $form_classes ) );
    $panel_id     = 'recrm-filter-panel-' . sanitize_html_class( $instance );
    ?>
    <form
        class="<?php echo esc_attr( implode( ' ', $form_classes ) ); ?>"
        method="get"
        action="<?php echo esc_url( $action_url ); ?>"
        data-instance="<?php echo esc_attr( $instance ); ?>"
        data-limit="<?php echo esc_attr( $limit ); ?>"
        data-fields="<?php echo esc_attr( $fields_attr ); ?>"
        data-layout="<?php echo esc_attr( $layout ); ?>"
    >
        <input type="hidden" name="recrm_filter" value="1">
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'recrm_filter_nonce' ) ); ?>">
        <input type="hidden" name="paged" value="<?php echo esc_attr( max( 1, absint( recrm_filter_get_request_value( $source, 'paged', 1 ) ) ) ); ?>">
        <input type="hidden" name="recrm_instance" value="<?php echo esc_attr( $instance ); ?>">

        <?php if ( ! empty( $atts['id'] ) ) : ?>
            <input type="hidden" name="filter_id" value="<?php echo esc_attr( $atts['id'] ); ?>">
        <?php endif; ?>

        <button
            type="button"
            class="recrm-filter-toggle"
            aria-expanded="false"
            aria-controls="<?php echo esc_attr( $panel_id ); ?>"
        >
            <span class="recrm-filter-toggle-text">Фільтер пошуку</span>
        </button>

        <div class="recrm-filter-collapsible" id="<?php echo esc_attr( $panel_id ); ?>">
            <div class="recrm-filter-inner">
                <div class="recrm-filter-grid">
                    <?php
                    foreach ( $enabled_fields as $field_key ) {
                        if ( isset( $fields_schema[ $field_key ] ) ) {
                            $field_config = isset( $field_configs[ $field_key ] ) && is_array( $field_configs[ $field_key ] )
                                ? $field_configs[ $field_key ]
                                : array();

                            recrm_filter_render_form_field(
                                $field_key,
                                $fields_schema[ $field_key ],
                                $source,
                                $field_config,
                                $instance
                            );
                        }
                    }
                    ?>
                </div>

                <div class="recrm-filter-actions">
                    <button type="submit" class="recrm-btn recrm-btn-primary">Показати</button>

                    <?php if ( empty( $atts['show_reset'] ) || '1' === (string) $atts['show_reset'] ) : ?>
                        <a href="<?php echo esc_url( $action_url ); ?>" class="recrm-btn recrm-btn-secondary">Скинути</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
    <?php
}