<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$post_id = get_the_ID();

/**
 * Дані плагіна
 */
$plugin_settings = function_exists( 'recrm_get_settings' ) ? recrm_get_settings() : array();

$phone_raw             = isset( $plugin_settings['phone'] ) ? $plugin_settings['phone'] : '';
$email_raw             = isset( $plugin_settings['email'] ) ? $plugin_settings['email'] : '';
$button_text           = isset( $plugin_settings['button_text'] ) ? $plugin_settings['button_text'] : 'Залишити заявку';
$form_shortcode        = isset( $plugin_settings['contact_form_shortcode'] ) ? $plugin_settings['contact_form_shortcode'] : '';
$fallback_image        = isset( $plugin_settings['fallback_image'] ) ? $plugin_settings['fallback_image'] : '';
$default_currency      = isset( $plugin_settings['default_currency'] ) ? $plugin_settings['default_currency'] : '$';
$area_unit             = isset( $plugin_settings['area_unit'] ) ? $plugin_settings['area_unit'] : 'м²';
$enable_map            = isset( $plugin_settings['enable_map'] ) ? $plugin_settings['enable_map'] : '1';
$enable_sticky_sidebar = isset( $plugin_settings['enable_sticky_sidebar'] ) ? $plugin_settings['enable_sticky_sidebar'] : '1';

$phone_href = $phone_raw ? preg_replace( '/[^0-9\+]/', '', $phone_raw ) : '';

/**
 * Meta поля об'єкта
 */
$price            = get_post_meta( $post_id, 'property_price', true );
$currency         = get_post_meta( $post_id, 'property_currency', true );
$price_type       = get_post_meta( $post_id, 'property_price_type', true );
$rooms            = get_post_meta( $post_id, 'property_rooms', true );
$bedrooms         = get_post_meta( $post_id, 'property_bedrooms', true );
$bathrooms        = get_post_meta( $post_id, 'property_bathrooms', true );
$area_total       = get_post_meta( $post_id, 'property_area_total', true );
$land_area        = get_post_meta( $post_id, 'property_land_area', true );
$floor            = get_post_meta( $post_id, 'property_floor', true );
$floors_total     = get_post_meta( $post_id, 'property_floors_total', true );
$address          = get_post_meta( $post_id, 'property_address', true );
$city             = get_post_meta( $post_id, 'property_city', true );
$district         = get_post_meta( $post_id, 'property_district', true );
$region           = get_post_meta( $post_id, 'property_region', true );
$heating          = get_post_meta( $post_id, 'property_heating', true );
$condition        = get_post_meta( $post_id, 'property_condition', true );
$parking          = get_post_meta( $post_id, 'property_parking', true );
$deal             = get_post_meta( $post_id, 'crm_deal', true );
$is_new           = get_post_meta( $post_id, 'crm_is_new_building', true );
$lat              = get_post_meta( $post_id, 'property_lat', true );
$lng              = get_post_meta( $post_id, 'property_lng', true );
$cadastral_number = get_post_meta( $post_id, 'property_cadastral_number', true );
$wall_material    = get_post_meta( $post_id, 'property_wall_material', true );
$land_width       = get_post_meta( $post_id, 'property_land_width', true );
$land_length      = get_post_meta( $post_id, 'property_land_length', true );
$gas              = get_post_meta( $post_id, 'property_gas', true );
$electricity      = get_post_meta( $post_id, 'property_electricity', true );
$water            = get_post_meta( $post_id, 'property_water', true );
$sewerage         = get_post_meta( $post_id, 'property_sewerage', true );
$exclusive        = get_post_meta( $post_id, 'property_exclusive', true );

/**
 * Fallback валюти
 */
if ( empty( $currency ) ) {
    $currency = $default_currency;
}

/**
 * Галерея
 */
$gallery_ids = get_post_meta( $post_id, 'property_gallery_ids', true );

if ( empty( $gallery_ids ) ) {
    $gallery_string = get_post_meta( $post_id, 'property_gallery', true );
    if ( ! empty( $gallery_string ) ) {
        $gallery_ids = array_filter( array_map( 'intval', explode( ',', $gallery_string ) ) );
    }
}

if ( ! is_array( $gallery_ids ) ) {
    $gallery_ids = array();
}

$featured_id   = get_post_thumbnail_id( $post_id );
$all_image_ids = array();

