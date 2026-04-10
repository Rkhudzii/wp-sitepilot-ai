<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_recrm_filter_properties', 'recrm_filter_ajax_handler' );
add_action( 'wp_ajax_nopriv_recrm_filter_properties', 'recrm_filter_ajax_handler' );

function recrm_filter_ajax_handler() {
    check_ajax_referer( 'recrm_filter_nonce', 'nonce' );

    $limit      = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 12;
    $fields     = isset( $_POST['fields'] ) ? sanitize_text_field( wp_unslash( $_POST['fields'] ) ) : '';
    $action_url = isset( $_POST['action_url'] ) ? esc_url_raw( wp_unslash( $_POST['action_url'] ) ) : '';
    $filter_id  = isset( $_POST['filter_id'] ) ? sanitize_title( wp_unslash( $_POST['filter_id'] ) ) : '';

    if (
    '' !== $filter_id &&
    function_exists( 'recrm_filter_builder_get' ) &&
    function_exists( 'recrm_filter_builder_merge_config' ) &&
    function_exists( 'recrm_filter_builder_get_effective_source' ) &&
    function_exists( 'recrm_filter_builder_render_results' ) &&
    function_exists( 'recrm_filter_builder_render_form_html' )
) {
    $config = recrm_filter_builder_get( $filter_id );

        if ( ! empty( $config ) && is_array( $config ) ) {
            $config = recrm_filter_builder_merge_config( $config );
            $source = recrm_filter_builder_get_effective_source( $config, $_POST );

            $html = recrm_filter_builder_render_results(
                $config,
                $source,
                array(
                    'limit' => $limit,
                )
            );

            $instance = isset( $_POST['recrm_instance'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_instance'] ) ) : '';
            $layout   = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : 'default';

            $form_html = recrm_filter_builder_render_form_html(
                $config,
                $source,
                $instance,
                $layout,
                $filter_id
            );

            wp_send_json_success(
                array(
                    'html'      => $html,
                    'form_html' => $form_html,
                )
            );
        }
    }

    $atts = array(
        'limit'      => $limit,
        'fields'     => $fields,
        'action_url' => $action_url,
    );

    $html = recrm_filter_render_results( $_POST, $atts );

    ob_start();
    recrm_filter_render_form( $atts, $_POST, isset( $_POST['recrm_instance'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_instance'] ) ) : '' );
    $form_html = ob_get_clean();

    wp_send_json_success(
        array(
            'html'      => $html,
            'form_html' => $form_html,
        )
    );
}
