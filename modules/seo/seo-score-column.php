<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'save_post', 'recrm_update_seo_score_on_save', 30, 3 );
add_filter( 'manage_post_posts_columns', 'recrm_add_seo_score_column' );
add_action( 'manage_post_posts_custom_column', 'recrm_render_seo_score_column', 10, 2 );
add_filter( 'manage_edit-post_sortable_columns', 'recrm_make_seo_score_column_sortable' );
add_action( 'pre_get_posts', 'recrm_handle_seo_score_orderby' );

/**
 * -----------------------------
 * SUPPORTED TYPES
 * -----------------------------
 */

function recrm_seo_score_supported_post_types() {
	return array( 'post' );
}

/**
 * -----------------------------
 * HELPERS
 * -----------------------------
 */

function recrm_seo_score_normalize_text( $value ) {
	$value = (string) $value;
	$value = wp_strip_all_tags( $value );
	$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$value = mb_strtolower( $value, 'UTF-8' );
	$value = preg_replace( '/[^\p{L}\p{N}\s\-]+/u', ' ', $value );
	$value = preg_replace( '/\s+/u', ' ', $value );
	return trim( $value );
}

function recrm_seo_score_count_words( $text ) {
	$text = wp_strip_all_tags( (string) $text );
	preg_match_all( '/[\p{L}\p{N}\-]+/u', $text, $matches );
	return ! empty( $matches[0] ) ? count( $matches[0] ) : 0;
}

function recrm_seo_score_get_focus_keyword( $post_id ) {
	$keys = array(
		'_recrm_seo_keyword', // ← ОСНОВНИЙ ТВОЇЙ
		'recrm_focus_keyword',
		'rank_math_focus_keyword',
		'_yoast_wpseo_focuskw',
	);

	foreach ( $keys as $key ) {
		$value = trim( (string) get_post_meta( $post_id, $key, true ) );
		if ( '' !== $value ) {
			return recrm_seo_score_normalize_text( $value );
		}
	}

	return '';
}

function recrm_seo_score_get_meta_description( $post_id ) {
	$keys = array(
		'_recrm_seo_meta_description',
		'_yoast_wpseo_metadesc',
		'rank_math_description',
		'recrm_meta_description',
	);

	foreach ( $keys as $key ) {
		$value = trim( (string) get_post_meta( $post_id, $key, true ) );
		if ( '' !== $value ) {
			return $value;
		}
	}

	return '';
}

function recrm_seo_score_get_seo_title( $post_id, $post ) {
	$keys = array(
		'_recrm_seo_meta_title',
		'_yoast_wpseo_title',
		'rank_math_title',
		'recrm_seo_title',
	);

	foreach ( $keys as $key ) {
		$value = trim( (string) get_post_meta( $post_id, $key, true ) );
		if ( '' !== $value ) {
			return $value;
		}
	}

	return isset( $post->post_title ) ? (string) $post->post_title : '';
}

function recrm_seo_score_get_plain_content( $content ) {
	$content = (string) $content;

	if ( function_exists( 'do_blocks' ) ) {
		$content = do_blocks( $content );
	}

	$content = preg_replace( '#<script(.*?)>(.*?)</script>#is', ' ', $content );
	$content = preg_replace( '#<style(.*?)>(.*?)</style>#is', ' ', $content );

	return wp_strip_all_tags( $content, true );
}