if ( $featured_id ) {
    $all_image_ids[] = (int) $featured_id;
}

if ( ! empty( $gallery_ids ) ) {
    foreach ( $gallery_ids as $img_id ) {
        $img_id = (int) $img_id;
        if ( $img_id && ! in_array( $img_id, $all_image_ids, true ) ) {
            $all_image_ids[] = $img_id;
        }
    }
}

$has_gallery = ! empty( $all_image_ids ) || ! empty( $fallback_image );

/**
 * Локація
 */
$location_parts = array_filter( array( $address, $district, $city, $region ) );
$location_full  = implode( ', ', $location_parts );

/**
 * Основні характеристики
 */
$meta_items = array();

if ( $deal ) {
    $deal_label = $deal;

    if ( 'sale' === $deal ) {
        $deal_label = 'Продаж';
    } elseif ( 'rent' === $deal ) {
        $deal_label = 'Оренда';
    }

    $meta_items[] = array(
        'label' => 'Тип угоди',
        'value' => $deal_label,
    );
}

if ( $bedrooms ) {
    $meta_items[] = array(
        'label' => 'Спальні',
        'value' => $bedrooms,
    );
}
if ( $land_area ) {
    $meta_items[] = array(
        'label' => 'Площа ділянки',
        'value' => $land_area . ' ' . $area_unit,
    );
}
if ( $floors_total ) {
    $meta_items[] = array(
        'label' => 'Поверховість',
        'value' => $floors_total,
    );
}
if ( $parking ) {
    $meta_items[] = array(
        'label' => 'Паркінг',
        'value' => $parking,
    );
}
if ( $condition ) {
    $meta_items[] = array(
        'label' => 'Стан',
        'value' => $condition,
    );
}
if ( $heating ) {
    $meta_items[] = array(
        'label' => 'Опалення',
        'value' => $heating,
    );
}
if ( $wall_material ) {
    $meta_items[] = array(
        'label' => 'Матеріал стін',
        'value' => $wall_material,
    );
}
if ( $cadastral_number ) {
    $meta_items[] = array(
        'label' => 'Кадастровий номер',
        'value' => $cadastral_number,
    );
}
if ( $land_width ) {
    $meta_items[] = array(
        'label' => 'Ширина ділянки',
        'value' => $land_width,
    );
}
if ( $land_length ) {
    $meta_items[] = array(
        'label' => 'Довжина ділянки',
        'value' => $land_length,
    );
}
if ( $gas ) {
    $meta_items[] = array(
        'label' => 'Газ',
        'value' => $gas,
    );
}
if ( $electricity ) {
    $meta_items[] = array(
        'label' => 'Світло',
        'value' => $electricity,
    );
}
if ( $water ) {
    $meta_items[] = array(
        'label' => 'Вода',
        'value' => $water,
    );
}
if ( $sewerage ) {
    $meta_items[] = array(
        'label' => 'Каналізація',
        'value' => $sewerage,
    );
}
if ( '1' === (string) $is_new ) {
    $meta_items[] = array(
        'label' => 'Новобудова',
        'value' => 'Так',
    );
}
if ( '1' === (string) $exclusive ) {
    $meta_items[] = array(
        'label' => 'Ексклюзив',
        'value' => 'Так',
    );
}

/**
 * Короткі параметри
 */
$quick_items = array();

if ( $rooms ) {
    $quick_items[] = array(
        'label' => 'Кімнати',
        'value' => $rooms,
    );
}
if ( $area_total ) {
    $quick_items[] = array(
        'label' => 'Площа',
        'value' => $area_total . ' ' . $area_unit,
    );
}
if ( $floor ) {
    $quick_items[] = array(
        'label' => 'Поверх',
        'value' => $floor,
    );
}
if ( $bathrooms ) {
    $quick_items[] = array(
        'label' => 'Санвузли',
        'value' => $bathrooms,
    );
}

/**
 * Форматування ціни
 */
$price_output = '';
if ( '' !== (string) $price ) {
    $price_output = number_format( (float) $price, 0, '.', ' ' );
    if ( $currency ) {
        $price_output .= ' ' . $currency;
    }
}
?>

