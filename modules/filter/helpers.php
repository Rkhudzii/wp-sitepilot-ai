<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function recrm_filter_enqueue_assets() {
    wp_enqueue_style(
        'recrm-filter',
        RECRM_XML_IMPORT_URL . 'modules/filter/assets/css/filter.css',
        array(),
        filemtime( RECRM_XML_IMPORT_PATH . 'modules/filter/assets/css/filter.css' )
    );

    wp_enqueue_script(
        'recrm-filter',
        RECRM_XML_IMPORT_URL . 'modules/filter/assets/js/filter.js',
        array(),
        filemtime( RECRM_XML_IMPORT_PATH . 'modules/filter/assets/js/filter.js' ),
        true
    );

    wp_localize_script(
        'recrm-filter',
        'recrmFilter',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'recrm_filter_nonce' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'recrm_filter_enqueue_assets', 20 );

function recrm_filter_get_request_value( $source, $key, $default = '' ) {
    if ( ! is_array( $source ) || ! isset( $source[ $key ] ) ) {
        return $default;
    }

    return sanitize_text_field( wp_unslash( $source[ $key ] ) );
}

function recrm_filter_is_active_request( $source ) {
    return isset( $source['recrm_filter'] ) && '1' === sanitize_text_field( wp_unslash( $source['recrm_filter'] ) );
}

function recrm_filter_get_current_page( $source ) {
    if ( isset( $source['paged'] ) ) {
        return max( 1, absint( $source['paged'] ) );
    }

    if ( isset( $source['page'] ) ) {
        return max( 1, absint( $source['page'] ) );
    }

    return max( 1, absint( get_query_var( 'paged' ) ) ?: absint( get_query_var( 'page' ) ) ?: 1 );
}

function recrm_filter_get_offer_options() {
    return array(
        ''     => 'Будь-яка операція',
        'sale' => 'Продаж',
        'rent' => 'Оренда',
    );
}

function recrm_filter_get_sort_options() {
    return array(
        'date_desc'  => 'Спочатку нові',
        'date_asc'   => 'Спочатку старі',
        'price_asc'  => 'Ціна ↑',
        'price_desc' => 'Ціна ↓',
        'title_asc'  => 'По назві',
    );
}

function recrm_filter_get_property_type_options() {
    $options = array(
        '' => 'Всі типи',
    );

    $terms = get_terms(
        array(
            'taxonomy'   => 'property_type',
            'hide_empty' => false,
        )
    );

    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            $options[ (string) $term->term_id ] = $term->name;
        }
    }

    return $options;
}

function recrm_filter_get_filtered_post_ids( $source = array(), $exclude_key = '' ) {
    $query_source = is_array( $source ) ? $source : array();

    unset( $query_source['paged'], $query_source['page'] );

    if ( '' !== $exclude_key ) {
        unset( $query_source[ $exclude_key ] );
    }

    $args = array(
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    );

    $meta_query = recrm_filter_build_meta_query( $query_source );
    $tax_query  = recrm_filter_build_tax_query( $query_source );

    if ( count( $meta_query ) > 1 ) {
        $args['meta_query'] = $meta_query;
    }

    if ( count( $tax_query ) > 1 ) {
        $args['tax_query'] = $tax_query;
    }

    $post_ids = get_posts( $args );

    return is_array( $post_ids ) ? array_map( 'absint', $post_ids ) : array();
}

function recrm_filter_get_distinct_meta_options_by_post_ids( $meta_key, $post_ids = array(), $empty_label = '' ) {
    global $wpdb;

    $options = array();

    if ( '' !== $empty_label ) {
        $options[''] = $empty_label;
    }

    $post_ids = array_values( array_filter( array_map( 'absint', (array) $post_ids ) ) );

    if ( empty( $post_ids ) ) {
        return $options;
    }

    $placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

    $query = $wpdb->prepare(
        "
        SELECT DISTINCT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s
          AND pm.meta_value <> ''
          AND p.post_type = 'property'
          AND p.post_status = 'publish'
          AND p.ID IN ($placeholders)
        ORDER BY pm.meta_value ASC
        ",
        array_merge( array( $meta_key ), $post_ids )
    );

    $results = $wpdb->get_col( $query );

    if ( ! empty( $results ) ) {
        foreach ( $results as $value ) {
            $value = trim( (string) $value );

            if ( '' !== $value ) {
                $options[ $value ] = $value;
            }
        }
    }

    return $options;
}