function recrm_seo_score_extract_links( $content ) {
	$result = array(
		'total'    => 0,
		'internal' => 0,
		'external' => 0,
	);

	$content = (string) $content;

	if ( ! preg_match_all( '/<a\s[^>]*href=(["\'])(.*?)\1/iu', $content, $matches ) ) {
		return $result;
	}

	$site_url  = home_url();
	$site_host = wp_parse_url( $site_url, PHP_URL_HOST );

	foreach ( $matches[2] as $href ) {
		$href = trim( html_entity_decode( $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

		if ( '' === $href || 0 === strpos( $href, '#' ) || 0 === strpos( $href, 'tel:' ) || 0 === strpos( $href, 'mailto:' ) ) {
			continue;
		}

		$result['total']++;

		$link_host = wp_parse_url( $href, PHP_URL_HOST );

		if ( empty( $link_host ) ) {
			$result['internal']++;
			continue;
		}

		if ( $site_host && $link_host === $site_host ) {
			$result['internal']++;
		} else {
			$result['external']++;
		}
	}

	return $result;
}

function recrm_seo_score_extract_images_data( $content, $focus_keyword = '' ) {
	$data = array(
		'total'      => 0,
		'with_alt'   => 0,
		'alt_values' => array(),
	);

	if ( empty( $content ) ) {
		return $data;
	}

	libxml_use_internal_errors( true );

	$dom = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $content );

	$images = $dom->getElementsByTagName( 'img' );

	$index = 0;

	foreach ( $images as $img ) {
		$data['total']++;

		$alt = trim( $img->getAttribute( 'alt' ) );

		// Якщо alt порожній — генеруємо
		if ( '' === $alt && '' !== $focus_keyword ) {
			$alt = recrm_generate_alt_text( $focus_keyword, $index );
		}

		if ( '' !== $alt ) {
			$data['with_alt']++;
			$data['alt_values'][] = $alt;
		}

		$index++;
	}

	return $data;
}

function recrm_seo_score_extract_h2_texts( $content ) {
	$texts = array();

	if ( empty( $content ) ) {
		return $texts;
	}

	libxml_use_internal_errors( true );

	$dom = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $content );

	$h2s = $dom->getElementsByTagName( 'h2' );

	foreach ( $h2s as $h2 ) {
		$text = trim( $h2->textContent );

		if ( '' !== $text ) {
			$texts[] = $text;
		}
	}

	return $texts;
}

function recrm_seo_score_contains_keyword( $text, $keyword ) {
	$text    = recrm_seo_score_prepare_phrase_for_match( $text );
	$keyword = recrm_seo_score_prepare_phrase_for_match( $keyword );

	if ( '' === $text || '' === $keyword ) {
		return false;
	}

	return false !== mb_strpos( $text, $keyword, 0, 'UTF-8' );
}

function recrm_seo_score_prepare_phrase_for_match( $text ) {
	$text = recrm_seo_score_normalize_text( $text );

	if ( '' === $text ) {
		return '';
	}

	$ignore_words = array( 'в', 'у', 'на', 'і', 'й', 'та', 'з', 'із', 'зі', 'до', 'по', 'для', 'від', 'про', 'як' );

	$words    = preg_split( '/\s+/u', $text );
	$prepared = array();

	foreach ( $words as $word ) {
		$word = trim( $word );

		if ( '' === $word || in_array( $word, $ignore_words, true ) ) {
			continue;
		}

		$prepared[] = recrm_seo_score_prepare_word_for_match( $word );
	}

	$prepared = array_filter( $prepared );

	return implode( ' ', $prepared );
}

function recrm_seo_score_prepare_word_for_match( $word ) {
	$word = recrm_seo_score_normalize_text( $word );

	if ( '' === $word ) {
		return '';
	}

	$replacements = array(
		'тернополі'   => 'тернопіль',
		'тернополя'   => 'тернопіль',
		'тернополем'  => 'тернопіль',
		'квартиру'    => 'квартира',
		'квартири'    => 'квартира',
		'квартирі'    => 'квартира',
		'квартирою'   => 'квартира',
		'нерухомості' => 'нерухомість',
		'нерухомістю' => 'нерухомість',
		'новобудови'  => 'новобудова',
		'новобудові'  => 'новобудова',
		'новобудовах' => 'новобудова',
		'будинку'     => 'будинок',
		'будинки'     => 'будинок',
		'будинків'    => 'будинок',
	);

	if ( isset( $replacements[ $word ] ) ) {
		return $replacements[ $word ];
	}

	$endings = array(
		'ями', 'еві', 'ові', 'ого', 'ому', 'ами', 'ах', 'ях',
		'ою', 'ею', 'єю', 'ий', 'ій', 'а', 'у', 'і', 'и', 'о', 'е', 'ю', 'я'
	);

	foreach ( $endings as $ending ) {
		$ending_len = mb_strlen( $ending, 'UTF-8' );
		$word_len   = mb_strlen( $word, 'UTF-8' );

		if ( $word_len <= 5 ) {
			break;
		}

		if ( mb_substr( $word, -$ending_len, null, 'UTF-8' ) === $ending ) {
			$base = mb_substr( $word, 0, $word_len - $ending_len, 'UTF-8' );

			if ( mb_strlen( $base, 'UTF-8' ) >= 4 ) {
				return $base;
			}
		}
	}

	return $word;
}

