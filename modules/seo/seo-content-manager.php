<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'recrm_seo_manager_register_menu', 31 );
add_action( 'admin_init', 'recrm_seo_manager_handle_save' );
add_action( 'admin_enqueue_scripts', 'recrm_seo_manager_admin_assets' );
add_filter( 'pre_get_document_title', 'recrm_seo_manager_filter_term_document_title', 30 );
add_action( 'wp_head', 'recrm_seo_manager_output_term_meta_description', 4 );

function recrm_seo_manager_register_menu() {
	add_submenu_page(
		'recrm-main',
		'SEO сторінки',
		'SEO сторінки',
		'manage_options',
		'recrm-seo-manager',
		'recrm_seo_manager_render_page'
	);
}

function recrm_seo_manager_admin_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'recrm-seo-manager' ) ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'recrm-seo-manager',
		RECRM_XML_IMPORT_URL . 'assets/js/seo-manager.js',
		array( 'jquery' ),
		RECRM_XML_IMPORT_VERSION,
		true
	);

	wp_localize_script(
		'recrm-seo-manager',
		'recrmSeoManagerData',
		array(
			'placeholder' => RECRM_XML_IMPORT_URL . 'assets/img/seo-placeholder.png',
		)
	);
}

function recrm_seo_manager_get_tabs() {
	$tabs = array(
		'pages' => array(
			'label'     => 'Сторінки',
			'entity'    => 'post',
			'post_type' => 'page',
		),
		'posts' => array(
			'label'     => 'Записи',
			'entity'    => 'post',
			'post_type' => 'post',
		),
	);

	if ( post_type_exists( 'property' ) ) {
		$tabs['property'] = array(
			'label'     => 'Нерухомість',
			'entity'    => 'post',
			'post_type' => 'property',
		);
	}

	if ( taxonomy_exists( 'category' ) ) {
		$tabs['category'] = array(
			'label'    => 'Категорії',
			'entity'   => 'term',
			'taxonomy' => 'category',
		);
	}

	$taxonomies = get_taxonomies(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'objects'
	);

	$excluded_taxonomies = array( 'category', 'post_tag', 'nav_menu', 'link_category', 'post_format' );

	foreach ( $taxonomies as $taxonomy => $taxonomy_obj ) {
		if ( in_array( $taxonomy, $excluded_taxonomies, true ) ) {
			continue;
		}

		$tabs[ 'tax_' . $taxonomy ] = array(
			'label'    => $taxonomy_obj->labels->menu_name ? $taxonomy_obj->labels->menu_name : $taxonomy_obj->labels->name,
			'entity'   => 'term',
			'taxonomy' => $taxonomy,
		);
	}

	return $tabs;
}

function recrm_seo_manager_get_current_tab() {
	$tabs = recrm_seo_manager_get_tabs();
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'pages';

	if ( ! isset( $tabs[ $tab ] ) ) {
		$tab = key( $tabs );
	}

	return $tab;
}

function recrm_seo_manager_count_posts( $post_type ) {
	$counts = wp_count_posts( $post_type );
	$total  = 0;

	if ( ! $counts ) {
		return 0;
	}

	foreach ( (array) $counts as $status => $count ) {
		if ( in_array( $status, array( 'trash', 'auto-draft', 'inherit' ), true ) ) {
			continue;
		}

		$total += (int) $count;
	}

	return $total;
}

function recrm_seo_manager_count_terms( $taxonomy ) {
	$count = wp_count_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);

	return is_wp_error( $count ) ? 0 : (int) $count;
}

function recrm_seo_manager_get_tab_count( $tab_config ) {
	if ( 'post' === $tab_config['entity'] ) {
		return recrm_seo_manager_count_posts( $tab_config['post_type'] );
	}

	return recrm_seo_manager_count_terms( $tab_config['taxonomy'] );
}

function recrm_seo_manager_normalize_keywords( $keywords ) {
	$parts   = preg_split( '/\s*,\s*/u', (string) $keywords );
	$cleaned = array();

	foreach ( (array) $parts as $part ) {
		$part = sanitize_text_field( $part );
		$part = trim( preg_replace( '/\s+/u', ' ', $part ) );

		if ( '' === $part ) {
			continue;
		}

		$cleaned[] = $part;
	}

	$cleaned = array_values( array_unique( $cleaned ) );

	return implode( ', ', $cleaned );
}

function recrm_seo_manager_primary_keyword( $keywords ) {
	$normalized = recrm_seo_manager_normalize_keywords( $keywords );

	if ( '' === $normalized ) {
		return '';
	}

	$parts = preg_split( '/\s*,\s*/u', $normalized );
	return isset( $parts[0] ) ? trim( (string) $parts[0] ) : '';
}

