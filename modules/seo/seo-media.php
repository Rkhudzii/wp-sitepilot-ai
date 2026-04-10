<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'save_post', 'recrm_seo_sync_post_images', 30, 2 );

function recrm_seo_image_sync_enabled() {
    if ( ! function_exists( 'recrm_get_seo_settings' ) ) {
        return true;
    }

    $settings = recrm_get_seo_settings();

    return ! isset( $settings['auto_image_seo'] ) || '1' === (string) $settings['auto_image_seo'];
}

function recrm_seo_get_post_image_ids( $post_id ) {
    $ids = array();

    $featured_id = get_post_thumbnail_id( $post_id );
    if ( $featured_id ) {
        $ids[] = (int) $featured_id;
    }

    $gallery_ids = get_post_meta( $post_id, 'property_gallery_ids', true );
    if ( is_array( $gallery_ids ) ) {
        $ids = array_merge( $ids, array_map( 'intval', $gallery_ids ) );
    }

    $attached_ids = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_parent'    => $post_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'menu_order ID',
            'order'          => 'ASC',
        )
    );

    if ( ! empty( $attached_ids ) ) {
        $ids = array_merge( $ids, array_map( 'intval', $attached_ids ) );
    }

    $ids = array_values( array_unique( array_filter( $ids ) ) );

    return $ids;
}

function recrm_seo_build_attachment_meta( $post_id, $attachment_id, $position = 1 ) {
    $post_title = get_the_title( $post_id );
    $brand      = function_exists( 'recrm_get_seo_settings' ) ? recrm_get_seo_settings()['brand_name'] : get_bloginfo( 'name' );
    $keyword    = get_post_meta( $post_id, '_recrm_seo_keyword', true );
    $base       = $keyword ? $keyword : $post_title;
    $base       = wp_strip_all_tags( $base );
    $post_title = wp_strip_all_tags( $post_title );

    $alt = 1 === (int) $position ? $post_title : sprintf( '%s — фото %d', $post_title, (int) $position );

    return array(
        'alt'         => $alt,
        'title'       => 1 === (int) $position ? $post_title : sprintf( '%s — фото %d', $post_title, (int) $position ),
        'caption'     => 1 === (int) $position ? sprintf( '%s — %s', $base, $brand ) : sprintf( '%s — фото %d', $base, (int) $position ),
        'description' => sprintf( 'Фото до матеріалу «%s». %s.', $post_title, $brand ),
    );
}

function recrm_seo_sync_attachment_metadata( $post_id, $attachment_id, $position = 1 ) {
    $attachment_id = (int) $attachment_id;
    $post_id       = (int) $post_id;

    if ( ! $post_id || ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) || ! recrm_seo_image_sync_enabled() ) {
        return;
    }

    $meta = recrm_seo_build_attachment_meta( $post_id, $attachment_id, $position );

    update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $meta['alt'] ) );

    remove_action( 'save_post', 'recrm_seo_sync_post_images', 30 );
    wp_update_post(
        array(
            'ID'           => $attachment_id,
            'post_title'   => sanitize_text_field( $meta['title'] ),
            'post_excerpt' => sanitize_text_field( $meta['caption'] ),
            'post_content' => sanitize_textarea_field( $meta['description'] ),
        )
    );
    add_action( 'save_post', 'recrm_seo_sync_post_images', 30, 2 );
}

function recrm_seo_sync_post_images( $post_id, $post ) {
    if ( is_numeric( $post ) ) {
        $post = get_post( $post );
    }

    if ( ! $post || ! function_exists( 'recrm_seo_supported_post_types' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) || 'attachment' === $post->post_type ) {
        return;
    }

    if ( ! in_array( $post->post_type, recrm_seo_supported_post_types(), true ) ) {
        return;
    }

    if ( ! recrm_seo_image_sync_enabled() ) {
        return;
    }

    $image_ids = recrm_seo_get_post_image_ids( $post_id );

    foreach ( $image_ids as $index => $attachment_id ) {
        recrm_seo_sync_attachment_metadata( $post_id, $attachment_id, $index + 1 );
    }
}