function recrm_seo_score_count_keyword_occurrences( $text, $keyword ) {
	$text    = recrm_seo_score_prepare_phrase_for_match( $text );
	$keyword = recrm_seo_score_prepare_phrase_for_match( $keyword );

	if ( '' === $text || '' === $keyword ) {
		return 0;
	}

	return preg_match_all(
		'/' . preg_quote( $keyword, '/' ) . '/u',
		$text,
		$matches
	);
}

function recrm_seo_score_get_color( $score, $max_score ) {
	if ( $max_score <= 0 ) {
		return '#6b7280';
	}

	$ratio = $score / $max_score;

	if ( $ratio >= 0.75 ) {
		return '#15803d';
	}

	if ( $ratio >= 0.45 ) {
		return '#b45309';
	}

	return '#b91c1c';
}

function recrm_seo_score_add_check( &$checks, &$score, &$max_score, $key, $label, $points, $max_points, $passed, $details = '' ) {
	$checks[ $key ] = array(
		'label'   => $label,
		'points'  => (int) $points,
		'max'     => (int) $max_points,
		'passed'  => (bool) $passed,
		'details' => (string) $details,
	);

	$score     += (int) $points;
	$max_score += (int) $max_points;
}

/**
 * -----------------------------
 * MAIN ANALYSIS
 * -----------------------------
 */

