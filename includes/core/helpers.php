<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


function recrm_get_module_registry() {
    return array(
        'property' => array(
            'label'        => 'Property',
            'description'  => 'Базовий модуль: CPT, таксономії, картки та фронтенд обʼєктів.',
            'always_on'    => true,
            'depends_on'   => array(),
        ),
        'filter' => array(
            'label'        => 'Фільтр',
            'description'  => 'Фільтр, builder, архівні списки та AJAX-фільтрація.',
            'always_on'    => false,
            'depends_on'   => array( 'property' ),
        ),
        'seo' => array(
            'label'        => 'SEO',
            'description'  => 'SEO-поля, schema, noindex, score та SEO-структура.',
            'always_on'    => false,
            'depends_on'   => array(),
        ),
        'import' => array(
            'label'        => 'Імпорт',
            'description'  => 'XML-імпорт обʼєктів, cron і сторінка ручного імпорту.',
            'always_on'    => false,
            'depends_on'   => array(),
        ),
    );
}

function recrm_get_module_option_key() {
    return 'recrm_module_settings';
}

function recrm_get_default_module_settings() {
    return array(
        'filter' => '1',
        'seo'    => '1',
        'import' => '1',
    );
}

function recrm_get_module_settings() {
    $settings = get_option( recrm_get_module_option_key(), array() );
    if ( ! is_array( $settings ) ) {
        $settings = array();
    }

    $settings = wp_parse_args( $settings, recrm_get_default_module_settings() );

    foreach ( recrm_get_module_registry() as $module_key => $module_data ) {
        if ( ! empty( $module_data['always_on'] ) ) {
            $settings[ $module_key ] = '1';
        } else {
            $settings[ $module_key ] = ! empty( $settings[ $module_key ] ) ? '1' : '0';
        }
    }

    return $settings;
}

function recrm_update_module_settings( $settings ) {
    $current = recrm_get_module_settings();
    $merged  = array_merge( $current, is_array( $settings ) ? $settings : array() );
    $registry = recrm_get_module_registry();

    foreach ( $registry as $module_key => $module_data ) {
        if ( ! empty( $module_data['always_on'] ) ) {
            $merged[ $module_key ] = '1';
            continue;
        }

        $merged[ $module_key ] = ! empty( $merged[ $module_key ] ) ? '1' : '0';
    }

    if ( empty( $merged['property'] ) ) {
        $merged['property'] = '1';
    }

    update_option( recrm_get_module_option_key(), $merged, false );

    return $merged;
}

function recrm_is_module_enabled( $module_key ) {
    $registry = recrm_get_module_registry();

    if ( ! isset( $registry[ $module_key ] ) ) {
        return false;
    }

    if ( ! empty( $registry[ $module_key ]['always_on'] ) ) {
        return true;
    }

    $settings = recrm_get_module_settings();

    if ( empty( $settings[ $module_key ] ) ) {
        return false;
    }

    if ( ! empty( $registry[ $module_key ]['depends_on'] ) ) {
        foreach ( (array) $registry[ $module_key ]['depends_on'] as $dependency ) {
            if ( ! recrm_is_module_enabled( $dependency ) ) {
                return false;
            }
        }
    }

    return true;
}


function recrm_get_import_option_key() {
    return 'recrm_import_settings';
}

function recrm_get_default_import_settings() {
    return array(
        'xml_url'        => '',
        'auto_import'    => '0',
        'import_interval'=> 'twicedaily',
        'batch_size'     => 3,
        'last_import'    => array(
            'time'       => '',
            'status'     => '',
            'message'    => '',
            'created'    => 0,
            'updated'    => 0,
            'skipped'    => 0,
            'failed'     => 0,
            'source'     => '',
            'source_type'=> '',
        ),
    );
}

function recrm_get_import_settings() {
    $settings = get_option( recrm_get_import_option_key(), array() );

    if ( ! is_array( $settings ) ) {
        $settings = array();
    }

    return wp_parse_args( $settings, recrm_get_default_import_settings() );
}

