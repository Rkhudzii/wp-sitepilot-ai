<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

@set_time_limit( 300 );
@ini_set( 'max_execution_time', '300' );
@ini_set( 'memory_limit', '512M' );

function recrm_parse_xml_string( $xml_string ) {
    if ( empty( $xml_string ) ) {
        return new WP_Error( 'xml_empty', 'XML порожній.' );
    }

    libxml_use_internal_errors( true );
    $xml = simplexml_load_string( $xml_string );

    if ( ! $xml ) {
        return new WP_Error( 'xml_parse_error', 'Не вдалося розібрати XML.' );
    }

    return recrm_import_xml_items( $xml );
}

function recrm_fetch_xml_string_from_url( $xml_url ) {
    $response = wp_remote_get(
        $xml_url,
        array(
            'timeout'     => 60,
            'redirection' => 5,
            'sslverify'   => false,
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'xml_fetch_error', 'Не вдалося завантажити XML: ' . $response->get_error_message() );
    }

    $status_code = wp_remote_retrieve_response_code( $response );

    if ( 200 !== $status_code ) {
        return new WP_Error( 'xml_http_error', 'Сервер повернув код: ' . $status_code );
    }

    return wp_remote_retrieve_body( $response );
}

function recrm_process_xml_import_from_url( $xml_url ) {
    $body = recrm_fetch_xml_string_from_url( $xml_url );

    if ( is_wp_error( $body ) ) {
        return $body;
    }

    return recrm_parse_xml_string( $body );
}

function recrm_process_xml_import_from_file( $file ) {
    if ( empty( $file ) || empty( $file['tmp_name'] ) ) {
        return new WP_Error( 'xml_file_missing', 'Файл не вибраний.' );
    }

    if ( ! empty( $file['error'] ) ) {
        return new WP_Error( 'xml_file_error', 'Помилка завантаження файлу. Код: ' . (int) $file['error'] );
    }

    $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
    if ( 'xml' !== $extension ) {
        return new WP_Error( 'xml_file_type', 'Дозволено завантажувати тільки XML-файл.' );
    }

    $xml_string = file_get_contents( $file['tmp_name'] );

    if ( false === $xml_string ) {
        return new WP_Error( 'xml_file_read', 'Не вдалося прочитати XML-файл.' );
    }

    return recrm_parse_xml_string( $xml_string );
}

function recrm_normalize_deal_type( $deal_text ) {
    $deal_text = trim( (string) $deal_text );
    $deal_text = mb_strtolower( $deal_text, 'UTF-8' );

    $sale_values = array( 'sale', 'продаж', 'продажа', 'buy', 'sell' );
    $rent_values = array( 'rent', 'оренда', 'аренда', 'lease' );

    if ( in_array( $deal_text, $sale_values, true ) ) {
        return 'sale';
    }

    if ( in_array( $deal_text, $rent_values, true ) ) {
        return 'rent';
    }

    return '';
}

function recrm_import_xml_items( $xml ) {
    if ( empty( $xml->item ) ) {
        return new WP_Error( 'xml_no_items', 'У XML не знайдено обʼєктів <item>.' );
    }

    $stats = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed'  => 0,
    );

    foreach ( $xml->item as $item ) {
        $result = recrm_import_single_xml_item_with_status( $item->asXML() );

        if ( isset( $stats[ $result ] ) ) {
            $stats[ $result ]++;
        } else {
            $stats['failed']++;
        }
    }

    return sprintf(
        'Імпорт завершено. Створено: %d, оновлено: %d, пропущено: %d, помилки: %d.',
        $stats['created'],
        $stats['updated'],
        $stats['skipped'],
        $stats['failed']
    );
}

function recrm_collect_item_image_urls( $item ) {
    $urls = array();

    if ( empty( $item->images ) || empty( $item->images->image_url ) ) {
        return $urls;
    }

    foreach ( $item->images->image_url as $image_url ) {
        $url = esc_url_raw( trim( (string) $image_url ) );
        if ( ! empty( $url ) ) {
            $urls[] = $url;
        }
    }

    return array_values( array_unique( $urls ) );
}

function recrm_disable_intermediate_sizes_for_import( $sizes ) {
    return array();
}

