<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'recrm_filter_admin_menu', 35 );
add_action( 'admin_init', 'recrm_filter_admin_handle_actions' );

function recrm_filter_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=property',
        'Фільтри',
        'Фільтри',
        'manage_options',
        'recrm-filter-presets',
        'recrm_filter_admin_render_page'
    );
}

function recrm_filter_admin_handle_actions() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) || empty( $_POST['recrm_filter_admin_action'] ) ) {
        return;
    }

    $action = sanitize_key( wp_unslash( $_POST['recrm_filter_admin_action'] ) );

    if ( 'save_preset' === $action ) {
        check_admin_referer( 'recrm_filter_save_preset', 'recrm_filter_nonce' );
        recrm_filter_admin_save_preset();
    }

    if ( 'delete_preset' === $action ) {
        check_admin_referer( 'recrm_filter_delete_preset', 'recrm_filter_nonce' );
        recrm_filter_admin_delete_preset();
    }
}

function recrm_filter_admin_save_preset() {
    $presets     = recrm_filter_get_presets();
    $fields      = recrm_filter_get_fields_schema();
    $original_id = isset( $_POST['preset_original_id'] ) ? sanitize_title( wp_unslash( $_POST['preset_original_id'] ) ) : '';
    $name        = isset( $_POST['preset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_name'] ) ) : '';
    $id_input    = isset( $_POST['preset_id'] ) ? sanitize_title( wp_unslash( $_POST['preset_id'] ) ) : '';
    $id          = $id_input ? $id_input : sanitize_title( $name );

    if ( '' === $id ) {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'post_type' => 'property',
                    'page'      => 'recrm-filter-presets',
                    'error'     => 'id',
                ),
                admin_url( 'edit.php' )
            )
        );
        exit;
    }

    if ( $original_id && $original_id !== $id && isset( $presets[ $original_id ] ) ) {
        unset( $presets[ $original_id ] );
    }

    $preset = recrm_filter_preset_defaults();

    $preset['name']           = $name ? $name : $id;
    $preset['id']             = $id;
    $preset['title']          = isset( $_POST['preset_title'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_title'] ) ) : '';
    $preset['subtitle']       = isset( $_POST['preset_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_subtitle'] ) ) : '';
    $preset['action_url']     = isset( $_POST['preset_action_url'] ) ? esc_url_raw( wp_unslash( $_POST['preset_action_url'] ) ) : '';
    $preset['posts_per_page'] = isset( $_POST['preset_posts_per_page'] ) ? max( 1, absint( $_POST['preset_posts_per_page'] ) ) : 12;
    $preset['show_form']      = ! empty( $_POST['preset_show_form'] ) ? '1' : '0';
    $preset['show_results']   = ! empty( $_POST['preset_show_results'] ) ? '1' : '0';
    $preset['show_reset']     = ! empty( $_POST['preset_show_reset'] ) ? '1' : '0';

    $enabled_fields = isset( $_POST['preset_fields'] ) && is_array( $_POST['preset_fields'] )
    ? array_values( array_intersect( array_map( 'sanitize_key', wp_unslash( $_POST['preset_fields'] ) ), array_keys( $fields ) ) )
    : array();

    $preset['fields'] = array();

    foreach ( $fields as $field_key => $field ) {
        $default_value = isset( $_POST['preset_defaults'][ $field_key ] )
            ? sanitize_text_field( wp_unslash( $_POST['preset_defaults'][ $field_key ] ) )
            : '';

        $is_enabled = in_array( $field_key, $enabled_fields, true );
        $is_hidden  = isset( $_POST['preset_hidden'][ $field_key ] );

        $preset['fields'][ $field_key ] = array(
            'enabled'         => $is_enabled ? '1' : '0',
            'visible'         => $is_hidden ? '0' : '1',
            'editable'        => '1',
            'default'         => $default_value,
            'allowed_options' => array(),
        );
    }

    $presets[ $id ] = $preset;
    recrm_filter_update_presets( $presets );

    wp_safe_redirect(
        add_query_arg(
            array(
                'post_type' => 'property',
                'page'      => 'recrm-filter-presets',
                'action'    => 'edit',
                'preset'    => $id,
                'saved'     => 1,
            ),
            admin_url( 'edit.php' )
        )
    );
    exit;
}

function recrm_filter_admin_delete_preset() {
    $id      = isset( $_POST['preset_id'] ) ? sanitize_title( wp_unslash( $_POST['preset_id'] ) ) : '';
    $presets = recrm_filter_get_presets();

    if ( $id && isset( $presets[ $id ] ) ) {
        unset( $presets[ $id ] );
        recrm_filter_update_presets( $presets );
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'post_type' => 'property',
                'page'      => 'recrm-filter-presets',
                'deleted'   => 1,
            ),
            admin_url( 'edit.php' )
        )
    );
    exit;
}

