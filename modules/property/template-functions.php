<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function recrm_get_template( $template_name, $args = array() ) {
    $template_path = RECRM_XML_IMPORT_PATH . 'templates/' . ltrim( $template_name, '/' );

    if ( ! file_exists( $template_path ) ) {
        return '';
    }

    if ( ! empty( $args ) && is_array( $args ) ) {
        extract( $args, EXTR_SKIP );
    }

    ob_start();
    include $template_path;
    return ob_get_clean();
}