function recrm_import_property_images( $post_id, $image_urls, $internal_id = '' ) {
    if ( empty( $post_id ) || empty( $image_urls ) || ! is_array( $image_urls ) ) {
        return;
    }

    if ( ! function_exists( 'media_handle_sideload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $gallery_ids  = array();
    $stored_urls  = array();
    $featured_set = false;

    $image_urls = array_slice( $image_urls, 0, 5 );

    foreach ( $image_urls as $index => $image_url ) {
        $stored_urls[] = $image_url;

        $existing_attachment_id = recrm_find_attachment_by_source_url( $post_id, $image_url );

        if ( $existing_attachment_id ) {
            $gallery_ids[] = $existing_attachment_id;

            if ( 0 === $index && ! has_post_thumbnail( $post_id ) ) {
                set_post_thumbnail( $post_id, $existing_attachment_id );
            }

            if ( function_exists( 'recrm_seo_sync_attachment_metadata' ) ) {
                recrm_seo_sync_attachment_metadata( $post_id, $existing_attachment_id, $index + 1 );
            }

            continue;
        }

        $tmp = download_url( $image_url, 20 );

        if ( is_wp_error( $tmp ) ) {
            continue;
        }

        $parsed_url = wp_parse_url( $image_url );
        $path       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
        $filename   = basename( $path );

        if ( empty( $filename ) || '.' === $filename ) {
            $filename = 'property-' . $post_id . '-' . ( $index + 1 ) . '.jpg';
        }

        $file_array = array(
            'name'     => sanitize_file_name( $filename ),
            'tmp_name' => $tmp,
        );

        add_filter( 'intermediate_image_sizes_advanced', 'recrm_disable_intermediate_sizes_for_import', 999 );

        $attachment_id = media_handle_sideload( $file_array, $post_id, 'Фото обʼєкта ' . $internal_id );

        remove_filter( 'intermediate_image_sizes_advanced', 'recrm_disable_intermediate_sizes_for_import', 999 );

        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            continue;
        }

        update_post_meta( $attachment_id, 'recrm_source_image_url', esc_url_raw( $image_url ) );

        $gallery_ids[] = $attachment_id;

        if ( 0 === $index && ! $featured_set ) {
            set_post_thumbnail( $post_id, $attachment_id );
            $featured_set = true;
        }

        if ( function_exists( 'recrm_seo_sync_attachment_metadata' ) ) {
            recrm_seo_sync_attachment_metadata( $post_id, $attachment_id, $index + 1 );
        }
    }

    if ( ! empty( $gallery_ids ) ) {
        $gallery_ids = array_values( array_unique( array_map( 'intval', $gallery_ids ) ) );

        update_post_meta( $post_id, 'property_gallery_ids', $gallery_ids );
        update_post_meta( $post_id, 'property_gallery', implode( ',', $gallery_ids ) );
    }

    if ( ! empty( $stored_urls ) ) {
        update_post_meta( $post_id, 'property_image_urls', $stored_urls );
    }
}

function recrm_find_attachment_by_source_url( $post_id, $image_url ) {
    $attachments = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'post_parent'    => $post_id,
            'meta_key'       => 'recrm_source_image_url',
            'meta_value'     => esc_url_raw( $image_url ),
            'fields'         => 'ids',
        )
    );

    if ( ! empty( $attachments ) ) {
        return (int) $attachments[0];
    }

    return 0;
}

function recrm_prepare_xml_items_from_string( $xml_string ) {
    if ( empty( $xml_string ) ) {
        return new WP_Error( 'xml_empty', 'XML порожній.' );
    }

    libxml_use_internal_errors( true );
    $xml = simplexml_load_string( $xml_string );

    if ( ! $xml ) {
        return new WP_Error( 'xml_parse_error', 'Не вдалося розібрати XML.' );
    }

    if ( empty( $xml->item ) ) {
        return new WP_Error( 'xml_no_items', 'У XML не знайдено обʼєктів <item>.' );
    }

    $items = array();

    foreach ( $xml->item as $item ) {
        $items[] = $item->asXML();
    }

    return $items;
}

function recrm_import_single_xml_item( $item_xml_string ) {
    $result = recrm_import_single_xml_item_with_status( $item_xml_string );
    return in_array( $result, array( 'created', 'updated' ), true );
}