function recrm_filter_get_distinct_numeric_options_by_post_ids( $meta_key, $post_ids = array(), $empty_label = '' ) {
    global $wpdb;

    $options = array();

    if ( '' !== $empty_label ) {
        $options[''] = $empty_label;
    }

    $post_ids = array_values( array_filter( array_map( 'absint', (array) $post_ids ) ) );

    if ( empty( $post_ids ) ) {
        return $options;
    }

    $placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

    $query = $wpdb->prepare(
        "
        SELECT DISTINCT CAST(pm.meta_value AS DECIMAL(20,2)) AS meta_number
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s
          AND pm.meta_value <> ''
          AND p.post_type = 'property'
          AND p.post_status = 'publish'
          AND p.ID IN ($placeholders)
        ORDER BY meta_number ASC
        ",
        array_merge( array( $meta_key ), $post_ids )
    );

    $results = $wpdb->get_col( $query );

    if ( ! empty( $results ) ) {
        foreach ( $results as $value ) {
            $number = (float) $value;

            if ( $number <= 0 ) {
                continue;
            }

            $label = fmod( $number, 1.0 ) === 0.0 ? (string) (int) $number : (string) $number;
            $options[ $label ] = $label;
        }
    }

    return $options;
}

function recrm_filter_get_property_type_options_by_post_ids( $post_ids = array(), $empty_label = 'Всі типи' ) {
    $options = array(
        '' => $empty_label,
    );

    $post_ids = array_values( array_filter( array_map( 'absint', (array) $post_ids ) ) );

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

    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            $options[ (string) $term->term_id ] = $term->name;
        }
    }

    return $options;
}

function recrm_filter_get_distinct_meta_options( $meta_key, $empty_label = '' ) {
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

            if ( '' !== $value ) {
                $options[ $value ] = $value;
            }
        }
    }

    return $options;
}

function recrm_filter_get_distinct_numeric_options( $meta_key, $empty_label = '' ) {
    global $wpdb;

    $results = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT DISTINCT CAST(pm.meta_value AS DECIMAL(20,2)) AS meta_number
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
            $number = (float) $value;

            if ( $number <= 0 ) {
                continue;
            }

            $label = fmod( $number, 1.0 ) === 0.0 ? (string) (int) $number : (string) $number;
            $options[ $label ] = $label;
        }
    }

    return $options;
}

function recrm_filter_get_fields_schema( $source = array() ) {
    $offer_post_ids        = recrm_filter_get_filtered_post_ids( $source, 'offer_type' );
    $property_type_post_ids = recrm_filter_get_filtered_post_ids( $source, 'property_type' );
    $city_post_ids         = recrm_filter_get_filtered_post_ids( $source, 'city' );
    $district_post_ids     = recrm_filter_get_filtered_post_ids( $source, 'district' );
    $rooms_post_ids        = recrm_filter_get_filtered_post_ids( $source, 'rooms' );
    $floor_post_ids        = recrm_filter_get_filtered_post_ids( $source, 'floor' );
    $floors_total_post_ids = recrm_filter_get_filtered_post_ids( $source, 'floors_total' );
    $condition_post_ids    = recrm_filter_get_filtered_post_ids( $source, 'condition' );
    $heating_post_ids      = recrm_filter_get_filtered_post_ids( $source, 'heating' );

    return array(
        'offer_type'    => array(
            'label'   => 'Операція',
            'type'    => 'select',
            'options' => recrm_filter_get_offer_options(),
        ),
        'property_type' => array(
            'label'   => 'Тип',
            'type'    => 'select',
            'options' => recrm_filter_get_property_type_options_by_post_ids( $property_type_post_ids, 'Всі типи' ),
        ),
        'city'          => array(
            'label'   => 'Місто',
            'type'    => 'select',
            'options' => recrm_filter_get_distinct_meta_options_by_post_ids( 'property_city', $city_post_ids, 'Всі міста' ),
        ),
        'district'      => array(
            'label'   => 'Район',
            'type'    => 'select',
            'options' => recrm_filter_get_distinct_meta_options_by_post_ids( 'property_district', $district_post_ids, 'Всі райони' ),
        ),
        'rooms'         => array(
            'label'   => 'Кімнат',
            'type'    => 'select',
            'options' => array_map(
                static function( $value ) {
                    return '' === $value ? 'Будь-яка кількість' : $value . ' кімн.';
                },
                recrm_filter_get_distinct_numeric_options_by_post_ids( 'property_rooms', $rooms_post_ids, 'Будь-яка кількість' )
            ),
        ),
        'floor'         => array(
            'label'   => 'Поверх',
            'type'    => 'select',
            'options' => recrm_filter_get_distinct_numeric_options_by_post_ids( 'property_floor', $floor_post_ids, 'Будь-який поверх' ),
        ),
        'floors_total'  => array(
            'label'   => 'Поверховість',
            'type'    => 'select',
            'options' => recrm_filter_get_distinct_numeric_options_by_post_ids( 'property_floors_total', $floors_total_post_ids, 'Будь-яка поверховість' ),
        ),
        'condition'     => array(
            'label'   => 'Стан',
            'type'    => 'select',
            'options' => recrm_filter_get_distinct_meta_options_by_post_ids( 'property_condition', $condition_post_ids, 'Будь-який стан' ),
        ),
        'heating'       => array(
            'label'   => 'Опалення',
            'type'    => 'select',
            'options' => recrm_filter_get_distinct_meta_options_by_post_ids( 'property_heating', $heating_post_ids, 'Будь-яке опалення' ),
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
            'options' => recrm_filter_get_sort_options(),
        ),
    );
}

