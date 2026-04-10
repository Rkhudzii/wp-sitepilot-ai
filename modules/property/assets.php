<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', 'recrm_enqueue_front_styles' );

function recrm_enqueue_front_styles() {
    wp_enqueue_style(
        'recrm-property-cards',
        RECRM_XML_IMPORT_URL . 'assets/css/property-cards.css',
        array(),
        filemtime( RECRM_XML_IMPORT_PATH . 'assets/css/property-cards.css' )
    );

    if ( is_singular( 'property' ) ) {
        wp_enqueue_style(
            'recrm-property-single',
            RECRM_XML_IMPORT_URL . 'assets/css/property-single.css',
            array(),
            filemtime( RECRM_XML_IMPORT_PATH . 'assets/css/property-single.css' )
        );
    }
}