function recrm_map_realty_type( $realty_type_text ) {
    $raw   = trim( (string) $realty_type_text );
    $lower = mb_strtolower( $raw, 'UTF-8' );

    $result = array(
        'group'                    => '',
        'property_subtype'         => '',
        'property_house_type'      => '',
        'property_commercial_type' => '',
        'property_land_purpose'    => '',
    );

    if ( '' === $raw ) {
        return $result;
    }

    // Кімната
    if ( false !== mb_stripos( $lower, 'кімната' ) ) {
        $result['group'] = 'kimnata';
        $result['property_subtype'] = $raw;
        return $result;
    }

    // Земля
    if (
        false !== mb_stripos( $lower, 'земля' ) ||
        false !== mb_stripos( $lower, 'ділян' )
    ) {
        $result['group'] = 'dilyanky';
        $result['property_land_purpose'] = $raw;
        return $result;
    }

    // Комерція
    $commercial_keywords = array(
        'офіс',
        'торгів',
        'склад',
        'виробнич',
        'харчув',
        'обслугов',
        'магазин',
        'готель',
        'будівля',
        'сто',
        'азс',
        'автомийка',
        'об\'єкт',
    );

    foreach ( $commercial_keywords as $keyword ) {
        if ( false !== mb_stripos( $lower, $keyword ) ) {
            $result['group'] = 'komertsiya';
            $result['property_commercial_type'] = $raw;
            return $result;
        }
    }

    // Будинки
    $house_keywords = array(
        'будинок',
        'вілла',
        'таунхаус',
        'дуплекс',
    );

    foreach ( $house_keywords as $keyword ) {
        if ( false !== mb_stripos( $lower, $keyword ) ) {
            $result['group'] = 'budynky';
            $result['property_house_type'] = $raw;
            return $result;
        }
    }

    // Квартирна група
    $apartment_keywords = array(
        'квартира',
        'апартамент',
        'пентхаус',
    );

    foreach ( $apartment_keywords as $keyword ) {
        if ( false !== mb_stripos( $lower, $keyword ) ) {
            $result['group'] = 'kvartyry';
            $result['property_subtype'] = $raw;
            return $result;
        }
    }

    // Гараж / паркування
    if (
        false !== mb_stripos( $lower, 'гараж' ) ||
        false !== mb_stripos( $lower, 'паркування' )
    ) {
        $result['group'] = 'komertsiya';
        $result['property_commercial_type'] = $raw;
        return $result;
    }

    // fallback
    $result['group'] = 'komertsiya';
    $result['property_commercial_type'] = $raw;

    return $result;
}

function recrm_get_xml_location_value( $location, $keys ) {
    if ( empty( $location ) ) {
        return '';
    }

    foreach ( (array) $keys as $key ) {
        if ( isset( $location->{$key} ) && '' !== trim( (string) $location->{$key} ) ) {
            return sanitize_text_field( (string) $location->{$key} );
        }
    }

    return '';
}

