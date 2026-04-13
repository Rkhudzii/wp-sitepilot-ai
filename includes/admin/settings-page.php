<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_init', 'recrm_register_settings' );

function recrm_register_settings() {
    register_setting(
        'recrm_settings_group',
        'recrm_settings',
        'recrm_sanitize_settings'
    );

    add_settings_section(
        'recrm_modules_section',
        'Модулі',
        '__return_false',
        'recrm-settings'
    );

    add_settings_field(
        'modules',
        'Керування модулями',
        'recrm_settings_field_modules',
        'recrm-settings',
        'recrm_modules_section'
    );

    add_settings_section(
        'recrm_contact_section',
        'Контактні дані',
        '__return_false',
        'recrm-settings'
    );

    add_settings_field(
        'phone',
        'Телефон',
        'recrm_settings_field_phone',
        'recrm-settings',
        'recrm_contact_section'
    );

    add_settings_field(
        'email',
        'Email',
        'recrm_settings_field_email',
        'recrm-settings',
        'recrm_contact_section'
    );

    add_settings_field(
        'address',
        'Адреса',
        'recrm_settings_field_address',
        'recrm-settings',
        'recrm_contact_section'
    );

    add_settings_section(
        'recrm_single_section',
        'Single property',
        '__return_false',
        'recrm-settings'
    );

    add_settings_field(
        'button_text',
        'Текст головної кнопки',
        'recrm_settings_field_button_text',
        'recrm-settings',
        'recrm_single_section'
    );

    add_settings_field(
        'contact_form_shortcode',
        'Шорткод форми',
        'recrm_settings_field_contact_form_shortcode',
        'recrm-settings',
        'recrm_single_section'
    );

    add_settings_field(
        'fallback_image',
        'Fallback image URL',
        'recrm_settings_field_fallback_image',
        'recrm-settings',
        'recrm_single_section'
    );

    add_settings_field(
        'enable_map',
        'Карта',
        'recrm_settings_field_enable_map',
        'recrm-settings',
        'recrm_single_section'
    );

    add_settings_field(
        'enable_sticky_sidebar',
        'Sticky sidebar',
        'recrm_settings_field_enable_sticky_sidebar',
        'recrm-settings',
        'recrm_single_section'
    );

    add_settings_section(
        'recrm_catalog_section',
        'Каталог і загальні параметри',
        '__return_false',
        'recrm-settings'
    );

    add_settings_field(
        'default_currency',
        'Валюта за замовчуванням',
        'recrm_settings_field_default_currency',
        'recrm-settings',
        'recrm_catalog_section'
    );

    add_settings_field(
        'area_unit',
        'Одиниця площі',
        'recrm_settings_field_area_unit',
        'recrm-settings',
        'recrm_catalog_section'
    );

    add_settings_field(
        'properties_per_page',
        'Об’єктів на сторінку',
        'recrm_settings_field_properties_per_page',
        'recrm-settings',
        'recrm_catalog_section'
    );

    add_settings_field(
        'property_post_type_slug',
        'Slug CPT',
        'recrm_settings_field_property_post_type_slug',
        'recrm-settings',
        'recrm_catalog_section'
    );


    add_settings_field(
        'enable_dynamic_seo_menu',
        'Динамічне SEO-меню',
        'recrm_settings_field_enable_dynamic_seo_menu',
        'recrm-settings',
        'recrm_catalog_section'
    );

    /**
     * --------------------------------------
     * SCHEMA SECTION
     * --------------------------------------
     */

    add_settings_section(
        'recrm_schema_section',
        'Schema.org / JSON-LD',
        '__return_false',
        'recrm-settings'
    );

    add_settings_field(
        'schema_enabled',
        'Schema markup',
        'recrm_settings_field_schema_enabled',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_realestate_enabled',
        'RealEstateAgent schema',
        'recrm_settings_field_schema_realestate_enabled',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_company_name',
        'Назва компанії для schema',
        'recrm_settings_field_schema_company_name',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_company_phone',
        'Телефон для schema',
        'recrm_settings_field_schema_company_phone',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_company_email',
        'Email для schema',
        'recrm_settings_field_schema_company_email',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_company_city',
        'Місто',
        'recrm_settings_field_schema_company_city',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_company_country',
        'Країна',
        'recrm_settings_field_schema_company_country',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_company_address',
        'Адреса для schema',
        'recrm_settings_field_schema_company_address',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_company_description',
        'Опис компанії',
        'recrm_settings_field_schema_company_description',
        'recrm-settings',
        'recrm_schema_section'
    );

    add_settings_field(
        'schema_preview',
        'Preview JSON-LD',
        'recrm_settings_field_schema_preview',
        'recrm-settings',
        'recrm_schema_section'
    );
}

