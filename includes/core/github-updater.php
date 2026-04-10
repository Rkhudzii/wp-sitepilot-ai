<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RECRM_GitHub_Updater {
    const CACHE_KEY = 'recrm_core_update_meta_v1';

    protected static $instance = null;
    protected $plugin_file;
    protected $plugin_basename;

    public static function boot() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->plugin_file     = RECRM_XML_IMPORT_PATH . 'recrm-xml-import.php';
        $this->plugin_basename = plugin_basename( $this->plugin_file );

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 20, 3 );

        add_action( 'upgrader_process_complete', array( $this, 'clear_cache_after_upgrade' ), 10, 2 );
    }

    public function get_plugin_basename() {
        return $this->plugin_basename;
    }

    public function get_github_source() {
        $source = function_exists( 'recrm_get_github_module_source' )
            ? recrm_get_github_module_source()
            : array(
                'owner'  => 'Rkhudzii',
                'repo'   => 'wp-sitepilot-ai',
                'branch' => 'main',
            );

        $source = wp_parse_args(
            $source,
            array(
                'owner'  => 'Rkhudzii',
                'repo'   => 'wp-sitepilot-ai',
                'branch' => 'main',
            )
        );

        $source['owner']  = trim( (string) $source['owner'] );
        $source['repo']   = trim( (string) $source['repo'] );
        $source['branch'] = trim( (string) $source['branch'] );

        return $source;
    }

    public function get_repo_url() {
        $source = $this->get_github_source();

        return sprintf(
            'https://github.com/%s/%s',
            rawurlencode( $source['owner'] ),
            rawurlencode( $source['repo'] )
        );
    }

    public function get_package_url() {
        $source = $this->get_github_source();

        return sprintf(
            'https://codeload.github.com/%s/%s/zip/refs/heads/%s',
            rawurlencode( $source['owner'] ),
            rawurlencode( $source['repo'] ),
            rawurlencode( $source['branch'] )
        );
    }

    protected function get_plugin_json_url() {
        $source = $this->get_github_source();

        return sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/version.json',
            rawurlencode( $source['owner'] ),
            rawurlencode( $source['repo'] ),
            rawurlencode( $source['branch'] )
        );
    }

    protected function get_remote_main_file_url() {
        $source = $this->get_github_source();

        return sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/recrm-xml-import.php',
            rawurlencode( $source['owner'] ),
            rawurlencode( $source['repo'] ),
            rawurlencode( $source['branch'] )
        );
    }

    protected function remote_get( $url ) {
        return wp_remote_get(
            $url,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Accept'     => 'application/json, text/plain;q=0.9, */*;q=0.8',
                    'User-Agent' => 'RECRM-Core-Updater/' . RECRM_XML_IMPORT_VERSION,
                ),
            )
        );
    }

    public function clear_cached_remote_data() {
        delete_site_transient( self::CACHE_KEY );
        wp_clean_plugins_cache( true );
    }

    protected function parse_plugin_headers_from_body( $body ) {
        $data = array();
        $map  = array(
            'name'         => 'Plugin Name',
            'version'      => 'Version',
            'description'  => 'Description',
            'author'       => 'Author',
            'requires_php' => 'Requires PHP',
            'requires_wp'  => 'Requires at least',
        );

        foreach ( $map as $target => $header ) {
            if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':(.*)$/mi', $body, $matches ) ) {
                $data[ $target ] = trim( wp_strip_all_tags( $matches[1] ) );
            }
        }

        return $data;
    }

    public function get_remote_metadata( $force = false ) {
        $cached = get_site_transient( self::CACHE_KEY );
        if ( ! $force && is_array( $cached ) ) {
            return $cached;
        }

        $metadata = array(
            'name'         => 'WP SitePilot AI',
            'slug'         => dirname( $this->plugin_basename ),
            'plugin'       => $this->plugin_basename,
            'version'      => '',
            'description'  => 'Оновлення ядра плагіна із GitHub.',
            'author'       => 'Roman',
            'homepage'     => $this->get_repo_url(),
            'package'      => $this->get_package_url(),
            'tested'       => '',
            'requires'     => '',
            'requires_php' => '',
            'sections'     => array(),
        );

        $plugin_json_response = $this->remote_get( $this->get_plugin_json_url() );
        if ( ! is_wp_error( $plugin_json_response ) && 200 === (int) wp_remote_retrieve_response_code( $plugin_json_response ) ) {
            $json = json_decode( wp_remote_retrieve_body( $plugin_json_response ), true );

            if ( is_array( $json ) ) {
                $metadata['name']         = ! empty( $json['name'] ) ? sanitize_text_field( $json['name'] ) : $metadata['name'];
                $metadata['version']      = ! empty( $json['version'] ) ? sanitize_text_field( $json['version'] ) : $metadata['version'];
                $metadata['description']  = ! empty( $json['description'] ) ? wp_kses_post( $json['description'] ) : $metadata['description'];
                $metadata['author']       = ! empty( $json['author'] ) ? sanitize_text_field( $json['author'] ) : $metadata['author'];
                $metadata['homepage']     = ! empty( $json['homepage'] ) ? esc_url_raw( $json['homepage'] ) : $metadata['homepage'];
                $metadata['package']      = ! empty( $json['package'] ) ? esc_url_raw( $json['package'] ) : $metadata['package'];
                $metadata['tested']       = ! empty( $json['tested'] ) ? sanitize_text_field( $json['tested'] ) : '';
                $metadata['requires']     = ! empty( $json['requires'] ) ? sanitize_text_field( $json['requires'] ) : '';
                $metadata['requires_php'] = ! empty( $json['requires_php'] ) ? sanitize_text_field( $json['requires_php'] ) : '';

                if ( ! empty( $json['sections'] ) && is_array( $json['sections'] ) ) {
                    $metadata['sections'] = array_map( 'wp_kses_post', $json['sections'] );
                }
            }
        }

        if ( '' === $metadata['version'] ) {
            $main_file_response = $this->remote_get( $this->get_remote_main_file_url() );

            if ( is_wp_error( $main_file_response ) ) {
                return $main_file_response;
            }

            if ( 200 !== (int) wp_remote_retrieve_response_code( $main_file_response ) ) {
                return new WP_Error( 'recrm_updater_http_error', 'Не вдалося прочитати головний файл плагіна з GitHub.' );
            }

            $headers = $this->parse_plugin_headers_from_body( wp_remote_retrieve_body( $main_file_response ) );

            $metadata['name']         = ! empty( $headers['name'] ) ? $headers['name'] : $metadata['name'];
            $metadata['version']      = ! empty( $headers['version'] ) ? $headers['version'] : $metadata['version'];
            $metadata['description']  = ! empty( $headers['description'] ) ? $headers['description'] : $metadata['description'];
            $metadata['author']       = ! empty( $headers['author'] ) ? $headers['author'] : $metadata['author'];
            $metadata['requires']     = ! empty( $headers['requires_wp'] ) ? $headers['requires_wp'] : $metadata['requires'];
            $metadata['requires_php'] = ! empty( $headers['requires_php'] ) ? $headers['requires_php'] : $metadata['requires_php'];
        }

        if ( empty( $metadata['sections'] ) ) {
            $metadata['sections'] = array(
                'description' => wp_kses_post( $metadata['description'] ),
                'changelog'   => 'Оновлення ядра завантажується безпосередньо з GitHub-репозиторію.',
            );
        }

        if ( '' === $metadata['version'] ) {
            return new WP_Error( 'recrm_updater_no_version', 'GitHub не повернув версію плагіна.' );
        }

        set_site_transient( self::CACHE_KEY, $metadata, 6 * HOUR_IN_SECONDS );

        return $metadata;
    }

    public function get_update_state( $force = false ) {
        $remote = $this->get_remote_metadata( $force );

        if ( is_wp_error( $remote ) ) {
            return $remote;
        }

        return array(
            'current_version' => RECRM_XML_IMPORT_VERSION,
            'remote_version'  => $remote['version'],
            'has_update'      => version_compare( RECRM_XML_IMPORT_VERSION, $remote['version'], '<' ),
            'package'         => $remote['package'],
            'homepage'        => $remote['homepage'],
            'checked_at'      => time(),
            'remote'          => $remote,
        );
    }

    public function inject_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        $state = $this->get_update_state();

        if ( is_wp_error( $state ) || empty( $state['has_update'] ) ) {
            return $transient;
        }

        $update = new stdClass();
        $update->slug           = dirname( $this->plugin_basename );
        $update->plugin         = $this->plugin_basename;
        $update->new_version    = $state['remote_version'];
        $update->url            = $state['homepage'];
        $update->package        = $state['package'];
        $update->icons          = array();
        $update->banners        = array();
        $update->tested         = ! empty( $state['remote']['tested'] ) ? $state['remote']['tested'] : '';
        $update->requires       = ! empty( $state['remote']['requires'] ) ? $state['remote']['requires'] : '';
        $update->requires_php   = ! empty( $state['remote']['requires_php'] ) ? $state['remote']['requires_php'] : '';
        $update->compatibility  = new stdClass();

        $transient->response[ $this->plugin_basename ] = $update;

        return $transient;
    }

    public function plugins_api( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( $this->plugin_basename ) !== $args->slug ) {
            return $result;
        }

        $remote = $this->get_remote_metadata();

        if ( is_wp_error( $remote ) ) {
            return $result;
        }

        $info = new stdClass();
        $info->name          = $remote['name'];
        $info->slug          = dirname( $this->plugin_basename );
        $info->version       = $remote['version'];
        $info->author        = $remote['author'];
        $info->homepage      = $remote['homepage'];
        $info->requires      = $remote['requires'];
        $info->requires_php  = $remote['requires_php'];
        $info->tested        = $remote['tested'];
        $info->last_updated  = gmdate( 'Y-m-d H:i:s' );
        $info->sections      = $remote['sections'];
        $info->download_link = $remote['package'];
        $info->banners       = array();
        $info->icons         = array();

        return $info;
    }

    public function clear_cache_after_upgrade( $upgrader, $options ) {
        if ( empty( $options['action'] ) || 'update' !== $options['action'] || empty( $options['type'] ) || 'plugin' !== $options['type'] ) {
            return;
        }

        if ( ! empty( $options['plugins'] ) && in_array( $this->plugin_basename, (array) $options['plugins'], true ) ) {
            $this->clear_cached_remote_data();
        }
    }
}