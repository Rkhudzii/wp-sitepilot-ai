<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * -------------------------------------------------------
 * POST TYPE
 * -------------------------------------------------------
 */
function recrm_register_property_post_type() {
    $labels = array(
        'name'                  => 'Обʼєкти',
        'singular_name'         => 'Обʼєкт',
        'menu_name'             => 'Обʼєкти',
        'name_admin_bar'        => 'Обʼєкт',
        'add_new'               => 'Додати новий',
        'add_new_item'          => 'Додати обʼєкт',
        'new_item'              => 'Новий обʼєкт',
        'edit_item'             => 'Редагувати обʼєкт',
        'view_item'             => 'Переглянути обʼєкт',
        'all_items'             => 'Усі обʼєкти',
        'search_items'          => 'Шукати обʼєкти',
        'not_found'             => 'Обʼєктів не знайдено',
        'not_found_in_trash'    => 'У кошику обʼєктів не знайдено',
        'featured_image'        => 'Головне фото',
        'set_featured_image'    => 'Встановити головне фото',
        'remove_featured_image' => 'Видалити головне фото',
        'use_featured_image'    => 'Використати як головне фото',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'menu_icon'           => 'dashicons-admin-home',
        'has_archive'         => true,
        'rewrite'             => array( 'slug' => 'property' ),
        'show_in_rest'        => true,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions' ),
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'exclude_from_search' => false,
        'capability_type'     => 'post',
        'hierarchical'        => false,
    );

    register_post_type( 'property', $args );
}
add_action( 'init', 'recrm_register_property_post_type' );

/**
 * -------------------------------------------------------
 * TAXONOMIES
 * -------------------------------------------------------
 */
function recrm_register_property_taxonomies() {

    register_taxonomy(
        'property_feature',
        'property',
        array(
            'labels' => array(
                'name'              => 'Особливості',
                'singular_name'     => 'Особливість',
                'search_items'      => 'Шукати особливості',
                'all_items'         => 'Усі особливості',
                'edit_item'         => 'Редагувати особливість',
                'update_item'       => 'Оновити особливість',
                'add_new_item'      => 'Додати особливість',
                'new_item_name'     => 'Нова особливість',
                'menu_name'         => 'Особливості',
            ),
            'public'            => true,
            'hierarchical'      => false,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'property-feature' ),
        )
    );

    register_taxonomy(
        'property_location',
        'property',
        array(
            'labels' => array(
                'name'              => 'Локації',
                'singular_name'     => 'Локація',
                'search_items'      => 'Шукати локації',
                'all_items'         => 'Усі локації',
                'edit_item'         => 'Редагувати локацію',
                'update_item'       => 'Оновити локацію',
                'add_new_item'      => 'Додати локацію',
                'new_item_name'     => 'Нова локація',
                'menu_name'         => 'Локації',
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'property-location' ),
        )
    );

    register_taxonomy(
        'property_type',
        'property',
        array(
            'labels' => array(
                'name'              => 'Типи нерухомості',
                'singular_name'     => 'Тип нерухомості',
                'search_items'      => 'Шукати типи',
                'all_items'         => 'Усі типи',
                'edit_item'         => 'Редагувати тип',
                'update_item'       => 'Оновити тип',
                'add_new_item'      => 'Додати тип',
                'new_item_name'     => 'Новий тип',
                'menu_name'         => 'Типи нерухомості',
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'property-type' ),
        )
    );

    register_taxonomy(
        'property_status',
        'property',
        array(
            'labels' => array(
                'name'              => 'Статуси',
                'singular_name'     => 'Статус',
                'search_items'      => 'Шукати статуси',
                'all_items'         => 'Усі статуси',
                'edit_item'         => 'Редагувати статус',
                'update_item'       => 'Оновити статус',
                'add_new_item'      => 'Додати статус',
                'new_item_name'     => 'Новий статус',
                'menu_name'         => 'Статуси',
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'property-status' ),
        )
    );
}
add_action( 'init', 'recrm_register_property_taxonomies' );

/**
 * -------------------------------------------------------
 * META BOXES
 * -------------------------------------------------------
 */
