<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * --------------------------------------
 * CONFIG
 * --------------------------------------
 */

function recrm_seo_types_map() {
	return array(
		'kvartyry' => array(
			'label' => 'Квартири',
			'slug'  => 'kvartyry',
		),
		'budynky' => array(
			'label' => 'Будинки',
			'slug'  => 'budynky',
		),
		'komertsiya' => array(
			'label' => 'Комерція',
			'slug'  => 'komertsiya',
		),
		'dilyanky' => array(
			'label' => 'Ділянки',
			'slug'  => 'dilyanky',
		),
	);
}

function recrm_seo_deals_map() {
	return array(
		'prodazh' => array(
			'label' => 'Продаж',
			'value' => 'sale',
		),
		'orenda'  => array(
			'label' => 'Оренда',
			'value' => 'rent',
		),
	);
}

function recrm_seo_districts_map() {
	return array(
		'druzhba'    => 'Дружба',
		'skhidnyi'   => 'Східний',
		'novyi-svit' => 'Новий світ',
		'tsentr'     => 'Центр',
		'alaska'     => 'Аляска',
		'bam'        => 'БАМ',
	);
}

function recrm_seo_rooms_map() {
	return array(
		'1-kimnatni' => 1,
		'2-kimnatni' => 2,
		'3-kimnatni' => 3,
		'4-kimnatni' => 4,
	);
}

/**
 * --------------------------------------
 * HELPERS
 * --------------------------------------
 */

function recrm_seo_catalog_page_slug() {
	return 'neruhomist';
}

function recrm_seo_catalog_page_url() {
	return home_url( '/' . trailingslashit( recrm_seo_catalog_page_slug() ) );
}

function recrm_get_archive_action_url() {
	global $wp;

	if ( ! empty( $wp->request ) ) {
		return home_url( '/' . trailingslashit( $wp->request ) );
	}

	return recrm_seo_catalog_page_url();
}

function recrm_get_current_request_path() {
	global $wp;

	return ! empty( $wp->request ) ? trim( $wp->request, '/' ) : '';
}

function recrm_is_seo_catalog_request() {
	$path = recrm_get_current_request_path();

	if ( '' === $path ) {
		return false;
	}

	if ( recrm_seo_catalog_page_slug() === $path ) {
		return true;
	}

	$types = array_keys( recrm_seo_types_map() );
	$deals = array_keys( recrm_seo_deals_map() );

	$segments = explode( '/', $path );
	$first    = isset( $segments[0] ) ? $segments[0] : '';

	return in_array( $first, $types, true ) || in_array( $first, $deals, true );
}

/**
 * --------------------------------------
 * QUERY VARS + REWRITE
 * --------------------------------------
 */

function recrm_seo_register_query_vars( $vars ) {
	$vars[] = 'recrm_seo_deal';
	$vars[] = 'recrm_seo_type';
	$vars[] = 'recrm_seo_district';
	$vars[] = 'recrm_seo_rooms';

	return $vars;
}
add_filter( 'query_vars', 'recrm_seo_register_query_vars' );

