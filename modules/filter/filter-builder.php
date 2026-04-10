<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'recrm_filter_builder_register_menu', 35 );
add_action( 'admin_init', 'recrm_filter_builder_handle_actions' );
add_shortcode( 'recrm_filter', 'recrm_filter_builder_shortcode' );
add_filter( 'posts_clauses', 'recrm_filter_builder_exclusive_first_clauses', 10, 2 );

function recrm_filter_builder_register_menu() {
    add_submenu_page(
        'edit.php?post_type=property',
        'Фільтри',
        'Фільтри',
        'manage_options',
        'recrm-filter-builder',
        'recrm_filter_builder_render_admin_page'
    );
}

function recrm_filter_builder_option_key() {
    return 'recrm_filter_builder_sets';
}

function recrm_filter_builder_get_all() {
    $items = get_option( recrm_filter_builder_option_key(), array() );

    if ( ! is_array( $items ) ) {
        $items = array();
    }

    uasort(
        $items,
        static function( $a, $b ) {
            $a_name = isset( $a['name'] ) ? (string) $a['name'] : '';
            $b_name = isset( $b['name'] ) ? (string) $b['name'] : '';
            return strnatcasecmp( $a_name, $b_name );
        }
    );

    return $items;
}

function recrm_filter_builder_update_all( $items ) {
    update_option( recrm_filter_builder_option_key(), $items, false );
}

function recrm_filter_builder_get( $id ) {
    $all = recrm_filter_builder_get_all();
    $id  = sanitize_title( (string) $id );

    return isset( $all[ $id ] ) && is_array( $all[ $id ] ) ? $all[ $id ] : array();
}

function recrm_filter_builder_default_field_config() {
    return array(
        'enabled'         => '0',
        'visible'         => '1',
        'editable'        => '1',
        'default'         => '',
        'allowed_options' => array(),
    );
}

function recrm_filter_builder_get_distinct_meta_options( $meta_key, $empty_label = '' ) {
    global $wpdb;

    $results = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND pm.meta_value <> ''
              AND p.post_type = 'property'
              AND p.post_status = 'publish'
            ORDER BY pm.meta_value ASC
            ",
            $meta_key
        )
    );

    $options = array();

    if ( '' !== $empty_label ) {
        $options[''] = $empty_label;
    }

    if ( ! empty( $results ) ) {
        foreach ( $results as $value ) {
            $value = trim( (string) $value );

            if ( '' === $value ) {
                continue;
            }

            $options[ $value ] = $value;
        }
    }

    return $options;
}

function recrm_filter_builder_get_distinct_numeric_options( $meta_key, $empty_label = '' ) {
    global $wpdb;

    $results = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) AS meta_number
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND pm.meta_value <> ''
              AND p.post_type = 'property'
              AND p.post_status = 'publish'
            ORDER BY meta_number ASC
            ",
            $meta_key
        )
    );

    $options = array();

    if ( '' !== $empty_label ) {
        $options[''] = $empty_label;
    }

    if ( ! empty( $results ) ) {
        foreach ( $results as $value ) {
            $value = absint( $value );

            if ( $value <= 0 ) {
                continue;
            }

            $options[ (string) $value ] = (string) $value;
        }
    }

    return $options;
}

function recrm_filter_builder_get_available_fields() {
    return array(
        'offer_type'    => array(
            'label'   => 'Операція',
            'type'    => 'select',
            'options' => function_exists( 'recrm_get_property_offer_options' ) ? recrm_get_property_offer_options() : array(),
        ),
        'property_type' => array(
            'label'   => 'Тип нерухомості',
            'type'    => 'select',
            'options' => function_exists( 'recrm_get_property_type_options' ) ? recrm_get_property_type_options() : array(),
        ),
        'city'          => array(
            'label'   => 'Місто',
            'type'    => 'select',
            'options' => recrm_filter_builder_get_distinct_meta_options( 'property_city', 'Всі міста' ),
        ),
        'district'      => array(
            'label'   => 'Район',
            'type'    => 'select',
            'options' => function_exists( 'recrm_get_property_district_options' ) ? recrm_get_property_district_options() : array(),
        ),
        'rooms'         => array(
            'label'   => 'Кімнат',
            'type'    => 'select',
            'options' => function_exists( 'recrm_get_property_room_options' ) ? recrm_get_property_room_options() : array(),
        ),
        'floor'         => array(
            'label'   => 'Поверх',
            'type'    => 'select',
            'options' => recrm_filter_builder_get_distinct_numeric_options( 'property_floor', 'Будь-який поверх' ),
        ),
        'floors_total'  => array(
            'label'   => 'Поверховість',
            'type'    => 'select',
            'options' => recrm_filter_builder_get_distinct_numeric_options( 'property_floors_total', 'Будь-яка поверховість' ),
        ),
        'condition'     => array(
            'label'   => 'Стан',
            'type'    => 'select',
            'options' => recrm_filter_builder_get_distinct_meta_options( 'property_condition', 'Будь-який стан' ),
        ),
        'heating'       => array(
            'label'   => 'Опалення',
            'type'    => 'select',
            'options' => recrm_filter_builder_get_distinct_meta_options( 'property_heating', 'Будь-яке опалення' ),
        ),
        'price_min'     => array(
            'label' => 'Ціна від',
            'type'  => 'number',
        ),
        'price_max'     => array(
            'label' => 'Ціна до',
            'type'  => 'number',
        ),
        'area_min'      => array(
            'label' => 'Площа від',
            'type'  => 'number',
        ),
        'area_max'      => array(
            'label' => 'Площа до',
            'type'  => 'number',
        ),
        'land_area_min' => array(
            'label' => 'Площа ділянки від',
            'type'  => 'number',
        ),
        'land_area_max' => array(
            'label' => 'Площа ділянки до',
            'type'  => 'number',
        ),
        'sort'          => array(
            'label'   => 'Сортування',
            'type'    => 'select',
            'options' => array(
                'date_desc'  => 'Спочатку нові',
                'date_asc'   => 'Спочатку старі',
                'price_asc'  => 'Ціна ↑',
                'price_desc' => 'Ціна ↓',
                'title_asc'  => 'По назві',
            ),
        ),
    );
}

