<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_post_recrm_manage_module', 'recrm_handle_manage_module_action' );
add_action( 'admin_notices', 'recrm_render_module_manager_notice' );

function recrm_get_github_module_source() {
    $repo_string = defined( 'RECRM_XML_IMPORT_GITHUB_REPO' ) ? RECRM_XML_IMPORT_GITHUB_REPO : 'Rkhudzii/wp-sitepilot-ai';
    $repo_parts  = array_pad( explode( '/', $repo_string, 2 ), 2, '' );

    $config = array(
        'owner'  => $repo_parts[0],
        'repo'   => $repo_parts[1],
        'branch' => defined( 'RECRM_XML_IMPORT_GITHUB_BRANCH' ) ? RECRM_XML_IMPORT_GITHUB_BRANCH : 'main',
    );

    return apply_filters( 'recrm_github_module_source', $config );
}

function recrm_get_module_local_dir( $module_key ) {
    return trailingslashit( RECRM_XML_IMPORT_PATH . 'modules/' . sanitize_key( $module_key ) );
}

function recrm_get_module_bootstrap_file( $module_key ) {
    return recrm_get_module_local_dir( $module_key ) . 'bootstrap.php';
}

function recrm_is_module_installed( $module_key ) {
    $registry = function_exists( 'recrm_get_manageable_module_registry' ) ? recrm_get_manageable_module_registry() : recrm_get_module_registry();

    if ( ! isset( $registry[ $module_key ] ) ) {
        return false;
    }

    if ( ! empty( $registry[ $module_key ]['always_on'] ) ) {
        return true;
    }

    return file_exists( recrm_get_module_bootstrap_file( $module_key ) );
}

function recrm_get_module_github_path( $module_key ) {
    $registry = recrm_get_module_registry();

    if ( empty( $registry[ $module_key ]['github_path'] ) ) {
        return '';
    }

    return trim( (string) $registry[ $module_key ]['github_path'], '/' );
}

function recrm_is_module_github_managed( $module_key ) {
    $registry = function_exists( 'recrm_get_manageable_module_registry' ) ? recrm_get_manageable_module_registry() : recrm_get_module_registry();
    return ! empty( $registry[ $module_key ]['github_managed'] );
}

function recrm_get_manage_module_url( $task, $module_key ) {
    $args = array(
        'action'     => 'recrm_manage_module',
        'task'       => sanitize_key( $task ),
        'module_key' => sanitize_key( $module_key ),
    );

    $url = add_query_arg( $args, admin_url( 'admin-post.php' ) );

    return wp_nonce_url( $url, 'recrm_manage_module_' . $task . '_' . $module_key );
}

function recrm_set_module_manager_notice( $type, $message ) {
    set_transient(
        'recrm_module_manager_notice',
        array(
            'type'    => sanitize_key( $type ),
            'message' => wp_strip_all_tags( (string) $message ),
        ),
        MINUTE_IN_SECONDS
    );
}

function recrm_render_module_manager_notice() {
    if ( ! is_admin() ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 're-crm_page_recrm-settings' !== $screen->id ) {
        return;
    }

    $notice = get_transient( 'recrm_module_manager_notice' );
    if ( empty( $notice['message'] ) ) {
        return;
    }

    delete_transient( 'recrm_module_manager_notice' );

    $class = 'notice notice-info is-dismissible';
    if ( 'success' === $notice['type'] ) {
        $class = 'notice notice-success is-dismissible';
    } elseif ( 'error' === $notice['type'] ) {
        $class = 'notice notice-error is-dismissible';
    }

    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $notice['message'] ) );
}