function recrm_get_settings() {
    $defaults = array(
        'phone'                   => '',
        'email'                   => '',
        'address'                 => '',
        'button_text'             => 'Залишити заявку',
        'contact_form_shortcode'  => '',
        'fallback_image'          => '',
        'default_currency'        => '$',
        'area_unit'               => 'м²',
        'properties_per_page'     => 12,
        'property_post_type_slug' => 'property',
        'enable_map'              => '1',
        'enable_sticky_sidebar'   => '1',
        'enable_dynamic_seo_menu' => '0',
        'module_filter'           => '1',
        'module_seo'              => '1',
        'module_import'           => '1',
    );

    $settings = get_option( 'recrm_settings', array() );

    if ( ! is_array( $settings ) ) {
        $settings = array();
    }

    return wp_parse_args( $settings, $defaults );
}

function recrm_sanitize_settings( $input ) {
    $output = array();

    $output['phone'] = isset( $input['phone'] )
        ? sanitize_text_field( $input['phone'] )
        : '';

    $output['email'] = isset( $input['email'] )
        ? sanitize_email( $input['email'] )
        : '';

    $output['address'] = isset( $input['address'] )
        ? sanitize_text_field( $input['address'] )
        : '';

    $output['button_text'] = isset( $input['button_text'] )
        ? sanitize_text_field( $input['button_text'] )
        : 'Залишити заявку';

    $output['contact_form_shortcode'] = isset( $input['contact_form_shortcode'] )
        ? wp_kses_post( $input['contact_form_shortcode'] )
        : '';

    $output['fallback_image'] = isset( $input['fallback_image'] )
        ? esc_url_raw( $input['fallback_image'] )
        : '';

    $output['default_currency'] = isset( $input['default_currency'] )
        ? sanitize_text_field( $input['default_currency'] )
        : '$';

    $output['area_unit'] = isset( $input['area_unit'] )
        ? sanitize_text_field( $input['area_unit'] )
        : 'м²';

    $output['properties_per_page'] = isset( $input['properties_per_page'] )
        ? max( 1, absint( $input['properties_per_page'] ) )
        : 12;

    $output['property_post_type_slug'] = isset( $input['property_post_type_slug'] )
        ? sanitize_title( $input['property_post_type_slug'] )
        : 'property';

    $output['enable_map'] = isset( $input['enable_map'] ) ? '1' : '0';
    $output['enable_sticky_sidebar'] = isset( $input['enable_sticky_sidebar'] ) ? '1' : '0';
    $output['enable_dynamic_seo_menu'] = isset( $input['enable_dynamic_seo_menu'] ) ? '1' : '0';

    $module_settings = array(
        'filter' => isset( $input['module_filter'] ) ? '1' : '0',
        'seo'    => isset( $input['module_seo'] ) ? '1' : '0',
        'import' => isset( $input['module_import'] ) ? '1' : '0',
    );

    recrm_update_module_settings( $module_settings );

    $output['module_filter'] = $module_settings['filter'];
    $output['module_seo']    = $module_settings['seo'];
    $output['module_import'] = $module_settings['import'];

    return $output;
}


