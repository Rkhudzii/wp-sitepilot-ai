<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'properties', 'recrm_properties_shortcode' );
add_shortcode( 'recrm_showcase_properties', 'recrm_showcase_properties_shortcode' );

function recrm_properties_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'limit' => 6,
        ),
        $atts,
        'properties'
    );

    $query = new WP_Query(
        array(
            'post_type'      => 'property',
            'posts_per_page' => (int) $atts['limit'],
            'post_status'    => 'publish',
        )
    );

    if ( ! $query->have_posts() ) {
        return '<p>Немає обʼєктів.</p>';
    }

    ob_start();

    echo '<div class="recrm-grid">';

    while ( $query->have_posts() ) {
        $query->the_post();

        echo recrm_get_template(
            'property-card.php',
            array(
                'post_id' => get_the_ID(),
            )
        );
    }

    echo '</div>';

    wp_reset_postdata();

    return ob_get_clean();
}

function recrm_showcase_properties_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'limit' => 4,
        ),
        $atts,
        'recrm_showcase_properties'
    );

    $query = new WP_Query(
        array(
            'post_type'      => 'property',
            'posts_per_page' => (int) $atts['limit'],
            'post_status'    => 'publish',
            'meta_key'       => 'property_showcase',
            'meta_value'     => '1',
            'orderby'        => array(
                'date' => 'DESC',
            ),
        )
    );

    if ( ! $query->have_posts() ) {
        return '';
    }

    ob_start();

    echo '<div class="recrm-showcase-grid">';

    while ( $query->have_posts() ) {
        $query->the_post();

        $post_id   = get_the_ID();
        $title     = get_the_title();
        $permalink = get_permalink();
        $image_url = get_the_post_thumbnail_url( $post_id, 'full' );

        if ( ! $image_url ) {
            continue;
        }

        $city     = get_post_meta( $post_id, 'property_city', true );
        $district = get_post_meta( $post_id, 'property_district', true );
        $address  = get_post_meta( $post_id, 'property_address', true );

        $location_parts = array();

        if ( $district ) {
            $location_parts[] = $district;
        }

        if ( $city ) {
            $location_parts[] = $city;
        }

        if ( empty( $location_parts ) && $address ) {
            $location_parts[] = $address;
        }

        $location = implode( ', ', $location_parts );

        echo '<article class="recrm-showcase-card">';
            echo '<a class="recrm-showcase-link" href="' . esc_url( $permalink ) . '">';
                echo '<div class="recrm-showcase-media">';
                    echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '">';
                echo '</div>';
                echo '<div class="recrm-showcase-overlay"></div>';
                echo '<div class="recrm-showcase-content">';
                    echo '<h3 class="recrm-showcase-title">' . esc_html( $title ) . '</h3>';

                    if ( $location ) {
                        echo '<div class="recrm-showcase-location">' . esc_html( $location ) . '</div>';
                    }
                echo '</div>';
            echo '</a>';
        echo '</article>';
    }

    echo '</div>';

    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * Кількість опублікованих об'єктів
 */
function recrm_properties_count_shortcode() {
    $count = wp_count_posts( 'property' );

    if ( ! $count || ! isset( $count->publish ) ) {
        return '50+';
    }

    $count_number = absint( $count->publish );

    // мінімальний поріг
    if ( $count_number < 50 ) {
        return '50';
    }

    return $count_number . '+';
}
add_shortcode( 'recrm_properties_count', 'recrm_properties_count_shortcode' );

add_action( 'wp_enqueue_scripts', 'recrm_showcase_assets' );

function recrm_showcase_assets() {
    wp_enqueue_style(
        'recrm-showcase',
        RECRM_XML_IMPORT_URL . 'assets/css/showcase-properties.css',
        array(),
        RECRM_XML_IMPORT_VERSION
    );
}