function recrm_import_single_xml_item_with_status( $item_xml_string ) {
     error_log( 'IMPORT STEP 1: function started' );

    if ( empty( $item_xml_string ) ) {
        error_log('STEP FAIL: EMPTY XML STRING');
        return 'failed';
    }

    $item = simplexml_load_string( $item_xml_string );
    error_log('STEP 2: XML PARSED');

    if ( ! $item ) {
        error_log('STEP FAIL: XML PARSE ERROR');
        return 'failed';
    }

    $internal_id = isset( $item['internal-id'] ) ? sanitize_text_field( (string) $item['internal-id'] ) : '';
    error_log( 'IMPORT STEP 3: internal_id=' . $internal_id );

    if ( empty( $internal_id ) ) {
        return 'skipped';
    }

    $title       = sanitize_text_field( (string) $item->title );
    $description = wp_kses_post( (string) $item->description );
    $status      = sanitize_text_field( (string) $item->status );

    $deal_text        = sanitize_text_field( (string) $item->deal );
    $deal_normalized  = recrm_normalize_deal_type( $deal_text );
    $category_text    = sanitize_text_field( (string) $item->category );
    $realty_type_text = sanitize_text_field( (string) $item->realty_type );
    $realty_type_value = isset( $item->realty_type['value'] ) ? sanitize_text_field( (string) $item->realty_type['value'] ) : '';

    $region   = recrm_get_xml_location_value( $item->location, array( 'region', 'oblast' ) );
    $city     = recrm_get_xml_location_value( $item->location, array( 'city' ) );
    $locality = recrm_get_xml_location_value( $item->location, array( 'locality', 'settlement', 'village', 'township', 'town' ) );
    $district = recrm_get_xml_location_value( $item->location, array( 'district', 'county', 'area' ) );
    $borough  = recrm_get_xml_location_value( $item->location, array( 'borough' ) );
    $street   = recrm_get_xml_location_value( $item->location, array( 'street', 'address' ) );
    $street_type = recrm_get_xml_location_value( $item->location, array( 'street_type' ) );

    $street_type_map = array(
        'вулиця'   => 'вул.',
        'улица'    => 'вул.',
        'проспект' => 'просп.',
        'провулок' => 'пров.',
        'площадь'  => 'пл.',
        'площа'    => 'пл.',
        'бульвар'  => 'бул.',
        'узвіз'    => 'узв.',
    );

    $street_type_lower = mb_strtolower( trim( $street_type ), 'UTF-8' );
    $street_type_short = isset( $street_type_map[ $street_type_lower ] ) ? $street_type_map[ $street_type_lower ] : $street_type;

    $address = trim( $street ? ( $street_type_short ? $street_type_short . ' ' . $street : $street ) : '' );

    $lat      = recrm_get_xml_location_value( $item->location, array( 'map_lat', 'lat', 'latitude' ) );
    $lng      = recrm_get_xml_location_value( $item->location, array( 'map_lng', 'lng', 'longitude' ) );

    $location_parts = array_filter(
        array(
            $district,
            $borough && $borough !== $city ? $borough : '',
            $locality && $locality !== $city ? $locality : '',
            $city,
            $region,
        )
    );

    $location = implode( ', ', $location_parts );

    $price          = isset( $item->price ) ? sanitize_text_field( (string) $item->price ) : '';
    $price_currency = isset( $item->price['currency'] ) ? sanitize_text_field( (string) $item->price['currency'] ) : '';
    $price_type     = isset( $item->price['type'] ) ? sanitize_text_field( (string) $item->price['type'] ) : '';

    $room_count   = isset( $item->room_count ) ? absint( (string) $item->room_count ) : '';
    $floor        = isset( $item->floor ) ? absint( (string) $item->floor ) : '';
    $total_floors = isset( $item->total_floors ) ? absint( (string) $item->total_floors ) : '';
    $area_total   = isset( $item->area_total ) ? sanitize_text_field( (string) $item->area_total ) : '';
    $area_land    = isset( $item->area_land ) ? sanitize_text_field( (string) $item->area_land ) : '';
    $is_new       = isset( $item->is_new_building ) ? sanitize_text_field( (string) $item->is_new_building ) : '0';
    $article      = isset( $item->article ) ? sanitize_text_field( (string) $item->article ) : '';

    $existing_posts = get_posts(
        array(
            'post_type'      => 'property',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => 'crm_internal_id',
            'meta_value'     => $internal_id,
            'fields'         => 'ids',
        )
    );

    $is_active = in_array(
        strtolower( (string) $status ),
        array( 'active', '1' ),
        true
    );

    $post_data = array(
        'post_type'    => 'property',
        'post_title'   => ! empty( $title ) ? $title : 'Обʼєкт #' . $internal_id,
        'post_content' => $description,
        'post_status'  => $is_active ? 'publish' : 'draft',
    );

    $result_type = 'created';

    if ( ! empty( $existing_posts ) ) {
        $post_data['ID'] = (int) $existing_posts[0];
        $post_id         = wp_update_post( $post_data, true );
        $result_type     = 'updated';

        if ( is_wp_error( $post_id ) ) {
            return 'failed';
        }
    } else {
        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return 'failed';
        }
    }


    update_post_meta( $post_id, 'crm_internal_id', $internal_id );
    update_post_meta( $post_id, 'recrm_import_source', 'crm_xml' );
    update_post_meta( $post_id, 'crm_article', $article );
    update_post_meta( $post_id, 'crm_deal', $deal_normalized );
    update_post_meta( $post_id, 'crm_deal_raw', $deal_text );
    update_post_meta( $post_id, 'crm_category', $category_text );
    update_post_meta( $post_id, 'crm_realty_type', $realty_type_text );
    $type_data = recrm_map_realty_type( $realty_type_text, $realty_type_value );
    update_post_meta( $post_id, 'crm_realty_group', $type_data['group'] );

    update_post_meta( $post_id, 'property_subtype', $type_data['property_subtype'] );
    update_post_meta( $post_id, 'property_house_type', $type_data['property_house_type'] );
    update_post_meta( $post_id, 'property_commercial_type', $type_data['property_commercial_type'] );
    update_post_meta( $post_id, 'property_land_purpose', $type_data['property_land_purpose'] );

    $type_slug = $type_data['group'];

    if ( $type_slug ) {
        $term = get_term_by( 'slug', $type_slug, 'property_type' );

        if ( $term && ! is_wp_error( $term ) ) {
            wp_set_object_terms( $post_id, (int) $term->term_id, 'property_type' );
        }
    }
    update_post_meta( $post_id, 'crm_status', $status );
    update_post_meta( $post_id, 'crm_is_new_building', $is_new );

    update_post_meta( $post_id, 'property_price', $price );
    update_post_meta( $post_id, 'property_currency', $price_currency );
    update_post_meta( $post_id, 'property_price_type', $price_type );

    update_post_meta( $post_id, 'property_address', $address );
    update_post_meta( $post_id, 'property_city', $city );
    update_post_meta( $post_id, 'property_locality', $locality );
    update_post_meta( $post_id, 'property_district', $district );
    update_post_meta( $post_id, 'property_region', $region );
    update_post_meta( $post_id, 'property_location_full', $location );
    update_post_meta( $post_id, 'property_latitude', $lat );
    update_post_meta( $post_id, 'property_longitude', $lng );
    update_post_meta( $post_id, 'property_lat', $lat );
    update_post_meta( $post_id, 'property_lng', $lng );

    update_post_meta( $post_id, 'property_rooms', $room_count );
    update_post_meta( $post_id, 'property_floor', $floor );
    update_post_meta( $post_id, 'property_floors_total', $total_floors );
    update_post_meta( $post_id, 'property_area_total', $area_total );
    update_post_meta( $post_id, 'property_land_area', $area_land );

        $properties_map = array(
        'property_18' => 'property_condition',
        'property_52' => 'property_heating',
        'property_65' => 'property_wall_material',
        'property_66' => 'property_land_area',
        'property_67' => 'property_purpose',
        'property_68' => 'property_gas',
        'property_69' => 'property_electricity',
        'property_70' => 'property_water',
        'property_71' => 'property_sewerage',
        'property_72' => 'property_cadastral_number',
        'property_73' => 'property_land_width',
        'property_74' => 'property_land_length',
        'property_76' => 'property_exclusive',
    );

    $raw_properties = array();

    if ( ! empty( $item->properties ) && ! empty( $item->properties->property ) ) {
        foreach ( $item->properties->property as $property ) {
            $attribute = isset( $property['attribute'] ) ? sanitize_key( (string) $property['attribute'] ) : '';
            $label     = isset( $property['label'] ) ? sanitize_text_field( (string) $property['label'] ) : '';
            $value     = sanitize_text_field( trim( (string) $property ) );

            if ( $attribute === 'property_76' ) {
                $value = mb_strtolower( trim( (string) $property ) ) === 'так' ? '1' : '0';
            }

            if ( '' === $attribute || '' === $value ) {
                continue;
            }

            $raw_properties[ $attribute ] = array(
                'label' => $label,
                'value' => $value,
            );

            if ( isset( $properties_map[ $attribute ] ) ) {
                update_post_meta( $post_id, $properties_map[ $attribute ], $value );
            }

            update_post_meta( $post_id, 'crm_' . $attribute, $value );
        }
    }

    if ( ! empty( $raw_properties ) ) {
        update_post_meta( $post_id, 'crm_properties_raw', $raw_properties );
    }

    if ( ! empty( $status ) ) {
        wp_set_object_terms( $post_id, $status, 'property_status', false );
    }

    $location_terms = array_filter( array( $region, $city, $locality, $district ) );
    if ( ! empty( $location_terms ) ) {
        wp_set_object_terms( $post_id, $location_terms, 'property_location', false );
    }

    $image_urls = recrm_collect_item_image_urls( $item );
    if ( ! empty( $image_urls ) ) {
        recrm_import_property_images( $post_id, $image_urls, $internal_id );
    }

    return $result_type;
}

