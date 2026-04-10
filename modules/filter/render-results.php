<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function recrm_filter_get_pagination_base( $source = array(), $atts = array() ) {
    $big = 999999999;

    $action_url = '';

    if ( is_array( $source ) && ! empty( $source['action_url'] ) ) {
        $action_url = esc_url_raw( wp_unslash( $source['action_url'] ) );
    }

    if ( ! $action_url && ! empty( $atts['action_url'] ) ) {
        $action_url = esc_url_raw( $atts['action_url'] );
    }

    if ( ! $action_url ) {
        $action_url = function_exists( 'recrm_filter_get_current_page_url' )
            ? recrm_filter_get_current_page_url()
            : home_url( '/' );
    }

    $action_url = remove_query_arg(
        array( 'paged', 'page', '_wp_http_referer' ),
        $action_url
    );

    $base = add_query_arg( 'paged', $big, $action_url );

    return str_replace( $big, '%#%', esc_url( $base ) );
}

function recrm_filter_render_results( $source, $atts = array() ) {
    $args  = recrm_filter_get_query_args( $source, $atts );
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
                recrm_filter_render_property_card( get_the_ID() );
            endwhile;
            ?>
        </div>

        <?php
        $current_params = is_array( $source ) ? $source : array();
        unset( $current_params['action'], $current_params['nonce'], $current_params['paged'], $current_params['page'], $current_params['_wp_http_referer'] );

        echo '<div class="recrm-pagination">';
        echo wp_kses_post(
            paginate_links(
                array(
                    'base'      => recrm_filter_get_pagination_base( $source, $atts ),
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
