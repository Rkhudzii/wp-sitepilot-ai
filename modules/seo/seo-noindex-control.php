<?php
/**
 * Plugin Name: SEO Noindex Control
 * Description: Просте керування noindex/nofollow для сторінок, записів і об'єктів нерухомості.
 * Version: 1.0.4
 * Author: Roman
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Які post types підтримувати
 */
function seonx_supported_post_types() {
	return array( 'page', 'post', 'property' );
}

function seonx_default_noindex_taxonomies() {
	return array( 'category', 'post_tag', 'property-location', 'property-status', 'property-type', 'property-feature' );
}

function seonx_term_indexing_enabled( $term ) {
	if ( ! ( $term instanceof WP_Term ) ) {
		return true;
	}

	$value = get_term_meta( $term->term_id, 'recrm_seo_noindex', true );

	if ( '' === $value ) {
		return ! in_array( $term->taxonomy, seonx_default_noindex_taxonomies(), true );
	}

	return '1' !== (string) $value;
}

/**
 * Додаємо метабокс
 */
add_action( 'add_meta_boxes', 'seonx_add_meta_box' );
function seonx_add_meta_box() {
	foreach ( seonx_supported_post_types() as $post_type ) {
		add_meta_box(
			'seonx_noindex_box',
			'SEO: Індексація',
			'seonx_render_meta_box',
			$post_type,
			'side',
			'high'
		);
	}
}

/**
 * HTML метабокса
 */
function seonx_render_meta_box( $post ) {
	wp_nonce_field( 'seonx_save_meta_box', 'seonx_noindex_nonce' );

	$value = get_post_meta( $post->ID, '_seonx_noindex', true );
	?>
	<p>
		<label>
			<input type="checkbox" name="seonx_noindex" value="1" <?php checked( $value, '1' ); ?>>
			Закрити сторінку від індексації
		</label>
	</p>
	<p style="font-size:12px;color:#666;margin:0;">
		Додасть meta robots: <code>noindex, nofollow</code>
	</p>
	<?php
}

/**
 * Збереження
 */
add_action( 'save_post', 'seonx_save_meta_box' );
function seonx_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['seonx_noindex_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['seonx_noindex_nonce'], 'seonx_save_meta_box' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && ! in_array( $_POST['post_type'], seonx_supported_post_types(), true ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$value = isset( $_POST['seonx_noindex'] ) ? '1' : '';
	
	if ( $value ) {
		update_post_meta( $post_id, '_seonx_noindex', '1' );
	} else {
		delete_post_meta( $post_id, '_seonx_noindex' );
	}
}

/**
 * Вивід meta robots у head
 */
add_filter( 'wp_robots', 'seonx_filter_wp_robots', 999 );

function seonx_filter_wp_robots( $robots ) {

	// ❌ технічні сторінки
	if (
		is_tag() ||
		is_tax('property-location') ||
		is_tax('property-status') ||
		is_tax('property-type') ||
		is_tax('property-feature') ||
		is_category() ||
		is_search() ||
		is_paged() ||
		is_author() ||
		is_date()
	) {
		$robots['noindex'] = true;
		unset($robots['index']);

		$robots['follow'] = true;
		unset($robots['nofollow']);

		return $robots;
	}

	// ❌ ручний noindex
	if ( is_singular( seonx_supported_post_types() ) ) {
		$post_id = get_queried_object_id();

		if ( $post_id && '1' === get_post_meta( $post_id, '_seonx_noindex', true ) ) {
			$robots['noindex'] = true;
			unset($robots['index']);

			$robots['follow'] = true;
			unset($robots['nofollow']);
		}
	}

	return $robots;
}