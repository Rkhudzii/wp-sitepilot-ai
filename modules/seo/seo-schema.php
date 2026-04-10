<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * --------------------------------------
 * SEO SCHEMA SETTINGS
 * --------------------------------------
 */

add_action( 'admin_init', 'recrm_register_schema_settings' );

function recrm_register_schema_settings() {
	register_setting(
		'recrm_settings_group',
		'recrm_schema_enabled',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'recrm_schema_sanitize_checkbox',
			'default'           => 1,
		)
	);

	register_setting(
		'recrm_settings_group',
		'recrm_schema_realestate_enabled',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'recrm_schema_sanitize_checkbox',
			'default'           => 1,
		)
	);

	register_setting(
		'recrm_settings_group',
		'recrm_schema_company_name',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'recrm_settings_group',
		'recrm_schema_company_phone',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	register_setting(
		'recrm_settings_group',
		'recrm_schema_company_email',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => '',
		)
	);

	register_setting(
		'recrm_settings_group',
		'recrm_schema_company_city',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Тернопіль',
		)
	);

	register_setting(
		'recrm_settings_group',
		'recrm_schema_company_country',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'UA',
		)
	);

	register_setting(
		'recrm_settings_group',
		'recrm_schema_company_description',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		)
	);

	register_setting(
		'recrm_settings_group',
		'recrm_schema_company_address',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
}

function recrm_schema_sanitize_checkbox( $value ) {
	return empty( $value ) ? 0 : 1;
}

/**
 * --------------------------------------
 * SEO SCHEMA OUTPUT
 * --------------------------------------
 */

add_action( 'wp_head', 'recrm_output_schema_markup', 20 );

function recrm_output_schema_markup() {
	if ( is_admin() ) {
		return;
	}

	if ( ! (int) get_option( 'recrm_schema_enabled', 1 ) ) {
		return;
	}

	$schemas = array();

	if ( (int) get_option( 'recrm_schema_realestate_enabled', 1 ) ) {
		$real_estate_schema = recrm_get_real_estate_agent_schema();
		if ( ! empty( $real_estate_schema ) ) {
			$schemas[] = $real_estate_schema;
		}
	}

    $local_business_schema = recrm_get_local_business_schema();

    if ( ! empty( $local_business_schema ) ) {
        $schemas[] = $local_business_schema;
    }

    $breadcrumb_schema = recrm_get_breadcrumb_schema();

    if ( ! empty( $breadcrumb_schema ) ) {
        $schemas[] = $breadcrumb_schema;
    }

    if ( is_singular( 'post' ) ) {
    $article = recrm_get_article_schema();
    if ( ! empty( $article ) ) {
        $schemas[] = $article;
    }
    }

	foreach ( $schemas as $schema ) {
		echo "\n" . '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		echo "\n" . '</script>' . "\n";
	}


}

/**
 * --------------------------------------
 * REAL ESTATE AGENT SCHEMA
 * --------------------------------------
 */

function recrm_get_real_estate_agent_schema() {
    $settings     = function_exists( 'recrm_get_settings' ) ? recrm_get_settings() : array();

    $company_name = trim( get_option( 'recrm_schema_company_name', '' ) );
    $phone        = trim( get_option( 'recrm_schema_company_phone', '' ) );
    $email        = trim( get_option( 'recrm_schema_company_email', '' ) );
    $city         = trim( get_option( 'recrm_schema_company_city', 'Тернопіль' ) );
    $country      = trim( get_option( 'recrm_schema_company_country', 'UA' ) );
    $description  = trim( get_option( 'recrm_schema_company_description', '' ) );
    $address      = trim( get_option( 'recrm_schema_company_address', '' ) );

    if ( '' === $company_name ) {
        $company_name = get_bloginfo( 'name' );
    }

    if ( '' === $phone && ! empty( $settings['phone'] ) ) {
        $phone = trim( $settings['phone'] );
    }

    if ( '' === $email && ! empty( $settings['email'] ) ) {
        $email = trim( $settings['email'] );
    }

    if ( '' === $address && ! empty( $settings['address'] ) ) {
        $address = trim( $settings['address'] );
    }

    if ( '' === $description ) {
        $description = get_bloginfo( 'description' );
    }

    $logo_url = recrm_get_schema_logo_url();

    $schema = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'RealEstateAgent',
        'name'        => $company_name,
        'url'         => home_url( '/' ),
        'description' => $description,
        'areaServed'  => array(
            '@type' => 'City',
            'name'  => $city,
        ),
        'address'     => array(
            '@type'           => 'PostalAddress',
            'streetAddress'   => $address,
            'addressLocality' => $city,
            'addressCountry'  => $country,
        ),
    );

    if ( '' !== $phone ) {
        $schema['telephone'] = $phone;
    }

    if ( '' !== $email ) {
        $schema['email'] = $email;
    }

    $image = '';

    if ( is_singular() ) {
        $thumb = get_the_post_thumbnail_url( get_the_ID(), 'full' );
        if ( $thumb ) {
            $image = $thumb;
        }
    }

    if ( empty( $image ) && ! empty( $logo_url ) ) {
        $image = $logo_url;
    }

    if ( ! empty( $logo_url ) ) {
        $schema['logo'] = $logo_url;
    }

    if ( ! empty( $image ) ) {
        $schema['image'] = $image;
    }

    return $schema;
}