function recrm_import_is_locked() {
    return (bool) get_transient( 'recrm_import_lock' );
}

function recrm_set_import_lock() {
    set_transient( 'recrm_import_lock', 1, 30 * MINUTE_IN_SECONDS );
}

function recrm_release_import_lock() {
    delete_transient( 'recrm_import_lock' );
}

function recrm_save_import_result( $args ) {
    $defaults = array(
        'time'        => current_time( 'mysql' ),
        'status'      => 'success',
        'message'     => '',
        'created'     => 0,
        'updated'     => 0,
        'skipped'     => 0,
        'failed'      => 0,
        'source'      => '',
        'source_type' => '',
        'trashed' => 0,
    );

    recrm_set_last_import_data( wp_parse_args( $args, $defaults ) );
}

function recrm_run_saved_feed_import() {
    if ( recrm_import_is_locked() ) {
        return new WP_Error( 'recrm_import_locked', 'Імпорт уже виконується.' );
    }

    $settings = recrm_get_import_settings();
    $xml_url  = ! empty( $settings['xml_url'] ) ? esc_url_raw( $settings['xml_url'] ) : '';

    if ( empty( $xml_url ) ) {
        $error = new WP_Error( 'recrm_no_saved_url', 'Не збережено XML URL для автоімпорту.' );
        recrm_save_import_result(
            array(
                'status'      => 'error',
                'message'     => $error->get_error_message(),
                'source'      => '',
                'source_type' => 'saved_url',
            )
        );
        return $error;
    }

    recrm_set_import_lock();

    try {
        $xml_string = recrm_fetch_xml_string_from_url( $xml_url );

        if ( is_wp_error( $xml_string ) ) {
            recrm_save_import_result(
                array(
                    'status'      => 'error',
                    'message'     => $xml_string->get_error_message(),
                    'source'      => $xml_url,
                    'source_type' => 'saved_url',
                )
            );
            return $xml_string;
        }

        $items = recrm_prepare_xml_items_from_string( $xml_string );
        if ( is_wp_error( $items ) ) {
            recrm_save_import_result(
                array(
                    'status'      => 'error',
                    'message'     => $items->get_error_message(),
                    'source'      => $xml_url,
                    'source_type' => 'saved_url',
                )
            );
            return $items;
        }

        $stats = array(
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed'  => 0,
            'trashed' => 0,
        );

        $seen_internal_ids = array();

        foreach ( $items as $item_xml_string ) {
            $item = simplexml_load_string( $item_xml_string );

            if ( $item && isset( $item['internal-id'] ) ) {
                $seen_internal_ids[] = (string) $item['internal-id'];
            }

            $result = recrm_import_single_xml_item_with_status( $item_xml_string );

            if ( isset( $stats[ $result ] ) ) {
                $stats[ $result ]++;
            } else {
                $stats['failed']++;
            }
        }

        $stats['trashed'] = recrm_trash_missing_imported_properties( $seen_internal_ids );

        $message = sprintf(
            'Автоімпорт завершено. Створено: %d, оновлено: %d, пропущено: %d, у кошик: %d, помилки: %d.',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
            $stats['trashed'],
            $stats['failed']
        );

        recrm_save_import_result(
            array(
                'status'      => 'success',
                'message'     => $message,
                'created'     => $stats['created'],
                'updated'     => $stats['updated'],
                'skipped'     => $stats['skipped'],
                'trashed'     => $stats['trashed'],
                'failed'      => $stats['failed'],
                'source'      => $xml_url,
                'source_type' => 'saved_url',
            )
        );

        return $stats;
    } finally {
        recrm_release_import_lock();
    }
}

