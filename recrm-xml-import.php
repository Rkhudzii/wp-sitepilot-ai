<?php
/**
 * Plugin Name: RE CRM XML Import
 * Plugin URI: https://example.com/
 * Description: Модульний плагін для нерухомості: property, filter, SEO та import.
 * Version: 2.0.4
 * Author: Roman
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RECRM_XML_IMPORT_PATH', plugin_dir_path( __FILE__ ) );
define( 'RECRM_XML_IMPORT_URL', plugin_dir_url( __FILE__ ) );
define( 'RECRM_XML_IMPORT_VERSION', '2.0.4' );

require_once RECRM_XML_IMPORT_PATH . 'includes/core/helpers.php';
require_once RECRM_XML_IMPORT_PATH . 'includes/core/loader.php';