function recrm_filter_builder_get_default_config() {
    $fields = array();

    foreach ( recrm_filter_builder_get_available_fields() as $field_key => $field_data ) {
        $fields[ $field_key ] = recrm_filter_builder_default_field_config();
    }

    return array(
        'name'           => '',
        'id'             => '',
        'title'          => 'Нерухомість',
        'subtitle'       => 'Підібрані обʼєкти нерухомості',
        'action_url'     => '',
        'show_form'      => '1',
        'show_results'   => '1',
        'show_reset'     => '1',
        'posts_per_page' => 12,
        'fields'         => $fields,
    );
}

function recrm_filter_builder_merge_config( $config ) {
    $config   = is_array( $config ) ? $config : array();
    $defaults = recrm_filter_builder_get_default_config();
    $merged   = wp_parse_args( $config, $defaults );

    $merged['fields'] = isset( $merged['fields'] ) && is_array( $merged['fields'] ) ? $merged['fields'] : array();

    foreach ( $defaults['fields'] as $field_key => $field_defaults ) {
        $field_value = isset( $merged['fields'][ $field_key ] ) && is_array( $merged['fields'][ $field_key ] )
            ? $merged['fields'][ $field_key ]
            : array();

        $merged['fields'][ $field_key ] = wp_parse_args( $field_value, $field_defaults );

        if ( empty( $merged['fields'][ $field_key ]['allowed_options'] ) || ! is_array( $merged['fields'][ $field_key ]['allowed_options'] ) ) {
            $merged['fields'][ $field_key ]['allowed_options'] = array();
        }

        $merged['fields'][ $field_key ]['enabled']  = '1' === (string) $merged['fields'][ $field_key ]['enabled'] ? '1' : '0';
        $merged['fields'][ $field_key ]['visible']  = '1' === (string) $merged['fields'][ $field_key ]['visible'] ? '1' : '0';
        $merged['fields'][ $field_key ]['editable'] = '1' === (string) $merged['fields'][ $field_key ]['editable'] ? '1' : '0';
        $merged['fields'][ $field_key ]['default']  = isset( $merged['fields'][ $field_key ]['default'] ) ? (string) $merged['fields'][ $field_key ]['default'] : '';

        if ( '0' === $merged['fields'][ $field_key ]['enabled'] ) {
            $merged['fields'][ $field_key ]['visible']         = '0';
            $merged['fields'][ $field_key ]['editable']        = '1';
            $merged['fields'][ $field_key ]['default']         = '';
            $merged['fields'][ $field_key ]['allowed_options'] = array();
        }
    }

    return $merged;
}

function recrm_filter_builder_handle_actions() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_POST['recrm_filter_builder_action'] ) ) {
        return;
    }

    $action = sanitize_key( wp_unslash( $_POST['recrm_filter_builder_action'] ) );

    if ( 'save_filter' === $action ) {
        check_admin_referer( 'recrm_filter_builder_save', 'recrm_filter_builder_nonce' );
        recrm_filter_builder_save_from_request();
    }

    if ( 'delete_filter' === $action ) {
        check_admin_referer( 'recrm_filter_builder_delete', 'recrm_filter_builder_nonce' );
        recrm_filter_builder_delete_from_request();
    }
}