function recrm_get_schema_logo_url() {
	$custom_logo_id = get_theme_mod( 'custom_logo' );

	if ( $custom_logo_id ) {
		$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
		if ( $logo_url ) {
			return $logo_url;
		}
	}

	$site_icon_id = get_option( 'site_icon' );

	if ( $site_icon_id ) {
		$icon_url = wp_get_attachment_image_url( $site_icon_id, 'full' );
		if ( $icon_url ) {
			return $icon_url;
		}
	}

	return '';
}

/**
 * --------------------------------------
 * SCHEMA PREVIEW HELPERS
 * --------------------------------------
 */

function recrm_get_schema_preview_data() {
	$data = array();

	if ( (int) get_option( 'recrm_schema_enabled', 1 ) ) {
		if ( (int) get_option( 'recrm_schema_realestate_enabled', 1 ) ) {
			$data['real_estate_agent'] = recrm_get_real_estate_agent_schema();
		}

		$data['local_business'] = recrm_get_local_business_schema();

		$breadcrumb = recrm_get_breadcrumb_schema();
		if ( ! empty( $breadcrumb ) ) {
			$data['breadcrumb'] = $breadcrumb;
		}

		if ( is_singular( 'post' ) ) {
			$article = recrm_get_article_schema();
			if ( ! empty( $article ) ) {
				$data['article'] = $article;
			}
		}
	}

	return $data;
}

function recrm_get_schema_preview_json() {
	$preview_data = recrm_get_schema_preview_data();

	if ( empty( $preview_data ) ) {
		return '';
	}

	return wp_json_encode(
		$preview_data,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
	);
}

/**
 * --------------------------------------
 * ARTICLE SCHEMA
 * --------------------------------------
 */

function recrm_get_article_schema() {

    if ( ! is_singular( 'post' ) ) {
        return array();
    }

    global $post;

    if ( ! $post instanceof WP_Post ) {
        return array();
    }

    $title = get_the_title( $post->ID );

    $description = get_the_excerpt( $post->ID );
    if ( empty( $description ) ) {
        $description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 25 );
    }

    $image = get_the_post_thumbnail_url( $post->ID, 'full' );

    if ( ! $image ) {
        $image = recrm_get_schema_logo_url();
    }

    $author_name = get_the_author_meta( 'display_name', $post->post_author );

    $date_published = get_the_date( 'c', $post->ID );
    $date_modified  = get_the_modified_date( 'c', $post->ID );

    $schema = array(
        '@context'         => 'https://schema.org',
        '@type'            => 'Article',
        'headline'         => wp_strip_all_tags( $title ),
        'description'      => wp_strip_all_tags( $description ),
        'datePublished'    => $date_published,
        'dateModified'     => $date_modified,
        'author'           => array(
            '@type' => 'Person',
            'name'  => $author_name,
        ),
        'mainEntityOfPage' => get_permalink( $post->ID ),
    );

    if ( $image ) {
        $schema['image'] = array( $image );
    }

    return $schema;
}

/**
 * --------------------------------------
 * BREADCRUMB SCHEMA
 * --------------------------------------
 */