function recrm_seo_register_rewrite_rules() {
	$page_slug = recrm_seo_catalog_page_slug();

	$type_slugs = implode( '|', array_map( 'preg_quote', array_keys( recrm_seo_types_map() ) ) );
	$deal_slugs = implode( '|', array_map( 'preg_quote', array_keys( recrm_seo_deals_map() ) ) );

	add_rewrite_rule(
		'^(' . $type_slugs . ')/?$',
		'index.php?pagename=' . $page_slug . '&recrm_seo_type=$matches[1]',
		'top'
	);

	add_rewrite_rule(
		'^(' . $deal_slugs . ')/(' . $type_slugs . ')/?$',
		'index.php?pagename=' . $page_slug . '&recrm_seo_deal=$matches[1]&recrm_seo_type=$matches[2]',
		'top'
	);

	add_rewrite_rule(
		'^(' . $type_slugs . ')/([^/]+)/?$',
		'index.php?pagename=' . $page_slug . '&recrm_seo_type=$matches[1]&recrm_seo_district=$matches[2]',
		'top'
	);

	add_rewrite_rule(
		'^(' . $deal_slugs . ')/(' . $type_slugs . ')/([^/]+)/?$',
		'index.php?pagename=' . $page_slug . '&recrm_seo_deal=$matches[1]&recrm_seo_type=$matches[2]&recrm_seo_district=$matches[3]',
		'top'
	);

	add_rewrite_rule(
		'^(' . $type_slugs . ')/(1-kimnatni|2-kimnatni|3-kimnatni|4-kimnatni)/?$',
		'index.php?pagename=' . $page_slug . '&recrm_seo_type=$matches[1]&recrm_seo_rooms=$matches[2]',
		'top'
	);

	add_rewrite_rule(
		'^(' . $deal_slugs . ')/(' . $type_slugs . ')/(1-kimnatni|2-kimnatni|3-kimnatni|4-kimnatni)/?$',
		'index.php?pagename=' . $page_slug . '&recrm_seo_deal=$matches[1]&recrm_seo_type=$matches[2]&recrm_seo_rooms=$matches[3]',
		'top'
	);

	add_rewrite_rule(
		'^(' . $type_slugs . ')/([^/]+)/(1-kimnatni|2-kimnatni|3-kimnatni|4-kimnatni)/?$',
		'index.php?pagename=' . $page_slug . '&recrm_seo_type=$matches[1]&recrm_seo_district=$matches[2]&recrm_seo_rooms=$matches[3]',
		'top'
	);

	add_rewrite_rule(
		'^(' . $deal_slugs . ')/(' . $type_slugs . ')/([^/]+)/(1-kimnatni|2-kimnatni|3-kimnatni|4-kimnatni)/?$',
		'index.php?pagename=' . $page_slug . '&recrm_seo_deal=$matches[1]&recrm_seo_type=$matches[2]&recrm_seo_district=$matches[3]&recrm_seo_rooms=$matches[4]',
		'top'
	);
}
add_action( 'init', 'recrm_seo_register_rewrite_rules', 30 );

/**
 * --------------------------------------
 * APPLY SEO URL TO FILTER
 * --------------------------------------
 */

function recrm_seo_apply_filters_from_url() {
	if ( is_admin() ) {
		return;
	}

	if ( ! is_page( recrm_seo_catalog_page_slug() ) && ! recrm_is_seo_catalog_request() ) {
		return;
	}

	$seo_type     = get_query_var( 'recrm_seo_type', '' );
	$seo_deal     = get_query_var( 'recrm_seo_deal', '' );
	$seo_district = get_query_var( 'recrm_seo_district', '' );
	$seo_rooms    = get_query_var( 'recrm_seo_rooms', '' );

	$types     = recrm_seo_types_map();
	$deals     = recrm_seo_deals_map();
	$districts = recrm_seo_districts_map();
	$rooms     = recrm_seo_rooms_map();

	$has_seo_filters = false;

	if ( $seo_type && isset( $types[ $seo_type ] ) ) {
		$type_slug = ! empty( $types[ $seo_type ]['slug'] ) ? $types[ $seo_type ]['slug'] : '';

		if ( $type_slug ) {
			$term = get_term_by( 'slug', $type_slug, 'property_type' );

			if ( $term && ! is_wp_error( $term ) ) {
				$_GET['property_type'] = (string) $term->term_id;
				$has_seo_filters       = true;
			}
		}
	}

	if ( $seo_deal && isset( $deals[ $seo_deal ] ) ) {
		$_GET['offer_type'] = $deals[ $seo_deal ]['value'];
		$has_seo_filters    = true;
	}

	if ( $seo_district && isset( $districts[ $seo_district ] ) ) {
		$_GET['district'] = $districts[ $seo_district ];
		$has_seo_filters  = true;
	}

	if ( $seo_rooms && isset( $rooms[ $seo_rooms ] ) ) {
		$_GET['rooms']   = (string) $rooms[ $seo_rooms ];
		$has_seo_filters = true;
	}

	if ( $has_seo_filters ) {
		$_GET['recrm_filter'] = '1';
	}
}
add_action( 'wp', 'recrm_seo_apply_filters_from_url', 5 );

