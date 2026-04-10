<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RECRM_Plugin_Updater {
    protected static $config = array();

    public static function boot( $config ) {
        self::$config = wp_parse_args(
            $config,
            array(
                'plugin_file' => '',
                'plugin_slug' => '',
                'version'     => '',
                'cache_key'   => 'recrm_plugin_update_data',
                'cache_ttl'   => 21600,
                'version_url' => '',
                'details_url' => '',
            )
        );

        if ( empty( self::$config['plugin_file'] ) || empty( self::$config['version_url'] ) ) {
            return;
        }

        add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
        add_filter( 'plugins_api', array( __CLASS__, 'plugins_api' ), 20, 3 );
        add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
        add_action( 'admin_init', array( __CLASS__, 'maybe_force_refresh' ) );
    }

    public static function maybe_force_refresh() {
        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }

        if ( empty( $_GET['recrm_check_updates'] ) ) {
            return;
        }

        check_admin_referer( 'recrm_check_updates' );

        delete_transient( self::$config['cache_key'] );
        delete_site_transient( 'update_plugins' );
        wp_update_plugins();

        $redirect = remove_query_arg( array( 'recrm_check_updates', '_wpnonce' ) );
        wp_safe_redirect( $redirect ? $redirect : admin_url( 'plugins.php' ) );
        exit;
    }

    public static function inject_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        $remote = self::get_remote_data();
        if ( empty( $remote['version'] ) ) {
            return $transient;
        }

        $plugin_slug    = self::$config['plugin_slug'];
        $current_version = self::$config['version'];

        if ( version_compare( $remote['version'], $current_version, '>' ) ) {
            $package = ! empty( $remote['download_url'] ) ? $remote['download_url'] : '';

            $transient->response[ $plugin_slug ] = (object) array(
                'slug'        => dirname( $plugin_slug ),
                'plugin'      => $plugin_slug,
                'new_version' => $remote['version'],
                'tested'      => isset( $remote['tested'] ) ? $remote['tested'] : '',
                'requires'    => isset( $remote['requires'] ) ? $remote['requires'] : '',
                'requires_php'=> isset( $remote['requires_php'] ) ? $remote['requires_php'] : '',
                'package'     => $package,
                'url'         => ! empty( $remote['details_url'] ) ? $remote['details_url'] : self::$config['details_url'],
            );
        } else {
            $transient->no_update[ $plugin_slug ] = (object) array(
                'slug'        => dirname( $plugin_slug ),
                'plugin'      => $plugin_slug,
                'new_version' => $current_version,
                'tested'      => isset( $remote['tested'] ) ? $remote['tested'] : '',
                'requires'    => isset( $remote['requires'] ) ? $remote['requires'] : '',
                'requires_php'=> isset( $remote['requires_php'] ) ? $remote['requires_php'] : '',
                'package'     => '',
                'url'         => ! empty( $remote['details_url'] ) ? $remote['details_url'] : self::$config['details_url'],
            );
        }

        return $transient;
    }

    public static function plugins_api( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
            return $result;
        }

        if ( $args->slug !== dirname( self::$config['plugin_slug'] ) ) {
            return $result;
        }

        $remote = self::get_remote_data();
        if ( empty( $remote['version'] ) ) {
            return $result;
        }

        return (object) array(
            'name'          => isset( $remote['name'] ) ? $remote['name'] : 'RE CRM XML Import',
            'slug'          => dirname( self::$config['plugin_slug'] ),
            'version'       => $remote['version'],
            'author'        => isset( $remote['author'] ) ? $remote['author'] : 'Roman',
            'homepage'      => ! empty( $remote['details_url'] ) ? $remote['details_url'] : self::$config['details_url'],
            'requires'      => isset( $remote['requires'] ) ? $remote['requires'] : '',
            'tested'        => isset( $remote['tested'] ) ? $remote['tested'] : '',
            'requires_php'  => isset( $remote['requires_php'] ) ? $remote['requires_php'] : '',
            'last_updated'  => isset( $remote['last_updated'] ) ? $remote['last_updated'] : '',
            'download_link' => isset( $remote['download_url'] ) ? $remote['download_url'] : '',
            'sections'      => isset( $remote['sections'] ) && is_array( $remote['sections'] ) ? $remote['sections'] : array(),
            'banners'       => array(),
            'icons'         => array(),
        );
    }

    public static function plugin_row_meta( $plugin_meta, $plugin_file ) {
        if ( $plugin_file !== self::$config['plugin_slug'] ) {
            return $plugin_meta;
        }

        $url = wp_nonce_url(
            add_query_arg( 'recrm_check_updates', '1', admin_url( 'plugins.php' ) ),
            'recrm_check_updates'
        );

        $plugin_meta[] = '<a href="' . esc_url( $url ) . '">Перевірити оновлення</a>';

        return $plugin_meta;
    }

    protected static function get_remote_data() {
        $cached = get_transient( self::$config['cache_key'] );
        if ( is_array( $cached ) && ! empty( $cached['version'] ) ) {
            return $cached;
        }

        $response = wp_remote_get(
            self::$config['version_url'],
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code || empty( $body ) ) {
            return array();
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) || empty( $data['version'] ) ) {
            return array();
        }

        set_transient( self::$config['cache_key'], $data, (int) self::$config['cache_ttl'] );

        return $data;
    }
}
