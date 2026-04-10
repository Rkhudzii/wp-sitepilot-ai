<?php
/**
 * Plugin Name: RE CRM XML Import
 * Plugin URI: https://example.com/
 * Description: Модульний плагін для нерухомості з GitHub-first ядром, самооновленням і менеджером модулів.
 * Version: 2.4.1
 * Author: Roman
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RECRM_XML_IMPORT_PATH', plugin_dir_path( __FILE__ ) );
define( 'RECRM_XML_IMPORT_URL', plugin_dir_url( __FILE__ ) );
define( 'RECRM_XML_IMPORT_VERSION', '2.4.0' );
define( 'RECRM_XML_IMPORT_GITHUB_REPO', 'Rkhudzii/wp-sitepilot-ai' );
define( 'RECRM_XML_IMPORT_GITHUB_BRANCH', 'main' );

require_once RECRM_XML_IMPORT_PATH . 'includes/core/helpers.php';
require_once RECRM_XML_IMPORT_PATH . 'includes/core/module-manager.php';
require_once RECRM_XML_IMPORT_PATH . 'includes/core/github-updater.php';
RECRM_GitHub_Updater::boot();
require_once RECRM_XML_IMPORT_PATH . 'includes/core/loader.php';