/**
 * --------------------------------------
 * MENU URL HELPERS
 * --------------------------------------
 */

function recrm_seo_build_url( $args = array() ) {
	$base  = home_url( '/' );
	$parts = array();

	if ( ! empty( $args['deal'] ) ) {
		$parts[] = sanitize_title( $args['deal'] );
	}

	if ( ! empty( $args['type'] ) ) {
		$parts[] = sanitize_title( $args['type'] );
	}

	if ( ! empty( $args['district'] ) ) {
		$parts[] = sanitize_title( $args['district'] );
	}

	if ( ! empty( $args['rooms'] ) ) {
		$parts[] = sanitize_title( $args['rooms'] );
	}

	return trailingslashit( $base . implode( '/', $parts ) );
}

function recrm_seo_has_properties( $args = array() ) {
	$meta_query = array(
		'relation' => 'AND',
	);

	$tax_query = array(
		'relation' => 'AND',
	);

	if ( ! empty( $args['deal'] ) ) {
		$meta_query[] = array(
			'key'     => 'crm_deal',
			'value'   => $args['deal'],
			'compare' => '=',
		);
	}

	if ( ! empty( $args['district'] ) ) {
		$meta_query[] = array(
			'key'     => 'property_district',
			'value'   => $args['district'],
			'compare' => '=',
		);
	}

	if ( ! empty( $args['rooms'] ) ) {
		$meta_query[] = array(
			'key'     => 'property_rooms',
			'value'   => (int) $args['rooms'],
			'type'    => 'NUMERIC',
			'compare' => '=',
		);
	}

	if ( ! empty( $args['type_id'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'property_type',
			'field'    => 'term_id',
			'terms'    => array( (int) $args['type_id'] ),
			'operator' => 'IN',
		);
	}

	$query_args = array(
		'post_type'      => 'property',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);

	if ( count( $meta_query ) > 1 ) {
		$query_args['meta_query'] = $meta_query;
	}

	if ( count( $tax_query ) > 1 ) {
		$query_args['tax_query'] = $tax_query;
	}

	$query = new WP_Query( $query_args );

	return ! empty( $query->posts );
}

function recrm_seo_get_available_rooms( $args = array() ) {
	global $wpdb;

	$where = array(
		"p.post_type = 'property'",
		"p.post_status = 'publish'",
		"pm_rooms.meta_key = 'property_rooms'",
		"pm_rooms.meta_value <> ''",
	);

	$joins = array(
		"INNER JOIN {$wpdb->postmeta} pm_rooms ON p.ID = pm_rooms.post_id",
	);

	if ( ! empty( $args['deal'] ) ) {
		$joins[] = "INNER JOIN {$wpdb->postmeta} pm_deal ON p.ID = pm_deal.post_id";
		$where[] = $wpdb->prepare( "pm_deal.meta_key = 'crm_deal' AND pm_deal.meta_value = %s", $args['deal'] );
	}

	if ( ! empty( $args['district'] ) ) {
		$joins[] = "INNER JOIN {$wpdb->postmeta} pm_district ON p.ID = pm_district.post_id";
		$where[] = $wpdb->prepare( "pm_district.meta_key = 'property_district' AND pm_district.meta_value = %s", $args['district'] );
	}

	if ( ! empty( $args['type_id'] ) ) {
		$joins[] = "INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id";
		$joins[] = "INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
		$where[] = $wpdb->prepare( "tt.taxonomy = 'property_type' AND tt.term_id = %d", (int) $args['type_id'] );
	}

	$sql = "
		SELECT DISTINCT CAST(pm_rooms.meta_value AS UNSIGNED) AS rooms
		FROM {$wpdb->posts} p
		" . implode( "\n", $joins ) . "
		WHERE " . implode( "\nAND ", $where ) . "
		ORDER BY rooms ASC
	";

	$results = $wpdb->get_col( $sql );

	$allowed = array( 1, 2, 3, 4 );
	$rooms   = array();

	if ( ! empty( $results ) ) {
		foreach ( $results as $room ) {
			$room = (int) $room;

			if ( in_array( $room, $allowed, true ) ) {
				$rooms[] = $room;
			}
		}
	}

	return array_values( array_unique( $rooms ) );
}

function recrm_seo_room_slug_from_number( $room_number ) {
	$map = array(
		1 => '1-kimnatni',
		2 => '2-kimnatni',
		3 => '3-kimnatni',
		4 => '4-kimnatni',
	);

	return isset( $map[ $room_number ] ) ? $map[ $room_number ] : '';
}

function recrm_seo_room_label_from_number( $room_number ) {
	return (int) $room_number . '-кімнатні';
}

/**
 * --------------------------------------
 * DYNAMIC MENU
 * --------------------------------------
 */

function recrm_seo_make_menu_item( $id, $parent_id, $title, $url, $classes = array() ) {
	$item                        = new stdClass();
	$item->ID                    = $id;
	$item->db_id                 = $id;
	$item->menu_item_parent      = $parent_id;
	$item->object_id             = $id;
	$item->object                = 'custom';
	$item->type                  = 'custom';
	$item->type_label            = 'Custom Link';
	$item->title                 = $title;
	$item->url                   = $url;
	$item->target                = '';
	$item->attr_title            = '';
	$item->description           = '';
	$item->classes               = array_merge(
		array(
			'menu-item',
			'menu-item-type-custom',
			'menu-item-object-custom',
		),
		(array) $classes
	);
	$item->xfn                   = '';
	$item->status                = 'publish';
	$item->current               = false;
	$item->current_item_ancestor = false;
	$item->current_item_parent   = false;

	return $item;
}


function recrm_is_dynamic_seo_menu_enabled() {
    if ( function_exists( 'recrm_get_settings' ) ) {
        $settings = recrm_get_settings();
        return ! empty( $settings['enable_dynamic_seo_menu'] );
    }

    return false;
}

function recrm_seo_extend_nav_menu( $items, $args ) {
	if ( ! recrm_is_dynamic_seo_menu_enabled() ) {
		return $items;
	}

	if ( is_admin() ) {
		return $items;
	}

	$catalog_url = untrailingslashit( recrm_seo_catalog_page_url() );
	$parent      = null;
	$parent_key  = null;

	foreach ( $items as $index => $item ) {
		$item_url = isset( $item->url ) ? untrailingslashit( $item->url ) : '';

		if (
			$item_url === $catalog_url ||
			( isset( $item->title ) && in_array( trim( wp_strip_all_tags( $item->title ) ), array( 'Нерухомість', 'Обʼєкти', 'Обєкти' ), true ) )
		) {
			$parent     = $item;
			$parent_key = $index;
			break;
		}
	}

	if ( ! $parent ) {
		return $items;
	}

	if ( ! isset( $items[ $parent_key ]->classes ) || ! is_array( $items[ $parent_key ]->classes ) ) {
		$items[ $parent_key ]->classes = array();
	}

	if ( ! in_array( 'menu-item-has-children', $items[ $parent_key ]->classes, true ) ) {
		$items[ $parent_key ]->classes[] = 'menu-item-has-children';
	}

	if ( ! in_array( 'has-submenu', $items[ $parent_key ]->classes, true ) ) {
		$items[ $parent_key ]->classes[] = 'has-submenu';
	}

	$extra_items = array();
	$next_id     = -1000;

	$types     = recrm_seo_types_map();
	$districts = recrm_seo_districts_map();

	$kvartyry_id = $next_id--;

	$extra_items[] = recrm_seo_make_menu_item(
		$kvartyry_id,
		$parent->ID,
		'Квартири',
		recrm_seo_build_url(
			array(
				'type' => 'kvartyry',
			)
		),
		array( 'menu-item-has-children', 'has-submenu' )
	);

	$available_apartment_rooms = recrm_seo_get_available_rooms(
		array(
			'type_id' => $types['kvartyry']['id'],
		)
	);

	foreach ( $available_apartment_rooms as $room_number ) {
		$room_slug = recrm_seo_room_slug_from_number( $room_number );

		if ( ! $room_slug ) {
			continue;
		}

		$extra_items[] = recrm_seo_make_menu_item(
			$next_id--,
			$kvartyry_id,
			recrm_seo_room_label_from_number( $room_number ),
			recrm_seo_build_url(
				array(
					'type'  => 'kvartyry',
					'rooms' => $room_slug,
				)
			)
		);
	}

	foreach ( $districts as $district_slug => $district_label ) {
		if ( ! recrm_seo_has_properties(
			array(
				'type_id'  => $types['kvartyry']['id'],
				'district' => $district_label,
			)
		) ) {
			continue;
		}

		$district_rooms = recrm_seo_get_available_rooms(
			array(
				'type_id'  => $types['kvartyry']['id'],
				'district' => $district_label,
			)
		);

		$district_classes = array();

		if ( ! empty( $district_rooms ) ) {
			$district_classes = array( 'menu-item-has-children', 'has-submenu' );
		}

		$district_id = $next_id--;

		$extra_items[] = recrm_seo_make_menu_item(
			$district_id,
			$kvartyry_id,
			$district_label,
			recrm_seo_build_url(
				array(
					'type'     => 'kvartyry',
					'district' => $district_slug,
				)
			),
			$district_classes
		);

		foreach ( $district_rooms as $room_number ) {
			$room_slug = recrm_seo_room_slug_from_number( $room_number );

			if ( ! $room_slug ) {
				continue;
			}

			$extra_items[] = recrm_seo_make_menu_item(
				$next_id--,
				$district_id,
				recrm_seo_room_label_from_number( $room_number ) . ' ' . $district_label,
				recrm_seo_build_url(
					array(
						'type'     => 'kvartyry',
						'district' => $district_slug,
						'rooms'    => $room_slug,
					)
				)
			);
		}
	}

	$extra_items[] = recrm_seo_make_menu_item(
		$next_id--,
		$parent->ID,
		$types['budynky']['label'],
		recrm_seo_build_url(
			array(
				'type' => 'budynky',
			)
		)
	);

	$extra_items[] = recrm_seo_make_menu_item(
		$next_id--,
		$parent->ID,
		$types['zemlya-komertsiyna']['label'],
		recrm_seo_build_url(
			array(
				'type' => 'zemlya-komertsiyna',
			)
		)
	);

	if ( recrm_seo_has_properties(
		array(
			'deal'    => 'rent',
			'type_id' => $types['kvartyry']['id'],
		)
	) ) {
		$extra_items[] = recrm_seo_make_menu_item(
			$next_id--,
			$parent->ID,
			'Оренда квартир',
			recrm_seo_build_url(
				array(
					'deal' => 'orenda',
					'type' => 'kvartyry',
				)
			)
		);
	}

	if ( recrm_seo_has_properties(
		array(
			'deal'    => 'rent',
			'type_id' => $types['budynky']['id'],
		)
	) ) {
		$extra_items[] = recrm_seo_make_menu_item(
			$next_id--,
			$parent->ID,
			'Оренда будинків',
			recrm_seo_build_url(
				array(
					'deal' => 'orenda',
					'type' => 'budynky',
				)
			)
		);
	}

	if ( recrm_seo_has_properties(
		array(
			'deal'    => 'sale',
			'type_id' => $types['kvartyry']['id'],
		)
	) ) {
		$extra_items[] = recrm_seo_make_menu_item(
			$next_id--,
			$parent->ID,
			'Продаж квартир',
			recrm_seo_build_url(
				array(
					'deal' => 'prodazh',
					'type' => 'kvartyry',
				)
			)
		);
	}

	if ( recrm_seo_has_properties(
		array(
			'deal'    => 'sale',
			'type_id' => $types['budynky']['id'],
		)
	) ) {
		$extra_items[] = recrm_seo_make_menu_item(
			$next_id--,
			$parent->ID,
			'Продаж будинків',
			recrm_seo_build_url(
				array(
					'deal' => 'prodazh',
					'type' => 'budynky',
				)
			)
		);
	}

	return array_merge( $items, $extra_items );
}
add_filter( 'wp_nav_menu_objects', 'recrm_seo_extend_nav_menu', 20, 2 );

/**
 * --------------------------------------
 * CURRENT MENU CLASS
 * --------------------------------------
 */

function recrm_seo_mark_current_menu_items( $classes, $item ) {
	$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$current_path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
	$item_path    = '';

	if ( ! empty( $item->url ) ) {
		$item_path = trim( (string) wp_parse_url( $item->url, PHP_URL_PATH ), '/' );
	}

	$catalog_slug = trim( recrm_seo_catalog_page_slug(), '/' );
	$seo_types    = array_keys( recrm_seo_types_map() );
	$seo_deals    = array_keys( recrm_seo_deals_map() );
	$segments     = '' !== $current_path ? explode( '/', $current_path ) : array();
	$first        = isset( $segments[0] ) ? $segments[0] : '';

	$is_catalog_context = (
		$current_path === $catalog_slug ||
		in_array( $first, $seo_types, true ) ||
		in_array( $first, $seo_deals, true )
	);

	$is_catalog_item = (
		$item_path === $catalog_slug ||
		( isset( $item->title ) && in_array( trim( wp_strip_all_tags( $item->title ) ), array( 'Нерухомість', 'Обʼєкти', 'Обєкти' ), true ) )
	);

	$classes = array_diff(
		(array) $classes,
		array(
			'current-menu-item',
			'current_page_item',
			'current-menu-ancestor',
			'current_page_ancestor',
			'current-menu-parent',
			'current_page_parent',
		)
	);

	if ( $is_catalog_item ) {
		if ( $is_catalog_context ) {
			$classes[] = 'current-menu-item';
		}

		return array_unique( $classes );
	}

	if ( $item_path && $item_path === $current_path ) {
		$classes[] = 'current-menu-item';
	}

	return array_unique( $classes );
}
add_filter( 'nav_menu_css_class', 'recrm_seo_mark_current_menu_items', 10, 2 );

function recrm_check_seo_files() {
	$result = array();

	$robots_path      = ABSPATH . 'robots.txt';
	$result['robots'] = file_exists( $robots_path );

	$result['sitemap']       = recrm_check_remote_file_exists( home_url( '/sitemap.xml' ) );
	$result['favicon']       = recrm_check_remote_file_exists( home_url( '/favicon.ico' ) );
	$result['image_sitemap'] = recrm_check_remote_file_exists( home_url( '/sitemap-images.xml' ) );
	$result['news_sitemap']  = recrm_check_remote_file_exists( home_url( '/sitemap-news.xml' ) );

	return $result;
}

function recrm_check_trust_pages() {
	return array(
		'about'    => (bool) get_page_by_path( 'pro-nas' ),
		'contacts' => (bool) get_page_by_path( 'kontakty' ),
		'privacy'  => (bool) get_page_by_path( 'privacy-policy' ),
		'terms'    => (bool) get_page_by_path( 'terms' ),
	);
}

function recrm_check_remote_file_exists( $url ) {
	$response = wp_remote_head($url, array('timeout' => 10));

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	return $code >= 200 && $code < 400;
}

add_action( 'wp_head', function() {
	if ( is_admin() ) return;

	// якщо є GET параметри (фільтр)
	if ( ! empty( $_GET ) ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}
}, 1 );

add_action( 'wp_head', function() {
	if ( is_admin() ) return;

	if ( ! empty( $_GET ) ) {
		$url = home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) );

		// прибираємо GET параметри
		$clean_url = strtok( $url, '?' );

		echo '<link rel="canonical" href="' . esc_url( $clean_url ) . '">' . "\n";
	}
}, 1 );

add_action('init', function() {
	add_rewrite_rule(
		'^neruhomist/filter/([^/]+)/?$',
		'index.php?recrm_filter_slug=$matches[1]',
		'top'
	);
});

add_filter('query_vars', function($vars) {
	$vars[] = 'recrm_filter_slug';
	return $vars;
});