function recrm_handle_manage_module_action() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Недостатньо прав.', 'recrm' ) );
    }

    $task       = isset( $_GET['task'] ) ? sanitize_key( wp_unslash( $_GET['task'] ) ) : '';
    $module_key = isset( $_GET['module_key'] ) ? sanitize_key( wp_unslash( $_GET['module_key'] ) ) : '';

    check_admin_referer( 'recrm_manage_module_' . $task . '_' . $module_key );

    $redirect = admin_url( 'admin.php?page=recrm-settings' );

    $registry = function_exists( 'recrm_get_manageable_module_registry' ) ? recrm_get_manageable_module_registry() : recrm_get_module_registry();
    if ( empty( $task ) || empty( $module_key ) || ! isset( $registry[ $module_key ] ) ) {
        recrm_set_module_manager_notice( 'error', 'Не вдалося визначити модуль.' );
        wp_safe_redirect( $redirect );
        exit;
    }

    if ( ! recrm_is_module_github_managed( $module_key ) ) {
        recrm_set_module_manager_notice( 'error', 'Цей модуль не керується через GitHub.' );
        wp_safe_redirect( $redirect );
        exit;
    }

    if ( 'install' === $task || 'sync' === $task ) {
        $result = recrm_install_module_from_github( $module_key, 'sync' === $task );
        if ( is_wp_error( $result ) ) {
            recrm_set_module_manager_notice( 'error', $result->get_error_message() );
        } else {
            if ( function_exists( 'recrm_clear_remote_module_registry_cache' ) ) {
                recrm_clear_remote_module_registry_cache();
            }
            recrm_set_module_manager_notice( 'success', sprintf( 'Модуль "%s" завантажено з GitHub.', $registry[ $module_key ]['label'] ) );
        }
    } elseif ( 'delete' === $task ) {
        $result = recrm_delete_local_module( $module_key );
        if ( is_wp_error( $result ) ) {
            recrm_set_module_manager_notice( 'error', $result->get_error_message() );
        } else {
            if ( function_exists( 'recrm_clear_remote_module_registry_cache' ) ) {
                recrm_clear_remote_module_registry_cache();
            }
            recrm_set_module_manager_notice( 'success', sprintf( 'Модуль "%s" видалено з сайту.', $registry[ $module_key ]['label'] ) );
        }
    } else {
        recrm_set_module_manager_notice( 'error', 'Невідома дія для модуля.' );
    }

    wp_safe_redirect( $redirect );
    exit;
}