function recrm_settings_field_modules() {
    $settings = recrm_get_settings();
    $registry = recrm_get_module_registry();
    ?>
    <div style="display:grid; gap:12px; max-width:720px;">
        <?php foreach ( $registry as $module_key => $module_data ) : ?>
            <div style="padding:14px 16px; border:1px solid #e5e7eb; border-radius:14px; background:#fff;">
                <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start;">
                    <div>
                        <strong style="display:block; margin-bottom:6px;"><?php echo esc_html( $module_data['label'] ); ?></strong>
                        <div style="color:#475569; line-height:1.6;"><?php echo esc_html( $module_data['description'] ); ?></div>
                        <?php if ( ! empty( $module_data['depends_on'] ) ) : ?>
                            <div style="margin-top:6px; color:#64748b; font-size:12px;">Залежить від: <?php echo esc_html( implode( ', ', (array) $module_data['depends_on'] ) ); ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ( ! empty( $module_data['always_on'] ) ) : ?>
                            <span style="display:inline-flex; min-height:34px; align-items:center; padding:0 12px; border-radius:999px; background:#dcfce7; color:#166534; font-weight:700;">Завжди увімкнено</span>
                        <?php else : ?>
                            <label>
                                <input type="checkbox" name="recrm_settings[module_<?php echo esc_attr( $module_key ); ?>]" value="1" <?php checked( $settings[ 'module_' . $module_key ], '1' ); ?>>
                                Увімкнути
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function recrm_settings_field_phone() {
    $settings = recrm_get_settings();
    ?>
    <input type="text" name="recrm_settings[phone]" value="<?php echo esc_attr( $settings['phone'] ); ?>" class="regular-text" placeholder="+380XXXXXXXXX">
    <?php
}

function recrm_settings_field_email() {
    $settings = recrm_get_settings();
    ?>
    <input type="email" name="recrm_settings[email]" value="<?php echo esc_attr( $settings['email'] ); ?>" class="regular-text" placeholder="info@example.com">
    <?php
}

function recrm_settings_field_address() {
    $settings = recrm_get_settings();
    ?>
    <input type="text" name="recrm_settings[address]" value="<?php echo esc_attr( $settings['address'] ); ?>" class="regular-text" placeholder="м. Тернопіль, ...">
    <?php
}

function recrm_settings_field_button_text() {
    $settings = recrm_get_settings();
    ?>
    <input type="text" name="recrm_settings[button_text]" value="<?php echo esc_attr( $settings['button_text'] ); ?>" class="regular-text" placeholder="Залишити заявку">
    <?php
}

function recrm_settings_field_contact_form_shortcode() {
    $settings = recrm_get_settings();
    ?>
    <textarea name="recrm_settings[contact_form_shortcode]" rows="4" class="large-text" placeholder='[contact-form-7 id="123" title="Форма"]'><?php echo esc_textarea( $settings['contact_form_shortcode'] ); ?></textarea>
    <p class="description">Шорткод форми, яка буде виводитись на сторінці об’єкта.</p>
    <?php
}

function recrm_settings_field_fallback_image() {
    $settings  = recrm_get_settings();
    $image_url = isset( $settings['fallback_image'] ) ? (string) $settings['fallback_image'] : '';
    ?>
    <div class="recrm-fallback-image-field" style="display:grid; gap:10px; max-width:720px;">
        <input type="url" id="recrm_fallback_image" name="recrm_settings[fallback_image]" value="<?php echo esc_attr( $image_url ); ?>" class="large-text" placeholder="https://site.com/uploads/no-image.jpg">

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button type="button" class="button button-secondary" id="recrm_pick_fallback_image">Обрати з галереї</button>
            <button type="button" class="button-link-delete" id="recrm_remove_fallback_image" <?php echo empty( $image_url ) ? 'style="display:none; align-self:center;"' : 'style="align-self:center;"'; ?>>Прибрати фото</button>
        </div>

        <div id="recrm_fallback_image_preview_wrap" <?php echo empty( $image_url ) ? 'style="display:none;"' : ''; ?>>
            <img id="recrm_fallback_image_preview" src="<?php echo esc_url( $image_url ); ?>" alt="" style="display:block; width:140px; height:140px; object-fit:cover; border-radius:8px; border:1px solid #dcdcde; background:#fff;">
        </div>

        <p class="description" style="margin:0;">Картинка за замовчуванням, якщо в об’єкта немає фото.</p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('recrm_fallback_image');
        var pickButton = document.getElementById('recrm_pick_fallback_image');
        var removeButton = document.getElementById('recrm_remove_fallback_image');
        var previewWrap = document.getElementById('recrm_fallback_image_preview_wrap');
        var preview = document.getElementById('recrm_fallback_image_preview');

        if (!input || !pickButton || typeof wp === 'undefined' || !wp.media) {
            return;
        }

        function updatePreview(url) {
            if (!preview || !previewWrap || !removeButton) {
                return;
            }

            if (url) {
                preview.src = url;
                previewWrap.style.display = '';
                removeButton.style.display = '';
            } else {
                preview.src = '';
                previewWrap.style.display = 'none';
                removeButton.style.display = 'none';
            }
        }

        var frame = wp.media({
            title: 'Оберіть fallback-зображення',
            button: {
                text: 'Використати зображення'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        pickButton.addEventListener('click', function (event) {
            event.preventDefault();
            frame.open();
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment.url || '';

            if (!url && attachment.sizes) {
                if (attachment.sizes.full && attachment.sizes.full.url) {
                    url = attachment.sizes.full.url;
                } else if (attachment.sizes.large && attachment.sizes.large.url) {
                    url = attachment.sizes.large.url;
                }
            }

            input.value = url;
            updatePreview(url);
        });

        if (removeButton) {
            removeButton.addEventListener('click', function (event) {
                event.preventDefault();
                input.value = '';
                updatePreview('');
            });
        }
    });
    </script>
    <?php
}

function recrm_settings_field_enable_map() {
    $settings = recrm_get_settings();
    ?>
    <label>
        <input type="checkbox" name="recrm_settings[enable_map]" value="1" <?php checked( $settings['enable_map'], '1' ); ?>>
        Увімкнути блок карти на single property
    </label>
    <?php
}

function recrm_settings_field_enable_sticky_sidebar() {
    $settings = recrm_get_settings();
    ?>
    <label>
        <input type="checkbox" name="recrm_settings[enable_sticky_sidebar]" value="1" <?php checked( $settings['enable_sticky_sidebar'], '1' ); ?>>
        Увімкнути sticky sidebar на single property
    </label>
    <?php
}


function recrm_settings_field_enable_dynamic_seo_menu() {
    $settings = recrm_get_settings();
    ?>
    <label>
        <input type="checkbox" name="recrm_settings[enable_dynamic_seo_menu]" value="1" <?php checked( $settings['enable_dynamic_seo_menu'], '1' ); ?>>
        Увімкнути динамічне SEO-підменю для каталогу
    </label>
    <div style="margin-top:10px; max-width:700px; padding:12px 14px; border:1px solid #fde68a; background:#fffbeb; border-radius:12px; color:#7c5d10;">
        <strong>В розробці.</strong> Автоматично додає підпункти до меню «Нерухомість» на основі типів, районів і кімнат.
        Рекомендується тримати вимкненим, якщо SEO-посадкові створюються вручну.
    </div>
    <?php
}

function recrm_settings_field_default_currency() {
    $settings = recrm_get_settings();
    ?>
    <input type="text" name="recrm_settings[default_currency]" value="<?php echo esc_attr( $settings['default_currency'] ); ?>" class="small-text" placeholder="$">
    <p class="description">Наприклад: $, €, грн</p>
    <?php
}

function recrm_settings_field_area_unit() {
    $settings = recrm_get_settings();
    ?>
    <input type="text" name="recrm_settings[area_unit]" value="<?php echo esc_attr( $settings['area_unit'] ); ?>" class="small-text" placeholder="м²">
    <?php
}

function recrm_settings_field_properties_per_page() {
    $settings = recrm_get_settings();
    ?>
    <input type="number" min="1" step="1" name="recrm_settings[properties_per_page]" value="<?php echo esc_attr( $settings['properties_per_page'] ); ?>" class="small-text">
    <?php
}

function recrm_settings_field_property_post_type_slug() {
    $settings = recrm_get_settings();
    ?>
    <input type="text" name="recrm_settings[property_post_type_slug]" value="<?php echo esc_attr( $settings['property_post_type_slug'] ); ?>" class="regular-text" placeholder="property">
    <p class="description">Slug custom post type. Якщо вже використовуєш property — не змінюй без потреби.</p>
    <?php
}

/**
 * --------------------------------------
 * SCHEMA FIELDS
 * --------------------------------------
 */

function recrm_settings_field_schema_enabled() {
    $value = (int) get_option( 'recrm_schema_enabled', 1 );
    ?>
    <label>
        <input type="checkbox" name="recrm_schema_enabled" value="1" <?php checked( $value, 1 ); ?>>
        Увімкнути Schema.org / JSON-LD на сайті
    </label>
    <?php
}

function recrm_settings_field_schema_realestate_enabled() {
    $value = (int) get_option( 'recrm_schema_realestate_enabled', 1 );
    ?>
    <label>
        <input type="checkbox" name="recrm_schema_realestate_enabled" value="1" <?php checked( $value, 1 ); ?>>
        Виводити RealEstateAgent schema
    </label>
    <?php
}


function recrm_settings_field_schema_company_name() {
    $value = get_option( 'recrm_schema_company_name', '' );
    ?>
    <input type="text" name="recrm_schema_company_name" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
    <p class="description">Якщо поле пусте — буде використана назва сайту.</p>
    <?php
}

function recrm_settings_field_schema_company_phone() {
    $value    = get_option( 'recrm_schema_company_phone', '' );
    $settings = recrm_get_settings();
    ?>
    <input type="text" name="recrm_schema_company_phone" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $settings['phone'] ); ?>">
    <p class="description">Якщо не заповнювати окремо, орієнтуйся на основний телефон у контактних даних.</p>
    <?php
}

function recrm_settings_field_schema_company_email() {
    $value    = get_option( 'recrm_schema_company_email', '' );
    $settings = recrm_get_settings();
    ?>
    <input type="email" name="recrm_schema_company_email" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $settings['email'] ); ?>">
    <p class="description">Email для schema. Можна дублювати основний email сайту.</p>
    <?php
}