function recrm_seo_manager_get_post_image_id( $post_id ) {
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	return $thumbnail_id ? (int) $thumbnail_id : 0;
}

function recrm_seo_manager_get_term_image_id( $term_id ) {
	$image_id = get_term_meta( $term_id, 'recrm_seo_image_id', true );
	return $image_id ? (int) $image_id : 0;
}

function recrm_seo_manager_get_image_url( $image_id ) {
	if ( ! $image_id ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $image_id, 'medium' );
	return $url ? $url : '';
}


function recrm_seo_manager_extract_h2_from_html( $html ) {
	$html = (string) $html;

	if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
		return array();
	}

	$items = array();

	if ( class_exists( 'DOMDocument' ) ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();

		$h2_nodes = $dom->getElementsByTagName( 'h2' );

		foreach ( $h2_nodes as $node ) {
			$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $node->textContent ) ) );

			if ( '' !== $text ) {
				$items[] = $text;
			}
		}
	} else {
		if ( preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/isu', $html, $matches ) ) {
			foreach ( (array) $matches[1] as $match ) {
				$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $match ) ) );

				if ( '' !== $text ) {
					$items[] = $text;
				}
			}
		}
	}

	return array_values( array_unique( $items ) );
}

function recrm_seo_manager_collect_h2_from_elementor_nodes( $nodes, &$results ) {
	foreach ( (array) $nodes as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}

		$widget_type = isset( $node['widgetType'] ) ? (string) $node['widgetType'] : '';
		$settings    = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
		$size_keys   = array( 'header_size', 'size', 'title_size' );
		$is_h2       = false;

		foreach ( $size_keys as $size_key ) {
			if ( isset( $settings[ $size_key ] ) && 'h2' === strtolower( (string) $settings[ $size_key ] ) ) {
				$is_h2 = true;
				break;
			}
		}

		if ( 'heading' === $widget_type && $is_h2 ) {
			$text = isset( $settings['title'] ) ? trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $settings['title'] ) ) ) : '';

			if ( '' !== $text ) {
				$results[] = $text;
			}
		}

		foreach ( array( 'editor', 'text', 'content', 'html' ) as $content_key ) {
			if ( ! empty( $settings[ $content_key ] ) && is_string( $settings[ $content_key ] ) ) {
				$results = array_merge( $results, recrm_seo_manager_extract_h2_from_html( $settings[ $content_key ] ) );
			}
		}

		if ( ! empty( $node['elements'] ) ) {
			recrm_seo_manager_collect_h2_from_elementor_nodes( $node['elements'], $results );
		}
	}
}

function recrm_seo_manager_get_post_h2_suggestions( $post_id ) {
	$post_id = absint( $post_id );

	if ( $post_id <= 0 ) {
		return array();
	}

	$results = array();
	$content = (string) get_post_field( 'post_content', $post_id );

	if ( '' !== $content ) {
		$results = array_merge( $results, recrm_seo_manager_extract_h2_from_html( $content ) );
	}

	$elementor_data = get_post_meta( $post_id, '_elementor_data', true );

	if ( is_string( $elementor_data ) && '' !== $elementor_data ) {
		$decoded = json_decode( $elementor_data, true );

		if ( is_array( $decoded ) ) {
			recrm_seo_manager_collect_h2_from_elementor_nodes( $decoded, $results );
		}
	}

	$results = array_map(
		function( $value ) {
			return trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( $value ) ) );
		},
		(array) $results
	);
	$results = array_values( array_unique( array_filter( $results ) ) );

	if ( count( $results ) > 12 ) {
		$results = array_slice( $results, 0, 12 );
	}

	return $results;
}

