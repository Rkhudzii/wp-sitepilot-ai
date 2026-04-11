<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function recrm_get_core_module_registry() {
    return array(
        'property' => array(
            'label'          => 'Property',
            'description'    => 'Базовий модуль: CPT, таксономії, картки та фронтенд обʼєктів.',
            'always_on'      => true,
            'depends_on'     => array(),
            'github_managed' => false,
            'github_path'    => 'modules/property',
        ),
        'filter' => array(
            'label'          => 'Фільтр',
            'description'    => 'Фільтр, builder, архівні списки та AJAX-фільтрація.',
            'always_on'      => false,
            'depends_on'     => array( 'property' ),
            'github_managed' => true,
            'github_path'    => 'modules/filter',
        ),
        'seo' => array(
            'label'          => 'SEO',
            'description'    => 'SEO-поля, schema, noindex, score та SEO-структура.',
            'always_on'      => false,
            'depends_on'     => array(),
            'github_managed' => true,
            'github_path'    => 'modules/seo',
        ),
        'import' => array(
            'label'          => 'Імпорт',
            'description'    => 'XML-імпорт обʼєктів, cron і сторінка ручного імпорту.',
            'always_on'      => false,
            'depends_on'     => array(),
            'github_managed' => true,
            'github_path'    => 'modules/import',
        ),
        'chatbot' => array(
            'label'          => 'Chat Bot',
            'description'    => 'Модуль підключення AI-чату.',
            'always_on'      => false,
            'depends_on'     => array(),
            'github_managed' => true,
            'github_path'    => 'modules/chatbot',
        ),
    );
}

function recrm_format_module_label( $module_key ) {
    $label = str_replace( array( '-', '_' ), ' ', sanitize_key( $module_key ) );
    $label = ucwords( $label );

    if ( 'Chatgpt' === $label ) {
        return 'ChatGPT';
    }

    if ( 'Seo' === $label ) {
        return 'SEO';
    }

    return $label;
}

function recrm_normalize_module_registry_entry( $module_key, $data = array() ) {
    $module_key = sanitize_key( $module_key );
    $data       = is_array( $data ) ? $data : array();

    $label = ! empty( $data['label'] ) ? sanitize_text_field( $data['label'] ) : recrm_format_module_label( $module_key );
    $description = ! empty( $data['description'] )
        ? sanitize_text_field( $data['description'] )
        : sprintf( 'Модуль %s.', $label );

    $depends_on = array();
    if ( ! empty( $data['depends_on'] ) ) {
        foreach ( (array) $data['depends_on'] as $dependency ) {
            $dependency = sanitize_key( $dependency );
            if ( '' !== $dependency ) {
                $depends_on[] = $dependency;
            }
        }
        $depends_on = array_values( array_unique( $depends_on ) );
    }

    $always_on = ! empty( $data['always_on'] );

    $github_path = '';
    if ( ! empty( $data['github_path'] ) ) {
        $github_path = trim( (string) $data['github_path'], '/' );
    } elseif ( ! $always_on ) {
        $github_path = 'modules/' . $module_key;
    }

    $github_managed = $always_on ? false : ! empty( $data['github_managed'] );
    if ( ! $always_on && '' !== $github_path ) {
        $github_managed = true;
    }

    return array(
        'label'          => $label,
        'description'    => $description,
        'always_on'      => $always_on,
        'depends_on'     => $depends_on,
        'github_managed' => $github_managed,
        'github_path'    => $github_path,
    );
}

function recrm_get_local_module_directories() {
    $modules_dir = trailingslashit( RECRM_XML_IMPORT_PATH . 'modules' );
    if ( ! is_dir( $modules_dir ) ) {
        return array();
    }

    $items = scandir( $modules_dir );
    if ( false === $items ) {
        return array();
    }

    $modules = array();

    foreach ( $items as $item ) {
        if ( '.' === $item || '..' === $item || '.' === substr( $item, 0, 1 ) ) {
            continue;
        }

        $module_key = sanitize_key( $item );
        if ( '' === $module_key ) {
            continue;
        }

        $module_dir = trailingslashit( $modules_dir . $item );
        if ( ! is_dir( $module_dir ) ) {
            continue;
        }

        if ( file_exists( $module_dir . 'bootstrap.php' ) ) {
            $modules[] = $module_key;
        }
    }

    sort( $modules );

    return array_values( array_unique( $modules ) );
}

