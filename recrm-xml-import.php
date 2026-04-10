<?php
/**
 * Plugin Name: WP SitePilot AI
 * Plugin URI: https://github.com/Rkhudzii/wp-sitepilot-ai
 * Description: Модульний плагін для нерухомості з GitHub-first ядром, самооновленням і менеджером модулів.
 * Version: 2.4.2
 * Author: Roman
 * License: GPL2+
 * Text Domain: wp-sitepilot-ai
 * Update URI: https://github.com/Rkhudzii/wp-sitepilot-ai
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPSP_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSP_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSP_AI_VERSION', '2.4.2' );
define( 'WPSP_AI_GITHUB_REPO', 'Rkhudzii/wp-sitepilot-ai' );
define( 'WPSP_AI_GITHUB_BRANCH', 'main' );

/* Сумісність зі старим кодом */
define( 'RECRM_XML_IMPORT_PATH', WPSP_AI_PATH );
define( 'RECRM_XML_IMPORT_URL', WPSP_AI_URL );
define( 'RECRM_XML_IMPORT_VERSION', WPSP_AI_VERSION );
define( 'RECRM_XML_IMPORT_GITHUB_REPO', WPSP_AI_GITHUB_REPO );
define( 'RECRM_XML_IMPORT_GITHUB_BRANCH', WPSP_AI_GITHUB_BRANCH );

require_once WPSP_AI_PATH . 'includes/core/helpers.php';
require_once WPSP_AI_PATH . 'includes/core/module-manager.php';
require_once WPSP_AI_PATH . 'includes/core/github-updater.php';
RECRM_GitHub_Updater::boot();
require_once WPSP_AI_PATH . 'includes/core/loader.php';