function recrm_seo_manager_get_post_defaults( $post ) {

	$settings   = recrm_get_seo_settings();
	$title      = get_the_title( $post );
	$keywords   = get_post_meta( $post->ID, '_recrm_seo_keywords', true );
	$primary    = get_post_meta( $post->ID, '_recrm_seo_keyword', true );
	$meta_title = get_post_meta( $post->ID, '_recrm_seo_meta_title', true );
	$meta_desc  = get_post_meta( $post->ID, '_recrm_seo_meta_description', true );

	if ( '' === $keywords ) {
		$keywords = '' !== $primary ? $primary : recrm_seo_generate_keyword( $title );
	}

	$primary_keyword = recrm_seo_manager_primary_keyword( $keywords );
	if ( '' === $primary_keyword ) {
		$primary_keyword = recrm_seo_generate_keyword( $title );
	}

	if ( '' === $meta_title ) {
		$meta_title = recrm_seo_apply_template(
			$settings['title_template'],
			array(
				'title'   => $title,
				'brand'   => $settings['brand_name'],
				'city'    => $settings['default_city'],
				'keyword' => $primary_keyword,
			)
		);
	}

	if ( '' === $meta_desc ) {
		$meta_desc = recrm_seo_apply_template(
			$settings['description_template'],
			array(
				'title'   => $title,
				'brand'   => $settings['brand_name'],
				'city'    => $settings['default_city'],
				'keyword' => $primary_keyword,
			)
		);
	}

	$index_status = recrm_seo_manager_get_indexing_status_payload( recrm_seo_manager_is_post_indexing_enabled( $post->ID ) );

	return array(
		'title'            => $title,
		'keywords'         => $keywords,
		'meta_title'       => $meta_title,
		'meta_description' => $meta_desc,
		'image_id'         => recrm_seo_manager_get_post_image_id( $post->ID ),
		'url'              => get_permalink( $post ),
		'edit_url'         => get_edit_post_link( $post->ID, '' ),
		'status'           => $post->post_status,
		'updated'          => get_the_modified_date( 'd.m.Y H:i', $post ),
		'index_enabled'    => recrm_seo_manager_is_post_indexing_enabled( $post->ID ),
		'index_label'      => $index_status['label'],
		'index_class'      => $index_status['class'],
		'h2_suggestions'    => recrm_seo_manager_get_post_h2_suggestions( $post->ID ),
	);
}

function recrm_seo_manager_get_term_defaults( $term ) {
	$settings   = recrm_get_seo_settings();
	$title      = $term->name;
	$keywords   = get_term_meta( $term->term_id, 'recrm_seo_keywords', true );
	$primary    = get_term_meta( $term->term_id, 'recrm_seo_keyword', true );
	$meta_title = get_term_meta( $term->term_id, 'recrm_seo_meta_title', true );
	$meta_desc  = get_term_meta( $term->term_id, 'recrm_seo_meta_description', true );

	if ( '' === $keywords ) {
		$keywords = '' !== $primary ? $primary : recrm_seo_generate_keyword( $title );
	}

	$primary_keyword = recrm_seo_manager_primary_keyword( $keywords );
	if ( '' === $primary_keyword ) {
		$primary_keyword = recrm_seo_generate_keyword( $title );
	}

	if ( '' === $meta_title ) {
		$meta_title = recrm_seo_apply_template(
			$settings['title_template'],
			array(
				'title'   => $title,
				'brand'   => $settings['brand_name'],
				'city'    => $settings['default_city'],
				'keyword' => $primary_keyword,
			)
		);
	}

	if ( '' === $meta_desc ) {
		$meta_desc = recrm_seo_apply_template(
			$settings['description_template'],
			array(
				'title'   => $title,
				'brand'   => $settings['brand_name'],
				'city'    => $settings['default_city'],
				'keyword' => $primary_keyword,
			)
		);
	}

	$url = get_term_link( $term );
	if ( is_wp_error( $url ) ) {
		$url = '';
	}

	$taxonomy_obj = get_taxonomy( $term->taxonomy );
	$edit_url     = admin_url( 'term.php?taxonomy=' . rawurlencode( $term->taxonomy ) . '&tag_ID=' . (int) $term->term_id );

	$index_status = recrm_seo_manager_get_indexing_status_payload( recrm_seo_manager_is_term_indexing_enabled( $term->term_id, $term->taxonomy ) );

	return array(
		'title'            => $title,
		'keywords'         => $keywords,
		'meta_title'       => $meta_title,
		'meta_description' => $meta_desc,
		'image_id'         => recrm_seo_manager_get_term_image_id( $term->term_id ),
		'url'              => $url,
		'edit_url'         => $edit_url,
		'status'           => $taxonomy_obj && ! empty( $taxonomy_obj->labels->singular_name ) ? $taxonomy_obj->labels->singular_name : $term->taxonomy,
		'updated'          => '',
		'index_enabled'    => recrm_seo_manager_is_term_indexing_enabled( $term->term_id, $term->taxonomy ),
		'index_label'      => $index_status['label'],
		'index_class'      => $index_status['class'],
		'h2_suggestions'    => array(),
	);
}

function recrm_seo_manager_get_post_items( $post_type, $paged, $search ) {
	$per_page = 20;
	$query    = new WP_Query(
		array(
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => $per_page,
			'paged'          => max( 1, (int) $paged ),
			's'              => $search,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		)
	);

	$items = array();

	foreach ( (array) $query->posts as $post ) {
		$items[] = array_merge(
			array(
				'id'     => (int) $post->ID,
				'entity' => 'post',
			),
			recrm_seo_manager_get_post_defaults( $post )
		);
	}

	return array(
		'items'       => $items,
		'total_pages' => max( 1, (int) $query->max_num_pages ),
		'total_items' => (int) $query->found_posts,
		'per_page'    => $per_page,
	);
}