function recrm_calculate_seo_score( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return;
	}

	$content_html  = apply_filters( 'the_content', $post->post_content );
	$content_text  = wp_strip_all_tags( $content_html );
	$content_norm  = recrm_seo_score_normalize_text( $content_text );
	$title         = (string) $post->post_title;
	$seo_title     = (string) recrm_seo_score_get_seo_title( $post_id, $post );
	$meta_desc     = (string) recrm_seo_score_get_meta_description( $post_id );
	$excerpt       = (string) $post->post_excerpt;
	$focus_keyword = (string) recrm_seo_score_get_focus_keyword( $post_id );

	$title_norm     = recrm_seo_score_normalize_text( $title );
	$seo_title_norm = recrm_seo_score_normalize_text( $seo_title );
	$meta_norm      = recrm_seo_score_normalize_text( $meta_desc );
	$excerpt_norm   = recrm_seo_score_normalize_text( $excerpt );

	$word_count = recrm_seo_score_count_words( $content_text );
	$h2_texts   = recrm_seo_score_extract_h2_texts( $content_html );
	$links      = recrm_seo_score_extract_links( $content_html );
	$images = recrm_seo_score_extract_images_data( $content_html, $focus_keyword );
    $featured_image = recrm_seo_score_get_featured_image_data( $post_id, $focus_keyword );

    $images['total']      += $featured_image['total'];
    $images['with_alt']   += $featured_image['with_alt'];
    $images['alt_values'] = array_merge( $images['alt_values'], $featured_image['alt_values'] );

	$checks    = array();
	$score     = 0;
	$max_score = 0;

	/**
	 * Focus keyword exists
	 */
	/**
	 * Focus keyword exists
	 */
	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'focus_keyword',
		'Фокусний ключ задано',
		'' !== $focus_keyword ? 6 : 0,
		6,
		'' !== $focus_keyword,
		'' !== $focus_keyword ? $focus_keyword : 'Ключ не заповнено'
	);

	/**
	 * SEO title exists
	 */
	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'seo_title_exists',
		'SEO title заповнений',
		'' !== trim( $seo_title ) ? 4 : 0,
		4,
		'' !== trim( $seo_title ),
		'' !== trim( $seo_title ) ? $seo_title : 'Порожній'
	);

	/**
	 * Keyword in SEO title
	 */
	$title_has_keyword = ( '' !== $focus_keyword ) && recrm_seo_score_contains_keyword( $seo_title_norm, $focus_keyword );

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'keyword_in_title',
		'Ключ у SEO title',
		$title_has_keyword ? 8 : 0,
		8,
		$title_has_keyword,
		$title_has_keyword ? 'Є входження ключа' : 'Немає входження ключа'
	);

	/**
	 * SEO title length
	 */
	$seo_title_length = mb_strlen( trim( wp_strip_all_tags( $seo_title ) ), 'UTF-8' );
	$title_length_pts = 0;
	$title_length_ok  = false;

    if ( $seo_title_length >= 40 && $seo_title_length <= 60 ) {
        $title_length_pts = 6;
        $title_length_ok  = true;
    } elseif ( $seo_title_length >= 61 && $seo_title_length <= 70 ) {
        $title_length_pts = 4;
    } elseif ( $seo_title_length >= 71 && $seo_title_length <= 85 ) {
        $title_length_pts = 2;
    }

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'title_length',
		'Довжина SEO title',
		$title_length_pts,
		6,
		$title_length_ok,
		$seo_title_length . ' символів'
	);

	/**
	 * Meta description exists
	 */
	$meta_exists = '' !== trim( $meta_desc );

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'meta_exists',
		'Meta description заповнений',
		$meta_exists ? 6 : 0,
		6,
		$meta_exists,
		$meta_exists ? 'Заповнено' : 'Порожньо'
	);

	/**
	 * Keyword in meta
	 */
	$meta_has_keyword = ( '' !== $focus_keyword ) && recrm_seo_score_contains_keyword( $meta_norm, $focus_keyword );

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'keyword_in_meta',
		'Ключ у meta description',
		$meta_has_keyword ? 6 : 0,
		6,
		$meta_has_keyword,
		$meta_has_keyword ? 'Є входження ключа' : 'Немає входження ключа'
	);

	/**
	 * Meta length
	 */
	$meta_length  = mb_strlen( trim( wp_strip_all_tags( $meta_desc ) ), 'UTF-8' );
	$meta_len_pts = 0;
	$meta_len_ok  = false;

	if ( $meta_length >= 120 && $meta_length <= 160 ) {
		$meta_len_pts = 5;
		$meta_len_ok  = true;
	} elseif ( $meta_length >= 90 && $meta_length <= 180 ) {
		$meta_len_pts = 2;
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'meta_length',
		'Довжина meta description',
		$meta_len_pts,
		5,
		$meta_len_ok,
		$meta_length . ' символів'
	);

	/**
	 * Content length
	 */
	$content_length_pts = 0;
	$content_length_ok  = false;

	if ( $word_count >= 900 ) {
		$content_length_pts = 10;
		$content_length_ok  = true;
	} elseif ( $word_count >= 600 ) {
		$content_length_pts = 7;
	} elseif ( $word_count >= 300 ) {
		$content_length_pts = 4;
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'content_length',
		'Обсяг тексту',
		$content_length_pts,
		10,
		$content_length_ok,
		$word_count . ' слів'
	);

	/**
	 * Keyword in early content
	 */
	$first_words_text = '';
	if ( preg_match_all( '/[\p{L}\p{N}\-]+/u', $content_text, $words_match ) ) {
		$first_words      = array_slice( $words_match[0], 0, 120 );
		$first_words_text = implode( ' ', $first_words );
	}

	$early_keyword = ( '' !== $focus_keyword ) && recrm_seo_score_contains_keyword( $first_words_text, $focus_keyword );

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'keyword_early',
		'Ключ на початку тексту',
		$early_keyword ? 6 : 0,
		6,
		$early_keyword,
		$early_keyword ? 'Ключ знайдено у перших 120 словах' : 'Ключ не знайдено у перших 120 словах'
	);

	/**
	 * Keyword density
	 */
	$keyword_occurrences = recrm_seo_score_count_keyword_occurrences( $content_norm, $focus_keyword );
	$density             = ( $word_count > 0 && $keyword_occurrences > 0 ) ? ( $keyword_occurrences / $word_count ) * 100 : 0;
	$density_pts         = 0;
	$density_ok          = false;

	if ( $density >= 0.8 && $density <= 2.5 ) {
		$density_pts = 6;
		$density_ok  = true;
	} elseif ( $density > 0 && $density < 0.8 ) {
		$density_pts = 3;
	} elseif ( $density > 2.5 && $density <= 4 ) {
		$density_pts = 2;
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'keyword_density',
		'Щільність ключа',
		$density_pts,
		6,
		$density_ok,
		$keyword_occurrences . ' входж., ' . round( $density, 2 ) . '%'
	);

	/**
	 * H2 count
	 */
	$h2_count = count( $h2_texts );
	$h2_pts   = 0;
	$h2_ok    = false;

	if ( $h2_count >= 2 ) {
		$h2_pts = 5;
		$h2_ok  = true;
	} elseif ( 1 === $h2_count ) {
		$h2_pts = 2;
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'h2_count',
		'Кількість H2',
		$h2_pts,
		5,
		$h2_ok,
		$h2_count . ' H2'
	);

	/**
	 * Keyword in H2
	 */
	$h2_keyword = false;

	if ( '' !== $focus_keyword ) {
		foreach ( $h2_texts as $h2_text ) {
			if ( recrm_seo_score_contains_keyword( $h2_text, $focus_keyword ) ) {
				$h2_keyword = true;
				break;
			}
		}
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'keyword_in_h2',
		'Ключ у H2',
		$h2_keyword ? 6 : 0,
		6,
		$h2_keyword,
		$h2_keyword ? 'Є H2 з ключем' : 'Немає H2 з ключем'
	);

	/**
	 * Internal links
	 */
	$internal_pts = 0;
	$internal_ok  = false;

	if ( $links['internal'] >= 2 ) {
		$internal_pts = 5;
		$internal_ok  = true;
	} elseif ( 1 === $links['internal'] ) {
		$internal_pts = 2;
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'internal_links',
		'Внутрішні лінки',
		$internal_pts,
		5,
		$internal_ok,
		$links['internal'] . ' внутрішніх'
	);

	/**
	 * External links
	 */
	$external_pts = 0;
	$external_ok  = false;

	if ( $links['external'] >= 1 ) {
		$external_pts = 4;
		$external_ok  = true;
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'external_links',
		'Зовнішні лінки',
		$external_pts,
		4,
		$external_ok,
		$links['external'] . ' зовнішніх'
	);

	/**
	 * Images with alt
	 */
	$images_alt_pts = 0;
	$images_alt_ok  = false;

	if ( $images['total'] > 0 ) {
		if ( $images['with_alt'] === $images['total'] ) {
			$images_alt_pts = 6;
			$images_alt_ok  = true;
		} elseif ( $images['with_alt'] > 0 ) {
			$images_alt_pts = 3;
		}
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'images_alt',
		'Alt у зображеннях',
		$images_alt_pts,
		6,
		$images_alt_ok,
		$images['with_alt'] . '/' . $images['total'] . ' з alt'
	);

	/**
	 * Keyword in alt
	 */
	$keyword_in_alt = false;

	if ( '' !== $focus_keyword && ! empty( $images['alt_values'] ) ) {
		foreach ( $images['alt_values'] as $alt_value ) {
			if ( recrm_seo_score_contains_keyword( $alt_value, $focus_keyword ) ) {
				$keyword_in_alt = true;
				break;
			}
		}
	}

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'keyword_in_alt',
		'Ключ у alt',
		$keyword_in_alt ? 5 : 0,
		5,
		$keyword_in_alt,
		$keyword_in_alt ? 'Є alt з ключем' : 'Немає alt з ключем'
	);

	/**
	 * Excerpt
	 */
	$excerpt_exists = '' !== $excerpt_norm;

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'excerpt_exists',
		'Excerpt заповнений',
		$excerpt_exists ? 3 : 0,
		3,
		$excerpt_exists,
		$excerpt_exists ? 'Заповнено' : 'Порожньо'
	);

	/**
	 * Keyword in excerpt
	 */
	$excerpt_has_keyword = ( '' !== $focus_keyword ) && recrm_seo_score_contains_keyword( $excerpt_norm, $focus_keyword );

	recrm_seo_score_add_check(
		$checks,
		$score,
		$max_score,
		'keyword_in_excerpt',
		'Ключ в excerpt',
		$excerpt_has_keyword ? 3 : 0,
		3,
		$excerpt_has_keyword,
		$excerpt_has_keyword ? 'Є входження ключа' : 'Немає входження ключа'
	);

	/**
	 * Save meta
	 */
	update_post_meta( $post_id, 'recrm_seo_score', (int) $score );
	update_post_meta( $post_id, 'recrm_seo_score_max', (int) $max_score );
	update_post_meta( $post_id, 'recrm_seo_score_checks', $checks );
	update_post_meta( $post_id, 'recrm_seo_score_updated', current_time( 'mysql' ) );
}

