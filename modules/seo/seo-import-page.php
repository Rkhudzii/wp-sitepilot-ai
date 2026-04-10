<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'recrm_register_seo_import_menu', 30 );
add_action( 'admin_init', 'recrm_handle_seo_settings_save' );
add_action( 'add_meta_boxes', 'recrm_seo_register_meta_box' );
add_action( 'save_post', 'recrm_seo_save_post_meta', 20, 2 );
add_filter( 'pre_get_document_title', 'recrm_seo_filter_document_title', 20 );
add_action( 'wp_head', 'recrm_seo_output_meta_description', 5 );

function recrm_register_seo_import_menu() {
	add_submenu_page(
		'recrm-main',
		'SEO RE CRM',
		'SEO',
		'manage_options',
		'recrm-seo',
		'recrm_render_seo_import_page'
	);
}

function recrm_get_seo_settings() {
	$defaults = array(
		'brand_name'           => 'Sequoia Agency',
		'default_city'         => 'Тернопіль',
		'title_template'       => '{title} — {brand}',
		'description_template' => '{title}. Актуальні пропозиції нерухомості в {city}. Консультація, підбір та супровід від {brand}.',
		'separator'            => '—',
		'auto_keyword'         => '1',
		'auto_slug'            => '1',
		'analysis_enabled'     => '1',
	);

	$settings = get_option( 'recrm_seo_settings', array() );

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args( $settings, $defaults );
}

function recrm_handle_seo_settings_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_POST['recrm_save_seo_settings'] ) ) {
		return;
	}

	check_admin_referer( 'recrm_save_seo_settings_action', 'recrm_save_seo_settings_nonce' );

	$settings = array(
		'brand_name'           => isset( $_POST['brand_name'] ) ? sanitize_text_field( wp_unslash( $_POST['brand_name'] ) ) : '',
		'default_city'         => isset( $_POST['default_city'] ) ? sanitize_text_field( wp_unslash( $_POST['default_city'] ) ) : '',
		'title_template'       => isset( $_POST['title_template'] ) ? sanitize_text_field( wp_unslash( $_POST['title_template'] ) ) : '{title} — {brand}',
		'description_template' => isset( $_POST['description_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description_template'] ) ) : '',
		'separator'            => isset( $_POST['separator'] ) ? sanitize_text_field( wp_unslash( $_POST['separator'] ) ) : '—',
		'auto_keyword'         => ! empty( $_POST['auto_keyword'] ) ? '1' : '0',
		'auto_slug'            => ! empty( $_POST['auto_slug'] ) ? '1' : '0',
		'analysis_enabled'     => ! empty( $_POST['analysis_enabled'] ) ? '1' : '0',
	);

	update_option( 'recrm_seo_settings', $settings );

	add_settings_error( 'recrm_seo_settings', 'recrm_seo_settings_saved', 'SEO налаштування збережено.', 'updated' );
}

function recrm_seo_normalize_whitespace( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	$value = preg_replace( '/\s+/u', ' ', $value );
	return trim( (string) $value );
}

function recrm_seo_generate_keyword( $title ) {
	$settings = recrm_get_seo_settings();
	$city     = ! empty( $settings['default_city'] ) ? $settings['default_city'] : 'Тернопіль';

	$title_normalized = mb_strtolower( recrm_seo_normalize_whitespace( $title ), 'UTF-8' );

	if ( preg_match( '/новобуд/u', $title_normalized ) ) {
		return 'новобудови ' . $city;
	}

	if ( preg_match( '/забудов/u', $title_normalized ) ) {
		return 'квартира від забудовника ' . $city;
	}

	if (
		preg_match( '/ринок/u', $title_normalized ) ||
		preg_match( '/гід/u', $title_normalized ) ||
		preg_match( '/огляд/u', $title_normalized )
	) {
		return 'нерухомість ' . $city;
	}

	$type = 'нерухомість';

	if ( preg_match( '/квартир/u', $title_normalized ) ) {
		$type = 'квартиру';
	} elseif ( preg_match( '/будин|котедж/u', $title_normalized ) ) {
		$type = 'будинок';
	} elseif ( preg_match( '/земл|ділян/u', $title_normalized ) ) {
		$type = 'земельну ділянку';
	} elseif ( preg_match( '/комерц/u', $title_normalized ) ) {
		$type = 'комерційну нерухомість';
	}

	$deal = 'купити';
	if ( preg_match( '/оренд/u', $title_normalized ) ) {
		$deal = 'оренда';
	}

	$rooms = '';
	if ( preg_match( '/\b([1-4])[\s-]*кімнат/u', $title_normalized, $matches ) ) {
		$rooms = $matches[1] . '-кімнатну ';
	}

	$districts = array(
		'дружба'     => 'Дружба',
		'східний'    => 'Східний',
		'новий світ' => 'Новий світ',
		'центр'      => 'Центр',
		'аляска'     => 'Аляска',
		'бам'        => 'БАМ',
	);

	$district = '';
	foreach ( $districts as $needle => $label ) {
		if ( false !== mb_strpos( $title_normalized, $needle, 0, 'UTF-8' ) ) {
			$district = ' ' . $label;
			break;
		}
	}

	if ( 'оренда' === $deal ) {
		return trim( 'оренда ' . $rooms . $type . ' ' . $city . $district );
	}

	return trim( 'купити ' . $rooms . $type . ' ' . $city . $district );
}

