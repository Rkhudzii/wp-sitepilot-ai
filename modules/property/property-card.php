<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id = isset( $post_id ) ? (int) $post_id : get_the_ID();

if ( ! $post_id || 'property' !== get_post_type( $post_id ) ) {
    return;
}

if ( ! $post_id || 'property' !== get_post_type( $post_id ) ) {
    return;
}

$post_id = isset( $post_id ) ? (int) $post_id : get_the_ID();

$title      = get_the_title( $post_id );
$link       = get_permalink( $post_id );
$price      = get_post_meta( $post_id, 'property_price', true );
$currency   = get_post_meta( $post_id, 'property_currency', true );
$price_type = get_post_meta( $post_id, 'property_price_type', true );
$rooms      = get_post_meta( $post_id, 'property_rooms', true );
$area       = get_post_meta( $post_id, 'property_area_total', true );
$floor      = get_post_meta( $post_id, 'property_floor', true );
$address    = get_post_meta( $post_id, 'property_location_full', true );
$city     = get_post_meta( $post_id, 'property_city', true );
$district = get_post_meta( $post_id, 'property_district', true );
$deal       = get_post_meta( $post_id, 'crm_deal', true );
$type_terms = get_the_terms( $post_id, 'property_type' );
$type       = ( ! is_wp_error( $type_terms ) && ! empty( $type_terms ) ) ? $type_terms[0]->name : '';

$deal_label = $deal ? $deal : 'Обʼєкт';
$price_label = '';

if ( '' !== $price && is_numeric( $price ) && (float) $price > 0 ) {
    $formatted_price = number_format( (float) $price, 0, '.', ' ' ) . ' ' . $currency;

    if ( 'per_sqr' === $price_type ) {
        $price_label = $formatted_price . '/м²';
    } else {
        $price_label = $formatted_price;
    }
}
?>

<a href="<?php echo esc_url( $link ); ?>" class="recrm-card">
    <div class="recrm-card-media">
        <?php if ( has_post_thumbnail( $post_id ) ) : ?>
            <div class="recrm-img">
                <?php echo get_the_post_thumbnail( $post_id, 'medium_large' ); ?>
            </div>
        <?php else : ?>
            <div class="recrm-img recrm-img--placeholder"></div>
        <?php endif; ?>

        <div class="recrm-card-badges">
            <span class="recrm-badge recrm-badge--deal"><?php echo esc_html( $deal_label ); ?></span>

            <?php if ( ! empty( $type ) ) : ?>
                <span class="recrm-badge recrm-badge--type"><?php echo esc_html( $type ); ?></span>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $price_label ) ) : ?>
            <div class="recrm-card-price-overlay">
                <?php echo esc_html( $price_label ); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="recrm-content">
        <h3><?php echo esc_html( $title ); ?></h3>

        <div class="recrm-meta">
            <?php if ( ! empty( $rooms ) ) : ?>
                <span><?php echo esc_html( $rooms ); ?> кімн.</span>
            <?php endif; ?>

            <?php if ( ! empty( $area ) ) : ?>
                <span><?php echo esc_html( $area ); ?> м²</span>
            <?php endif; ?>

            <?php if ( ! empty( $floor ) ) : ?>
                <span><?php echo esc_html( $floor ); ?> поверх</span>
            <?php endif; ?>
        </div>

         <?php if ( ! empty( $address ) || ! empty( $district ) || ! empty( $city ) ) : ?>
            <p class="recrm-location"><?php echo esc_html( $address ); ?></p>
        <?php endif; ?>

        <span class="recrm-card-link">Детальніше</span>
    </div>
</a>