function recrm_filter_builder_save_from_request() {
    $all = recrm_filter_builder_get_all();

    $original_id = isset( $_POST['filter_original_id'] ) ? sanitize_title( wp_unslash( $_POST['filter_original_id'] ) ) : '';
    $name        = isset( $_POST['filter_name'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_name'] ) ) : '';
    $raw_id      = isset( $_POST['filter_id'] ) ? sanitize_title( wp_unslash( $_POST['filter_id'] ) ) : '';
    $id          = $raw_id ? $raw_id : sanitize_title( $name );

    if ( '' === $id ) {
        add_settings_error( 'recrm_filter_builder', 'recrm_filter_builder_id', 'Вкажи назву або slug фільтра.', 'error' );
        return;
    }

    if ( $original_id && $original_id !== $id && isset( $all[ $original_id ] ) ) {
        unset( $all[ $original_id ] );
    }

    $config = recrm_filter_builder_get_default_config();
    $config['name']           = $name ? $name : $id;
    $config['id']             = $id;
    $config['title']          = isset( $_POST['filter_title'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_title'] ) ) : 'Нерухомість';
    $config['subtitle']       = isset( $_POST['filter_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_subtitle'] ) ) : '';
    $config['action_url']     = isset( $_POST['filter_action_url'] ) ? esc_url_raw( wp_unslash( $_POST['filter_action_url'] ) ) : '';
    $config['show_form']      = ! empty( $_POST['filter_show_form'] ) ? '1' : '0';
    $config['show_results']   = ! empty( $_POST['filter_show_results'] ) ? '1' : '0';
    $config['show_reset']     = ! empty( $_POST['filter_show_reset'] ) ? '1' : '0';
    $config['posts_per_page'] = isset( $_POST['filter_posts_per_page'] ) ? max( 1, absint( wp_unslash( $_POST['filter_posts_per_page'] ) ) ) : 12;

    $available_fields = recrm_filter_builder_get_available_fields();

    foreach ( $available_fields as $field_key => $field_data ) {
        $config['fields'][ $field_key ]['enabled']  = ! empty( $_POST['fields'][ $field_key ]['enabled'] ) ? '1' : '0';
        $config['fields'][ $field_key ]['visible']  = ! empty( $_POST['fields'][ $field_key ]['visible'] ) ? '1' : '0';
        $config['fields'][ $field_key ]['editable'] = ! empty( $_POST['fields'][ $field_key ]['editable'] ) ? '1' : '0';
        $config['fields'][ $field_key ]['default']  = isset( $_POST['fields'][ $field_key ]['default'] )
            ? sanitize_text_field( wp_unslash( $_POST['fields'][ $field_key ]['default'] ) )
            : '';

        $allowed_options = isset( $_POST['fields'][ $field_key ]['allowed_options'] ) && is_array( $_POST['fields'][ $field_key ]['allowed_options'] )
            ? array_map( 'sanitize_text_field', wp_unslash( $_POST['fields'][ $field_key ]['allowed_options'] ) )
            : array();

        $config['fields'][ $field_key ]['allowed_options'] = array_values( array_filter( $allowed_options, 'strlen' ) );

        if ( 'select' !== $field_data['type'] ) {
            $config['fields'][ $field_key ]['allowed_options'] = array();
        }

        if ( '0' === $config['fields'][ $field_key ]['enabled'] ) {
            $config['fields'][ $field_key ]['visible']         = '0';
            $config['fields'][ $field_key ]['editable']        = '1';
            $config['fields'][ $field_key ]['default']         = '';
            $config['fields'][ $field_key ]['allowed_options'] = array();
        }
    }

    $all[ $id ] = $config;
    recrm_filter_builder_update_all( $all );

    wp_safe_redirect(
        add_query_arg(
            array(
                'post_type' => 'property',
                'page'      => 'recrm-filter-builder',
                'action'    => 'edit',
                'filter'    => $id,
                'saved'     => '1',
            ),
            admin_url( 'edit.php' )
        )
    );
    exit;
}

function recrm_filter_builder_delete_from_request() {
    $id  = isset( $_POST['filter_id'] ) ? sanitize_title( wp_unslash( $_POST['filter_id'] ) ) : '';
    $all = recrm_filter_builder_get_all();

    if ( $id && isset( $all[ $id ] ) ) {
        unset( $all[ $id ] );
        recrm_filter_builder_update_all( $all );
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'post_type' => 'property',
                'page'      => 'recrm-filter-builder',
                'deleted'   => '1',
            ),
            admin_url( 'edit.php' )
        )
    );
    exit;
}

function recrm_filter_builder_get_field_options( $field_key, $field_data, $field_config = array() ) {
    $options = isset( $field_data['options'] ) && is_array( $field_data['options'] ) ? $field_data['options'] : array();

    if ( 'select' !== $field_data['type'] ) {
        return $options;
    }

    $field_config = wp_parse_args( is_array( $field_config ) ? $field_config : array(), recrm_filter_builder_default_field_config() );
    $allowed      = isset( $field_config['allowed_options'] ) && is_array( $field_config['allowed_options'] ) ? $field_config['allowed_options'] : array();

    if ( empty( $allowed ) ) {
        return $options;
    }

    $filtered = array();

    foreach ( $options as $option_value => $option_label ) {
        if ( '' === (string) $option_value || in_array( (string) $option_value, $allowed, true ) ) {
            $filtered[ $option_value ] = $option_label;
        }
    }

    return $filtered;
}

function recrm_filter_builder_get_empty_option_label( $field_data ) {
    if ( empty( $field_data['options'] ) || ! is_array( $field_data['options'] ) ) {
        return '';
    }

    return isset( $field_data['options'][''] ) ? (string) $field_data['options'][''] : '';
}

function recrm_filter_builder_get_source_without_field( $source, $exclude_field ) {
    $source = is_array( $source ) ? $source : array();

    if ( isset( $source[ $exclude_field ] ) ) {
        unset( $source[ $exclude_field ] );
    }

    return $source;
}

function recrm_filter_builder_get_option_query_args( $source ) {
    $args = function_exists( 'recrm_get_properties_query_args_from_source' )
        ? recrm_get_properties_query_args_from_source( $source )
        : array(
            'post_type'      => 'property',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'paged'          => 1,
        );

    $meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array( 'relation' => 'AND' );

    if ( ! empty( $source['city'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_city',
            'value'   => sanitize_text_field( $source['city'] ),
            'compare' => '=',
        );
    }

    if ( ! empty( $source['district'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_district',
            'value'   => sanitize_text_field( $source['district'] ),
            'compare' => '=',
        );
    }

    if ( ! empty( $source['rooms'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_rooms',
            'value'   => absint( $source['rooms'] ),
            'type'    => 'NUMERIC',
            'compare' => '=',
        );
    }

    if ( ! empty( $source['floor'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_floor',
            'value'   => absint( $source['floor'] ),
            'type'    => 'NUMERIC',
            'compare' => '=',
        );
    }

    if ( ! empty( $source['floors_total'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_floors_total',
            'value'   => absint( $source['floors_total'] ),
            'type'    => 'NUMERIC',
            'compare' => '=',
        );
    }

    if ( ! empty( $source['condition'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_condition',
            'value'   => sanitize_text_field( $source['condition'] ),
            'compare' => '=',
        );
    }

    if ( ! empty( $source['heating'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_heating',
            'value'   => sanitize_text_field( $source['heating'] ),
            'compare' => '=',
        );
    }

    if ( isset( $source['price_min'] ) && '' !== (string) $source['price_min'] ) {
        $meta_query[] = array(
            'key'     => 'price',
            'value'   => (float) $source['price_min'],
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ( isset( $source['price_max'] ) && '' !== (string) $source['price_max'] ) {
        $meta_query[] = array(
            'key'     => 'price',
            'value'   => (float) $source['price_max'],
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }

    if ( isset( $source['area_min'] ) && '' !== (string) $source['area_min'] ) {
        $meta_query[] = array(
            'key'     => 'property_area_total',
            'value'   => (float) $source['area_min'],
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ( isset( $source['area_max'] ) && '' !== (string) $source['area_max'] ) {
        $meta_query[] = array(
            'key'     => 'property_area_total',
            'value'   => (float) $source['area_max'],
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }

    if ( isset( $source['land_area_min'] ) && '' !== (string) $source['land_area_min'] ) {
        $meta_query[] = array(
            'key'     => 'property_land_area',
            'value'   => (float) $source['land_area_min'],
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ( isset( $source['land_area_max'] ) && '' !== (string) $source['land_area_max'] ) {
        $meta_query[] = array(
            'key'     => 'property_land_area',
            'value'   => (float) $source['land_area_max'],
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }

    if ( count( $meta_query ) > 1 ) {
        $args['meta_query'] = $meta_query;
    }

    $args['posts_per_page']         = -1;
    $args['paged']                  = 1;
    $args['fields']                 = 'ids';
    $args['no_found_rows']          = true;
    $args['update_post_meta_cache'] = false;
    $args['update_post_term_cache'] = false;
    $args['recrm_exclusive_first']  = 0;

    return $args;
}

function recrm_filter_builder_get_matching_post_ids_for_field( $source, $exclude_field = '' ) {
    $source = recrm_filter_builder_get_source_without_field( $source, $exclude_field );
    $args   = recrm_filter_builder_get_option_query_args( $source );
    $query  = new WP_Query( $args );

    if ( empty( $query->posts ) || ! is_array( $query->posts ) ) {
        return array();
    }

    return array_map( 'intval', $query->posts );
}

function recrm_filter_builder_filter_allowed_options( $options, $field_config ) {
    $field_config = wp_parse_args( is_array( $field_config ) ? $field_config : array(), recrm_filter_builder_default_field_config() );
    $allowed      = isset( $field_config['allowed_options'] ) && is_array( $field_config['allowed_options'] ) ? $field_config['allowed_options'] : array();

    if ( empty( $allowed ) ) {
        return $options;
    }

    $filtered = array();

    foreach ( $options as $option_value => $option_label ) {
        if ( '' === (string) $option_value || in_array( (string) $option_value, $allowed, true ) ) {
            $filtered[ $option_value ] = $option_label;
        }
    }

    return $filtered;
}

function recrm_filter_builder_get_dynamic_meta_options( $meta_key, $source, $exclude_field, $empty_label = '', $numeric = false ) {
    global $wpdb;

    $post_ids = recrm_filter_builder_get_matching_post_ids_for_field( $source, $exclude_field );

    $options = array();

    if ( '' !== $empty_label ) {
        $options[''] = $empty_label;
    }

    if ( empty( $post_ids ) ) {
        return $options;
    }

    $placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

    if ( $numeric ) {
        $sql = "
            SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) AS meta_number
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = %s
              AND pm.post_id IN ($placeholders)
              AND pm.meta_value <> ''
            ORDER BY meta_number ASC
        ";
    } else {
        $sql = "
            SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = %s
              AND pm.post_id IN ($placeholders)
              AND pm.meta_value <> ''
            ORDER BY pm.meta_value ASC
        ";
    }

    $params  = array_merge( array( $meta_key ), $post_ids );
    $results = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

    if ( ! empty( $results ) ) {
        foreach ( $results as $value ) {
            if ( $numeric ) {
                $value = absint( $value );

                if ( $value <= 0 ) {
                    continue;
                }

                $options[ (string) $value ] = (string) $value;
            } else {
                $value = trim( (string) $value );

                if ( '' === $value ) {
                    continue;
                }

                $options[ $value ] = $value;
            }
        }
    }

    return $options;
}

function recrm_filter_builder_get_dynamic_property_type_options( $source, $exclude_field = 'property_type', $empty_label = 'Всі типи' ) {
    $post_ids = recrm_filter_builder_get_matching_post_ids_for_field( $source, $exclude_field );

    $options = array(
        '' => $empty_label,
    );

    if ( empty( $post_ids ) ) {
        return $options;
    }

    $terms = wp_get_object_terms(
        $post_ids,
        'property_type',
        array(
            'orderby' => 'name',
            'order'   => 'ASC',
        )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return $options;
    }

    foreach ( $terms as $term ) {
        $options[ (string) $term->term_id ] = $term->name;
    }

    return $options;
}

function recrm_filter_builder_get_dynamic_offer_type_options( $source, $exclude_field = 'offer_type', $empty_label = 'Будь-яка операція' ) {
    global $wpdb;

    $post_ids = recrm_filter_builder_get_matching_post_ids_for_field( $source, $exclude_field );

    $options = array(
        '' => $empty_label,
    );

    if ( empty( $post_ids ) ) {
        return $options;
    }

    $placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

    $sql = "
        SELECT DISTINCT pm.meta_value
        FROM {$wpdb->postmeta} pm
        WHERE pm.meta_key = 'crm_deal'
          AND pm.post_id IN ($placeholders)
          AND pm.meta_value <> ''
        ORDER BY pm.meta_value ASC
    ";

    $results = $wpdb->get_col( $wpdb->prepare( $sql, $post_ids ) );

    if ( ! empty( $results ) ) {
        foreach ( $results as $value ) {
            $value = trim( (string) $value );

            if ( 'sale' === $value ) {
                $options['sale'] = 'Продаж';
            } elseif ( 'rent' === $value ) {
                $options['rent'] = 'Оренда';
            }
        }
    }

    return $options;
}

function recrm_filter_builder_get_dynamic_field_options( $field_key, $field_data, $field_config, $active_source ) {
    $empty_label = recrm_filter_builder_get_empty_option_label( $field_data );

    switch ( $field_key ) {
        case 'offer_type':
            $options = recrm_filter_builder_get_dynamic_offer_type_options( $active_source, 'offer_type', $empty_label ? $empty_label : 'Будь-яка операція' );
            break;

        case 'property_type':
            $options = recrm_filter_builder_get_dynamic_property_type_options( $active_source, 'property_type', $empty_label ? $empty_label : 'Всі типи' );
            break;

        case 'city':
            $options = recrm_filter_builder_get_dynamic_meta_options( 'property_city', $active_source, 'city', $empty_label ? $empty_label : 'Всі міста', false );
            break;

        case 'district':
            $options = recrm_filter_builder_get_dynamic_meta_options( 'property_district', $active_source, 'district', $empty_label ? $empty_label : 'Всі райони', false );
            break;

        case 'rooms':
            $options = recrm_filter_builder_get_dynamic_meta_options( 'property_rooms', $active_source, 'rooms', $empty_label ? $empty_label : 'Будь-яка кількість', true );
            foreach ( $options as $value => $label ) {
                if ( '' === (string) $value ) {
                    continue;
                }
                $options[ $value ] = $label . ' кімн.';
            }
            break;

        case 'floor':
            $options = recrm_filter_builder_get_dynamic_meta_options( 'property_floor', $active_source, 'floor', $empty_label ? $empty_label : 'Будь-який поверх', true );
            break;

        case 'floors_total':
            $options = recrm_filter_builder_get_dynamic_meta_options( 'property_floors_total', $active_source, 'floors_total', $empty_label ? $empty_label : 'Будь-яка поверховість', true );
            break;

        case 'condition':
            $options = recrm_filter_builder_get_dynamic_meta_options( 'property_condition', $active_source, 'condition', $empty_label ? $empty_label : 'Будь-який стан', false );
            break;

        case 'heating':
            $options = recrm_filter_builder_get_dynamic_meta_options( 'property_heating', $active_source, 'heating', $empty_label ? $empty_label : 'Будь-яке опалення', false );
            break;

        default:
            $options = recrm_filter_builder_get_field_options( $field_key, $field_data, $field_config );
            break;
    }

    return recrm_filter_builder_filter_allowed_options( $options, $field_config );
}

function recrm_filter_builder_render_admin_page() {
    $action    = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
    $filter_id = isset( $_GET['filter'] ) ? sanitize_title( wp_unslash( $_GET['filter'] ) ) : '';

    echo '<div class="wrap">';

    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Фільтр збережено.</p></div>';
    }

    if ( isset( $_GET['deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Фільтр видалено.</p></div>';
    }

    echo '<h1 style="display:flex;align-items:center;justify-content:space-between;gap:16px;">';
    echo '<span>Фільтри</span>';
    echo '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'property', 'page' => 'recrm-filter-builder', 'action' => 'edit' ), admin_url( 'edit.php' ) ) ) . '" class="page-title-action">Створити новий</a>';
    echo '</h1>';

    if ( 'edit' === $action ) {
        $config = $filter_id ? recrm_filter_builder_get( $filter_id ) : array();
        $config = recrm_filter_builder_merge_config( $config );
        recrm_filter_builder_render_edit_form( $config );
    } else {
        recrm_filter_builder_render_list_table();
    }

    echo '</div>';
}

function recrm_filter_builder_render_list_table() {
    $items = recrm_filter_builder_get_all();

    echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px 20px;margin-top:16px;max-width:1100px;">';
    echo '<p style="margin-top:0;">Створюй окремі пресети фільтра для різних сторінок.</p>';

    if ( empty( $items ) ) {
        echo '<p style="margin-bottom:0;">Поки що немає жодного фільтра.</p>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat striped" style="margin-top:14px;">';
    echo '<thead><tr><th>Назва</th><th>Slug</th><th>Шорткод</th><th>Дії</th></tr></thead><tbody>';

    foreach ( $items as $id => $config ) {
        $edit_url = add_query_arg(
            array(
                'post_type' => 'property',
                'page'      => 'recrm-filter-builder',
                'action'    => 'edit',
                'filter'    => $id,
            ),
            admin_url( 'edit.php' )
        );

        echo '<tr>';
        echo '<td><strong>' . esc_html( $config['name'] ) . '</strong></td>';
        echo '<td><code>' . esc_html( $id ) . '</code></td>';
        echo '<td><code>[recrm_filter id="' . esc_html( $id ) . '"]</code></td>';
        echo '<td>';
        echo '<a href="' . esc_url( $edit_url ) . '" class="button button-secondary">Редагувати</a> ';
        echo '<form method="post" style="display:inline-block;margin-left:6px;">';
        wp_nonce_field( 'recrm_filter_builder_delete', 'recrm_filter_builder_nonce' );
        echo '<input type="hidden" name="recrm_filter_builder_action" value="delete_filter">';
        echo '<input type="hidden" name="filter_id" value="' . esc_attr( $id ) . '">';
        echo '<button type="submit" class="button button-link-delete" onclick="return confirm(\'Видалити фільтр?\');">Видалити</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

function recrm_filter_builder_render_edit_form( $config ) {
    $available_fields = recrm_filter_builder_get_available_fields();
    $is_new           = empty( $config['id'] );
    ?>
    <form method="post" style="max-width:1280px;margin-top:18px;">
        <?php wp_nonce_field( 'recrm_filter_builder_save', 'recrm_filter_builder_nonce' ); ?>
        <input type="hidden" name="recrm_filter_builder_action" value="save_filter">
        <input type="hidden" name="filter_original_id" value="<?php echo esc_attr( $config['id'] ); ?>">

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:20px;margin-bottom:24px;">
            <h2 style="margin-top:0;"><?php echo $is_new ? 'Новий фільтр' : 'Редагування фільтра'; ?></h2>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="filter_name">Назва</label></th>
                    <td><input type="text" id="filter_name" name="filter_name" class="regular-text" value="<?php echo esc_attr( $config['name'] ); ?>" placeholder="Продаж квартир"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="filter_id">Slug / ID</label></th>
                    <td>
                        <input type="text" id="filter_id" name="filter_id" class="regular-text" value="<?php echo esc_attr( $config['id'] ); ?>" placeholder="kvartyry-prodazh">
                        <p class="description">Саме цей slug піде в шорткод.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="filter_title">Заголовок</label></th>
                    <td><input type="text" id="filter_title" name="filter_title" class="regular-text" value="<?php echo esc_attr( $config['title'] ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="filter_subtitle">Підзаголовок</label></th>
                    <td><input type="text" id="filter_subtitle" name="filter_subtitle" class="regular-text" value="<?php echo esc_attr( $config['subtitle'] ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="filter_action_url">Action URL</label></th>
                    <td><input type="url" id="filter_action_url" name="filter_action_url" class="regular-text" value="<?php echo esc_attr( $config['action_url'] ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="filter_posts_per_page">Обʼєктів на сторінку</label></th>
                    <td><input type="number" min="1" id="filter_posts_per_page" name="filter_posts_per_page" value="<?php echo esc_attr( (int) $config['posts_per_page'] ); ?>"></td>
                </tr>
            </table>

            <p>
                <label><input type="checkbox" name="filter_show_form" value="1" <?php checked( $config['show_form'], '1' ); ?>> Показувати форму</label><br>
                <label><input type="checkbox" name="filter_show_results" value="1" <?php checked( $config['show_results'], '1' ); ?>> Показувати результати</label><br>
                <label><input type="checkbox" name="filter_show_reset" value="1" <?php checked( $config['show_reset'], '1' ); ?>> Показувати кнопку скидання</label>
            </p>

            <?php if ( ! empty( $config['id'] ) ) : ?>
                <div style="margin-top:14px;padding:12px 14px;border:1px dashed #c3c4c7;border-radius:10px;background:#f6f7f7;">
                    <strong>Шорткод:</strong>
                    <code>[recrm_filter id="<?php echo esc_html( $config['id'] ); ?>"]</code>
                </div>
            <?php endif; ?>
        </div>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:20px;">
            <h2 style="margin-top:0;">Поля та значення за замовчуванням</h2>
            <p>Увімкни тільки ті поля, які мають бути на конкретній сторінці. Default значення одразу задають потрібний контекст фільтра.</p>

            <?php foreach ( $available_fields as $field_key => $field_data ) :
                $field_config  = isset( $config['fields'][ $field_key ] ) ? $config['fields'][ $field_key ] : recrm_filter_builder_default_field_config();
                $field_options = recrm_filter_builder_get_field_options( $field_key, $field_data, array( 'allowed_options' => array() ) );
                ?>
                <div style="border:1px solid #dcdcde;border-radius:12px;padding:14px 14px 12px;margin-bottom:14px;">
                    <p style="margin:0 0 10px;">
                        <label>
                            <input type="checkbox" name="fields[<?php echo esc_attr( $field_key ); ?>][enabled]" value="1" <?php checked( $field_config['enabled'], '1' ); ?>>
                            <strong><?php echo esc_html( $field_data['label'] ); ?></strong>
                        </label>
                    </p>

                    <p style="margin:0 0 10px;display:flex;flex-wrap:wrap;gap:16px;">
                        <label><input type="checkbox" name="fields[<?php echo esc_attr( $field_key ); ?>][visible]" value="1" <?php checked( $field_config['visible'], '1' ); ?>> Показувати</label>
                        <label><input type="checkbox" name="fields[<?php echo esc_attr( $field_key ); ?>][editable]" value="1" <?php checked( $field_config['editable'], '1' ); ?>> Дозволити зміну</label>
                    </p>

                    <div>
                        <label for="field-default-<?php echo esc_attr( $field_key ); ?>" style="display:block;font-weight:600;margin-bottom:6px;">Default значення</label>
                        <?php if ( 'select' === $field_data['type'] ) : ?>
                            <select id="field-default-<?php echo esc_attr( $field_key ); ?>" name="fields[<?php echo esc_attr( $field_key ); ?>][default]" style="min-width:260px;max-width:100%;">
                                <?php foreach ( $field_options as $option_value => $option_label ) : ?>
                                    <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $field_config['default'], (string) $option_value ); ?>>
                                        <?php echo esc_html( $option_label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input type="number" id="field-default-<?php echo esc_attr( $field_key ); ?>" name="fields[<?php echo esc_attr( $field_key ); ?>][default]" value="<?php echo esc_attr( $field_config['default'] ); ?>" style="width:180px;">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <p style="margin-top:18px;">
                <button type="submit" class="button button-primary">Зберегти фільтр</button>
                <a href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'property', 'page' => 'recrm-filter-builder' ), admin_url( 'edit.php' ) ) ); ?>" class="button button-secondary">Назад до списку</a>
            </p>
        </div>
    </form>
    <?php
}

function recrm_filter_builder_get_effective_source( $config, $request = array() ) {
    $config          = recrm_filter_builder_merge_config( $config );
    $effective       = array();
    $current_request = is_array( $request ) ? $request : array();

    $effective['recrm_filter'] = '1';

    foreach ( $config['fields'] as $field_key => $field_config ) {
        if ( '1' !== $field_config['enabled'] ) {
            continue;
        }

        $default_value = isset( $field_config['default'] ) ? (string) $field_config['default'] : '';
        $request_value = isset( $current_request[ $field_key ] )
            ? sanitize_text_field( wp_unslash( $current_request[ $field_key ] ) )
            : '';

        if ( isset( $field_config['visible'] ) && '0' === (string) $field_config['visible'] ) {
            $value = $default_value;
        } else {
            $value = '' !== $request_value ? $request_value : $default_value;
        }

        $effective[ $field_key ] = $value;
    }

    foreach ( array( 'paged', 'page' ) as $passthrough_key ) {
        if ( isset( $current_request[ $passthrough_key ] ) ) {
            $effective[ $passthrough_key ] = max( 1, absint( $current_request[ $passthrough_key ] ) );
        }
    }

    return $effective;
}

function recrm_filter_builder_render_field( $field_key, $field_data, $field_config, $active_source, $layout = 'default' ) {
    if ( '1' !== $field_config['enabled'] ) {
        return;
    }

    $current_value = isset( $active_source[ $field_key ] ) ? (string) $active_source[ $field_key ] : '';

    if ( 'property_type' === $field_key && function_exists( 'recrm_filter_resolve_property_type_term_id' ) ) {
        $resolved_type = recrm_filter_resolve_property_type_term_id( $current_value );
        if ( $resolved_type > 0 ) {
            $current_value = (string) $resolved_type;
        }
    }

    $readonly = '1' !== $field_config['editable'];

    if ( '1' !== $field_config['visible'] ) {
        echo '<input type="hidden" name="' . esc_attr( $field_key ) . '" value="' . esc_attr( $current_value ) . '">';
        return;
    }

    echo '<div class="recrm-filter-field">';

    if ( 'header' !== $layout ) {
        echo '<label for="' . esc_attr( $field_key ) . '">' . esc_html( $field_data['label'] ) . '</label>';
    }

    if ( 'select' === $field_data['type'] ) {
        $options = recrm_filter_builder_get_dynamic_field_options( $field_key, $field_data, $field_config, $active_source );

        if ( ! empty( $current_value ) && ! isset( $options[ $current_value ] ) ) {
            $options[ $current_value ] = $current_value;
        }

        if ( empty( $options ) ) {
            echo '</div>';
            return;
        }

        echo '<select name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '"' . ( $readonly ? ' disabled' : '' ) . '>';

        foreach ( $options as $option_value => $option_label ) {
            echo '<option value="' . esc_attr( $option_value ) . '"' . selected( $current_value, (string) $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
        }

        echo '</select>';

        if ( $readonly ) {
            echo '<input type="hidden" name="' . esc_attr( $field_key ) . '" value="' . esc_attr( $current_value ) . '">';
        }
    } else {
        echo '<input type="number" name="' . esc_attr( $field_key ) . '" id="' . esc_attr( $field_key ) . '" step="any" min="0" value="' . esc_attr( $current_value ) . '"' . ( $readonly ? ' readonly' : '' ) . '>';
    }

    echo '</div>';
}

function recrm_filter_builder_get_reset_url( $config ) {
    if ( ! empty( $config['action_url'] ) ) {
        return $config['action_url'];
    }

    return get_permalink();
}

function recrm_filter_builder_render_form_html( $config, $active_source, $instance = '', $layout = 'default', $id = '' ) {
    $config    = recrm_filter_builder_merge_config( $config );
    $available = recrm_filter_builder_get_available_fields();

    ob_start();
    ?>
    <form class="recrm-filter recrm-filter-<?php echo esc_attr( $layout ); ?>"
          data-instance="<?php echo esc_attr( $instance ); ?>"
          method="get"
          action="<?php echo esc_url( ! empty( $config['action_url'] ) ? $config['action_url'] : get_permalink() ); ?>">
        <input type="hidden" name="recrm_instance" value="<?php echo esc_attr( $instance ); ?>">
        <input type="hidden" name="recrm_filter" value="1">
        <input type="hidden" name="filter_id" value="<?php echo esc_attr( $id ); ?>">

        <div class="recrm-filter-grid">
            <?php
            foreach ( $available as $field_key => $field_data ) {
                $field_config = isset( $config['fields'][ $field_key ] )
                    ? $config['fields'][ $field_key ]
                    : recrm_filter_builder_default_field_config();

                recrm_filter_builder_render_field( $field_key, $field_data, $field_config, $active_source, $layout );
            }
            ?>
        </div>

        <div class="recrm-filter-actions">
            <button type="submit" class="recrm-btn recrm-btn-primary">Показати</button>
            <?php if ( '1' === $config['show_reset'] ) : ?>
                <a href="<?php echo esc_url( recrm_filter_builder_get_reset_url( $config ) ); ?>" class="recrm-btn recrm-btn-secondary">Скинути</a>
            <?php endif; ?>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

function recrm_filter_builder_shortcode( $atts ) {
    if ( wp_style_is( 'recrm-properties-archive', 'registered' ) ) {
        wp_enqueue_style( 'recrm-properties-archive' );
    }

    if ( ! function_exists( 'recrm_render_archive_property_card' ) && ! function_exists( 'recrm_get_properties_query_args_from_source' ) ) {
        return '<p>Файл archive-properties.php ще не підключений.</p>';
    }

    $atts = shortcode_atts(
        array(
            'id'           => '',
            'layout'       => 'default',
            'show_form'    => '',
            'show_results' => '',
            'limit'        => '',
            'instance'     => '',
        ),
        $atts,
        'recrm_filter'
    );

    $layout = ! empty( $atts['layout'] ) ? sanitize_key( $atts['layout'] ) : 'default';
    $id     = sanitize_title( (string) $atts['id'] );

    if ( '' === $id ) {
        return '<p>Не вказано id фільтра.</p>';
    }

    $instance = ! empty( $atts['instance'] ) ? sanitize_title( $atts['instance'] ) : $id;
    $config   = recrm_filter_builder_get( $id );

    if ( empty( $config ) ) {
        return '<p>Фільтр не знайдено.</p>';
    }

    $config = recrm_filter_builder_merge_config( $config );

    if ( '' !== $atts['show_form'] ) {
        $config['show_form'] = in_array( strtolower( (string) $atts['show_form'] ), array( '1', 'yes', 'true' ), true ) ? '1' : '0';
    }

    if ( '' !== $atts['show_results'] ) {
        $config['show_results'] = in_array( strtolower( (string) $atts['show_results'] ), array( '1', 'yes', 'true' ), true ) ? '1' : '0';
    }

    $active_source = recrm_filter_builder_get_effective_source( $config, $_GET );

    ob_start();
    ?>
    <div class="recrm-archive-page recrm-filter-builder-instance recrm-filter-builder-<?php echo esc_attr( $id ); ?>" data-instance="<?php echo esc_attr( $instance ); ?>">
        <div class="recrm-archive-top">
            <div class="recrm-archive-heading">
                <h2 class="recrm-archive-title"><?php echo esc_html( $config['title'] ); ?></h2>
                <?php if ( ! empty( $config['subtitle'] ) ) : ?>
                    <p class="recrm-archive-subtitle"><?php echo esc_html( $config['subtitle'] ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( '1' === $config['show_form'] ) : ?>
            <?php echo recrm_filter_builder_render_form_html( $config, $active_source, $instance, $layout, $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php endif; ?>

        <?php if ( '1' === $config['show_results'] ) : ?>
            <div class="recrm-results-wrap" data-instance="<?php echo esc_attr( $instance ); ?>">
                <?php echo recrm_filter_builder_render_results( $config, $active_source, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>

        <?php if ( '1' === $config['show_form'] && '1' === $config['show_results'] && function_exists( 'recrm_render_archive_ajax_script' ) ) : ?>
            <?php recrm_render_archive_ajax_script(); ?>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}

function recrm_filter_builder_get_pagination_base() {
    $big  = 999999999;
    $base = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );
    return $base;
}

function recrm_filter_builder_get_query_args( $config, $source ) {
    $args = function_exists( 'recrm_get_properties_query_args_from_source' )
        ? recrm_get_properties_query_args_from_source( $source )
        : array(
            'post_type'      => 'property',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'paged'          => isset( $source['paged'] ) ? max( 1, absint( $source['paged'] ) ) : 1,
        );

    $args['posts_per_page'] = isset( $config['posts_per_page'] ) ? max( 1, (int) $config['posts_per_page'] ) : 12;

    $meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array( 'relation' => 'AND' );

    if ( ! empty( $source['city'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_city',
            'value'   => sanitize_text_field( $source['city'] ),
            'compare' => '=',
        );
    }

    if ( ! empty( $source['district'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_district',
            'value'   => sanitize_text_field( $source['district'] ),
            'compare' => '=',
        );
    }

    if ( ! empty( $source['rooms'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_rooms',
            'value'   => absint( $source['rooms'] ),
            'type'    => 'NUMERIC',
            'compare' => '=',
        );
    }

    if ( ! empty( $source['floor'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_floor',
            'value'   => absint( $source['floor'] ),
            'type'    => 'NUMERIC',
            'compare' => '=',
        );
    }

    if ( ! empty( $source['floors_total'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_floors_total',
            'value'   => absint( $source['floors_total'] ),
            'type'    => 'NUMERIC',
            'compare' => '=',
        );
    }

    if ( ! empty( $source['condition'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_condition',
            'value'   => sanitize_text_field( $source['condition'] ),
            'compare' => '=',
        );
    }

    if ( ! empty( $source['heating'] ) ) {
        $meta_query[] = array(
            'key'     => 'property_heating',
            'value'   => sanitize_text_field( $source['heating'] ),
            'compare' => '=',
        );
    }

    if ( isset( $source['price_min'] ) && '' !== (string) $source['price_min'] ) {
        $meta_query[] = array(
            'key'     => 'price',
            'value'   => (float) $source['price_min'],
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ( isset( $source['price_max'] ) && '' !== (string) $source['price_max'] ) {
        $meta_query[] = array(
            'key'     => 'price',
            'value'   => (float) $source['price_max'],
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }

    if ( isset( $source['area_min'] ) && '' !== (string) $source['area_min'] ) {
        $meta_query[] = array(
            'key'     => 'property_area_total',
            'value'   => (float) $source['area_min'],
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ( isset( $source['area_max'] ) && '' !== (string) $source['area_max'] ) {
        $meta_query[] = array(
            'key'     => 'property_area_total',
            'value'   => (float) $source['area_max'],
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }

    if ( isset( $source['land_area_min'] ) && '' !== (string) $source['land_area_min'] ) {
        $meta_query[] = array(
            'key'     => 'property_land_area',
            'value'   => (float) $source['land_area_min'],
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    if ( isset( $source['land_area_max'] ) && '' !== (string) $source['land_area_max'] ) {
        $meta_query[] = array(
            'key'     => 'property_land_area',
            'value'   => (float) $source['land_area_max'],
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }

    if ( count( $meta_query ) > 1 ) {
        $args['meta_query'] = $meta_query;
    }

    $args['recrm_exclusive_first'] = 1;

    return $args;
}

function recrm_filter_builder_exclusive_first_clauses( $clauses, $query ) {
    global $wpdb;

    if ( ! $query->get( 'recrm_exclusive_first' ) ) {
        return $clauses;
    }

    $post_type = $query->get( 'post_type' );

    if ( is_array( $post_type ) ) {
        if ( ! in_array( 'property', $post_type, true ) ) {
            return $clauses;
        }
    } elseif ( 'property' !== $post_type ) {
        return $clauses;
    }

    $alias = 'recrm_exclusive_pm';

    if ( false === strpos( $clauses['join'], $alias ) ) {
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} {$alias} ON ({$wpdb->posts}.ID = {$alias}.post_id AND {$alias}.meta_key = 'property_exclusive')";
    }

    $exclusive_order = "CAST(COALESCE(NULLIF({$alias}.meta_value, ''), '0') AS UNSIGNED) DESC";

    if ( ! empty( $clauses['orderby'] ) ) {
        $clauses['orderby'] = $exclusive_order . ', ' . $clauses['orderby'];
    } else {
        $clauses['orderby'] = $exclusive_order . ", {$wpdb->posts}.post_date DESC";
    }

    return $clauses;
}

function recrm_filter_builder_render_results( $config, $source, $atts = array() ) {
    $limit = isset( $config['limit'] ) ? intval( $config['limit'] ) : 12;

    if ( isset( $atts['limit'] ) && '' !== $atts['limit'] ) {
        $limit = intval( $atts['limit'] );
    }

    if ( $limit < 1 ) {
        $limit = 12;
    }

    $args                  = recrm_filter_builder_get_query_args( $config, $source );
    $args['posts_per_page'] = $limit;

    $query = new WP_Query( $args );
    $paged = isset( $args['paged'] ) ? (int) $args['paged'] : 1;

    ob_start();
    ?>
    <div id="recrm-results" class="recrm-results-bar">
        Знайдено: <strong><?php echo (int) $query->found_posts; ?></strong>
    </div>

    <?php if ( $query->have_posts() ) : ?>
        <div class="recrm-properties-grid">
            <?php
            while ( $query->have_posts() ) :
                $query->the_post();
                if ( function_exists( 'recrm_render_archive_property_card' ) ) {
                    recrm_render_archive_property_card( get_the_ID() );
                } elseif ( function_exists( 'recrm_render_property_card' ) ) {
                    echo recrm_render_property_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
            endwhile;
            ?>
        </div>

        <?php
        $current_params = $source;
        unset( $current_params['paged'], $current_params['page'] );

        echo '<div class="recrm-pagination">';
        echo wp_kses_post(
            paginate_links(
                array(
                    'base'      => recrm_filter_builder_get_pagination_base(),
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => $query->max_num_pages,
                    'prev_text' => '«',
                    'next_text' => '»',
                    'type'      => 'list',
                    'add_args'  => $current_params,
                )
            )
        );
        echo '</div>';
        ?>
    <?php else : ?>
        <div class="recrm-empty-state">
            <h3>Нічого не знайдено</h3>
            <p>Спробуй змінити параметри фільтра.</p>
        </div>
    <?php endif; ?>
    <?php
    wp_reset_postdata();

    return ob_get_clean();
}