function recrm_should_query_remote_modules() {
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        return true;
    }

    if ( ! is_admin() ) {
        return false;
    }

    $page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

    if ( 'recrm-settings' === $page || 'recrm_manage_module' === $action ) {
        return true;
    }

    return false;
}

function recrm_get_remote_modules_cache_key() {
    return 'recrm_remote_module_dirs_v1';
}

function recrm_get_remote_module_manifest_cache_key( $module_key ) {
    return 'recrm_remote_module_manifest_' . sanitize_key( $module_key ) . '_v1';
}

function recrm_clear_remote_module_registry_cache() {
    delete_transient( recrm_get_remote_modules_cache_key() );

    foreach ( recrm_get_local_module_directories() as $module_key ) {
        delete_transient( recrm_get_remote_module_manifest_cache_key( $module_key ) );
    }

    foreach ( array_keys( recrm_get_core_module_registry() ) as $module_key ) {
        delete_transient( recrm_get_remote_module_manifest_cache_key( $module_key ) );
    }
}

function recrm_get_remote_module_directories() {
    $cache_key = recrm_get_remote_modules_cache_key();
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) ) {
        return $cached;
    }

    if ( ! recrm_should_query_remote_modules() || ! function_exists( 'recrm_github_api_request' ) ) {
        return array();
    }

    $items = recrm_github_api_request( 'modules' );
    if ( is_wp_error( $items ) || ! is_array( $items ) ) {
        return array();
    }

    $modules = array();

    foreach ( $items as $item ) {
        if ( empty( $item['type'] ) || 'dir' !== $item['type'] || empty( $item['name'] ) ) {
            continue;
        }

        $module_key = sanitize_key( $item['name'] );
        if ( '' === $module_key ) {
            continue;
        }

        $modules[] = $module_key;
    }

    sort( $modules );
    $modules = array_values( array_unique( $modules ) );

    set_transient( $cache_key, $modules, 12 * HOUR_IN_SECONDS );

    return $modules;
}

function recrm_get_local_module_manifest( $module_key ) {
    $module_key = sanitize_key( $module_key );
    $manifest   = trailingslashit( RECRM_XML_IMPORT_PATH . 'modules/' . $module_key ) . 'module.json';

    if ( ! file_exists( $manifest ) ) {
        return array();
    }

    $contents = file_get_contents( $manifest );
    if ( false === $contents ) {
        return array();
    }

    $data = json_decode( $contents, true );

    return is_array( $data ) ? $data : array();
}

function recrm_get_remote_module_manifest( $module_key ) {
    $module_key = sanitize_key( $module_key );
    $cache_key  = recrm_get_remote_module_manifest_cache_key( $module_key );
    $cached     = get_transient( $cache_key );

    if ( is_array( $cached ) ) {
        return $cached;
    }

    if ( ! recrm_should_query_remote_modules() || ! function_exists( 'recrm_github_api_request' ) ) {
        return array();
    }

    $manifest = recrm_github_api_request( 'modules/' . $module_key . '/module.json' );
    if ( is_wp_error( $manifest ) || ! is_array( $manifest ) || empty( $manifest['download_url'] ) ) {
        return array();
    }

    $contents = recrm_github_download_file_contents( $manifest['download_url'] );
    if ( is_wp_error( $contents ) ) {
        return array();
    }

    $data = json_decode( $contents, true );
    if ( ! is_array( $data ) ) {
        return array();
    }

    set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

    return $data;
}

