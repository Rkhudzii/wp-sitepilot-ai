<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'posts_clauses', 'recrm_filter_exclusive_first_clauses', 10, 2 );

function recrm_filter_get_order_args( $sort = 'date_desc' ) {
    switch ( $sort ) {
        case 'price_asc':
            return array(
                'meta_key' => 'property_price',
                'orderby'  => 'meta_value_num',
                'order'    => 'ASC',
            );

        case 'price_desc':
            return array(
                'meta_key' => 'property_price',
                'orderby'  => 'meta_value_num',
                'order'    => 'DESC',
            );

        case 'title_asc':
            return array(
                'orderby' => 'title',
                'order'   => 'ASC',
            );

        case 'date_asc':
            return array(
                'orderby' => 'date',
                'order'   => 'ASC',
            );

        case 'date_desc':
        default:
            return array(
                'orderby' => 'date',
                'order'   => 'DESC',
            );
    }
}

function recrm_filter_build_meta_query( $source ) {
    $meta_query = array(
        'relation' => 'AND',
    );

    $type = recrm_filter_get_request_value( $source, 'type', '' );

    if ( ! empty( $type ) ) {
        $type_map = array(
            'kvartyry'   => 'квартира',
            'budynky'    => 'будинок',
            'komertsiya' => 'комерц',
            'dilyanky'   => 'ділян',
        );

        if ( isset( $type_map[ $type ] ) ) {
            $meta_query[] = array(
                'key'     => 'crm_realty_type',
                'value'   => $type_map[ $type ],
                'compare' => 'LIKE',
            );
        }
    }

    $equals_map = array(
        'offer_type' => 'crm_deal',
        'city'       => 'property_city',
        'district'   => 'property_district',
        'condition'  => 'property_condition',
        'heating'    => 'property_heating',
    );

    foreach ( $equals_map as $request_key => $meta_key ) {
        $value = recrm_filter_get_request_value( $source, $request_key, '' );

        if ( '' !== $value ) {
            $meta_query[] = array(
                'key'     => $meta_key,
                'value'   => $value,
                'compare' => '=',
            );
        }
    }

    $numeric_equals_map = array(
        'rooms'        => 'property_rooms',
        'floor'        => 'property_floor',
        'floors_total' => 'property_floors_total',
    );

    foreach ( $numeric_equals_map as $request_key => $meta_key ) {
        $value = recrm_filter_get_request_value( $source, $request_key, '' );

        if ( '' !== $value ) {
            $meta_query[] = array(
                'key'     => $meta_key,
                'value'   => (float) $value,
                'type'    => 'NUMERIC',
                'compare' => '=',
            );
        }
    }

    $ranges = array(
        'price_min'     => array( 'key' => 'property_price',      'compare' => '>=' ),
        'price_max'     => array( 'key' => 'property_price',      'compare' => '<=' ),
        'area_min'      => array( 'key' => 'property_area_total', 'compare' => '>=' ),
        'area_max'      => array( 'key' => 'property_area_total', 'compare' => '<=' ),
        'land_area_min' => array( 'key' => 'property_land_area',  'compare' => '>=' ),
        'land_area_max' => array( 'key' => 'property_land_area',  'compare' => '<=' ),
    );

    foreach ( $ranges as $request_key => $range_data ) {
        $value = recrm_filter_get_request_value( $source, $request_key, '' );

        if ( '' !== $value ) {
            $meta_query[] = array(
                'key'     => $range_data['key'],
                'value'   => (float) $value,
                'type'    => 'NUMERIC',
                'compare' => $range_data['compare'],
            );
        }
    }

    return $meta_query;
}

function recrm_filter_resolve_property_type_term_id( $raw_value ) {
    if ( '' === $raw_value || null === $raw_value ) {
        return 0;
    }

    if ( is_numeric( $raw_value ) ) {
        return absint( $raw_value );
    }

    $raw_value = (string) $raw_value;

    $term = get_term_by( 'slug', sanitize_title( $raw_value ), 'property_type' );

    if ( ! $term ) {
        $term = get_term_by( 'name', $raw_value, 'property_type' );
    }

    if ( $term && ! is_wp_error( $term ) ) {
        return (int) $term->term_id;
    }

    return 0;
}

function recrm_filter_build_tax_query( $source ) {
    $tax_query = array(
        'relation' => 'AND',
    );

    if ( ! recrm_filter_is_active_request( $source ) ) {
        return $tax_query;
    }

    $property_type_raw = recrm_filter_get_request_value( $source, 'property_type', '' );
    $property_type_id  = recrm_filter_resolve_property_type_term_id( $property_type_raw );

    if ( $property_type_id > 0 ) {
        $tax_query[] = array(
            'taxonomy' => 'property_type',
            'field'    => 'term_id',
            'terms'    => array( $property_type_id ),
            'operator' => 'IN',
        );
    }

    return $tax_query;
}

function recrm_filter_get_query_args( $source, $atts = array() ) {
    $sort  = recrm_filter_get_request_value( $source, 'sort', 'date_desc' );
    $paged = max( 1, absint( recrm_filter_get_request_value( $source, 'paged', 1 ) ) );
    $limit = ! empty( $atts['limit'] ) ? max( 1, absint( $atts['limit'] ) ) : 12;

    $args = array(
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'paged'          => $paged,
        'recrm_exclusive_first' => 1,
    );

    $meta_query = recrm_filter_build_meta_query( $source );
    $tax_query  = recrm_filter_build_tax_query( $source );

    if ( count( $meta_query ) > 1 ) {
        $args['meta_query'] = $meta_query;
    }

    if ( count( $tax_query ) > 1 ) {
        $args['tax_query'] = $tax_query;
    }

    $order_args = recrm_filter_get_order_args( $sort );

    return array_merge( $args, $order_args );
}

function recrm_filter_exclusive_first_clauses( $clauses, $query ) {
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

    $alias = 'recrm_exclusive_sort_meta';

    if ( false === strpos( $clauses['join'], $alias ) ) {
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} {$alias}
            ON ({$wpdb->posts}.ID = {$alias}.post_id AND {$alias}.meta_key = 'property_exclusive')";
    }

    $exclusive_order = "CAST(COALESCE(NULLIF({$alias}.meta_value, ''), '0') AS UNSIGNED) DESC";

    if ( ! empty( $clauses['orderby'] ) ) {
        $clauses['orderby'] = $exclusive_order . ', ' . $clauses['orderby'];
    } else {
        $clauses['orderby'] = $exclusive_order . ", {$wpdb->posts}.post_date DESC";
    }

    return $clauses;
}