function recrm_install_module_from_github( $module_key, $force_sync = false ) {
    $module_key = sanitize_key( $module_key );

    if ( ! recrm_is_module_github_managed( $module_key ) ) {
        return new WP_Error( 'recrm_module_not_remote', 'Модуль не підтримує встановлення з GitHub.' );
    }

    $github_path = recrm_get_module_github_path( $module_key );
    if ( '' === $github_path ) {
        return new WP_Error( 'recrm_module_missing_path', 'Не знайдено шлях до модуля в GitHub.' );
    }

    $module_root = untrailingslashit( recrm_get_module_local_dir( $module_key ) );
    $modules_dir = untrailingslashit( RECRM_XML_IMPORT_PATH . 'modules' );

    if ( ! file_exists( $modules_dir ) && ! wp_mkdir_p( $modules_dir ) ) {
        return new WP_Error( 'recrm_module_dir_create_failed', 'Не вдалося створити папку modules.' );
    }

    if ( file_exists( $module_root ) && ! is_writable( $module_root ) ) {
        return new WP_Error( 'recrm_module_dir_not_writable', 'Папка модуля недоступна для запису.' );
    }

    if ( ! file_exists( $module_root ) && ! is_writable( $modules_dir ) ) {
        return new WP_Error( 'recrm_modules_parent_not_writable', 'Папка modules недоступна для запису.' );
    }

    $files = recrm_github_collect_module_files( $github_path );
    if ( is_wp_error( $files ) ) {
        return $files;
    }

    if ( empty( $files ) ) {
        return new WP_Error( 'recrm_module_empty', 'GitHub не повернув файлів для цього модуля.' );
    }

    $temp_root = $modules_dir . '/.tmp-' . $module_key . '-' . wp_generate_password( 8, false, false );
    if ( file_exists( $temp_root ) ) {
        recrm_recursive_delete_path( $temp_root );
    }

    if ( ! wp_mkdir_p( $temp_root ) ) {
        return new WP_Error( 'recrm_temp_dir_failed', 'Не вдалося створити тимчасову папку для модуля.' );
    }

    foreach ( $files as $file ) {
        $relative_path = ltrim( str_replace( array( '../', '..\\' ), '', (string) $file['relative_path'] ), '/' );
        if ( '' === $relative_path ) {
            continue;
        }

        $destination = $temp_root . '/' . $relative_path;
        $destination_dir = dirname( $destination );

        if ( ! file_exists( $destination_dir ) && ! wp_mkdir_p( $destination_dir ) ) {
            recrm_recursive_delete_path( $temp_root );
            return new WP_Error( 'recrm_temp_subdir_failed', 'Не вдалося створити вкладену папку модуля.' );
        }

        $download = recrm_github_download_file_contents( $file['download_url'] );
        if ( is_wp_error( $download ) ) {
            recrm_recursive_delete_path( $temp_root );
            return $download;
        }

        if ( false === file_put_contents( $destination, $download ) ) {
            recrm_recursive_delete_path( $temp_root );
            return new WP_Error( 'recrm_temp_write_failed', sprintf( 'Не вдалося записати файл %s.', basename( $relative_path ) ) );
        }
    }

    if ( ! file_exists( $temp_root . '/bootstrap.php' ) ) {
        recrm_recursive_delete_path( $temp_root );
        return new WP_Error( 'recrm_bootstrap_missing', 'У модулі немає bootstrap.php.' );
    }

    if ( file_exists( $module_root ) ) {
        if ( ! $force_sync && recrm_is_module_installed( $module_key ) ) {
            recrm_recursive_delete_path( $temp_root );
            return new WP_Error( 'recrm_module_exists', 'Модуль уже встановлений.' );
        }

        $deleted = recrm_recursive_delete_path( $module_root );
        if ( ! $deleted ) {
            recrm_recursive_delete_path( $temp_root );
            return new WP_Error( 'recrm_module_delete_failed', 'Не вдалося очистити стару версію модуля.' );
        }
    }

    if ( ! @rename( $temp_root, $module_root ) ) {
        $copied = recrm_recursive_copy_path( $temp_root, $module_root );
        recrm_recursive_delete_path( $temp_root );
        if ( ! $copied ) {
            return new WP_Error( 'recrm_module_move_failed', 'Не вдалося перенести модуль у папку plugins.' );
        }
    }

    recrm_update_module_settings( array( $module_key => '1' ) );

    return true;
}

function recrm_delete_local_module( $module_key ) {
    $module_key = sanitize_key( $module_key );

    if ( ! recrm_is_module_github_managed( $module_key ) ) {
        return new WP_Error( 'recrm_module_not_remote', 'Цей модуль не можна видалити через менеджер.' );
    }

    $module_root = untrailingslashit( recrm_get_module_local_dir( $module_key ) );

    if ( ! file_exists( $module_root ) ) {
        recrm_update_module_settings( array( $module_key => '0' ) );
        return true;
    }

    if ( ! recrm_recursive_delete_path( $module_root ) ) {
        return new WP_Error( 'recrm_module_delete_failed', 'Не вдалося видалити папку модуля.' );
    }

    recrm_update_module_settings( array( $module_key => '0' ) );

    return true;
}

function recrm_github_collect_module_files( $repo_path, $relative_prefix = '' ) {
    $items = recrm_github_api_request( $repo_path );
    if ( is_wp_error( $items ) ) {
        return $items;
    }

    if ( isset( $items['type'] ) && 'file' === $items['type'] ) {
        return array(
            array(
                'relative_path' => ltrim( $relative_prefix . $items['name'], '/' ),
                'download_url'  => $items['download_url'],
            ),
        );
    }

    if ( ! is_array( $items ) ) {
        return new WP_Error( 'recrm_github_invalid_response', 'GitHub повернув неочікувану відповідь.' );
    }

    $files = array();

    foreach ( $items as $item ) {
        if ( empty( $item['type'] ) || empty( $item['name'] ) ) {
            continue;
        }

        if ( 'file' === $item['type'] && ! empty( $item['download_url'] ) ) {
            $files[] = array(
                'relative_path' => ltrim( $relative_prefix . $item['name'], '/' ),
                'download_url'  => $item['download_url'],
            );
            continue;
        }

        if ( 'dir' === $item['type'] && ! empty( $item['path'] ) ) {
            $child_files = recrm_github_collect_module_files( $item['path'], $relative_prefix . $item['name'] . '/' );
            if ( is_wp_error( $child_files ) ) {
                return $child_files;
            }

            $files = array_merge( $files, $child_files );
        }
    }

    return $files;
}