function recrm_run_cron_import() {
    recrm_run_saved_feed_import();
}
add_action( 'recrm_xml_import_cron', 'recrm_run_cron_import' );

function recrm_ajax_start_import() {

    check_ajax_referer( 'recrm_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Недостатньо прав.' ), 403 );
    }

    $xml_string  = '';
    $source      = '';
    $source_type = '';

    if ( ! empty( $_POST['xml_url'] ) ) {
        $xml_url = esc_url_raw( wp_unslash( $_POST['xml_url'] ) );
        $body    = recrm_fetch_xml_string_from_url( $xml_url );

        if ( is_wp_error( $body ) ) {
            wp_send_json_error( array( 'message' => $body->get_error_message() ) );
        }

        $xml_string  = $body;
        $source      = $xml_url;
        $source_type = 'url';

        $settings            = recrm_get_import_settings();
        $settings['xml_url'] = $xml_url;
        recrm_update_import_settings( $settings );
        recrm_sync_import_schedule();
    } elseif ( ! empty( $_FILES['xml_file']['tmp_name'] ) ) {
        $xml_string  = file_get_contents( $_FILES['xml_file']['tmp_name'] );
        $source      = sanitize_file_name( $_FILES['xml_file']['name'] );
        $source_type = 'file';
    } else {
        wp_send_json_error( array( 'message' => 'Не передано XML.' ) );
    }

    $items = recrm_prepare_xml_items_from_string( $xml_string );

    if ( is_wp_error( $items ) ) {
        wp_send_json_error( array( 'message' => $items->get_error_message() ) );
    }

    recrm_set_import_lock();

    $session_key = 'recrm_import_' . wp_generate_password( 12, false, false );

    set_transient(
        $session_key,
        array(
            'items'       => $items,
            'total'       => count( $items ),
            'current'     => 0,
            'done'        => 0,
            'failed'      => 0,
            'created'     => 0,
            'updated'     => 0,
            'skipped'     => 0,
            'source'      => $source,
            'source_type' => $source_type,
            'started_at'  => current_time( 'mysql' ),
        ),
        HOUR_IN_SECONDS
    );

    wp_send_json_success(
        array(
            'session_key' => $session_key,
            'total'       => count( $items ),
        )
    );
}
add_action( 'wp_ajax_recrm_start_import', 'recrm_ajax_start_import' );