function recrm_update_import_settings( $settings ) {
    $current = recrm_get_import_settings();
    $merged  = array_replace_recursive( $current, $settings );

    $merged['xml_url']         = isset( $merged['xml_url'] ) ? esc_url_raw( trim( (string) $merged['xml_url'] ) ) : '';
    $merged['auto_import']     = ! empty( $merged['auto_import'] ) ? '1' : '0';
    $merged['import_interval'] = isset( $merged['import_interval'] ) ? sanitize_key( $merged['import_interval'] ) : 'twicedaily';
    $merged['batch_size']      = isset( $merged['batch_size'] ) ? max( 1, min( 20, absint( $merged['batch_size'] ) ) ) : 3;

    update_option( recrm_get_import_option_key(), $merged, false );

    return $merged;
}

function recrm_get_last_import_data() {
    $settings = recrm_get_import_settings();
    return isset( $settings['last_import'] ) && is_array( $settings['last_import'] ) ? $settings['last_import'] : array();
}

function recrm_set_last_import_data( $data ) {
    $settings = recrm_get_import_settings();
    $settings['last_import'] = wp_parse_args(
        $data,
        array(
            'time'        => current_time( 'mysql' ),
            'status'      => '',
            'message'     => '',
            'created'     => 0,
            'updated'     => 0,
            'skipped'     => 0,
            'failed'      => 0,
            'source'      => '',
            'source_type' => '',
            'trashed' => 0,
        )
    );

    update_option( recrm_get_import_option_key(), $settings, false );
}

function recrm_import_intervals() {
    return array(
        'hourly'          => 'Щогодини',
        'twicedaily'      => '2 рази на день',
        'daily'           => '1 раз на день',
        'recrm_15_minutes'=> 'Кожні 15 хвилин',
        'recrm_30_minutes'=> 'Кожні 30 хвилин',
        'recrm_6_hours'   => 'Кожні 6 годин',
    );
}

function recrm_add_custom_cron_schedules( $schedules ) {
    $schedules['recrm_15_minutes'] = array(
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display'  => 'RE CRM: кожні 15 хвилин',
    );

    $schedules['recrm_30_minutes'] = array(
        'interval' => 30 * MINUTE_IN_SECONDS,
        'display'  => 'RE CRM: кожні 30 хвилин',
    );

    $schedules['recrm_6_hours'] = array(
        'interval' => 6 * HOUR_IN_SECONDS,
        'display'  => 'RE CRM: кожні 6 годин',
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'recrm_add_custom_cron_schedules' );

function recrm_sync_import_schedule() {
    $settings = recrm_get_import_settings();
    $enabled  = ! empty( $settings['auto_import'] ) && ! empty( $settings['xml_url'] );
    $interval = ! empty( $settings['import_interval'] ) ? $settings['import_interval'] : 'twicedaily';
    $hook     = 'recrm_xml_import_cron';

    $next = wp_next_scheduled( $hook );

    if ( ! $enabled ) {
        if ( $next ) {
            wp_clear_scheduled_hook( $hook );
        }
        return;
    }

    if ( ! $next ) {
        wp_schedule_event( time() + MINUTE_IN_SECONDS, $interval, $hook );
        return;
    }

    $current = wp_get_schedule( $hook );
    if ( $current !== $interval ) {
        wp_clear_scheduled_hook( $hook );
        wp_schedule_event( time() + MINUTE_IN_SECONDS, $interval, $hook );
    }
}

function recrm_maybe_sync_import_schedule() {
    if ( wp_doing_ajax() ) {
        return;
    }

    recrm_sync_import_schedule();
}
add_action( 'init', 'recrm_maybe_sync_import_schedule', 20 );

/**
 * --------------------------------------
 * CLEAN WORDPRESS SITEMAP
 * --------------------------------------
 */

// прибираємо непотрібні post types
add_filter( 'wp_sitemaps_post_types', function( $post_types ) {
	unset( $post_types['sureforms_form'] ); // форми
	return $post_types;
});

// прибираємо теги
add_filter( 'wp_sitemaps_taxonomies', function( $taxonomies ) {
	unset( $taxonomies['post_tag'] ); // теги
	return $taxonomies;
});

// прибираємо users sitemap
add_filter( 'wp_sitemaps_add_provider', function( $provider, $name ) {
	if ( 'users' === $name ) {
		return false;
	}
	return $provider;
}, 10, 2 );