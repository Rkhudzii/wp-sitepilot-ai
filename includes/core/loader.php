<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once RECRM_XML_IMPORT_PATH . 'includes/admin/settings-page.php';
require_once RECRM_XML_IMPORT_PATH . 'includes/admin/class-recrm-admin.php';

$recrm_module_registry = recrm_get_module_registry();

foreach ( $recrm_module_registry as $module_key => $module_data ) {
    if ( ! recrm_is_module_enabled( $module_key ) ) {
        continue;
    }

    $module_file = RECRM_XML_IMPORT_PATH . 'modules/' . sanitize_key( $module_key ) . '/bootstrap.php';
    if ( file_exists( $module_file ) ) {
        require_once $module_file;
    }
}

if ( class_exists( 'RECRM_Admin' ) ) {
    RECRM_Admin::boot();
}