function recrm_add_property_meta_boxes() {

    add_meta_box(
        'recrm_property_pricing',
        'Ціна та статус',
        'recrm_render_property_pricing_meta_box',
        'property',
        'normal',
        'high'
    );

    add_meta_box(
        'recrm_property_location',
        'Локація',
        'recrm_render_property_location_meta_box',
        'property',
        'normal',
        'default'
    );

    add_meta_box(
        'recrm_property_details',
        'Характеристики',
        'recrm_render_property_details_meta_box',
        'property',
        'normal',
        'default'
    );

    add_meta_box(
        'recrm_property_media',
        'Медіа та додатково',
        'recrm_render_property_media_meta_box',
        'property',
        'normal',
        'default'
    );

    add_meta_box(
        'recrm_property_gallery',
        'Галерея обʼєкта',
        'recrm_render_property_gallery_meta_box',
        'property',
        'normal',
        'default'
    );

    add_meta_box(
        'recrm_property_showcase',
        'Showcase блок',
        'recrm_render_property_showcase_meta_box',
        'property',
        'side',
        'high'
    );

}
add_action( 'add_meta_boxes', 'recrm_add_property_meta_boxes' );

/**
 * -------------------------------------------------------
 * HELPERS
 * -------------------------------------------------------
 */
function recrm_meta_value( $post_id, $key, $default = '' ) {
    $value = get_post_meta( $post_id, $key, true );
    return ( '' !== $value ) ? $value : $default;
}

function recrm_checkbox_checked( $value ) {
    checked( $value, '1' );
}

/**
 * -------------------------------------------------------
 * RENDER: PRICING
 * -------------------------------------------------------
 */