function recrm_settings_field_schema_company_city() {
    $value = get_option( 'recrm_schema_company_city', 'Тернопіль' );
    ?>
    <input type="text" name="recrm_schema_company_city" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="Тернопіль">
    <?php
}

function recrm_settings_field_schema_company_country() {
    $value = get_option( 'recrm_schema_company_country', 'UA' );
    ?>
    <input type="text" name="recrm_schema_company_country" value="<?php echo esc_attr( $value ); ?>" class="small-text" placeholder="UA">
    <p class="description">Для України залишай UA.</p>
    <?php
}

function recrm_settings_field_schema_company_address() {
    $value    = get_option( 'recrm_schema_company_address', '' );
    $settings = recrm_get_settings();
    ?>
    <input type="text" name="recrm_schema_company_address" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $settings['address'] ); ?>">
    <p class="description">Можна використати ту саму адресу, що в контактних даних.</p>
    <?php
}

function recrm_settings_field_schema_company_description() {
    $value = get_option( 'recrm_schema_company_description', '' );
    ?>
    <textarea name="recrm_schema_company_description" rows="4" class="large-text" placeholder="Агентство нерухомості в Тернополі. Купівля, продаж, підбір нерухомості та супровід угод."><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description">Короткий опис компанії для пошукових систем та AI.</p>
    <?php
}







