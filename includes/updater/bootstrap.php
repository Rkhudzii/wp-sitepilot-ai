<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-recrm-plugin-updater.php';

add_action( 'plugins_loaded', function() {
    RECRM_Plugin_Updater::boot(
        array(
            'plugin_file' => RECRM_XML_IMPORT_FILE,
            'plugin_slug' => plugin_basename( RECRM_XML_IMPORT_FILE ),
            'version'     => RECRM_XML_IMPORT_VERSION,
            'cache_key'   => 'recrm_plugin_update_data',
            'cache_ttl'   => 6 * HOUR_IN_SECONDS,
            'version_url' => 'https://raw.githubusercontent.com/Rkhudzii/wp-sitepilot-ai/main/version.json',
            'details_url' => 'https://github.com/Rkhudzii/wp-sitepilot-ai',
        )
    );
} );