<div class="recrm-single">
    <div class="recrm-single-layout">

        <div class="recrm-single-main">

            <section class="recrm-single-hero">

                <div class="recrm-single-gallery-card">
                    <?php if ( $has_gallery ) : ?>

                        <?php if ( ! empty( $all_image_ids ) ) : ?>
                            <?php
                            $first_img_id = (int) $all_image_ids[0];
                            $first_large  = wp_get_attachment_image_url( $first_img_id, 'large' );
                            $first_full   = wp_get_attachment_image_url( $first_img_id, 'full' );
                            $first_alt    = get_post_meta( $first_img_id, '_wp_attachment_image_alt', true );
                            ?>

                            <div class="recrm-single-main-slider">
                                <img
                                    id="recrmSingleMainPhoto"
                                    class="recrm-single-main-photo"
                                    src="<?php echo esc_url( $first_large ? $first_large : $first_full ); ?>"
                                    data-full="<?php echo esc_url( $first_full ? $first_full : $first_large ); ?>"
                                    alt="<?php echo esc_attr( $first_alt ? $first_alt : get_the_title() ); ?>"
                                >
                            </div>

                            <?php if ( count( $all_image_ids ) > 1 ) : ?>
                                <div class="recrm-single-gallery-wrap">
                                    <button class="recrm-gallery-arrow recrm-gallery-arrow-prev" type="button" aria-label="Попередні фото">
                                        &#10094;
                                    </button>

                                    <div class="recrm-single-gallery-grid" id="recrmSingleGalleryTrack">
                                        <?php foreach ( $all_image_ids as $index => $attachment_id ) : ?>
                                            <?php
                                            $thumb_url = wp_get_attachment_image_url( $attachment_id, 'medium' );
                                            $large_url = wp_get_attachment_image_url( $attachment_id, 'large' );
                                            $full_url  = wp_get_attachment_image_url( $attachment_id, 'full' );
                                            $alt_text  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

                                            if ( ! $thumb_url ) {
                                                continue;
                                            }
                                            ?>
                                            <button
                                                type="button"
                                                class="recrm-single-thumb <?php echo 0 === $index ? 'is-active' : ''; ?>"
                                                data-large="<?php echo esc_url( $large_url ? $large_url : $full_url ); ?>"
                                                data-full="<?php echo esc_url( $full_url ? $full_url : $large_url ); ?>"
                                                data-alt="<?php echo esc_attr( $alt_text ? $alt_text : get_the_title( $post_id ) ); ?>"
                                                aria-label="Фото <?php echo esc_attr( $index + 1 ); ?>"
                                            >
                                                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $alt_text ? $alt_text : get_the_title( $post_id ) ); ?>">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                    <button class="recrm-gallery-arrow recrm-gallery-arrow-next" type="button" aria-label="Наступні фото">
                                        &#10095;
                                    </button>
                                </div>
                            <?php endif; ?>

                        <?php elseif ( $fallback_image ) : ?>
                            <div class="recrm-single-main-slider">
                                <img
                                    id="recrmSingleMainPhoto"
                                    class="recrm-single-main-photo"
                                    src="<?php echo esc_url( $fallback_image ); ?>"
                                    data-full="<?php echo esc_url( $fallback_image ); ?>"
                                    alt="<?php echo esc_attr( get_the_title() ); ?>"
                                >
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>

                <div class="recrm-single-heading-card">
                    <h2 class="recrm-single-title"><?php the_title(); ?></h2>

                    <?php if ( $location_full ) : ?>
                        <div class="recrm-single-location">
                            <?php echo esc_html( $location_full ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $quick_items ) ) : ?>
                        <div class="recrm-single-quick">
                            <?php foreach ( $quick_items as $item ) : ?>
                                <div class="recrm-single-quick-item">
                                    <span><?php echo esc_html( $item['label'] ); ?></span>
                                    <strong><?php echo esc_html( $item['value'] ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </section>

            <?php if ( ! empty( $meta_items ) ) : ?>
                <section class="recrm-single-section">
                    <h2 class="recrm-single-section-title">Характеристики</h2>

                    <div class="recrm-single-meta">
                        <?php foreach ( $meta_items as $item ) : ?>
                            <div class="recrm-single-meta-item">
                                <span class="recrm-single-meta-label"><?php echo esc_html( $item['label'] ); ?></span>
                                <strong class="recrm-single-meta-value"><?php echo esc_html( $item['value'] ); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="recrm-single-section recrm-single-description">
                <h2 class="recrm-single-section-title">Опис</h2>
                <div class="recrm-single-content">
                    <?php the_content(); ?>
                </div>
            </section>

            <?php if ( '1' === (string) $enable_map && ( $location_full || ( $lat && $lng ) ) ) : ?>
                <section class="recrm-single-section recrm-single-map-section">
                    <h2 class="recrm-single-section-title">Локація</h2>

                    <?php if ( $location_full ) : ?>
                        <div class="recrm-single-map-address">
                            <?php echo esc_html( $location_full ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="recrm-single-map-wrap">
                        <?php if ( $lat && $lng ) : ?>
                            <iframe
                                src="https://www.google.com/maps?q=<?php echo rawurlencode( $lat . ',' . $lng ); ?>&z=15&output=embed"
                                width="100%"
                                height="100%"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        <?php elseif ( $location_full ) : ?>
                            <iframe
                                src="https://www.google.com/maps?q=<?php echo rawurlencode( $location_full ); ?>&z=15&output=embed"
                                width="100%"
                                height="100%"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( ! empty( $form_shortcode ) ) : ?>
                <section class="recrm-single-section" id="recrm-contact-form">
                    <h2 class="recrm-single-section-title">Зв’язатися</h2>
                    <div class="recrm-single-content">
                        <?php echo do_shortcode( $form_shortcode ); ?>
                    </div>
                </section>
            <?php endif; ?>

        </div>

        <aside class="recrm-single-sidebar">
            <div class="recrm-single-sticky<?php echo '1' !== (string) $enable_sticky_sidebar ? ' recrm-single-sticky-off' : ''; ?>">
                <div class="recrm-single-summary">

                    <?php if ( $price_output ) : ?>
                        <div class="recrm-single-price">
                            <?php echo esc_html( $price_output ); ?>
                            <?php if ( 'per_sqr' === $price_type ) : ?>
                                <span class="recrm-single-price-note">/ <?php echo esc_html( $area_unit ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="recrm-single-actions">
                        <a href="#recrm-contact-form" class="recrm-single-btn recrm-single-btn-primary">
                            <?php echo esc_html( $button_text ); ?>
                        </a>

                        <?php if ( $phone_href ) : ?>
                            <a href="tel:<?php echo esc_attr( $phone_href ); ?>" class="recrm-single-btn recrm-single-btn-outline">
                                Подзвонити
                            </a>
                        <?php elseif ( ! empty( $email_raw ) ) : ?>
                            <a href="mailto:<?php echo esc_attr( $email_raw ); ?>" class="recrm-single-btn recrm-single-btn-outline">
                                Написати
                            </a>
                        <?php else : ?>
                            <a href="#recrm-contact-form" class="recrm-single-btn recrm-single-btn-outline">
                                Зв’язатися
                            </a>
                        <?php endif; ?>
                    </div>


                </div>
            </div>
        </aside>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var mainImage = document.getElementById('recrmSingleMainPhoto');
    var galleryTrack = document.getElementById('recrmSingleGalleryTrack');
    var prevBtn = document.querySelector('.recrm-gallery-arrow-prev');
    var nextBtn = document.querySelector('.recrm-gallery-arrow-next');
    var thumbs = document.querySelectorAll('.recrm-single-thumb');

    if (galleryTrack && prevBtn) {
        prevBtn.addEventListener('click', function () {
            galleryTrack.scrollBy({
                left: -320,
                behavior: 'smooth'
            });
        });
    }

    if (galleryTrack && nextBtn) {
        nextBtn.addEventListener('click', function () {
            galleryTrack.scrollBy({
                left: 320,
                behavior: 'smooth'
            });
        });
    }

    if (!mainImage || !thumbs.length) {
        return;
    }

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var large = this.getAttribute('data-large');
            var full = this.getAttribute('data-full');
            var alt = this.getAttribute('data-alt');

            if (!large) {
                return;
            }

            mainImage.src = large;
            mainImage.setAttribute('data-full', full || large);
            mainImage.alt = alt || '';
            mainImage.removeAttribute('srcset');
            mainImage.removeAttribute('sizes');

            thumbs.forEach(function (item) {
                item.classList.remove('is-active');
            });

            this.classList.add('is-active');
        });
    });
});
</script>

<?php get_footer(); ?>