function recrm_seo_manager_get_term_items( $taxonomy, $paged, $search ) {
	$per_page = 20;
	$offset   = ( max( 1, (int) $paged ) - 1 ) * $per_page;

	$args = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'number'     => $per_page,
		'offset'     => $offset,
		'orderby'    => 'name',
		'order'      => 'ASC',
	);

	if ( '' !== $search ) {
		$args['search'] = $search;
	}

	$terms = get_terms( $args );
	if ( is_wp_error( $terms ) ) {
		$terms = array();
	}

	$count_args = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'fields'     => 'ids',
		'number'     => 0,
	);

	if ( '' !== $search ) {
		$count_args['search'] = $search;
	}

	$total_ids = get_terms( $count_args );
	if ( is_wp_error( $total_ids ) ) {
		$total_ids = array();
	}

	$items = array();

	foreach ( (array) $terms as $term ) {
		$items[] = array_merge(
			array(
				'id'     => (int) $term->term_id,
				'entity' => 'term',
			),
			recrm_seo_manager_get_term_defaults( $term )
		);
	}

	$total_items = count( $total_ids );

	return array(
		'items'       => $items,
		'total_pages' => max( 1, (int) ceil( $total_items / $per_page ) ),
		'total_items' => (int) $total_items,
		'per_page'    => $per_page,
	);
}

function recrm_seo_manager_get_listing_data( $tab_key, $paged, $search ) {
	$tabs       = recrm_seo_manager_get_tabs();
	$tab_config = isset( $tabs[ $tab_key ] ) ? $tabs[ $tab_key ] : reset( $tabs );

	if ( 'post' === $tab_config['entity'] ) {
		return recrm_seo_manager_get_post_items( $tab_config['post_type'], $paged, $search );
	}

	return recrm_seo_manager_get_term_items( $tab_config['taxonomy'], $paged, $search );
}

function recrm_seo_manager_is_site_indexing_enabled() {
	return '0' !== (string) get_option( 'blog_public', '1' );
}

function recrm_seo_manager_get_site_indexing_label() {
	return recrm_seo_manager_is_site_indexing_enabled()
		? 'Індексація сайту: увімкнена'
		: 'Індексація сайту: вимкнена';
}

function recrm_seo_manager_is_term_index_default_enabled( $taxonomy ) {
	$default_noindex_taxonomies = array( 'category', 'post_tag', 'property-location', 'property-status', 'property-type', 'property-feature' );

	return ! in_array( $taxonomy, $default_noindex_taxonomies, true );
}

function recrm_seo_manager_is_post_indexing_enabled( $post_id ) {
	return '1' !== (string) get_post_meta( $post_id, '_seonx_noindex', true );
}

function recrm_seo_manager_is_term_indexing_enabled( $term_id, $taxonomy ) {
	$value = get_term_meta( $term_id, 'recrm_seo_noindex', true );

	if ( '' === $value ) {
		return recrm_seo_manager_is_term_index_default_enabled( $taxonomy );
	}

	return '1' !== (string) $value;
}

function recrm_seo_manager_get_indexing_status_payload( $is_enabled ) {
	return array(
		'label' => $is_enabled ? 'Сторінка: index' : 'Сторінка: noindex',
		'class' => $is_enabled ? 'is-index-enabled' : 'is-index-disabled',
	);
}