function recrm_github_api_request( $repo_path ) {
    $source = recrm_get_github_module_source();

    $url = sprintf(
        'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
        rawurlencode( $source['owner'] ),
        rawurlencode( $source['repo'] ),
        implode( '/', array_map( 'rawurlencode', explode( '/', trim( $repo_path, '/' ) ) ) ),
        rawurlencode( $source['branch'] )
    );

    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 20,
            'headers' => array(
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'RECRM-Module-Manager/' . RECRM_XML_IMPORT_VERSION,
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'recrm_github_request_failed', 'Не вдалося підключитися до GitHub.' );
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    if ( 200 !== $code ) {
        if ( 403 === $code ) {
            return new WP_Error( 'recrm_github_forbidden', 'GitHub тимчасово відхилив запит. Спробуй ще раз трохи пізніше.' );
        }

        if ( 404 === $code ) {
            return new WP_Error( 'recrm_github_not_found', 'Не знайдено модуль або гілку в GitHub.' );
        }

        return new WP_Error( 'recrm_github_http_error', 'GitHub повернув помилку при отриманні файлів модуля.' );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return new WP_Error( 'recrm_github_invalid_json', 'GitHub повернув некоректну JSON-відповідь.' );
    }

    return $data;
}

function recrm_github_download_file_contents( $download_url ) {
    $host = wp_parse_url( $download_url, PHP_URL_HOST );
    if ( ! in_array( $host, array( 'raw.githubusercontent.com', 'github.com' ), true ) ) {
        return new WP_Error( 'recrm_invalid_download_host', 'Отримано недозволене джерело файлу.' );
    }

    $response = wp_remote_get(
        $download_url,
        array(
            'timeout' => 20,
            'headers' => array(
                'User-Agent' => 'RECRM-Module-Manager/' . RECRM_XML_IMPORT_VERSION,
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'recrm_download_failed', 'Не вдалося завантажити файл модуля з GitHub.' );
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    if ( 200 !== $code ) {
        return new WP_Error( 'recrm_download_http_error', 'GitHub повернув помилку при завантаженні файлу модуля.' );
    }

    return (string) wp_remote_retrieve_body( $response );
}

function recrm_recursive_delete_path( $path ) {
    if ( ! file_exists( $path ) ) {
        return true;
    }

    if ( is_file( $path ) || is_link( $path ) ) {
        return @unlink( $path );
    }

    $items = scandir( $path );
    if ( false === $items ) {
        return false;
    }

    foreach ( $items as $item ) {
        if ( '.' === $item || '..' === $item ) {
            continue;
        }

        if ( ! recrm_recursive_delete_path( $path . DIRECTORY_SEPARATOR . $item ) ) {
            return false;
        }
    }

    return @rmdir( $path );
}

function recrm_recursive_copy_path( $source, $destination ) {
    if ( is_file( $source ) ) {
        $destination_dir = dirname( $destination );
        if ( ! file_exists( $destination_dir ) && ! wp_mkdir_p( $destination_dir ) ) {
            return false;
        }

        return copy( $source, $destination );
    }

    if ( ! file_exists( $destination ) && ! wp_mkdir_p( $destination ) ) {
        return false;
    }

    $items = scandir( $source );
    if ( false === $items ) {
        return false;
    }

    foreach ( $items as $item ) {
        if ( '.' === $item || '..' === $item ) {
            continue;
        }

        $from = $source . DIRECTORY_SEPARATOR . $item;
        $to   = $destination . DIRECTORY_SEPARATOR . $item;

        if ( is_dir( $from ) ) {
            if ( ! recrm_recursive_copy_path( $from, $to ) ) {
                return false;
            }
        } else {
            if ( ! copy( $from, $to ) ) {
                return false;
            }
        }
    }

    return true;
}