function recrm_get_breadcrumb_schema() {
	if ( is_front_page() ) {
		return array();
	}

	$items = array();
	$position = 1;

	$items[] = array(
		'@type'    => 'ListItem',
		'position' => $position++,
		'name'     => 'Головна',
		'item'     => home_url( '/' ),
	);

	if ( is_home() ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => 'Блог',
			'item'     => get_permalink( get_option( 'page_for_posts' ) ),
		);
	} elseif ( is_singular( 'post' ) ) {
		$posts_page_id = (int) get_option( 'page_for_posts' );

		if ( $posts_page_id ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => get_the_title( $posts_page_id ),
				'item'     => get_permalink( $posts_page_id ),
			);
		} else {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => 'Блог',
				'item'     => home_url( '/' ),
			);
		}

		$categories = get_the_category();

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$primary_category = $categories[0];
			$ancestors = array_reverse( get_ancestors( $primary_category->term_id, 'category' ) );

			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_category( $ancestor_id );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$items[] = array(
						'@type'    => 'ListItem',
						'position' => $position++,
						'name'     => $ancestor->name,
						'item'     => get_category_link( $ancestor->term_id ),
					);
				}
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $primary_category->name,
				'item'     => get_category_link( $primary_category->term_id ),
			);
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => wp_strip_all_tags( get_the_title() ),
			'item'     => get_permalink(),
		);
	} elseif ( is_page() ) {
		global $post;

		if ( $post instanceof WP_Post ) {
			$ancestors = array_reverse( get_post_ancestors( $post->ID ) );

			foreach ( $ancestors as $ancestor_id ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => wp_strip_all_tags( get_the_title( $ancestor_id ) ),
					'item'     => get_permalink( $ancestor_id ),
				);
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => wp_strip_all_tags( get_the_title( $post->ID ) ),
				'item'     => get_permalink( $post->ID ),
			);
		}
	} elseif ( is_singular() ) {
		global $post;

		if ( $post instanceof WP_Post ) {
			$post_type = get_post_type( $post );

			if ( $post_type && 'post' !== $post_type && 'page' !== $post_type ) {
				$post_type_object = get_post_type_object( $post_type );

				if ( $post_type_object ) {
					$archive_link = get_post_type_archive_link( $post_type );

					if ( $archive_link ) {
						$items[] = array(
							'@type'    => 'ListItem',
							'position' => $position++,
							'name'     => $post_type_object->labels->name,
							'item'     => $archive_link,
						);
					} else {
						$items[] = array(
							'@type'    => 'ListItem',
							'position' => $position++,
							'name'     => $post_type_object->labels->name,
							'item'     => get_permalink( $post->ID ),
						);
					}
				}
			}

			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => wp_strip_all_tags( get_the_title( $post->ID ) ),
				'item'     => get_permalink( $post->ID ),
			);
		}
	} elseif ( is_category() || is_tax() ) {
		$term = get_queried_object();

		if ( $term && ! is_wp_error( $term ) ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $term->name,
				'item'     => get_term_link( $term ),
			);
		}
	}

	if ( count( $items ) < 2 ) {
		return array();
	}

	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/**
 * --------------------------------------
 * LOCAL BUSINESS SCHEMA
 * --------------------------------------
 */

function recrm_get_local_business_schema() {
	$settings     = function_exists( 'recrm_get_settings' ) ? recrm_get_settings() : array();

	$company_name = trim( get_option( 'recrm_schema_company_name', '' ) );
	$phone        = trim( get_option( 'recrm_schema_company_phone', '' ) );
	$email        = trim( get_option( 'recrm_schema_company_email', '' ) );
	$city         = trim( get_option( 'recrm_schema_company_city', 'Тернопіль' ) );
	$country      = trim( get_option( 'recrm_schema_company_country', 'UA' ) );
	$description  = trim( get_option( 'recrm_schema_company_description', '' ) );
	$address      = trim( get_option( 'recrm_schema_company_address', '' ) );

	if ( '' === $company_name ) {
		$company_name = get_bloginfo( 'name' );
	}

	if ( '' === $phone && ! empty( $settings['phone'] ) ) {
		$phone = trim( $settings['phone'] );
	}

	if ( '' === $email && ! empty( $settings['email'] ) ) {
		$email = trim( $settings['email'] );
	}

	if ( '' === $address && ! empty( $settings['address'] ) ) {
		$address = trim( $settings['address'] );
	}

	if ( '' === $description ) {
		$description = get_bloginfo( 'description' );
	}

	$logo_url = recrm_get_schema_logo_url();

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'LocalBusiness',
		'name'        => $company_name,
		'url'         => home_url( '/' ),
		'description' => $description,
		'address'     => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address,
			'addressLocality' => $city,
			'addressCountry'  => $country,
		),
		'areaServed'  => array(
			'@type' => 'City',
			'name'  => $city,
		),
	);

	if ( '' !== $phone ) {
		$schema['telephone'] = $phone;
	}

	if ( '' !== $email ) {
		$schema['email'] = $email;
	}

	if ( ! empty( $logo_url ) ) {
		$schema['image'] = $logo_url;
	}

	return $schema;
}