function recrm_filter_admin_render_page() {
    $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
    $id     = isset( $_GET['preset'] ) ? sanitize_title( wp_unslash( $_GET['preset'] ) ) : '';

    echo '<div class="wrap">';
    echo '<h1 style="display:flex;justify-content:space-between;align-items:center;gap:12px;"><span>Фільтри</span><a class="page-title-action" href="' . esc_url( add_query_arg( array( 'post_type' => 'property', 'page' => 'recrm-filter-presets', 'action' => 'edit' ), admin_url( 'edit.php' ) ) ) . '">Створити пресет</a></h1>';

    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Пресет збережено.</p></div>';
    }
    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Пресет видалено.</p></div>';
    }
    if ( isset( $_GET['error'] ) ) {
        echo '<div class="notice notice-error is-dismissible"><p>Заповни назву або slug пресета.</p></div>';
    }

    if ( 'edit' === $action ) {
        $preset = $id ? recrm_filter_get_preset( $id ) : recrm_filter_preset_defaults();
        recrm_filter_admin_render_edit( $preset );
    } else {
        recrm_filter_admin_render_list();
    }

    echo '</div>';
}

function recrm_filter_admin_render_list() {
    $presets = recrm_filter_get_presets();

    echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px 20px;margin-top:16px;max-width:1100px;">';
    echo '<p style="margin-top:0;">Створи окремий пресет для кожної сторінки: квартири, оренда, будинки, комерція.</p>';

    if ( empty( $presets ) ) {
        echo '<p style="margin-bottom:0;">Поки що пресетів немає.</p></div>';
        return;
    }

    echo '<table class="widefat striped"><thead><tr><th>Назва</th><th>Slug</th><th>Шорткод</th><th>Дії</th></tr></thead><tbody>';

    foreach ( $presets as $id => $preset ) {
        $edit_url = add_query_arg(
            array(
                'post_type' => 'property',
                'page'      => 'recrm-filter-presets',
                'action'    => 'edit',
                'preset'    => $id,
            ),
            admin_url( 'edit.php' )
        );

        echo '<tr>';
        echo '<td><strong>' . esc_html( $preset['name'] ) . '</strong></td>';
        echo '<td><code>' . esc_html( $id ) . '</code></td>';
        echo '<td><code>[recrm_filter id="' . esc_html( $id ) . '"]</code></td>';
        echo '<td><a class="button button-secondary" href="' . esc_url( $edit_url ) . '">Редагувати</a> ';
        echo '<form method="post" style="display:inline-block;margin-left:6px;">';
        wp_nonce_field( 'recrm_filter_delete_preset', 'recrm_filter_nonce' );
        echo '<input type="hidden" name="recrm_filter_admin_action" value="delete_preset">';
        echo '<input type="hidden" name="preset_id" value="' . esc_attr( $id ) . '">';
        echo '<button type="submit" class="button button-link-delete" onclick="return confirm(\'Видалити пресет?\');">Видалити</button></form></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

function recrm_filter_admin_render_default_input( $field_key, $field, $value ) {
    if ( 'select' === $field['type'] ) {
        echo '<select name="preset_defaults[' . esc_attr( $field_key ) . ']">';

        foreach ( $field['options'] as $option_value => $option_label ) {
            echo '<option value="' . esc_attr( $option_value ) . '"' . selected( (string) $value, (string) $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
        }

        echo '</select>';
        return;
    }

    echo '<input type="number" step="any" min="0" name="preset_defaults[' . esc_attr( $field_key ) . ']" value="' . esc_attr( $value ) . '">';
}

function recrm_filter_admin_render_edit( $preset ) {
    $fields = recrm_filter_get_fields_schema();
    $is_new = empty( $preset['id'] );

    $preset['fields'] = isset( $preset['fields'] ) && is_array( $preset['fields'] ) ? $preset['fields'] : array();

    echo '<form method="post" style="max-width:1220px;margin-top:18px;">';
    wp_nonce_field( 'recrm_filter_save_preset', 'recrm_filter_nonce' );
    echo '<input type="hidden" name="recrm_filter_admin_action" value="save_preset">';
    echo '<input type="hidden" name="preset_original_id" value="' . esc_attr( $preset['id'] ) . '">';

    echo '<div style="display:grid;grid-template-columns:minmax(320px,1fr) minmax(420px,1fr);gap:24px;align-items:start;">';

    echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:20px;">';
    echo '<h2 style="margin-top:0;">' . ( $is_new ? 'Новий пресет' : 'Редагування пресета' ) . '</h2>';
    echo '<table class="form-table" role="presentation">';
    echo '<tr><th><label for="preset_name">Назва</label></th><td><input class="regular-text" type="text" id="preset_name" name="preset_name" value="' . esc_attr( $preset['name'] ) . '" placeholder="Продаж квартир"></td></tr>';
    echo '<tr><th><label for="preset_id">Slug / ID</label></th><td><input class="regular-text" type="text" id="preset_id" name="preset_id" value="' . esc_attr( $preset['id'] ) . '" placeholder="kvartyry-prodazh"><p class="description">Використовується у shortcode.</p></td></tr>';
    echo '<tr><th><label for="preset_title">Заголовок</label></th><td><input class="regular-text" type="text" id="preset_title" name="preset_title" value="' . esc_attr( $preset['title'] ) . '"></td></tr>';
    echo '<tr><th><label for="preset_subtitle">Підзаголовок</label></th><td><input class="regular-text" type="text" id="preset_subtitle" name="preset_subtitle" value="' . esc_attr( $preset['subtitle'] ) . '"></td></tr>';
    echo '<tr><th><label for="preset_action_url">Action URL</label></th><td><input class="regular-text" type="url" id="preset_action_url" name="preset_action_url" value="' . esc_attr( $preset['action_url'] ) . '" placeholder="https://site.com/kvartyry/"></td></tr>';
    echo '<tr><th><label for="preset_posts_per_page">Обʼєктів на сторінку</label></th><td><input type="number" min="1" id="preset_posts_per_page" name="preset_posts_per_page" value="' . esc_attr( (int) $preset['posts_per_page'] ) . '"></td></tr>';
    echo '</table>';

    echo '<p><label><input type="checkbox" name="preset_show_form" value="1"' . checked( $preset['show_form'], '1', false ) . '> Показувати форму</label><br>';
    echo '<label><input type="checkbox" name="preset_show_results" value="1"' . checked( $preset['show_results'], '1', false ) . '> Показувати результати</label><br>';
    echo '<label><input type="checkbox" name="preset_show_reset" value="1"' . checked( $preset['show_reset'], '1', false ) . '> Показувати кнопку скидання</label></p>';

    if ( ! empty( $preset['id'] ) ) {
        echo '<div style="margin-top:14px;padding:12px 14px;border:1px dashed #c3c4c7;border-radius:10px;background:#f6f7f7;"><strong>Шорткод:</strong> <code>[recrm_filter id="' . esc_html( $preset['id'] ) . '"]</code></div>';
    }

    echo '<p style="margin-top:18px;"><button type="submit" class="button button-primary">Зберегти пресет</button> <a class="button button-secondary" href="' . esc_url( add_query_arg( array( 'post_type' => 'property', 'page' => 'recrm-filter-presets' ), admin_url( 'edit.php' ) ) ) . '">Назад</a></p>';
    echo '</div>';

    echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:20px;">';
    echo '<h2 style="margin-top:0;">Поля та значення за замовчуванням</h2>';
    echo '<p>Увімкни тільки ті поля, які мають бути на конкретній сторінці. Default значення одразу задають потрібний контекст фільтра.</p>';

    foreach ( $fields as $field_key => $field ) {
        $field_config = isset( $preset['fields'][ $field_key ] ) && is_array( $preset['fields'][ $field_key ] )
            ? $preset['fields'][ $field_key ]
            : array();

        $enabled = isset( $field_config['enabled'] ) && '1' === (string) $field_config['enabled'];
        $default = isset( $field_config['default'] ) ? $field_config['default'] : '';
        $hidden  = ( isset( $field_config['visible'] ) && '0' === (string) $field_config['visible'] ) ? '1' : '0';

        echo '<div style="border:1px solid #dcdcde;border-radius:12px;padding:14px;margin-bottom:14px;">';

        echo '<p style="margin:0 0 10px;"><label><input type="checkbox" name="preset_fields[]" value="' . esc_attr( $field_key ) . '"' . checked( $enabled, true, false ) . '> <strong>' . esc_html( $field['label'] ) . '</strong></label></p>';

        echo '<div style="margin-bottom:10px;">';
        echo '<label style="display:block;font-weight:600;margin-bottom:6px;">Default значення</label>';
        recrm_filter_admin_render_default_input( $field_key, $field, $default );
        echo '</div>';

        echo '<label style="display:block;">';
        echo '<input type="checkbox" name="preset_hidden[' . esc_attr( $field_key ) . ']" value="1"' . checked( $hidden, '1', false ) . '> ';
        echo 'Не відображати (але застосовувати)';
        echo '</label>';

        echo '</div>';
    }

    echo '</div></div></form>';
}