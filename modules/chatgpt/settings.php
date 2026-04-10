<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_init', 'recrm_chatgpt_register_settings' );

function recrm_chatgpt_get_settings() {
    $defaults = array(
        'api_key'        => '',
        'model'          => 'gpt-5.4-mini',
        'system_prompt'  => 'Ти допомагаєш з SEO і контентом для сайту нерухомості.',
        'default_tone'   => 'Професійний і простий',
    );

    $settings = get_option( 'recrm_chatgpt_settings', array() );
    return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
}

function recrm_chatgpt_register_settings() {
    register_setting( 'recrm_chatgpt_settings_group', 'recrm_chatgpt_settings', 'recrm_chatgpt_sanitize_settings' );
}

function recrm_chatgpt_sanitize_settings( $input ) {
    return array(
        'api_key'       => isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '',
        'model'         => isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : 'gpt-5.4-mini',
        'system_prompt' => isset( $input['system_prompt'] ) ? sanitize_textarea_field( $input['system_prompt'] ) : '',
        'default_tone'  => isset( $input['default_tone'] ) ? sanitize_text_field( $input['default_tone'] ) : '',
    );
}