function recrm_seo_transliterate( $value ) {
	$map = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g', 'д' => 'd', 'е' => 'e', 'є' => 'ye', 'ж' => 'zh',
		'з' => 'z', 'и' => 'y', 'і' => 'i', 'ї' => 'yi', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
		'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ю' => 'yu', 'я' => 'ya', 'ь' => '', '’' => '', "'" => '', 'ъ' => '',
		'ы' => 'y', 'э' => 'e'
	);

	$value = mb_strtolower( recrm_seo_normalize_whitespace( $value ), 'UTF-8' );
	$value = strtr( $value, $map );
	$value = preg_replace( '/[^a-z0-9\s-]/', '', $value );
	$value = preg_replace( '/[\s-]+/', '-', (string) $value );
	return trim( (string) $value, '-' );
}

function recrm_seo_apply_template( $template, $data ) {
	$replacements = array(
		'{title}'   => isset( $data['title'] ) ? $data['title'] : '',
		'{brand}'   => isset( $data['brand'] ) ? $data['brand'] : '',
		'{city}'    => isset( $data['city'] ) ? $data['city'] : '',
		'{keyword}' => isset( $data['keyword'] ) ? $data['keyword'] : '',
	);

	return trim( strtr( (string) $template, $replacements ) );
}

function recrm_seo_supported_post_types() {
	$types = array( 'post', 'page', 'property' );
	return array_values( array_unique( array_filter( $types ) ) );
}

function recrm_seo_get_post_meta_value( $post_id, $key, $fallback = '' ) {
	$value = get_post_meta( $post_id, $key, true );
	return '' !== $value ? $value : $fallback;
}

function recrm_seo_find_keyword_duplicates( $keyword, $exclude_post_id = 0 ) {
	$keyword = recrm_seo_normalize_whitespace( $keyword );

	if ( '' === $keyword ) {
		return array();
	}

	$args = array(
		'post_type'      => recrm_seo_supported_post_types(),
		'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'posts_per_page' => 10,
		'post__not_in'   => $exclude_post_id ? array( (int) $exclude_post_id ) : array(),
		'meta_query'     => array(
			array(
				'key'     => '_recrm_seo_keyword',
				'value'   => $keyword,
				'compare' => '=',
			),
		),
	);

	$query = new WP_Query( $args );

	if ( empty( $query->posts ) ) {
		return array();
	}

	$results = array();

	foreach ( $query->posts as $post ) {
		$results[] = array(
			'id'     => $post->ID,
			'title'  => get_the_title( $post ),
			'edit'   => get_edit_post_link( $post->ID, '' ),
			'type'   => $post->post_type,
			'status' => $post->post_status,
		);
	}

	return $results;
}

function recrm_seo_keyword_matches_title_context( $title, $keyword ) {
	$title   = mb_strtolower( recrm_seo_normalize_whitespace( $title ), 'UTF-8' );
	$keyword = mb_strtolower( recrm_seo_normalize_whitespace( $keyword ), 'UTF-8' );

	if ( '' === $title || '' === $keyword ) {
		return false;
	}

	$title_has_newbuild   = false !== mb_strpos( $title, 'новобуд', 0, 'UTF-8' );
	$title_has_developer  = false !== mb_strpos( $title, 'забудов', 0, 'UTF-8' );
	$title_has_house      = false !== mb_strpos( $title, 'будин', 0, 'UTF-8' );
	$title_has_land       = false !== mb_strpos( $title, 'земл', 0, 'UTF-8' );
	$title_has_commercial = false !== mb_strpos( $title, 'комерц', 0, 'UTF-8' );

	$keyword_has_newbuild   = false !== mb_strpos( $keyword, 'новобуд', 0, 'UTF-8' );
	$keyword_has_developer  = false !== mb_strpos( $keyword, 'забудов', 0, 'UTF-8' );
	$keyword_has_house      = false !== mb_strpos( $keyword, 'будин', 0, 'UTF-8' );
	$keyword_has_land       = false !== mb_strpos( $keyword, 'земл', 0, 'UTF-8' );
	$keyword_has_commercial = false !== mb_strpos( $keyword, 'комерц', 0, 'UTF-8' );

	if ( $title_has_newbuild && ! $keyword_has_newbuild ) {
		return false;
	}

	if ( $title_has_developer && ! $keyword_has_developer && ! $keyword_has_newbuild ) {
		return false;
	}

	if ( $title_has_house && ! $keyword_has_house ) {
		return false;
	}

	if ( $title_has_land && ! $keyword_has_land ) {
		return false;
	}

	if ( $title_has_commercial && ! $keyword_has_commercial ) {
		return false;
	}

	return true;
}

function recrm_seo_get_keyword_suggestion_from_title( $title ) {
	$title_normalized = mb_strtolower( recrm_seo_normalize_whitespace( $title ), 'UTF-8' );
	$settings         = recrm_get_seo_settings();
	$city             = ! empty( $settings['default_city'] ) ? $settings['default_city'] : 'Тернопіль';

	if ( false !== mb_strpos( $title_normalized, 'новобуд', 0, 'UTF-8' ) ) {
		return 'новобудови ' . $city;
	}

	if ( false !== mb_strpos( $title_normalized, 'забудов', 0, 'UTF-8' ) ) {
		return 'квартира від забудовника ' . $city;
	}

	if (
		false !== mb_strpos( $title_normalized, 'ринок', 0, 'UTF-8' ) ||
		false !== mb_strpos( $title_normalized, 'гід', 0, 'UTF-8' ) ||
		false !== mb_strpos( $title_normalized, 'огляд', 0, 'UTF-8' ) ||
		false !== mb_strpos( $title_normalized, 'нерухомість', 0, 'UTF-8' )
	) {
		return 'нерухомість ' . $city;
	}

	return recrm_seo_generate_keyword( $title );
}