function recrm_settings_field_schema_preview() {
    $preview = '';

    if ( function_exists( 'recrm_get_schema_preview_json' ) ) {
        $preview = recrm_get_schema_preview_json();
    }
    ?>
    <textarea rows="22" class="large-text code" readonly style="font-family: Consolas, Monaco, monospace;"><?php echo esc_textarea( $preview ); ?></textarea>
    <p class="description">Це preview того JSON-LD, який буде виводитись на сайті згідно з поточними налаштуваннями.</p>
    <?php
}


function recrm_render_settings_page() {
    $settings = recrm_get_settings();
    $stats = array(
        'phone'   => ! empty( $settings['phone'] ) ? 'Заповнено' : 'Порожньо',
        'email'   => ! empty( $settings['email'] ) ? 'Заповнено' : 'Порожньо',
        'schema'  => (int) get_option( 'recrm_schema_enabled', 1 ) ? 'Увімкнено' : 'Вимкнено',
        'seo_nav' => ! empty( $settings['enable_dynamic_seo_menu'] ) ? 'Увімкнено' : 'Вимкнено',
        'filter'  => recrm_is_module_enabled( 'filter' ) ? 'Увімкнено' : 'Вимкнено',
        'import'  => recrm_is_module_enabled( 'import' ) ? 'Увімкнено' : 'Вимкнено',
    );
    ?>
    <div class="wrap recrm-settings-page">
        <style>
            .recrm-settings-page { color:#0f172a; }
            .recrm-settings-page * { box-sizing:border-box; }
            .recrm-settings-page .recrm-settings-shell { max-width:1200px; }
            .recrm-settings-page .recrm-settings-hero {
                margin:18px 0 20px;
                padding:24px 26px;
                border-radius:22px;
                background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 100%);
                color:#fff;
                box-shadow:0 18px 40px rgba(15,23,42,.16);
            }
            .recrm-settings-page .recrm-settings-hero h1 { margin:0 0 8px; color:#fff; font-size:30px; }
            .recrm-settings-page .recrm-settings-hero p { margin:0; max-width:860px; color:rgba(255,255,255,.9); font-size:14px; line-height:1.7; }
            .recrm-settings-page .recrm-settings-stats {
                display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:14px; margin:18px 0 24px;
            }
            .recrm-settings-page .recrm-stat-card {
                background:#fff; border:1px solid #e5e7eb; border-radius:18px; padding:16px 18px; box-shadow:0 8px 22px rgba(15,23,42,.05);
            }
            .recrm-settings-page .recrm-stat-label { color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:.06em; }
            .recrm-settings-page .recrm-stat-value { margin-top:6px; font-size:18px; font-weight:700; }
            .recrm-settings-page .recrm-settings-layout { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:22px; align-items:start; }
            .recrm-settings-page .recrm-settings-main,
            .recrm-settings-page .recrm-settings-side { min-width:0; }
            .recrm-settings-page .recrm-settings-panel {
                background:#fff; border:1px solid #e5e7eb; border-radius:22px; padding:20px 20px 6px; box-shadow:0 10px 30px rgba(15,23,42,.05);
            }
            .recrm-settings-page .recrm-settings-tabs { display:flex; flex-wrap:wrap; gap:10px; margin:4px 0 18px; }
            .recrm-settings-page .recrm-settings-tab {
                border:1px solid #dbe2ea; background:#f8fafc; color:#0f172a; border-radius:999px; padding:10px 14px; cursor:pointer; font-weight:600;
            }
            .recrm-settings-page .recrm-settings-tab.is-active { background:#0f172a; color:#fff; border-color:#0f172a; }
            .recrm-settings-page .recrm-settings-section { display:none; }
            .recrm-settings-page .recrm-settings-section.is-active { display:block; }
            .recrm-settings-page .recrm-settings-section h2 {
                margin:6px 0 12px; padding-bottom:10px; border-bottom:1px solid #eef2f7; font-size:22px;
            }
            .recrm-settings-page .form-table { margin-top:0; }
            .recrm-settings-page .form-table th { width:260px; padding-top:18px; }
            .recrm-settings-page .form-table td { padding-top:16px; }
            .recrm-settings-page input[type="text"],
            .recrm-settings-page input[type="email"],
            .recrm-settings-page input[type="number"],
            .recrm-settings-page textarea,
            .recrm-settings-page select {
                border-color:#cbd5e1; border-radius:10px;
            }
            .recrm-settings-page textarea.code { white-space:pre; min-height:360px; }
            .recrm-settings-page .button-primary { padding:0 18px; min-height:40px; }
            .recrm-settings-page .recrm-side-card {
                background:#fff; border:1px solid #e5e7eb; border-radius:20px; padding:18px; box-shadow:0 10px 30px rgba(15,23,42,.05); margin-bottom:18px;
            }
            .recrm-settings-page .recrm-side-card h3 { margin:0 0 10px; font-size:18px; }
            .recrm-settings-page .recrm-side-card p,
            .recrm-settings-page .recrm-side-card li { color:#475569; line-height:1.6; }
            .recrm-settings-page .recrm-side-card ul { margin:0; padding-left:18px; }
            .recrm-settings-page .recrm-side-note {
                background:#eff6ff; border-color:#bfdbfe;
            }
            @media (max-width: 1080px) {
                .recrm-settings-page .recrm-settings-layout { grid-template-columns:1fr; }
                .recrm-settings-page .recrm-settings-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
            }
            @media (max-width: 640px) {
                .recrm-settings-page .recrm-settings-stats { grid-template-columns:1fr; }
                .recrm-settings-page .form-table th,
                .recrm-settings-page .form-table td { display:block; width:100%; padding-right:0; }
            }
        </style>

        <div class="recrm-settings-shell">
            <div class="recrm-settings-hero">
                <h1>RE CRM — Налаштування</h1>
                <p>Тут зібрані головні параметри плагіна: контактні дані, налаштування каталогу, поведінка картки об’єкта, schema-розмітка та експериментальні функції. Секції розбиті на вкладки, щоб не губитися в одному довгому екрані.</p>
            </div>

            <div class="recrm-settings-stats">
                <div class="recrm-stat-card"><div class="recrm-stat-label">Телефон</div><div class="recrm-stat-value"><?php echo esc_html( $stats['phone'] ); ?></div></div>
                <div class="recrm-stat-card"><div class="recrm-stat-label">Email</div><div class="recrm-stat-value"><?php echo esc_html( $stats['email'] ); ?></div></div>
                <div class="recrm-stat-card"><div class="recrm-stat-label">Schema</div><div class="recrm-stat-value"><?php echo esc_html( $stats['schema'] ); ?></div></div>
                <div class="recrm-stat-card"><div class="recrm-stat-label">SEO-підменю</div><div class="recrm-stat-value"><?php echo esc_html( $stats['seo_nav'] ); ?></div></div>
                <div class="recrm-stat-card"><div class="recrm-stat-label">Фільтр</div><div class="recrm-stat-value"><?php echo esc_html( $stats['filter'] ); ?></div></div>
                <div class="recrm-stat-card"><div class="recrm-stat-label">Імпорт</div><div class="recrm-stat-value"><?php echo esc_html( $stats['import'] ); ?></div></div>
            </div>

            <div class="recrm-settings-layout">
                <div class="recrm-settings-main">
                    <div class="recrm-settings-panel">
                        <div class="recrm-settings-tabs" id="recrm-settings-tabs"></div>
                        <form method="post" action="options.php">
                            <?php
                            settings_fields( 'recrm_settings_group' );
                            do_settings_sections( 'recrm-settings' );
                            submit_button( 'Зберегти налаштування' );
                            ?>
                        </form>
                    </div>
                </div>

                <aside class="recrm-settings-side">
                    <div class="recrm-side-card recrm-side-note">
                        <h3>Швидкий старт</h3>
                        <p>Спочатку заповни телефон, email та адресу. Потім перевір валюту, кількість об’єктів на сторінку і тільки після цього переходь до schema та експериментальних опцій.</p>
                    </div>

                    <div class="recrm-side-card">
                        <h3>Що важливо не зламати</h3>
                        <ul>
                            <li>Slug CPT краще не змінювати після запуску сайту.</li>
                            <li>Динамічне SEO-підменю тримай вимкненим, якщо SEO-сторінки створюються вручну.</li>
                            <li>Schema вмикай лише після заповнення базових контактів.</li>
                        </ul>
                    </div>

                    <div class="recrm-side-card">
                        <h3>Підказка</h3>
                        <p>Preview JSON-LD показує, що саме потрапить на сайт. Це зручно перевіряти після кожної зміни schema-блоку.</p>
                    </div>
                </aside>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var container = document.querySelector('.recrm-settings-page form');
            var tabsWrap = document.getElementById('recrm-settings-tabs');
            if (!container || !tabsWrap) return;

            var headings = Array.prototype.slice.call(container.querySelectorAll('h2'));
            var labelsMap = {
                'Контактні дані': 'Контакти',
                'Single property': 'Single',
                'Каталог і загальні параметри': 'Каталог',
                'Schema.org / JSON-LD': 'Schema'
            };

            headings.forEach(function (heading, index) {
                var section = document.createElement('div');
                section.className = 'recrm-settings-section' + (index === 0 ? ' is-active' : '');
                heading.parentNode.insertBefore(section, heading);
                section.appendChild(heading);

                var next = section.nextSibling;
                while (next && !(next.nodeType === 1 && next.tagName.toLowerCase() === 'h2') && !(next.nodeType === 1 && next.classList.contains('submit'))) {
                    var current = next;
                    next = next.nextSibling;
                    section.appendChild(current);
                }

                if (section.nextSibling && section.nextSibling.nodeType === 1 && section.nextSibling.classList.contains('submit')) {
                    container.appendChild(section.nextSibling);
                }

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'recrm-settings-tab' + (index === 0 ? ' is-active' : '');
                button.textContent = labelsMap[heading.textContent.trim()] || heading.textContent.trim();
                button.setAttribute('data-tab-index', index);
                tabsWrap.appendChild(button);
            });

            tabsWrap.addEventListener('click', function (event) {
                var button = event.target.closest('.recrm-settings-tab');
                if (!button) return;
                var index = Number(button.getAttribute('data-tab-index'));
                document.querySelectorAll('.recrm-settings-tab').forEach(function (tab, tabIndex) {
                    tab.classList.toggle('is-active', tabIndex === index);
                });
                document.querySelectorAll('.recrm-settings-section').forEach(function (section, sectionIndex) {
                    section.classList.toggle('is-active', sectionIndex === index);
                });
            });
        });
        </script>
    </div>
    <?php
}