function recrm_filter_get_default_fields_list() {
    return array( 'offer_type', 'property_type', 'district', 'rooms', 'price_min', 'price_max', 'sort' );
}

function recrm_filter_parse_fields_list( $fields_string ) {
    $schema = recrm_filter_get_fields_schema();

    if ( empty( $fields_string ) ) {
        return recrm_filter_get_default_fields_list();
    }

    $fields = array_map( 'trim', explode( ',', (string) $fields_string ) );
    $fields = array_values( array_filter( $fields ) );

    return array_values(
        array_filter(
            $fields,
            static function( $field_key ) use ( $schema ) {
                return isset( $schema[ $field_key ] );
            }
        )
    );
}

function recrm_filter_get_current_page_url() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $url         = home_url( $request_uri );

    return remove_query_arg(
        array( 'paged', 'page', '_wp_http_referer', 'nonce', 'action' ),
        $url
    );
}

function recrm_filter_get_form_action_url( $atts = array() ) {
    if ( ! empty( $atts['action_url'] ) ) {
        return esc_url_raw( $atts['action_url'] );
    }

    return recrm_filter_get_current_page_url();
}

function recrm_filter_format_price( $price, $currency = '' ) {
    if ( '' === $price || null === $price ) {
        return '';
    }

    $formatted = number_format_i18n( (float) $price, 0 );

    return $currency ? trim( $formatted . ' ' . $currency ) : $formatted;
}

function recrm_filter_render_property_card( $post_id ) {
    if ( function_exists( 'recrm_get_template' ) ) {
        echo recrm_get_template( 'property-card.php', array( 'post_id' => $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }

    echo '<article><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></article>';
}

function recrm_filter_presets_option_key() {
    return 'recrm_filter_presets';
}

if ( ! function_exists( 'recrm_filter_preset_defaults' ) ) {
    function recrm_filter_preset_defaults() {
        return array(
            'name'           => '',
            'id'             => '',
            'title'          => '',
            'subtitle'       => '',
            'action_url'     => '',
            'posts_per_page' => 12,
            'show_form'      => '1',
            'show_results'   => '1',
            'show_reset'     => '1',
            'fields'         => array(),
            'defaults'       => array(),
            'hidden'         => array(),
        );
    }
}

function recrm_filter_get_presets() {
    $items = get_option( recrm_filter_presets_option_key(), array() );
    return is_array( $items ) ? $items : array();
}

function recrm_filter_get_preset( $id ) {
    $id = sanitize_title( (string) $id );
    $all = recrm_filter_get_presets();
    $preset = isset( $all[ $id ] ) && is_array( $all[ $id ] ) ? $all[ $id ] : array();
    return wp_parse_args( $preset, recrm_filter_preset_defaults() );
}

function recrm_filter_update_presets( $items ) {
    update_option( recrm_filter_presets_option_key(), $items, false );
}

function recrm_filter_get_effective_source( $request = array(), $preset = array() ) {
    $request = is_array( $request ) ? $request : array();
    $preset  = is_array( $preset ) ? $preset : array();

    $source = array(
        'recrm_filter' => '1',
    );

    // 🔥 НОВИЙ ФОРМАТ (builder)
    if ( ! empty( $preset['fields'] ) && is_array( $preset['fields'] ) ) {

        foreach ( $preset['fields'] as $field_key => $field_config ) {

            if ( ! is_array( $field_config ) ) {
                continue;
            }

            // тільки включені поля
            if ( empty( $field_config['enabled'] ) || '1' !== (string) $field_config['enabled'] ) {
                continue;
            }

            $default_value = isset( $field_config['default'] ) ? (string) $field_config['default'] : '';

            $request_value = isset( $request[ $field_key ] )
                ? sanitize_text_field( wp_unslash( $request[ $field_key ] ) )
                : '';

            // 🔥 якщо hidden — завжди default
            if ( isset( $field_config['visible'] ) && '0' === (string) $field_config['visible'] ) {
                $value = $default_value;
            } else {
                $value = '' !== $request_value ? $request_value : $default_value;
            }

            $source[ $field_key ] = $value;
        }

    } else {
        // 🔙 fallback старого формату
        $enabled_fields = isset( $preset['fields'] ) && is_array( $preset['fields'] ) ? $preset['fields'] : array();
        $defaults       = isset( $preset['defaults'] ) && is_array( $preset['defaults'] ) ? $preset['defaults'] : array();

        foreach ( $enabled_fields as $field_key ) {
            $request_value = isset( $request[ $field_key ] )
                ? sanitize_text_field( wp_unslash( $request[ $field_key ] ) )
                : '';

            $default_value = isset( $defaults[ $field_key ] ) ? (string) $defaults[ $field_key ] : '';

            $value = '' !== $request_value ? $request_value : $default_value;

            $source[ $field_key ] = $value;
        }
    }

    foreach ( array( 'paged', 'page' ) as $key ) {
        if ( isset( $request[ $key ] ) ) {
            $source[ $key ] = max( 1, absint( $request[ $key ] ) );
        }
    }

    return $source;
}