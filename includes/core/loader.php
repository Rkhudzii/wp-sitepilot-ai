<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once RECRM_XML_IMPORT_PATH . 'includes/admin/settings-page.php';
require_once RECRM_XML_IMPORT_PATH . 'includes/admin/class-recrm-admin.php';

$recrm_bootstraps = array(
    'property' => RECRM_XML_IMPORT_PATH . 'modules/property/bootstrap.php',
    'filter'   => RECRM_XML_IMPORT_PATH . 'modules/filter/bootstrap.php',
    'seo'      => RECRM_XML_IMPORT_PATH . 'modules/seo/bootstrap.php',
    'import'   => RECRM_XML_IMPORT_PATH . 'modules/import/bootstrap.php',
);

foreach ( $recrm_bootstraps as $module_key => $module_file ) {
    if ( ! recrm_is_module_enabled( $module_key ) ) {
        continue;
    }

    if ( 'filter' === $module_key && ! recrm_is_module_enabled( 'property' ) ) {
        continue;
    }

    if ( file_exists( $module_file ) ) {
        require_once $module_file;
    }
}

if ( class_exists( 'RECRM_Admin' ) ) {
    RECRM_Admin::boot();
}