/**
 * -----------------------------
 * SAVE
 * -----------------------------
 */

function recrm_update_seo_score_on_save( $post_id, $post, $update ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! $post || ! isset( $post->post_type ) ) {
		return;
	}

	if ( ! in_array( $post->post_type, recrm_seo_score_supported_post_types(), true ) ) {
		return;
	}

	recrm_calculate_seo_score( $post_id );
}

/**
 * -----------------------------
 * COLUMN
 * -----------------------------
 */

function recrm_add_seo_score_column( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		if ( 'title' === $key ) {
			$new_columns['recrm_seo_score'] = 'SEO';
		}
	}

	if ( ! isset( $new_columns['recrm_seo_score'] ) ) {
		$new_columns['recrm_seo_score'] = 'SEO';
	}

	return $new_columns;
}

function recrm_render_seo_score_column( $column, $post_id ) {
	if ( 'recrm_seo_score' !== $column ) {
		return;
	}

	$post = get_post( $post_id );

	if ( ! $post || ! in_array( $post->post_type, recrm_seo_score_supported_post_types(), true ) ) {
		echo '—';
		return;
	}

	// Завжди перераховуємо при виводі колонки, щоб список не показував старі значення.
	recrm_calculate_seo_score( $post_id );

	$score     = (int) get_post_meta( $post_id, 'recrm_seo_score', true );
	$max_score = (int) get_post_meta( $post_id, 'recrm_seo_score_max', true );
	$checks    = get_post_meta( $post_id, 'recrm_seo_score_checks', true );

	if ( $max_score <= 0 ) {
		echo '—';
		return;
	}

	$color   = recrm_seo_score_get_color( $score, $max_score );
	$tooltip = array();

	if ( is_array( $checks ) ) {
		foreach ( $checks as $check ) {
			$line = $check['label'] . ': ' . $check['points'] . '/' . $check['max'];

			if ( ! empty( $check['details'] ) ) {
				$line .= ' — ' . $check['details'];
			}

			$tooltip[] = $line;
		}
	}

	echo '<div title="' . esc_attr( implode( "\n", $tooltip ) ) . '" style="font-weight:700;color:' . esc_attr( $color ) . ';">';
	echo esc_html( $score . ' / ' . $max_score );
	echo '</div>';
}