function recrm_seo_manager_handle_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_POST['recrm_seo_manager_save'] ) ) {
		return;
	}

	check_admin_referer( 'recrm_seo_manager_save_action', 'recrm_seo_manager_nonce' );

	$tabs       = recrm_seo_manager_get_tabs();
	$tab_key    = isset( $_POST['current_tab'] ) ? sanitize_key( wp_unslash( $_POST['current_tab'] ) ) : key( $tabs );
	$search     = isset( $_POST['current_search'] ) ? sanitize_text_field( wp_unslash( $_POST['current_search'] ) ) : '';
	$paged      = isset( $_POST['current_paged'] ) ? max( 1, absint( $_POST['current_paged'] ) ) : 1;
	$tab_config = isset( $tabs[ $tab_key ] ) ? $tabs[ $tab_key ] : reset( $tabs );
	$items      = isset( $_POST['items'] ) ? (array) wp_unslash( $_POST['items'] ) : array();

	if ( 'post' === $tab_config['entity'] ) {
		foreach ( $items as $post_id => $item ) {
			$post_id = absint( $post_id );

			if ( $post_id <= 0 || get_post_type( $post_id ) !== $tab_config['post_type'] ) {
				continue;
			}

			$title       = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
			$keywords    = isset( $item['keywords'] ) ? recrm_seo_manager_normalize_keywords( $item['keywords'] ) : '';
			$meta_title  = isset( $item['meta_title'] ) ? sanitize_text_field( $item['meta_title'] ) : '';
			$meta_desc   = isset( $item['meta_description'] ) ? sanitize_textarea_field( $item['meta_description'] ) : '';
			$image_id      = isset( $item['image_id'] ) ? absint( $item['image_id'] ) : 0;
			$index_enabled = ! empty( $item['index_enabled'] );
			$old_title     = get_the_title( $post_id );
			$primary_key   = recrm_seo_manager_primary_keyword( $keywords );

			if ( '' !== $title && $title !== $old_title ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $title,
					)
				);
			}

			update_post_meta( $post_id, '_recrm_seo_keywords', $keywords );
			update_post_meta( $post_id, '_recrm_seo_keyword', $primary_key );
			update_post_meta( $post_id, '_recrm_seo_meta_title', $meta_title );
			update_post_meta( $post_id, '_recrm_seo_meta_description', $meta_desc );

			if ( $image_id > 0 ) {
				set_post_thumbnail( $post_id, $image_id );
			} else {
				delete_post_thumbnail( $post_id );
			}

			if ( $index_enabled ) {
				delete_post_meta( $post_id, '_seonx_noindex' );
			} else {
				update_post_meta( $post_id, '_seonx_noindex', '1' );
			}
		}
	} else {
		foreach ( $items as $term_id => $item ) {
			$term_id = absint( $term_id );
			$term    = get_term( $term_id, $tab_config['taxonomy'] );

			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			$title       = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
			$keywords    = isset( $item['keywords'] ) ? recrm_seo_manager_normalize_keywords( $item['keywords'] ) : '';
			$meta_title  = isset( $item['meta_title'] ) ? sanitize_text_field( $item['meta_title'] ) : '';
			$meta_desc   = isset( $item['meta_description'] ) ? sanitize_textarea_field( $item['meta_description'] ) : '';
			$image_id      = isset( $item['image_id'] ) ? absint( $item['image_id'] ) : 0;
			$index_enabled = ! empty( $item['index_enabled'] );
			$primary_key   = recrm_seo_manager_primary_keyword( $keywords );

			if ( '' !== $title && $title !== $term->name ) {
				wp_update_term(
					$term_id,
					$tab_config['taxonomy'],
					array(
						'name' => $title,
					)
				);
			}

			update_term_meta( $term_id, 'recrm_seo_keywords', $keywords );
			update_term_meta( $term_id, 'recrm_seo_keyword', $primary_key );
			update_term_meta( $term_id, 'recrm_seo_meta_title', $meta_title );
			update_term_meta( $term_id, 'recrm_seo_meta_description', $meta_desc );

			if ( $image_id > 0 ) {
				update_term_meta( $term_id, 'recrm_seo_image_id', $image_id );
			} else {
				delete_term_meta( $term_id, 'recrm_seo_image_id' );
			}

			update_term_meta( $term_id, 'recrm_seo_noindex', $index_enabled ? '0' : '1' );
		}
	}

	$redirect = add_query_arg(
		array(
			'page'  => 'recrm-seo-manager',
			'tab'   => $tab_key,
			's'     => $search,
			'paged' => $paged,
			'saved' => 1,
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}

function recrm_seo_manager_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tabs       = recrm_seo_manager_get_tabs();
	$current    = recrm_seo_manager_get_current_tab();
	$paged      = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$tab_config = $tabs[ $current ];
	$data = recrm_seo_manager_get_listing_data( $current, $paged, $search );
	?>
	<div class="wrap recrm-seo-manager-wrap">
		<style>
			.recrm-seo-manager-wrap { margin-top: 18px; }
			.recrm-seo-manager-wrap * { box-sizing: border-box; }
			.recrm-seo-manager-hero {
				padding: 28px 30px;
				border-radius: 22px;
				background: linear-gradient(135deg, #0d1838 0%, #1f2d5c 100%);
				color: #fff;
				margin-bottom: 20px;
			}
			.recrm-seo-manager-hero h1 { margin: 0 0 10px; color: #fff; font-size: 30px; }
			.recrm-seo-manager-hero p { margin: 0; max-width: 960px; color: rgba(255,255,255,.88); font-size: 15px; line-height: 1.7; }
			.recrm-seo-manager-tabs {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				margin: 0 0 18px;
			}
			.recrm-seo-manager-tab {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				padding: 10px 14px;
				border-radius: 999px;
				background: #fff;
				border: 1px solid #dbe4ef;
				text-decoration: none;
				color: #0f172a;
				font-weight: 600;
			}
			.recrm-seo-manager-tab.is-active {
				background: #0d1838;
				border-color: #0d1838;
				color: #fff;
			}
			.recrm-seo-manager-tab-count {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 28px;
				height: 28px;
				padding: 0 8px;
				border-radius: 999px;
				background: rgba(255,255,255,.18);
				font-size: 12px;
				font-weight: 700;
			}
			.recrm-seo-manager-toolbar {
				display: flex;
				gap: 12px;
				justify-content: space-between;
				align-items: center;
				flex-wrap: wrap;
				background: #fff;
				border: 1px solid #e5edf5;
				border-radius: 18px;
				padding: 16px 18px;
				margin-bottom: 18px;
			}
			.recrm-seo-manager-toolbar form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
			.recrm-seo-manager-toolbar input[type="search"] {
				min-width: 260px;
				padding: 10px 12px;
				border-radius: 10px;
				border: 1px solid #cbd5e1;
			}
			.recrm-seo-manager-summary { color: #475467; font-weight: 600; }
			.recrm-seo-manager-list { display: grid; gap: 16px; }
			.recrm-seo-manager-card {
				background: #fff;
				border: 1px solid #e5edf5;
				border-radius: 20px;
				padding: 18px;
				box-shadow: 0 8px 24px rgba(13, 24, 56, 0.04);
			}
			.recrm-seo-manager-card-head {
				display: flex;
				justify-content: space-between;
				align-items: center;
				gap: 12px;
				margin-bottom: 16px;
				flex-wrap: wrap;
			}
			.recrm-seo-manager-card-meta {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
				align-items: center;
			}
			.recrm-seo-manager-badge {
				display: inline-flex;
				align-items: center;
				padding: 6px 10px;
				border-radius: 999px;
				background: #f8fafc;
				border: 1px solid #dbe4ef;
				font-size: 12px;
				font-weight: 700;
				color: #334155;
			}
			.recrm-seo-manager-badge.is-index-enabled {
				background: #ecfdf3;
				border-color: #b7e4c7;
				color: #166534;
			}
			.recrm-seo-manager-badge.is-index-disabled {
				background: #fef2f2;
				border-color: #fecaca;
				color: #991b1b;
			}
			.recrm-seo-manager-card-grid {
				display: grid;
				grid-template-columns: 190px minmax(0, 1fr);
				gap: 18px;
			}
			.recrm-seo-manager-photo {
				border: 1px dashed #cbd5e1;
				border-radius: 18px;
				padding: 12px;
				background: #f8fafc;
			}
			.recrm-seo-manager-photo-preview {
				width: 100%;
				aspect-ratio: 4 / 3;
				border-radius: 14px;
				overflow: hidden;
				background: #e2e8f0;
				margin-bottom: 12px;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.recrm-seo-manager-photo-preview img {
				width: 100%;
				height: 100%;
				object-fit: cover;
				display: block;
			}
			.recrm-seo-manager-photo-empty {
				padding: 14px;
				text-align: center;
				color: #64748b;
				font-size: 13px;
				line-height: 1.5;
			}
			.recrm-seo-manager-photo-actions { display: grid; gap: 8px; }
			.recrm-seo-manager-fields { display: grid; gap: 14px; }
			.recrm-seo-manager-row {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 14px;
			}
			.recrm-seo-manager-field label {
				display: block;
				margin-bottom: 6px;
				font-weight: 600;
				color: #0f172a;
			}
			.recrm-seo-manager-field input[type="text"],
			.recrm-seo-manager-field textarea {
				width: 100%;
				padding: 11px 12px;
				border-radius: 12px;
				border: 1px solid #cbd5e1;
				background: #fff;
			}
			.recrm-seo-manager-field input[readonly] {
				background: #f8fafc;
				color: #475467;
			}
			.recrm-seo-manager-field textarea { min-height: 112px; resize: vertical; }
			.recrm-seo-manager-note { margin: 6px 0 0; color: #64748b; font-size: 12px; }
			.recrm-seo-manager-suggestions { margin-top: 10px; }
			.recrm-seo-manager-suggestions-label { margin-bottom: 8px; font-size: 12px; font-weight: 600; color: #475467; }
			.recrm-seo-manager-suggestions-list { display: flex; flex-wrap: wrap; gap: 8px; }
			.recrm-seo-manager-suggestions-list .button { border-radius: 999px; padding: 0 12px; min-height: 32px; line-height: 30px; }
			.recrm-seo-manager-actions {
				position: sticky;
				bottom: 12px;
				margin-top: 18px;
				padding: 16px 18px;
				background: rgba(255,255,255,.96);
				backdrop-filter: blur(10px);
				border: 1px solid #e5edf5;
				border-radius: 18px;
				display: flex;
				justify-content: space-between;
				align-items: center;
				gap: 12px;
				flex-wrap: wrap;
			}
			.recrm-seo-manager-pagination {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
				margin-top: 18px;
			}
			.recrm-seo-manager-pagination a,
			.recrm-seo-manager-pagination span {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 38px;
				height: 38px;
				padding: 0 12px;
				border-radius: 12px;
				border: 1px solid #dbe4ef;
				background: #fff;
				text-decoration: none;
				font-weight: 600;
				color: #0f172a;
			}
			.recrm-seo-manager-pagination .current {
				background: #0d1838;
				border-color: #0d1838;
				color: #fff;
			}
			@media (max-width: 1200px) {
				.recrm-seo-manager-card-grid,
				.recrm-seo-manager-row {
					grid-template-columns: 1fr;
				}
			}
		</style>

		<div class="recrm-seo-manager-hero">
			<h1>SEO сторінки та категорії</h1>
			<p>Тут зібрані всі основні сторінки сайту, записи, об’єкти нерухомості та таксономії. Для кожного елемента можна додати фото з медіатеки, змінити назву, побачити URL, прописати SEO ключі через кому, meta title і meta description.</p>
		</div>

		<?php if ( ! empty( $_GET['saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>SEO дані для поточної вкладки збережено.</p></div>
		<?php endif; ?>

		<div class="recrm-seo-manager-tabs">
			<?php foreach ( $tabs as $tab_key => $tab ) : ?>
				<?php
				$url = add_query_arg(
					array(
						'page' => 'recrm-seo-manager',
						'tab'  => $tab_key,
					),
					admin_url( 'admin.php' )
				);
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="recrm-seo-manager-tab <?php echo $tab_key === $current ? 'is-active' : ''; ?>">
					<span><?php echo esc_html( $tab['label'] ); ?></span>
					<span class="recrm-seo-manager-tab-count"><?php echo (int) recrm_seo_manager_get_tab_count( $tab ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="recrm-seo-manager-toolbar">
			<form method="get">
				<input type="hidden" name="page" value="recrm-seo-manager">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $current ); ?>">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Пошук по назві...">
				<button type="submit" class="button button-secondary">Шукати</button>
				<?php if ( '' !== $search ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'recrm-seo-manager', 'tab' => $current ), admin_url( 'admin.php' ) ) ); ?>" class="button">Скинути</a>
				<?php endif; ?>
			</form>
			<div class="recrm-seo-manager-summary">
				<?php echo esc_html( $tab_config['label'] ); ?>: <?php echo (int) $data['total_items']; ?>
			</div>
		</div>

		<form method="post">
			<?php wp_nonce_field( 'recrm_seo_manager_save_action', 'recrm_seo_manager_nonce' ); ?>
			<input type="hidden" name="current_tab" value="<?php echo esc_attr( $current ); ?>">
			<input type="hidden" name="current_search" value="<?php echo esc_attr( $search ); ?>">
			<input type="hidden" name="current_paged" value="<?php echo (int) $paged; ?>">

			<div class="recrm-seo-manager-list">
				<?php if ( empty( $data['items'] ) ) : ?>
					<div class="recrm-seo-manager-card">
						<strong>Нічого не знайдено.</strong>
					</div>
				<?php endif; ?>

				<?php foreach ( $data['items'] as $item ) : ?>
					<?php
					$image_url = recrm_seo_manager_get_image_url( $item['image_id'] );
					?>
					<div class="recrm-seo-manager-card">
						<div class="recrm-seo-manager-card-head">
							<div class="recrm-seo-manager-card-meta">
								<span class="recrm-seo-manager-badge">ID: <?php echo (int) $item['id']; ?></span>
								<span class="recrm-seo-manager-badge"><?php echo esc_html( $item['status'] ); ?></span>
								<span class="recrm-seo-manager-badge <?php echo esc_attr( $item['index_class'] ); ?>"><?php echo esc_html( $item['index_label'] ); ?></span>
								<?php if ( ! empty( $item['updated'] ) ) : ?>
									<span class="recrm-seo-manager-badge">Оновлено: <?php echo esc_html( $item['updated'] ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $item['edit_url'] ) ) : ?>
								<a href="<?php echo esc_url( $item['edit_url'] ); ?>" class="button button-secondary">Редагувати окремо</a>
							<?php endif; ?>
						</div>

						<div class="recrm-seo-manager-card-grid">
							<div class="recrm-seo-manager-photo">
								<div class="recrm-seo-manager-photo-preview">
									<?php if ( $image_url ) : ?>
										<img src="<?php echo esc_url( $image_url ); ?>" alt="">
									<?php else : ?>
										<div class="recrm-seo-manager-photo-empty">Фото не вибране</div>
									<?php endif; ?>
								</div>
								<div class="recrm-seo-manager-photo-actions">
									<input type="hidden" class="recrm-seo-manager-image-id" name="items[<?php echo (int) $item['id']; ?>][image_id]" value="<?php echo (int) $item['image_id']; ?>">
									<button type="button" class="button button-secondary recrm-seo-media-select">Обрати з галереї</button>
									<button type="button" class="button recrm-seo-media-remove">Прибрати фото</button>
								</div>
							</div>

							<div class="recrm-seo-manager-fields">
								<div class="recrm-seo-manager-row">
									<div class="recrm-seo-manager-field">
										<label>URL</label>
										<input type="text" value="<?php echo esc_attr( $item['url'] ); ?>" readonly>
										<p class="recrm-seo-manager-note">URL тут показується для швидкої перевірки. Редагування slug лишається у стандартному редакторі.</p>
									</div>
									<div class="recrm-seo-manager-field">
										<label>Назва</label>
										<input type="text" name="items[<?php echo (int) $item['id']; ?>][title]" value="<?php echo esc_attr( $item['title'] ); ?>">
									</div>
								</div>

								<div class="recrm-seo-manager-field">
									<label>SEO ключі</label>
									<input type="text" class="recrm-seo-keywords-input" name="items[<?php echo (int) $item['id']; ?>][keywords]" value="<?php echo esc_attr( $item['keywords'] ); ?>">
									<p class="recrm-seo-manager-note">Можна вказувати кілька ключів через кому. Перший ключ автоматично стане основним.</p>
									<?php if ( ! empty( $item['h2_suggestions'] ) ) : ?>
										<div class="recrm-seo-manager-suggestions">
											<div class="recrm-seo-manager-suggestions-label">H2 зі сторінки:</div>
											<div class="recrm-seo-manager-suggestions-list">
												<?php foreach ( (array) $item['h2_suggestions'] as $h2_item ) : ?>
													<button type="button" class="button button-secondary recrm-seo-add-keyword" data-keyword="<?php echo esc_attr( $h2_item ); ?>">+ <?php echo esc_html( $h2_item ); ?></button>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endif; ?>
								</div>

								<div class="recrm-seo-manager-field">
									<label style="margin-bottom:10px;">Індексація сторінки</label>
									<label style="display:inline-flex;align-items:center;gap:8px;font-weight:600;">
										<input type="hidden" name="items[<?php echo (int) $item['id']; ?>][index_enabled]" value="0">
										<input type="checkbox" name="items[<?php echo (int) $item['id']; ?>][index_enabled]" value="1" <?php checked( ! empty( $item['index_enabled'] ) ); ?>>
										Дозволити індексацію цієї сторінки
									</label>
									<p class="recrm-seo-manager-note">Коли вимкнено, для цього елемента буде застосовано <code>noindex, nofollow</code>.</p>
								</div>

								<div class="recrm-seo-manager-field">
									<label>Meta title</label>
									<input type="text" name="items[<?php echo (int) $item['id']; ?>][meta_title]" value="<?php echo esc_attr( $item['meta_title'] ); ?>">
								</div>

								<div class="recrm-seo-manager-field">
									<label>Meta description</label>
									<textarea name="items[<?php echo (int) $item['id']; ?>][meta_description]"><?php echo esc_textarea( $item['meta_description'] ); ?></textarea>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="recrm-seo-manager-actions">
				<div>Зберігається тільки поточна вкладка: <strong><?php echo esc_html( $tab_config['label'] ); ?></strong>.</div>
				<button type="submit" name="recrm_seo_manager_save" value="1" class="button button-primary button-large">Зберегти поточну вкладку</button>
			</div>
		</form>

		<?php if ( $data['total_pages'] > 1 ) : ?>
			<div class="recrm-seo-manager-pagination">
				<?php echo wp_kses_post( paginate_links( array(
					'base'      => add_query_arg( array( 'page' => 'recrm-seo-manager', 'tab' => $current, 's' => $search, 'paged' => '%#%' ), admin_url( 'admin.php' ) ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $data['total_pages'],
					'type'      => 'plain',
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				) ) ); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

function recrm_seo_manager_get_current_term_object() {
	if ( is_admin() ) {
		return null;
	}

	if ( is_category() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}

	return null;
}

function recrm_seo_manager_filter_term_document_title( $title ) {
	$term = recrm_seo_manager_get_current_term_object();

	if ( ! $term ) {
		return $title;
	}

	$custom_title = get_term_meta( $term->term_id, 'recrm_seo_meta_title', true );
	return '' !== $custom_title ? $custom_title : $title;
}

function recrm_seo_manager_output_term_meta_description() {
	$term = recrm_seo_manager_get_current_term_object();

	if ( ! $term ) {
		return;
	}

	$description = get_term_meta( $term->term_id, 'recrm_seo_meta_description', true );

	if ( '' !== $description ) {
		echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