function recrm_render_property_pricing_meta_box( $post ) {
    wp_nonce_field( 'recrm_save_property_meta', 'recrm_property_meta_nonce' );

    $deal_type       = recrm_meta_value( $post->ID, 'crm_deal', 'sale' );
    $price           = recrm_meta_value( $post->ID, 'property_price' );
    $currency        = recrm_meta_value( $post->ID, 'property_currency', 'USD' );
    $price_text      = recrm_meta_value( $post->ID, 'property_price_text' );
    $hide_price      = recrm_meta_value( $post->ID, 'property_hide_price', '0' );
    $featured        = recrm_meta_value( $post->ID, 'property_featured', '0' );
    $under_offer     = recrm_meta_value( $post->ID, 'property_under_offer', '0' );
    $exclusive       = recrm_meta_value( $post->ID, 'property_exclusive', '0' );
    $external_id     = recrm_meta_value( $post->ID, 'crm_internal_id' );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="recrm_deal_type">Тип угоди</label></th>
            <td>
                <select name="recrm_deal_type" id="recrm_deal_type">
                    <option value="sale" <?php selected( $deal_type, 'sale' ); ?>>Продаж</option>
                    <option value="rent" <?php selected( $deal_type, 'rent' ); ?>>Оренда</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="recrm_price">Ціна</label></th>
            <td><input type="text" name="recrm_price" id="recrm_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_currency">Валюта</label></th>
            <td>
                <select name="recrm_currency" id="recrm_currency">
                    <option value="USD" <?php selected( $currency, 'USD' ); ?>>USD</option>
                    <option value="EUR" <?php selected( $currency, 'EUR' ); ?>>EUR</option>
                    <option value="UAH" <?php selected( $currency, 'UAH' ); ?>>UAH</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="recrm_price_text">Текст замість ціни</label></th>
            <td>
                <input type="text" name="recrm_price_text" id="recrm_price_text" value="<?php echo esc_attr( $price_text ); ?>" class="regular-text">
                <p class="description">Наприклад: Ціна за запитом / Договірна</p>
            </td>
        </tr>
        <tr>
            <th>Опції</th>
            <td>
                <label><input type="checkbox" name="recrm_hide_price" value="1" <?php recrm_checkbox_checked( $hide_price ); ?>> Приховати ціну</label><br>
                <label><input type="checkbox" name="recrm_featured" value="1" <?php recrm_checkbox_checked( $featured ); ?>> Виділений обʼєкт</label><br>
                <label><input type="checkbox" name="recrm_under_offer" value="1" <?php recrm_checkbox_checked( $under_offer ); ?>> Під завдатком / під угодою</label>
                <label><input type="checkbox" name="recrm_exclusive" value="1" <?php recrm_checkbox_checked( $exclusive ); ?>> Ексклюзив</label>
            </td>
        </tr>
        <tr>
            <th><label for="recrm_external_id">External ID</label></th>
            <td>
                <input type="text" name="recrm_external_id" id="recrm_external_id" value="<?php echo esc_attr( $external_id ); ?>" class="regular-text">
                <p class="description">ID з CRM або XML, щоб потім оновлювати обʼєкти без дублів.</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * -------------------------------------------------------
 * RENDER: LOCATION
 * -------------------------------------------------------
 */
function recrm_render_property_location_meta_box( $post ) {
    $address      = recrm_meta_value( $post->ID, 'property_address' );
    $city         = recrm_meta_value( $post->ID, 'property_city' );
    $district     = recrm_meta_value( $post->ID, 'property_district' );
    $region       = recrm_meta_value( $post->ID, 'property_region' );
    $postcode     = recrm_meta_value( $post->ID, 'property_postcode' );
    $latitude     = recrm_meta_value( $post->ID, 'property_latitude' );
    $longitude    = recrm_meta_value( $post->ID, 'property_longitude' );
    $hide_address = recrm_meta_value( $post->ID, 'property_hide_address', '0' );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="recrm_address">Адреса</label></th>
            <td><input type="text" name="recrm_address" id="recrm_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_city">Місто</label></th>
            <td><input type="text" name="recrm_city" id="recrm_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_district">Район</label></th>
            <td><input type="text" name="recrm_district" id="recrm_district" value="<?php echo esc_attr( $district ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_region">Область / регіон</label></th>
            <td><input type="text" name="recrm_region" id="recrm_region" value="<?php echo esc_attr( $region ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_postcode">Поштовий індекс</label></th>
            <td><input type="text" name="recrm_postcode" id="recrm_postcode" value="<?php echo esc_attr( $postcode ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_latitude">Latitude</label></th>
            <td><input type="text" name="recrm_latitude" id="recrm_latitude" value="<?php echo esc_attr( $latitude ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_longitude">Longitude</label></th>
            <td><input type="text" name="recrm_longitude" id="recrm_longitude" value="<?php echo esc_attr( $longitude ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th>Опція</th>
            <td>
                <label><input type="checkbox" name="recrm_hide_address" value="1" <?php recrm_checkbox_checked( $hide_address ); ?>> Приховати адресу</label>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * -------------------------------------------------------
 * RENDER: DETAILS
 * -------------------------------------------------------
 */
function recrm_render_property_details_meta_box( $post ) {
    $bedrooms     = recrm_meta_value( $post->ID, 'property_bedrooms' );
    $bathrooms    = recrm_meta_value( $post->ID, 'property_bathrooms' );
    $rooms        = recrm_meta_value( $post->ID, 'property_rooms' );
    $parking      = recrm_meta_value( $post->ID, 'property_parking' );
    $floor        = recrm_meta_value( $post->ID, 'property_floor' );
    $floors_total = recrm_meta_value( $post->ID, 'property_floors_total' );
    $area_total   = recrm_meta_value( $post->ID, 'property_area_total' );
    $area_living  = recrm_meta_value( $post->ID, 'property_area_living' );
    $area_kitchen = recrm_meta_value( $post->ID, 'property_area_kitchen' );
    $land_area    = recrm_meta_value( $post->ID, 'property_land_area' );
    $year_built   = recrm_meta_value( $post->ID, 'property_year_built' );
    $condition    = recrm_meta_value( $post->ID, 'property_condition' );
    $heating      = recrm_meta_value( $post->ID, 'property_heating' );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="recrm_bedrooms">Спальні</label></th>
            <td><input type="number" min="0" step="1" name="recrm_bedrooms" id="recrm_bedrooms" value="<?php echo esc_attr( $bedrooms ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_bathrooms">Санвузли</label></th>
            <td><input type="number" min="0" step="1" name="recrm_bathrooms" id="recrm_bathrooms" value="<?php echo esc_attr( $bathrooms ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_rooms">Кімнати</label></th>
            <td><input type="number" min="0" step="1" name="recrm_rooms" id="recrm_rooms" value="<?php echo esc_attr( $rooms ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_parking">Паркінг</label></th>
            <td>
                <select name="recrm_parking" id="recrm_parking">
                    <option value="">— Вибрати —</option>
                    <option value="none" <?php selected( $parking, 'none' ); ?>>Немає</option>
                    <option value="yard" <?php selected( $parking, 'yard' ); ?>>У дворі</option>
                    <option value="garage" <?php selected( $parking, 'garage' ); ?>>Гараж</option>
                    <option value="underground" <?php selected( $parking, 'underground' ); ?>>Підземний</option>
                    <option value="covered" <?php selected( $parking, 'covered' ); ?>>Критий</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="recrm_floor">Поверх</label></th>
            <td><input type="number" min="0" step="1" name="recrm_floor" id="recrm_floor" value="<?php echo esc_attr( $floor ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_floors_total">Поверховість</label></th>
            <td><input type="number" min="0" step="1" name="recrm_floors_total" id="recrm_floors_total" value="<?php echo esc_attr( $floors_total ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_area_total">Загальна площа, м²</label></th>
            <td><input type="text" name="recrm_area_total" id="recrm_area_total" value="<?php echo esc_attr( $area_total ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_area_living">Житлова площа, м²</label></th>
            <td><input type="text" name="recrm_area_living" id="recrm_area_living" value="<?php echo esc_attr( $area_living ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_area_kitchen">Площа кухні, м²</label></th>
            <td><input type="text" name="recrm_area_kitchen" id="recrm_area_kitchen" value="<?php echo esc_attr( $area_kitchen ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_land_area">Площа ділянки</label></th>
            <td><input type="text" name="recrm_land_area" id="recrm_land_area" value="<?php echo esc_attr( $land_area ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_year_built">Рік побудови</label></th>
            <td><input type="number" min="0" step="1" name="recrm_year_built" id="recrm_year_built" value="<?php echo esc_attr( $year_built ); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_condition">Стан</label></th>
            <td>
                <select name="recrm_condition" id="recrm_condition">
                    <option value="">— Вибрати —</option>
                    <option value="new" <?php selected( $condition, 'new' ); ?>>Новобудова</option>
                    <option value="renovated" <?php selected( $condition, 'renovated' ); ?>>З ремонтом</option>
                    <option value="cosmetic" <?php selected( $condition, 'cosmetic' ); ?>>Косметичний ремонт</option>
                    <option value="needs_repair" <?php selected( $condition, 'needs_repair' ); ?>>Потребує ремонту</option>
                    <option value="shell" <?php selected( $condition, 'shell' ); ?>>Сирець</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="recrm_heating">Опалення</label></th>
            <td>
                <select name="recrm_heating" id="recrm_heating">
                    <option value="">— Вибрати —</option>
                    <option value="central" <?php selected( $heating, 'central' ); ?>>Централізоване</option>
                    <option value="individual_gas" <?php selected( $heating, 'individual_gas' ); ?>>Індивідуальне газове</option>
                    <option value="electric" <?php selected( $heating, 'electric' ); ?>>Електричне</option>
                    <option value="solid" <?php selected( $heating, 'solid' ); ?>>Твердопаливне</option>
                    <option value="none" <?php selected( $heating, 'none' ); ?>>Без опалення</option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * -------------------------------------------------------
 * RENDER: MEDIA
 * -------------------------------------------------------
 */
function recrm_render_property_media_meta_box( $post ) {
    $video_url        = recrm_meta_value( $post->ID, 'property_video_url' );
    $virtual_tour_url = recrm_meta_value( $post->ID, 'property_virtual_tour_url' );
    $pdf_url          = recrm_meta_value( $post->ID, 'property_pdf_url' );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="recrm_video_url">Відео URL</label></th>
            <td><input type="url" name="recrm_video_url" id="recrm_video_url" value="<?php echo esc_attr( $video_url ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_virtual_tour_url">3D / Virtual Tour URL</label></th>
            <td><input type="url" name="recrm_virtual_tour_url" id="recrm_virtual_tour_url" value="<?php echo esc_attr( $virtual_tour_url ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="recrm_pdf_url">PDF / планування URL</label></th>
            <td><input type="url" name="recrm_pdf_url" id="recrm_pdf_url" value="<?php echo esc_attr( $pdf_url ); ?>" class="regular-text"></td>
        </tr>
    </table>
    <?php
}

/**
 * -------------------------------------------------------
 * RENDER: GALLERY
 * -------------------------------------------------------
 */

function recrm_render_property_gallery_meta_box( $post ) {
    $gallery_ids = get_post_meta( $post->ID, 'property_gallery_ids', true );
    $featured_id = get_post_thumbnail_id( $post->ID );

    if ( ! is_array( $gallery_ids ) ) {
        $gallery_ids = array_filter( array_map( 'intval', explode( ',', (string) $gallery_ids ) ) );
    }

    wp_nonce_field( 'recrm_gallery_meta_action', 'recrm_gallery_meta_nonce' );

    echo '<input type="hidden" id="recrm_gallery_ids" name="recrm_gallery_ids" value="' . esc_attr( implode( ',', $gallery_ids ) ) . '">';
    echo '<input type="hidden" name="recrm_gallery_featured_nonce_field" value="' . esc_attr( wp_create_nonce( 'recrm_gallery_featured_nonce' ) ) . '">';

    echo '<p style="margin:0 0 12px;">';
    echo '<button type="button" class="button button-primary" id="recrm-add-gallery-images">Додати зображення до галереї</button> ';
    echo '<button type="button" class="button" id="recrm-clear-gallery">Очистити галерею</button>';
    echo '</p>';

    echo '<div id="recrm-gallery-wrap" style="display:flex; flex-wrap:wrap; gap:15px;">';

    if ( ! empty( $gallery_ids ) ) {
        foreach ( $gallery_ids as $attachment_id ) {
            $image_html = wp_get_attachment_image(
                $attachment_id,
                'medium',
                false,
                array(
                    'style' => 'display:block; width:150px; height:150px; object-fit:cover; border-radius:6px; border:1px solid #ddd;'
                )
            );

            if ( ! $image_html ) {
                continue;
            }

            $checked = checked( $featured_id, $attachment_id, false );

            echo '<div class="recrm-gallery-item" data-attachment-id="' . esc_attr( $attachment_id ) . '" style="width:170px;">';
            echo $image_html;
            echo '<p style="margin:8px 0 6px;">';
            echo '<label>';
            echo '<input type="radio" name="recrm_featured_image_id" value="' . esc_attr( $attachment_id ) . '" ' . $checked . '> ';
            echo 'Головне фото';
            echo '</label>';
            echo '</p>';
            echo '<p style="margin:0;">';
            echo '<button type="button" class="button-link-delete recrm-remove-gallery-image">Видалити</button>';
            echo '</p>';
            echo '</div>';
        }
    } else {
        echo '<p id="recrm-gallery-empty" style="margin:0;">Галерея порожня. Додай фото через кнопку вище.</p>';
    }

    echo '</div>';

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var addButton = document.getElementById('recrm-add-gallery-images');
        var clearButton = document.getElementById('recrm-clear-gallery');
        var idsInput = document.getElementById('recrm_gallery_ids');
        var wrap = document.getElementById('recrm-gallery-wrap');

        if (!addButton || !idsInput || !wrap || typeof wp === 'undefined' || !wp.media) {
            return;
        }

        function getIds() {
            return (idsInput.value || '')
                .split(',')
                .map(function (id) { return parseInt(id, 10); })
                .filter(function (id) { return id > 0; });
        }

        function setIds(ids) {
            idsInput.value = ids.join(',');
        }

        function removeEmptyText() {
            var empty = document.getElementById('recrm-gallery-empty');
            if (empty) {
                empty.remove();
            }
        }

        function maybeShowEmpty() {
            if (!wrap.querySelector('.recrm-gallery-item')) {
                wrap.innerHTML = '<p id="recrm-gallery-empty" style="margin:0;">Галерея порожня. Додай фото через кнопку вище.</p>';
            }
        }

        function renderItem(attachment) {
            removeEmptyText();

            var item = document.createElement('div');
            item.className = 'recrm-gallery-item';
            item.setAttribute('data-attachment-id', attachment.id);
            item.style.width = '170px';

            var checked = !wrap.querySelector('input[name="recrm_featured_image_id"]:checked') ? 'checked' : '';

            item.innerHTML =
                '<img src="' + attachment.sizes.medium.url + '" style="display:block;width:150px;height:150px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">' +
                '<p style="margin:8px 0 6px;">' +
                    '<label>' +
                        '<input type="radio" name="recrm_featured_image_id" value="' + attachment.id + '" ' + checked + '> Головне фото' +
                    '</label>' +
                '</p>' +
                '<p style="margin:0;">' +
                    '<button type="button" class="button-link-delete recrm-remove-gallery-image">Видалити</button>' +
                '</p>';

            wrap.appendChild(item);
        }

        var frame = wp.media({
            title: 'Оберіть зображення для галереї',
            button: {
                text: 'Додати в галерею'
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });

        addButton.addEventListener('click', function (e) {
            e.preventDefault();
            frame.open();
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection').toJSON();
            var ids = getIds();

            selection.forEach(function (attachment) {
                if (ids.indexOf(attachment.id) === -1) {
                    ids.push(attachment.id);
                    renderItem(attachment);
                }
            });

            setIds(ids);
        });

        wrap.addEventListener('click', function (e) {
            if (!e.target.classList.contains('recrm-remove-gallery-image')) {
                return;
            }

            e.preventDefault();

            var item = e.target.closest('.recrm-gallery-item');
            if (!item) {
                return;
            }

            var id = parseInt(item.getAttribute('data-attachment-id'), 10);
            var ids = getIds().filter(function (galleryId) {
                return galleryId !== id;
            });

            var removedFeatured = item.querySelector('input[name="recrm_featured_image_id"]')?.checked;

            item.remove();
            setIds(ids);

            if (removedFeatured) {
                var firstRadio = wrap.querySelector('input[name="recrm_featured_image_id"]');
                if (firstRadio) {
                    firstRadio.checked = true;
                }
            }

            maybeShowEmpty();
        });

        if (clearButton) {
            clearButton.addEventListener('click', function (e) {
                e.preventDefault();
                setIds([]);
                wrap.innerHTML = '<p id="recrm-gallery-empty" style="margin:0;">Галерея порожня. Додай фото через кнопку вище.</p>';
            });
        }
    });
    </script>
    <?php
}

/**
 * -------------------------------------------------------
 * RENDER: SHOWCASE
 * -------------------------------------------------------
 */
function recrm_render_property_showcase_meta_box( $post ) {
    $showcase = recrm_meta_value( $post->ID, 'property_showcase', '0' );
    ?>
    <p>
        <label>
            <input type="checkbox" name="recrm_showcase" value="1" <?php recrm_checkbox_checked( $showcase ); ?>>
            Показувати в showcase-блоці
        </label>
    </p>
    <p class="description">
        У цей блок потраплятимуть тільки вручну відмічені об’єкти з якісним головним фото.
    </p>
    <?php
}

/**
 * -------------------------------------------------------
 * SAVE META
 * -------------------------------------------------------
 */
function recrm_save_property_meta( $post_id ) {

    if ( ! isset( $_POST['recrm_property_meta_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( $_POST['recrm_property_meta_nonce'], 'recrm_save_property_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! isset( $_POST['post_type'] ) || 'property' !== $_POST['post_type'] ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if (
        isset( $_POST['recrm_gallery_featured_nonce_field'] ) &&
        wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['recrm_gallery_featured_nonce_field'] ) ),
            'recrm_gallery_featured_nonce'
        )
    ) {
        if ( isset( $_POST['recrm_featured_image_id'] ) ) {
            $featured_image_id = absint( $_POST['recrm_featured_image_id'] );

            if ( $featured_image_id > 0 ) {
                set_post_thumbnail( $post_id, $featured_image_id );
            }
        }
    }

        if (
        isset( $_POST['recrm_gallery_meta_nonce'] ) &&
        wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['recrm_gallery_meta_nonce'] ) ),
            'recrm_gallery_meta_action'
        )
    ) {
        $gallery_ids = isset( $_POST['recrm_gallery_ids'] )
            ? array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_POST['recrm_gallery_ids'] ) ) ) )
            : array();

        $gallery_ids = array_values( array_unique( $gallery_ids ) );

        if ( ! empty( $gallery_ids ) ) {
            update_post_meta( $post_id, 'property_gallery_ids', $gallery_ids );
            update_post_meta( $post_id, 'property_gallery', implode( ',', $gallery_ids ) );

            foreach ( $gallery_ids as $index => $attachment_id ) {
                wp_update_post(
                    array(
                        'ID'          => $attachment_id,
                        'post_parent' => $post_id,
                    )
                );

                if ( function_exists( 'recrm_seo_sync_attachment_metadata' ) ) {
                    recrm_seo_sync_attachment_metadata( $post_id, $attachment_id, $index + 1 );
                }
            }

            if ( ! has_post_thumbnail( $post_id ) ) {
                set_post_thumbnail( $post_id, (int) $gallery_ids[0] );
            }
        } else {
            delete_post_meta( $post_id, 'property_gallery_ids' );
            delete_post_meta( $post_id, 'property_gallery' );
        }
    }

    $fields = array(
        'crm_deal'                  => isset( $_POST['recrm_deal_type'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_deal_type'] ) ) : 'sale',
        'property_price'            => isset( $_POST['recrm_price'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_price'] ) ) : '',
        'property_currency'         => isset( $_POST['recrm_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_currency'] ) ) : 'USD',
        'property_price_text'       => isset( $_POST['recrm_price_text'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_price_text'] ) ) : '',
        'property_hide_price'       => isset( $_POST['recrm_hide_price'] ) ? '1' : '0',
        'property_featured'         => isset( $_POST['recrm_featured'] ) ? '1' : '0',
        'property_under_offer'      => isset( $_POST['recrm_under_offer'] ) ? '1' : '0',
        'property_exclusive'        => isset( $_POST['recrm_exclusive'] ) ? '1' : '0',
        'crm_internal_id'           => isset( $_POST['recrm_external_id'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_external_id'] ) ) : '',

        'property_address'          => isset( $_POST['recrm_address'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_address'] ) ) : '',
        'property_city'             => isset( $_POST['recrm_city'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_city'] ) ) : '',
        'property_district'         => isset( $_POST['recrm_district'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_district'] ) ) : '',
        'property_region'           => isset( $_POST['recrm_region'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_region'] ) ) : '',
        'property_postcode'         => isset( $_POST['recrm_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_postcode'] ) ) : '',
        'property_latitude'         => isset( $_POST['recrm_latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_latitude'] ) ) : '',
        'property_longitude'        => isset( $_POST['recrm_longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_longitude'] ) ) : '',
        'property_hide_address'     => isset( $_POST['recrm_hide_address'] ) ? '1' : '0',

        'property_bedrooms'         => isset( $_POST['recrm_bedrooms'] ) ? absint( $_POST['recrm_bedrooms'] ) : '',
        'property_bathrooms'        => isset( $_POST['recrm_bathrooms'] ) ? absint( $_POST['recrm_bathrooms'] ) : '',
        'property_rooms'            => isset( $_POST['recrm_rooms'] ) ? absint( $_POST['recrm_rooms'] ) : '',
        'property_parking'          => isset( $_POST['recrm_parking'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_parking'] ) ) : '',
        'property_floor'            => isset( $_POST['recrm_floor'] ) ? absint( $_POST['recrm_floor'] ) : '',
        'property_floors_total'     => isset( $_POST['recrm_floors_total'] ) ? absint( $_POST['recrm_floors_total'] ) : '',
        'property_area_total'       => isset( $_POST['recrm_area_total'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_area_total'] ) ) : '',
        'property_area_living'      => isset( $_POST['recrm_area_living'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_area_living'] ) ) : '',
        'property_area_kitchen'     => isset( $_POST['recrm_area_kitchen'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_area_kitchen'] ) ) : '',
        'property_land_area'        => isset( $_POST['recrm_land_area'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_land_area'] ) ) : '',
        'property_year_built'       => isset( $_POST['recrm_year_built'] ) ? absint( $_POST['recrm_year_built'] ) : '',
        'property_condition'        => isset( $_POST['recrm_condition'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_condition'] ) ) : '',
        'property_heating'          => isset( $_POST['recrm_heating'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_heating'] ) ) : '',

        'property_video_url'        => isset( $_POST['recrm_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['recrm_video_url'] ) ) : '',
        'property_virtual_tour_url' => isset( $_POST['recrm_virtual_tour_url'] ) ? esc_url_raw( wp_unslash( $_POST['recrm_virtual_tour_url'] ) ) : '',
        'property_pdf_url'          => isset( $_POST['recrm_pdf_url'] ) ? esc_url_raw( wp_unslash( $_POST['recrm_pdf_url'] ) ) : '',
        'property_showcase'         => isset( $_POST['recrm_showcase'] ) ? '1' : '0',
    );

    foreach ( $fields as $meta_key => $meta_value ) {
        if ( '' === $meta_value ) {
            delete_post_meta( $post_id, $meta_key );
        } else {
            update_post_meta( $post_id, $meta_key, $meta_value );
        }
    }
}

add_action( 'admin_enqueue_scripts', 'recrm_property_admin_gallery_assets' );

function recrm_property_admin_gallery_assets( $hook ) {
    global $post;

    if ( ! $post || 'property' !== get_post_type( $post ) ) {
        return;
    }

    wp_enqueue_media();
}

add_action( 'save_post', 'recrm_save_property_meta' );

add_filter( 'single_template', function( $template ) {
    global $post;

    if ( $post->post_type === 'property' ) {
        $plugin_template = RECRM_XML_IMPORT_PATH . 'templates/single-property.php';

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    return $template;
});