function recrm_ajax_process_import_batch() {

    check_ajax_referer( 'recrm_import_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Недостатньо прав.' ), 403 );
    }

    $session_key = isset( $_POST['session_key'] ) ? sanitize_text_field( wp_unslash( $_POST['session_key'] ) ) : '';
    $batch_size  = isset( $_POST['batch_size'] ) ? max( 1, absint( $_POST['batch_size'] ) ) : 5;
    $data        = get_transient( $session_key );

    if ( empty( $data ) || empty( $data['items'] ) ) {
        recrm_release_import_lock();
        wp_send_json_error( array( 'message' => 'Сесію імпорту не знайдено або вона завершилась.' ) );
    }

    $items   = $data['items'];
    $total   = (int) $data['total'];
    $current = (int) $data['current'];
    $done    = (int) $data['done'];
    $failed  = (int) $data['failed'];
    $created = isset( $data['created'] ) ? (int) $data['created'] : 0;
    $updated = isset( $data['updated'] ) ? (int) $data['updated'] : 0;
    $skipped = isset( $data['skipped'] ) ? (int) $data['skipped'] : 0;
    $trashed           = isset( $data['trashed'] ) ? (int) $data['trashed'] : 0;
    $seen_internal_ids = isset( $data['seen_internal_ids'] ) ? (array) $data['seen_internal_ids'] : array();    

    $end = min( $current + $batch_size, $total );

    for ( $i = $current; $i < $end; $i++ ) {
        error_log( 'BATCH STEP 1: before import item ' . $i );

        try {
            $result = recrm_import_single_xml_item_with_status( $items[ $i ] );
        } catch ( Throwable $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }

        error_log( 'BATCH STEP 2: after import item ' . $i . ' result=' . $result );

        if ( 'created' === $result ) {
            $done++;
            $created++;
        } elseif ( 'updated' === $result ) {
            $done++;
            $updated++;
        } elseif ( 'skipped' === $result ) {
            $skipped++;
        } else {
            $failed++;
        }
    }

    $current  = $end;
    $finished = $current >= $total;
    $percent  = $total > 0 ? round( ( $current / $total ) * 100 ) : 100;

    if ( $finished ) {
        $trashed = recrm_trash_missing_imported_properties( $seen_internal_ids );

        delete_transient( $session_key );
        recrm_release_import_lock();

        $message = sprintf(
            'Імпорт завершено. Створено: %d, оновлено: %d, пропущено: %d, у кошик: %d, помилки: %d.',
            $created,
            $updated,
            $skipped,
            $trashed,
            $failed
        );

        recrm_save_import_result(
            array(
                'time'        => ! empty( $data['started_at'] ) ? $data['started_at'] : current_time( 'mysql' ),
                'status'      => 'success',
                'message'     => $message,
                'created'     => $created,
                'updated'     => $updated,
                'skipped'     => $skipped,
                'trashed'     => $trashed,
                'failed'      => $failed,
                'source'      => isset( $data['source'] ) ? $data['source'] : '',
                'source_type' => isset( $data['source_type'] ) ? $data['source_type'] : '',
            )
        );

        
    } else {

        $seen_internal_ids = array();

        foreach ( $items as $item_xml_string ) {
            $item = simplexml_load_string( $item_xml_string );

            if ( $item && isset( $item['internal-id'] ) ) {
                $seen_internal_ids[] = (string) $item['internal-id'];
            }
        }

        set_transient(
            $session_key,
            array(
                'items'       => $items,
                'total'       => $total,
                'current'     => $current,
                'done'        => $done,
                'failed'      => $failed,
                'created'     => $created,
                'updated'     => $updated,
                'skipped'     => $skipped,
                'trashed'           => 0,
                'seen_internal_ids' => $seen_internal_ids,
                'source'      => isset( $data['source'] ) ? $data['source'] : '',
                'source_type' => isset( $data['source_type'] ) ? $data['source_type'] : '',
                'started_at'  => isset( $data['started_at'] ) ? $data['started_at'] : current_time( 'mysql' ),
            ),
            HOUR_IN_SECONDS
        );
    }

    wp_send_json_success(
        array(
            'finished' => $finished,
            'total'    => $total,
            'current'  => $current,
            'done'     => $done,
            'failed'   => $failed,
            'created'  => $created,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'percent'  => $percent,
        )
    );
}