function recrm_render_seo_import_page() {
	$settings = recrm_get_seo_settings();

	$demo_title      = 'Купити квартиру Тернопіль: поради для покупця';
	$demo_keyword    = recrm_seo_generate_keyword( $demo_title );
	$demo_slug       = recrm_seo_transliterate( $demo_keyword );
	$demo_meta_title = recrm_seo_apply_template(
		$settings['title_template'],
		array(
			'title'   => $demo_title,
			'brand'   => $settings['brand_name'],
			'city'    => $settings['default_city'],
			'keyword' => $demo_keyword,
		)
	);
	$demo_meta_desc  = recrm_seo_apply_template(
		$settings['description_template'],
		array(
			'title'   => $demo_title,
			'brand'   => $settings['brand_name'],
			'city'    => $settings['default_city'],
			'keyword' => $demo_keyword,
		)
	);

	settings_errors( 'recrm_seo_settings' );
	?>
	<div class="wrap recrm-seo-page">
        <?php
$seo_files   = function_exists( 'recrm_check_seo_files' ) ? recrm_check_seo_files() : array();
$trust_pages = function_exists( 'recrm_check_trust_pages' ) ? recrm_check_trust_pages() : array();
?>

<div class="recrm-seo-card">
	<h2>SEO файли</h2>
	<div class="recrm-seo-checks">
		<div class="recrm-seo-check">
			<span>robots.txt</span>
			<span class="recrm-seo-status <?php echo ! empty( $seo_files['robots'] ) ? 'ok' : 'bad'; ?>">
				<?php echo ! empty( $seo_files['robots'] ) ? 'OK' : 'Відсутній'; ?>
			</span>
		</div>

		<div class="recrm-seo-check">
			<span>sitemap.xml</span>
			<span class="recrm-seo-status <?php echo ! empty( $seo_files['sitemap'] ) ? 'ok' : 'bad'; ?>">
				<?php echo ! empty( $seo_files['sitemap'] ) ? 'OK' : 'Відсутній'; ?>
			</span>
		</div>

		<div class="recrm-seo-check">
			<span>favicon.ico</span>
			<span class="recrm-seo-status <?php echo ! empty( $seo_files['favicon'] ) ? 'ok' : 'bad'; ?>">
				<?php echo ! empty( $seo_files['favicon'] ) ? 'OK' : 'Відсутній'; ?>
			</span>
		</div>

		<div class="recrm-seo-check">
			<span>Image Sitemap</span>
			<span class="recrm-seo-status <?php echo ! empty( $seo_files['image_sitemap'] ) ? 'ok' : 'warn'; ?>">
				<?php echo ! empty( $seo_files['image_sitemap'] ) ? 'OK' : 'Рекомендовано'; ?>
			</span>
		</div>

		<div class="recrm-seo-check">
			<span>News Sitemap</span>
			<span class="recrm-seo-status <?php echo ! empty( $seo_files['news_sitemap'] ) ? 'ok' : 'warn'; ?>">
				<?php echo ! empty( $seo_files['news_sitemap'] ) ? 'OK' : 'Необов’язково'; ?>
			</span>
		</div>
	</div>
</div>

<div class="recrm-seo-card">
	<h2>Сторінки довіри</h2>
	<div class="recrm-seo-checks">
		<div class="recrm-seo-check">
			<span>Про нас</span>
			<span class="recrm-seo-status <?php echo ! empty( $trust_pages['about'] ) ? 'ok' : 'bad'; ?>">
				<?php echo ! empty( $trust_pages['about'] ) ? 'OK' : 'Відсутня'; ?>
			</span>
		</div>

		<div class="recrm-seo-check">
			<span>Контакти</span>
			<span class="recrm-seo-status <?php echo ! empty( $trust_pages['contacts'] ) ? 'ok' : 'bad'; ?>">
				<?php echo ! empty( $trust_pages['contacts'] ) ? 'OK' : 'Відсутня'; ?>
			</span>
		</div>

		<div class="recrm-seo-check">
			<span>Privacy Policy</span>
			<span class="recrm-seo-status <?php echo ! empty( $trust_pages['privacy'] ) ? 'ok' : 'bad'; ?>">
				<?php echo ! empty( $trust_pages['privacy'] ) ? 'OK' : 'Відсутня'; ?>
			</span>
		</div>

		<div class="recrm-seo-check">
			<span>Terms</span>
			<span class="recrm-seo-status <?php echo ! empty( $trust_pages['terms'] ) ? 'ok' : 'bad'; ?>">
				<?php echo ! empty( $trust_pages['terms'] ) ? 'OK' : 'Відсутня'; ?>
			</span>
		</div>
	</div>
</div>
		<style>
			.recrm-seo-page { margin-top: 20px; }
			.recrm-seo-page * { box-sizing: border-box; }
			.recrm-seo-hero {
				background: linear-gradient(135deg,#0d1838 0%,#1f2d5c 100%);
				color: #fff;
				border-radius: 18px;
				padding: 28px 30px;
				margin-bottom: 24px;
			}
			.recrm-seo-hero h1 { margin: 0 0 10px; color: #fff; }
			.recrm-seo-hero p { margin: 0; color: rgba(255,255,255,.88); max-width: 920px; font-size: 15px; line-height: 1.7; }
			.recrm-seo-grid {
				display: grid;
				grid-template-columns: minmax(320px, 1.15fr) minmax(320px, .85fr);
				gap: 24px;
				align-items: start;
			}
			.recrm-seo-card {
				background: #fff;
				border: 1px solid #e7eaf0;
				border-radius: 18px;
				padding: 24px;
				box-shadow: 0 8px 24px rgba(13,24,56,.05);
				margin-bottom: 24px;
			}
			.recrm-seo-card h2 { margin: 0 0 16px; font-size: 22px; color: #0d1838; }
			.recrm-seo-field { margin-bottom: 16px; }
			.recrm-seo-field label { display: block; font-weight: 600; margin-bottom: 6px; color: #0f172a; }
			.recrm-seo-field input[type="text"],
			.recrm-seo-field textarea {
				width: 100%;
				border: 1px solid #cbd5e1;
				border-radius: 10px;
				padding: 11px 12px;
			}
			.recrm-seo-field textarea { min-height: 110px; resize: vertical; }
			.recrm-seo-description { margin: 6px 0 0; color: #64748b; }
			.recrm-seo-inline { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
			.recrm-seo-preview {
				border: 1px solid #e2e8f0;
				border-radius: 14px;
				padding: 16px;
				background: #fff;
			}
			.recrm-seo-preview-url { color: #15803d; font-size: 14px; margin-bottom: 6px; word-break: break-all; }
			.recrm-seo-preview-title { color: #1a0dab; font-size: 22px; line-height: 1.3; margin-bottom: 6px; }
			.recrm-seo-preview-desc { color: #4b5563; line-height: 1.55; }
			.recrm-seo-badges { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
			.recrm-seo-badge {
				display: inline-flex;
				align-items: center;
				padding: 8px 12px;
				border-radius: 999px;
				background: #f8fafc;
				border: 1px solid #e2e8f0;
				font-weight: 600;
			}
			.recrm-seo-checks { display: grid; gap: 10px; margin-top: 14px; }
			.recrm-seo-check {
				padding: 12px 14px;
				border-radius: 12px;
				border: 1px solid #e2e8f0;
				background: #f8fafc;
				display: flex;
				justify-content: space-between;
				gap: 12px;
				align-items: center;
			}
			.recrm-seo-status.ok { color: #15803d; font-weight: 700; }
			.recrm-seo-status.warn { color: #b45309; font-weight: 700; }
			.recrm-seo-status.bad { color: #b91c1c; font-weight: 700; }
			.recrm-seo-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
			@media (max-width: 1100px) {
				.recrm-seo-grid,
				.recrm-seo-inline { grid-template-columns: 1fr; }
			}
		</style>

		<div class="recrm-seo-hero">
			<h1>SEO RE CRM</h1>
			<p>Базовий SEO блок для швидкої роботи: шаблони meta title та description, авто-генерація ключа з назви, slug-превʼю та живий аналіз якості. Тепер SEO-поля також доступні прямо в редакторі записів, сторінок і обʼєктів нерухомості.</p>
		</div>

		<div class="recrm-seo-grid">
			<div>
				<div class="recrm-seo-card">
					<h2>SEO налаштування</h2>
					<div class="recrm-seo-check" style="margin-bottom:16px;">
						<span>Індексація сайту</span>
						<span class="recrm-seo-status <?php echo '0' !== (string) get_option( 'blog_public', '1' ) ? 'ok' : 'bad'; ?>"><?php echo '0' !== (string) get_option( 'blog_public', '1' ) ? 'Увімкнена' : 'Вимкнена'; ?></span>
					</div>
					<form method="post">
						<?php wp_nonce_field( 'recrm_save_seo_settings_action', 'recrm_save_seo_settings_nonce' ); ?>

						<div class="recrm-seo-inline">
							<div class="recrm-seo-field">
								<label for="brand_name">Назва бренду</label>
								<input type="text" id="brand_name" name="brand_name" value="<?php echo esc_attr( $settings['brand_name'] ); ?>">
							</div>
							<div class="recrm-seo-field">
								<label for="default_city">Місто за замовчуванням</label>
								<input type="text" id="default_city" name="default_city" value="<?php echo esc_attr( $settings['default_city'] ); ?>">
							</div>
						</div>

						<div class="recrm-seo-inline">
							<div class="recrm-seo-field">
								<label for="title_template">Шаблон meta title</label>
								<input type="text" id="title_template" name="title_template" value="<?php echo esc_attr( $settings['title_template'] ); ?>">
								<p class="recrm-seo-description">Доступні змінні: <code>{title}</code>, <code>{brand}</code>, <code>{city}</code>, <code>{keyword}</code>.</p>
							</div>
							<div class="recrm-seo-field">
								<label for="separator">Розділювач</label>
								<input type="text" id="separator" name="separator" value="<?php echo esc_attr( $settings['separator'] ); ?>">
							</div>
						</div>

						<div class="recrm-seo-field">
							<label for="description_template">Шаблон meta description</label>
							<textarea id="description_template" name="description_template"><?php echo esc_textarea( $settings['description_template'] ); ?></textarea>
						</div>

						<div class="recrm-seo-inline">
							<div class="recrm-seo-field">
								<label><input type="checkbox" name="auto_keyword" value="1" <?php checked( $settings['auto_keyword'], '1' ); ?>> Авто-генерація ключа з назви</label>
							</div>
							<div class="recrm-seo-field">
								<label><input type="checkbox" name="auto_slug" value="1" <?php checked( $settings['auto_slug'], '1' ); ?>> Авто-генерація slug з ключа</label>
							</div>
						</div>

						<div class="recrm-seo-field">
							<label><input type="checkbox" name="analysis_enabled" value="1" <?php checked( $settings['analysis_enabled'], '1' ); ?>> Увімкнути SEO аналізатор</label>
						</div>

						<p>
							<button type="submit" name="recrm_save_seo_settings" value="1" class="button button-primary">Зберегти SEO налаштування</button>
						</p>
					</form>
				</div>

				<div class="recrm-seo-card">
					<h2>Швидкий генератор</h2>
					<div class="recrm-seo-field">
						<label for="recrm-demo-title">Тестовий заголовок</label>
						<input type="text" id="recrm-demo-title" value="<?php echo esc_attr( $demo_title ); ?>">
						<p class="recrm-seo-description">Вводиш назву — нижче одразу бачиш ключ, slug, meta title і description.</p>
					</div>

					<div class="recrm-seo-inline">
						<div class="recrm-seo-field">
							<label for="recrm-demo-keyword">SEO ключ</label>
							<input type="text" id="recrm-demo-keyword" value="<?php echo esc_attr( $demo_keyword ); ?>">
						</div>
						<div class="recrm-seo-field">
							<label for="recrm-demo-slug">Slug</label>
							<input type="text" id="recrm-demo-slug" value="<?php echo esc_attr( $demo_slug ); ?>">
						</div>
					</div>

					<div class="recrm-seo-field">
						<label for="recrm-demo-meta-title">Meta title</label>
						<input type="text" id="recrm-demo-meta-title" value="<?php echo esc_attr( $demo_meta_title ); ?>">
					</div>

					<div class="recrm-seo-field">
						<label for="recrm-demo-meta-description">Meta description</label>
						<textarea id="recrm-demo-meta-description"><?php echo esc_textarea( $demo_meta_desc ); ?></textarea>
					</div>

					<div class="recrm-seo-actions">
						<button type="button" class="button button-secondary" id="recrm-generate-keyword">Взяти ключ з назви</button>
						<button type="button" class="button button-secondary" id="recrm-generate-slug">Згенерувати slug</button>
						<button type="button" class="button button-secondary" id="recrm-generate-meta">Оновити meta</button>
					</div>
				</div>
			</div>

			<div>
				<div class="recrm-seo-card">
					<h2>Пошуковий превʼю-блок</h2>
					<div class="recrm-seo-preview">
						<div class="recrm-seo-preview-url" id="recrm-preview-url">example.com/<?php echo esc_html( $demo_slug ); ?>/</div>
						<div class="recrm-seo-preview-title" id="recrm-preview-title"><?php echo esc_html( $demo_meta_title ); ?></div>
						<div class="recrm-seo-preview-desc" id="recrm-preview-desc"><?php echo esc_html( $demo_meta_desc ); ?></div>
					</div>

					<div class="recrm-seo-badges">
						<span class="recrm-seo-badge">Title: <strong id="recrm-title-length" style="margin-left:6px;"><?php echo (int) mb_strlen( $demo_meta_title, 'UTF-8' ); ?></strong></span>
						<span class="recrm-seo-badge">Description: <strong id="recrm-desc-length" style="margin-left:6px;"><?php echo (int) mb_strlen( $demo_meta_desc, 'UTF-8' ); ?></strong></span>
						<span class="recrm-seo-badge">Ключ: <strong id="recrm-keyword-words" style="margin-left:6px;"><?php echo count( preg_split( '/\s+/u', trim( $demo_keyword ) ) ); ?></strong></span>
					</div>
				</div>

				<div class="recrm-seo-card">
					<h2>SEO аналіз</h2>
					<p class="recrm-seo-description">Це перший базовий аналізатор. Далі ми можемо додати повноцінний аналіз контенту запису, першого абзацу, H2, alt і щільності ключа.</p>
					<div class="recrm-seo-checks" id="recrm-seo-checks"></div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

function recrm_seo_register_meta_box() {
	foreach ( recrm_seo_supported_post_types() as $post_type ) {
		add_meta_box(
			'recrm-seo-meta-box',
			'SEO RE CRM',
			'recrm_seo_render_meta_box',
			$post_type,
			'normal',
			'high'
		);
	}
}

function recrm_seo_render_meta_box( $post ) {
	$settings         = recrm_get_seo_settings();
	$post_title       = get_the_title( $post );
	$saved_keyword    = recrm_seo_get_post_meta_value( $post->ID, '_recrm_seo_keyword', '' );
	$saved_slug       = recrm_seo_get_post_meta_value( $post->ID, '_recrm_seo_slug', '' );
	$saved_meta_title = recrm_seo_get_post_meta_value( $post->ID, '_recrm_seo_meta_title', '' );
	$saved_meta_desc  = recrm_seo_get_post_meta_value( $post->ID, '_recrm_seo_meta_description', '' );

	$keyword    = '' !== $saved_keyword ? $saved_keyword : recrm_seo_generate_keyword( $post_title );
	$slug       = '' !== $saved_slug ? $saved_slug : ( $post->post_name ? $post->post_name : recrm_seo_transliterate( $keyword ) );
	$meta_title = '' !== $saved_meta_title ? $saved_meta_title : recrm_seo_apply_template(
		$settings['title_template'],
		array(
			'title'   => $post_title,
			'brand'   => $settings['brand_name'],
			'city'    => $settings['default_city'],
			'keyword' => $keyword,
		)
	);
	$meta_desc  = '' !== $saved_meta_desc ? $saved_meta_desc : recrm_seo_apply_template(
		$settings['description_template'],
		array(
			'title'   => $post_title,
			'brand'   => $settings['brand_name'],
			'city'    => $settings['default_city'],
			'keyword' => $keyword,
		)
	);

	$keyword_duplicates = recrm_seo_find_keyword_duplicates( $keyword, $post->ID );
	$keyword_relevance  = recrm_seo_keyword_matches_title_context( $post_title, $keyword );
	$keyword_suggestion = recrm_seo_get_keyword_suggestion_from_title( $post_title );

    if ( function_exists( 'recrm_calculate_seo_score' ) ) {
        recrm_calculate_seo_score( $post->ID );
    }

    $seo_score        = (int) get_post_meta( $post->ID, 'recrm_seo_score', true );
    $seo_score_max    = (int) get_post_meta( $post->ID, 'recrm_seo_score_max', true );
    $seo_score_checks = get_post_meta( $post->ID, 'recrm_seo_score_checks', true );

    $seo_ratio = $seo_score_max > 0 ? ( $seo_score / $seo_score_max ) : 0;

    $seo_score_label = 'Слабо';
    $seo_score_color = '#b91c1c';

    if ( $seo_ratio >= 0.75 ) {
        $seo_score_label = 'Сильно';
        $seo_score_color = '#15803d';
    } elseif ( $seo_ratio >= 0.45 ) {
        $seo_score_label = 'Нормально';
        $seo_score_color = '#b45309';
    }

    if ( ! is_array( $seo_score_checks ) ) {
        $seo_score_checks = array();
    }

	wp_nonce_field( 'recrm_seo_save_post_meta_action', 'recrm_seo_post_meta_nonce' );
	?>
	<style>
		.recrm-seo-post-box * { box-sizing:border-box; }
		.recrm-seo-post-box { padding-top: 6px; }
		.recrm-seo-post-grid { display:grid; grid-template-columns: minmax(0, 1fr) minmax(320px, .92fr); gap:20px; }
		.recrm-seo-post-card { background:#fff; border:1px solid #e7eaf0; border-radius:16px; padding:18px; }
		.recrm-seo-post-card h3 { margin:0 0 14px; font-size:18px; color:#0d1838; }
		.recrm-seo-post-field { margin-bottom:14px; }
		.recrm-seo-post-field label { display:block; margin-bottom:6px; font-weight:600; color:#0f172a; }
		.recrm-seo-post-field input[type="text"],
		.recrm-seo-post-field textarea { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; }
		.recrm-seo-post-field textarea { min-height:96px; resize:vertical; }
		.recrm-seo-post-inline { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
		.recrm-seo-post-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
		.recrm-seo-post-note { margin:6px 0 0; color:#64748b; }
		.recrm-seo-post-preview { border:1px solid #e2e8f0; border-radius:14px; padding:16px; background:#fff; }
		.recrm-seo-post-preview-url { color:#15803d; font-size:14px; margin-bottom:6px; word-break:break-all; }
		.recrm-seo-post-preview-title { color:#1a0dab; font-size:22px; line-height:1.3; margin-bottom:6px; }
		.recrm-seo-post-preview-desc { color:#4b5563; line-height:1.55; }
		.recrm-seo-post-badges { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
		.recrm-seo-post-badge { display:inline-flex; align-items:center; padding:7px 11px; border-radius:999px; background:#f8fafc; border:1px solid #e2e8f0; font-weight:600; }
		.recrm-seo-post-checks { display:grid; gap:10px; margin-top:14px; }
		.recrm-seo-post-check { padding:12px 14px; border-radius:12px; border:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:space-between; gap:12px; align-items:center; }
		.recrm-seo-post-status.ok { color:#15803d; font-weight:700; }
		.recrm-seo-post-status.warn { color:#b45309; font-weight:700; }
		.recrm-seo-post-status.bad { color:#b91c1c; font-weight:700; }
		@media (max-width: 1000px) {
			.recrm-seo-post-grid,
			.recrm-seo-post-inline { grid-template-columns:1fr; }
		}
	</style>
	<div class="recrm-seo-post-box" data-recrm-seo-post-box="1">
		<div class="recrm-seo-post-grid">
			<div class="recrm-seo-post-card">
				<h3>SEO поля запису</h3>
				<div class="recrm-seo-post-field">
					<label for="recrm-seo-source-title-<?php echo (int) $post->ID; ?>">Заголовок запису</label>
					<input type="text" id="recrm-seo-source-title-<?php echo (int) $post->ID; ?>" value="<?php echo esc_attr( $post_title ); ?>" class="recrm-seo-source-title" readonly>
					<p class="recrm-seo-post-note">Поле бере поточний заголовок запису. Після зміни заголовка просто натисни кнопку оновлення нижче.</p>
				</div>
				<div class="recrm-seo-post-inline">
					<div class="recrm-seo-post-field">
						<label for="recrm_seo_keyword">SEO ключ</label>
						<input type="text" id="recrm_seo_keyword" name="recrm_seo_keyword" value="<?php echo esc_attr( $keyword ); ?>" class="recrm-seo-keyword">
					</div>
					<div class="recrm-seo-post-field">
						<label for="recrm_seo_slug">Slug</label>
						<input type="text" id="recrm_seo_slug" name="recrm_seo_slug" value="<?php echo esc_attr( $slug ); ?>" class="recrm-seo-slug">
					</div>
				</div>
				<div class="recrm-seo-post-field">
					<label for="recrm_seo_meta_title">Meta title</label>
					<input type="text" id="recrm_seo_meta_title" name="recrm_seo_meta_title" value="<?php echo esc_attr( $meta_title ); ?>" class="recrm-seo-meta-title">
				</div>
				<div class="recrm-seo-post-field">
					<label for="recrm_seo_meta_description">Meta description</label>
					<textarea id="recrm_seo_meta_description" name="recrm_seo_meta_description" class="recrm-seo-meta-description"><?php echo esc_textarea( $meta_desc ); ?></textarea>
				</div>
				<div class="recrm-seo-post-actions">
					<button type="button" class="button button-secondary recrm-seo-btn-keyword">Взяти ключ з назви</button>
					<button type="button" class="button button-secondary recrm-seo-btn-slug">Згенерувати slug</button>
					<button type="button" class="button button-secondary recrm-seo-btn-meta">Оновити meta</button>
					<button type="button" class="button button-primary recrm-seo-btn-all">Оптимізувати все</button>
				</div>
			</div>
			<div class="recrm-seo-post-card">
				<h3>SEO preview та аналіз</h3>
                <div style="margin-bottom:14px;padding:14px 16px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:13px;color:#64748b;margin-bottom:4px;">Збережений SEO Score</div>
                        <div style="font-size:26px;font-weight:800;color:<?php echo esc_attr( $seo_score_color ); ?>;">
                            <?php echo esc_html( $seo_score . '/' . $seo_score_max ); ?>
                        </div>
                    </div>
                    <div style="display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;font-weight:700;color:<?php echo esc_attr( $seo_score_color ); ?>;">
                        <?php echo esc_html( $seo_score_label ); ?>
                    </div>
                </div>
                <?php if ( ! empty( $seo_score_checks ) ) : ?>
                    <div style="margin-bottom:14px;">
                        <div style="font-size:13px;color:#64748b;margin-bottom:8px;">Що впливає на SEO Score</div>

                        <?php foreach ( $seo_score_checks as $check ) : ?>
                            <?php
                            $label   = isset( $check['label'] ) ? $check['label'] : '';
                            $points  = isset( $check['points'] ) ? (int) $check['points'] : 0;
                            $max     = isset( $check['max'] ) ? (int) $check['max'] : 0;
                            $passed  = ! empty( $check['passed'] );
                            $details = isset( $check['details'] ) ? $check['details'] : '';

                            $badge_text  = $passed ? 'OK' : 'НІ';
                            $badge_color = $passed ? '#15803d' : '#b91c1c';
                            $row_bg      = $passed ? '#f0fdf4' : '#fef2f2';
                            $row_border  = $passed ? '#bbf7d0' : '#fecaca';

                            if ( $points > 0 && ! $passed ) {
                                $badge_text  = 'ЧАСТКОВО';
                                $badge_color = '#b45309';
                                $row_bg      = '#fffbeb';
                                $row_border  = '#fde68a';
                            }
                            ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 12px;margin-bottom:8px;border:1px solid <?php echo esc_attr( $row_border ); ?>;background:<?php echo esc_attr( $row_bg ); ?>;border-radius:12px;">
                                <div>
                                    <div style="font-weight:600;color:#0f172a;"><?php echo esc_html( $label ); ?></div>
                                    <div style="font-size:12px;color:#64748b;">
                                        Бал: <?php echo esc_html( $points . '/' . $max ); ?>
                                        <?php if ( '' !== $details ) : ?>
                                            — <?php echo esc_html( $details ); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="font-weight:800;color:<?php echo esc_attr( $badge_color ); ?>;">
                                    <?php echo esc_html( $badge_text ); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>                                
				<div class="recrm-seo-post-preview">
					<div class="recrm-seo-post-preview-url"></div>
					<div class="recrm-seo-post-preview-title"></div>
					<div class="recrm-seo-post-preview-desc"></div>
				</div>
				<div class="recrm-seo-post-badges">
					<span class="recrm-seo-post-badge">Title: <strong class="recrm-seo-title-length" style="margin-left:6px;"></strong></span>
					<span class="recrm-seo-post-badge">Description: <strong class="recrm-seo-desc-length" style="margin-left:6px;"></strong></span>
					<span class="recrm-seo-post-badge">Ключ: <strong class="recrm-seo-keyword-words" style="margin-left:6px;"></strong></span>
				</div>

				<?php if ( ! empty( $keyword_duplicates ) ) : ?>
					<div style="margin-top:14px; padding:12px 14px; border:1px solid #fecaca; background:#fef2f2; border-radius:12px;">
						<strong style="color:#b91c1c;">Увага: цей SEO ключ уже використовується</strong>
						<ul style="margin:8px 0 0 18px;">
							<?php foreach ( $keyword_duplicates as $duplicate ) : ?>
								<li>
									<?php if ( ! empty( $duplicate['edit'] ) ) : ?>
										<a href="<?php echo esc_url( $duplicate['edit'] ); ?>"><?php echo esc_html( $duplicate['title'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $duplicate['title'] ); ?>
									<?php endif; ?>
									(<?php echo esc_html( $duplicate['type'] ); ?> / <?php echo esc_html( $duplicate['status'] ); ?>)
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! $keyword_relevance ) : ?>
					<div style="margin-top:14px; padding:12px 14px; border:1px solid #fde68a; background:#fffbeb; border-radius:12px;">
						<strong style="color:#b45309;">Ключ може не відповідати заголовку</strong>
						<div style="margin-top:6px;">
							Рекомендований варіант:
							<strong><?php echo esc_html( $keyword_suggestion ); ?></strong>
						</div>
					</div>
				<?php endif; ?>

		</div>
	</div>
	<?php
}

function recrm_seo_save_post_meta( $post_id, $post ) {
	if ( empty( $_POST['recrm_seo_post_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['recrm_seo_post_meta_nonce'] ) ), 'recrm_seo_save_post_meta_action' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! in_array( $post->post_type, recrm_seo_supported_post_types(), true ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$keyword    = isset( $_POST['recrm_seo_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_seo_keyword'] ) ) : '';
	$slug       = isset( $_POST['recrm_seo_slug'] ) ? sanitize_title( wp_unslash( $_POST['recrm_seo_slug'] ) ) : '';
	$meta_title = isset( $_POST['recrm_seo_meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['recrm_seo_meta_title'] ) ) : '';
	$meta_desc  = isset( $_POST['recrm_seo_meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['recrm_seo_meta_description'] ) ) : '';

	update_post_meta( $post_id, '_recrm_seo_keyword', $keyword );
	update_post_meta( $post_id, '_recrm_seo_slug', $slug );
	update_post_meta( $post_id, '_recrm_seo_meta_title', $meta_title );
	update_post_meta( $post_id, '_recrm_seo_meta_description', $meta_desc );

	if ( '' !== $slug && $slug !== $post->post_name ) {
		remove_action( 'save_post', 'recrm_seo_save_post_meta', 20 );
		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_name' => $slug,
			)
		);
		add_action( 'save_post', 'recrm_seo_save_post_meta', 20, 2 );
	}
}

function recrm_seo_get_current_object_id() {
	if ( is_admin() ) {
		return 0;
	}

	if ( is_front_page() ) {
		$front_page_id = (int) get_option( 'page_on_front' );
		if ( $front_page_id > 0 ) {
			return $front_page_id;
		}
	}

	if ( is_home() ) {
		$posts_page_id = (int) get_option( 'page_for_posts' );
		if ( $posts_page_id > 0 ) {
			return $posts_page_id;
		}
	}

	if ( is_singular() ) {
		return (int) get_queried_object_id();
	}

	return 0;
}

function recrm_seo_filter_document_title( $title ) {
	if ( is_admin() ) {
		return $title;
	}

	$object_id = recrm_seo_get_current_object_id();

	if ( $object_id > 0 ) {
		$custom_title = get_post_meta( $object_id, '_recrm_seo_meta_title', true );

		if ( ! empty( $custom_title ) ) {
			return $custom_title;
		}
	}

	if ( is_front_page() ) {
		$settings = recrm_get_seo_settings();
		$template = isset( $settings['title_template'] ) ? trim( (string) $settings['title_template'] ) : '';

		if ( '' !== $template ) {
			return recrm_seo_apply_template(
				$template,
				array(
					'title'   => get_bloginfo( 'name' ),
					'brand'   => isset( $settings['brand_name'] ) ? $settings['brand_name'] : '',
					'city'    => isset( $settings['default_city'] ) ? $settings['default_city'] : '',
					'keyword' => 'нерухомість ' . ( isset( $settings['default_city'] ) ? $settings['default_city'] : '' ),
				)
			);
		}
	}

	return $title;
}

function recrm_seo_output_meta_description() {
	if ( is_admin() ) {
		return;
	}

	$object_id = recrm_seo_get_current_object_id();

	if ( $object_id > 0 ) {
		$description = get_post_meta( $object_id, '_recrm_seo_meta_description', true );

		if ( ! empty( $description ) ) {
			echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
			return;
		}
	}

	if ( is_front_page() ) {
		$settings = recrm_get_seo_settings();
		$template = isset( $settings['description_template'] ) ? trim( (string) $settings['description_template'] ) : '';

		if ( '' !== $template ) {
			$description = recrm_seo_apply_template(
				$template,
				array(
					'title'   => get_bloginfo( 'name' ),
					'brand'   => isset( $settings['brand_name'] ) ? $settings['brand_name'] : '',
					'city'    => isset( $settings['default_city'] ) ? $settings['default_city'] : '',
					'keyword' => 'нерухомість ' . ( isset( $settings['default_city'] ) ? $settings['default_city'] : '' ),
				)
			);

			echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}
	}
}