function recrm_sort_module_registry( $registry ) {
    if ( isset( $registry['property'] ) ) {
        $property = array( 'property' => $registry['property'] );
        unset( $registry['property'] );
        ksort( $registry );
        return $property + $registry;
    }

    ksort( $registry );
    return $registry;
}

function recrm_get_module_registry() {
    $registry      = array();
    $core_registry = recrm_get_core_module_registry();

    foreach ( $core_registry as $module_key => $module_data ) {
        $local_manifest = recrm_get_local_module_manifest( $module_key );

        $registry[ $module_key ] = recrm_normalize_module_registry_entry(
            $module_key,
            array_merge( $module_data, $local_manifest )
        );
    }

    foreach ( recrm_get_local_module_directories() as $module_key ) {
        if ( isset( $registry[ $module_key ] ) ) {
            continue;
        }

        $data = array_merge(
            array(
                'label'          => recrm_format_module_label( $module_key ),
                'description'    => sprintf( 'Локально встановлений модуль %s.', recrm_format_module_label( $module_key ) ),
                'always_on'      => false,
                'depends_on'     => array(),
                'github_managed' => false,
                'github_path'    => 'modules/' . $module_key,
            ),
            recrm_get_local_module_manifest( $module_key )
        );

        $registry[ $module_key ] = recrm_normalize_module_registry_entry( $module_key, $data );
    }

    $registry = recrm_sort_module_registry( $registry );

    return apply_filters( 'recrm_module_registry', $registry );
}

function recrm_get_manageable_module_registry() {
    $registry           = recrm_get_module_registry();
    $remote_directories = recrm_get_remote_module_directories();

    foreach ( $remote_directories as $module_key ) {
        $remote_manifest = recrm_get_remote_module_manifest( $module_key );

        if ( isset( $registry[ $module_key ] ) ) {
            $registry[ $module_key ] = recrm_normalize_module_registry_entry(
                $module_key,
                array_merge( $registry[ $module_key ], $remote_manifest, array(
                    'github_managed' => true,
                    'github_path'    => 'modules/' . $module_key,
                ) )
            );
            continue;
        }

        $data = array_merge(
            array(
                'label'          => recrm_format_module_label( $module_key ),
                'description'    => sprintf( 'Модуль %s, доступний для встановлення з GitHub.', recrm_format_module_label( $module_key ) ),
                'always_on'      => false,
                'depends_on'     => array(),
                'github_managed' => true,
                'github_path'    => 'modules/' . $module_key,
            ),
            $remote_manifest
        );

        $registry[ $module_key ] = recrm_normalize_module_registry_entry( $module_key, $data );
    }

    return recrm_sort_module_registry( $registry );
}

function recrm_get_module_option_key() {
    return 'recrm_module_settings';
}

function recrm_get_default_module_settings() {
    $defaults = array();

    foreach ( recrm_get_module_registry() as $module_key => $module_data ) {
        if ( ! empty( $module_data['always_on'] ) ) {
            continue;
        }

        $defaults[ $module_key ] = recrm_is_module_installed( $module_key ) ? '1' : '0';
    }

    return $defaults;
}

function recrm_get_module_settings() {
    $settings = get_option( recrm_get_module_option_key(), array() );
    if ( ! is_array( $settings ) ) {
        $settings = array();
    }

    if ( isset( $settings['chatgpt'] ) && ! isset( $settings['chatbot'] ) ) {
        $settings['chatbot'] = ! empty( $settings['chatgpt'] ) ? '1' : '0';
    }

    unset( $settings['chatgpt'] );

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
    $incoming = is_array( $settings ) ? $settings : array();

    if ( isset( $incoming['chatgpt'] ) && ! isset( $incoming['chatbot'] ) ) {
        $incoming['chatbot'] = $incoming['chatgpt'];
    }

    unset( $incoming['chatgpt'] );

    $merged   = array_merge( $current, $incoming );
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

    if ( ! recrm_is_module_installed( $module_key ) ) {
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