function recrm_make_seo_score_column_sortable( $columns ) {
	$columns['recrm_seo_score'] = 'recrm_seo_score';
	return $columns;
}

function recrm_handle_seo_score_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'recrm_seo_score' !== $query->get( 'orderby' ) ) {
		return;
	}

	$query->set( 'meta_key', 'recrm_seo_score' );
	$query->set( 'orderby', 'meta_value_num' );
}

function recrm_generate_alt_text( $focus_keyword, $index = 0 ) {
	$variants = array(
		$focus_keyword,
		$focus_keyword . ' квартира',
		$focus_keyword . ' фото',
		$focus_keyword . ' житло',
	);

	return isset( $variants[ $index ] ) ? $variants[ $index ] : $focus_keyword;
}

function recrm_seo_score_get_featured_image_data( $post_id, $focus_keyword = '' ) {
	$data = array(
		'total'      => 0,
		'with_alt'   => 0,
		'alt_values' => array(),
	);

	$thumbnail_id = get_post_thumbnail_id( $post_id );

	if ( ! $thumbnail_id ) {
		return $data;
	}

	$data['total'] = 1;

	$alt = trim( (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) );

	if ( '' === $alt && '' !== $focus_keyword ) {
		$alt = $focus_keyword;
	}

	if ( '' !== $alt ) {
		$data['with_alt']++;
		$data['alt_values'][] = $alt;
	}

	return $data;
}