function recrm_trash_missing_properties( $seen_internal_ids ) {
    $seen_internal_ids = array_filter(
        array_map(
            'strval',
            (array) $seen_internal_ids
        )
    );

    $posts = get_posts(
        array(
            'post_type'      => 'property',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => 'crm_internal_id',
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );

    $trashed = 0;

    foreach ( $posts as $post_id ) {
        $stored_internal_id = (string) get_post_meta( $post_id, 'crm_internal_id', true );

        if ( '' === $stored_internal_id ) {
            continue;
        }

        if ( in_array( $stored_internal_id, $seen_internal_ids, true ) ) {
            continue;
        }

        $result = wp_trash_post( $post_id );

        if ( $result ) {
            $trashed++;
        }
    }

    return $trashed;
}

function recrm_trash_missing_imported_properties( $seen_internal_ids ) {
    $seen_internal_ids = array_filter( array_map( 'strval', (array) $seen_internal_ids ) );

    $posts = get_posts(
        array(
            'post_type'      => 'property',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'recrm_import_source',
                    'value'   => 'crm_xml',
                    'compare' => '=',
                ),
                array(
                    'key'     => 'crm_internal_id',
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );

    $trashed = 0;

    foreach ( $posts as $post_id ) {
        $stored_internal_id = (string) get_post_meta( $post_id, 'crm_internal_id', true );

        if ( '' === $stored_internal_id ) {
            continue;
        }

        if ( in_array( $stored_internal_id, $seen_internal_ids, true ) ) {
            continue;
        }

        $result = wp_trash_post( $post_id );

        if ( $result ) {
            $trashed++;
        }
    }

    return $trashed;
}

add_action( 'wp_ajax_recrm_process_import_batch', 'recrm_ajax_process_import_batch' );
