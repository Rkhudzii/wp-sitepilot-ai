<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RECRM_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
    }

    
public function register_admin_menu() {
        add_menu_page(
            'RE CRM',
            'RE CRM',
            'manage_options',
            'recrm-main',
            '__return_null',
            'dashicons-building',
            25
        );

        add_submenu_page(
            'recrm-main',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'recrm-main',
            array( $this, 'render_dashboard' )
        );

        if ( recrm_is_module_enabled( 'import' ) && function_exists( 'recrm_xml_import_admin_page' ) ) {
            add_submenu_page(
                'recrm-main',
                'Імпорт',
                'Імпорт',
                'manage_options',
                'recrm-xml-import',
                'recrm_xml_import_admin_page'
            );
        }

        add_submenu_page(
            'recrm-main',
            'Налаштування',
            'Налаштування',
            'manage_options',
            'recrm-settings',
            'recrm_render_settings_page'
        );
    }

    public function render_dashboard() {
    $property_counts = wp_count_posts( 'property' );

    $published = isset( $property_counts->publish ) ? (int) $property_counts->publish : 0;
    $drafts    = isset( $property_counts->draft ) ? (int) $property_counts->draft : 0;
    $pending   = isset( $property_counts->pending ) ? (int) $property_counts->pending : 0;
    $total     = $published + $drafts + $pending;

    $settings_url     = admin_url( 'admin.php?page=recrm-settings' );
    $import_url       = admin_url( 'admin.php?page=recrm-xml-import' );
    $properties_url   = admin_url( 'edit.php?post_type=property' );
    $new_property_url = admin_url( 'post-new.php?post_type=property' );
    ?>
    <div class="wrap recrm-dashboard">
        <style>
            .recrm-dashboard {
                margin-top: 20px;
            }

            .recrm-dashboard * {
                box-sizing: border-box;
            }

            .recrm-dashboard-header {
                margin-bottom: 24px;
                padding: 28px 32px;
                background: linear-gradient(135deg, #0d1838 0%, #1f2d5c 100%);
                border-radius: 20px;
                color: #fff;
            }

            .recrm-dashboard-header h1 {
                margin: 0 0 10px;
                font-size: 32px;
                line-height: 1.2;
                color: #fff;
            }

            .recrm-dashboard-header p {
                margin: 0;
                font-size: 15px;
                line-height: 1.7;
                color: rgba(255,255,255,.88);
                max-width: 820px;
            }

            .recrm-dashboard-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 18px;
                margin-bottom: 24px;
            }

            .recrm-dashboard-card {
                background: #fff;
                border: 1px solid #e7eaf0;
                border-radius: 18px;
                padding: 22px;
                box-shadow: 0 8px 24px rgba(13, 24, 56, 0.05);
            }

            .recrm-dashboard-card-label {
                margin: 0 0 10px;
                font-size: 13px;
                font-weight: 600;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: .04em;
            }

            .recrm-dashboard-card-value {
                margin: 0;
                font-size: 34px;
                line-height: 1.1;
                font-weight: 800;
                color: #0d1838;
            }

            .recrm-dashboard-card-note {
                margin-top: 10px;
                color: #667085;
                font-size: 14px;
                line-height: 1.5;
            }

            .recrm-dashboard-columns {
                display: grid;
                grid-template-columns: 1.15fr .85fr;
                gap: 24px;
                margin-bottom: 24px;
            }

            .recrm-dashboard-panel {
                background: #fff;
                border: 1px solid #e7eaf0;
                border-radius: 18px;
                padding: 24px;
                box-shadow: 0 8px 24px rgba(13, 24, 56, 0.05);
            }

            .recrm-dashboard-panel h2 {
                margin: 0 0 16px;
                font-size: 22px;
                line-height: 1.3;
                color: #0d1838;
            }

            .recrm-dashboard-panel h3 {
                margin: 22px 0 12px;
                font-size: 17px;
                line-height: 1.35;
                color: #0d1838;
            }

            .recrm-dashboard-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .recrm-dashboard-action {
                display: block;
                padding: 18px;
                border-radius: 16px;
                text-decoration: none;
                border: 1px solid #e7eaf0;
                background: #f8fafc;
                transition: .2s ease;
            }

            .recrm-dashboard-action:hover {
                transform: translateY(-2px);
                border-color: #f59a00;
                box-shadow: 0 10px 24px rgba(245, 154, 0, 0.10);
            }

            .recrm-dashboard-action-title {
                display: block;
                margin-bottom: 8px;
                font-size: 17px;
                font-weight: 700;
                color: #0d1838;
            }

            .recrm-dashboard-action-text {
                display: block;
                color: #667085;
                font-size: 14px;
                line-height: 1.6;
            }

            .recrm-dashboard-list {
                margin: 0;
                padding-left: 18px;
            }

            .recrm-dashboard-list li {
                margin-bottom: 10px;
                color: #475467;
                line-height: 1.6;
            }

            .recrm-dashboard-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 30px;
                padding: 4px 10px;
                border-radius: 999px;
                background: #fff3df;
                color: #b46900;
                font-size: 12px;
                font-weight: 700;
                border: 1px solid #f6d8a4;
            }

            .recrm-dashboard-doc-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 24px;
            }

            .recrm-dashboard-doc-box {
                padding: 18px;
                border: 1px solid #e7eaf0;
                border-radius: 16px;
                background: #f8fafc;
            }

            .recrm-dashboard-doc-box p {
                margin: 0 0 10px;
                color: #475467;
                line-height: 1.7;
            }

            .recrm-dashboard-code {
                display: block;
                margin-top: 10px;
                padding: 12px 14px;
                background: #0f172a;
                color: #e5eefc;
                border-radius: 12px;
                overflow: auto;
                font-family: Consolas, Monaco, monospace;
                font-size: 13px;
                line-height: 1.6;
            }

            .recrm-dashboard-note {
                margin-top: 14px;
                padding: 12px 14px;
                border-radius: 12px;
                background: #fff8e8;
                border: 1px solid #f6d8a4;
                color: #8a5a00;
                line-height: 1.6;
            }

            .recrm-dashboard-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 12px;
            }

            .recrm-dashboard-table th,
            .recrm-dashboard-table td {
                padding: 12px 14px;
                text-align: left;
                border-bottom: 1px solid #e7eaf0;
                vertical-align: top;
            }

            .recrm-dashboard-table th {
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #667085;
            }

            .recrm-dashboard-table td code {
                background: #eef2f7;
                padding: 3px 7px;
                border-radius: 8px;
            }

            @media (max-width: 1200px) {
                .recrm-dashboard-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .recrm-dashboard-columns,
                .recrm-dashboard-doc-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 782px) {
                .recrm-dashboard-header {
                    padding: 22px;
                }

                .recrm-dashboard-header h1 {
                    font-size: 26px;
                }

                .recrm-dashboard-grid,
                .recrm-dashboard-actions {
                    grid-template-columns: 1fr;
                }

                .recrm-dashboard-card,
                .recrm-dashboard-panel {
                    padding: 18px;
                }
            }
        </style>

        <div class="recrm-dashboard-header">
            <span class="recrm-dashboard-badge">RE CRM XML Import</span>
            <h1>Dashboard</h1>
            <p>
                Плагін імпортує об’єкти нерухомості з XML у WordPress, створює картки об’єктів,
                зберігає характеристики, фото та допомагає виводити нерухомість на сайт через шорткоди.
            </p>
        </div>

        <div class="recrm-dashboard-grid">
            <div class="recrm-dashboard-card">
                <div class="recrm-dashboard-card-label">Усього об’єктів</div>
                <p class="recrm-dashboard-card-value"><?php echo esc_html( $total ); ?></p>
                <div class="recrm-dashboard-card-note">Загальна кількість об’єктів у системі.</div>
            </div>

            <div class="recrm-dashboard-card">
                <div class="recrm-dashboard-card-label">Опубліковано</div>
                <p class="recrm-dashboard-card-value"><?php echo esc_html( $published ); ?></p>
                <div class="recrm-dashboard-card-note">Об’єкти, які вже відображаються на сайті.</div>
            </div>

            <div class="recrm-dashboard-card">
                <div class="recrm-dashboard-card-label">Чернетки</div>
                <p class="recrm-dashboard-card-value"><?php echo esc_html( $drafts ); ?></p>
                <div class="recrm-dashboard-card-note">Записи, підготовлені, але ще не опубліковані.</div>
            </div>

            <div class="recrm-dashboard-card">
                <div class="recrm-dashboard-card-label">Очікують</div>
                <p class="recrm-dashboard-card-value"><?php echo esc_html( $pending ); ?></p>
                <div class="recrm-dashboard-card-note">Об’єкти, які чекають перевірки або публікації.</div>
            </div>
        </div>

        <div class="recrm-dashboard-columns">
            <div class="recrm-dashboard-panel">
                <h2>Швидкі дії</h2>

                <div class="recrm-dashboard-actions">
                    <a class="recrm-dashboard-action" href="<?php echo esc_url( $import_url ); ?>">
                        <span class="recrm-dashboard-action-title">Імпорт XML</span>
                        <span class="recrm-dashboard-action-text">Завантаження XML по URL або файлом та оновлення бази об’єктів.</span>
                    </a>

                    <a class="recrm-dashboard-action" href="<?php echo esc_url( $properties_url ); ?>">
                        <span class="recrm-dashboard-action-title">Усі об’єкти</span>
                        <span class="recrm-dashboard-action-text">Перегляд усіх імпортованих об’єктів нерухомості.</span>
                    </a>

                    <a class="recrm-dashboard-action" href="<?php echo esc_url( $new_property_url ); ?>">
                        <span class="recrm-dashboard-action-title">Додати об’єкт</span>
                        <span class="recrm-dashboard-action-text">Створення нового об’єкта вручну через WordPress.</span>
                    </a>

                    <a class="recrm-dashboard-action" href="<?php echo esc_url( $settings_url ); ?>">
                        <span class="recrm-dashboard-action-title">Налаштування</span>
                        <span class="recrm-dashboard-action-text">Контакти, форми, тексти кнопок та інші параметри плагіна.</span>
                    </a>
                </div>
            </div>

            <div class="recrm-dashboard-panel">
                <h2>Як працює плагін</h2>
                <ol class="recrm-dashboard-list">
                    <li>Ти завантажуєш XML-файл або вставляєш XML URL.</li>
                    <li>Плагін читає XML та створює або оновлює записи типу <code>property</code>.</li>
                    <li>Для кожного об’єкта зберігаються основні дані: ціна, площа, тип, фото, адреса та інші характеристики.</li>
                    <li>Після імпорту об’єкти можна показувати на сайті через шорткоди або шаблони.</li>
                    <li>Окремі сторінки об’єктів формуються автоматично на базі single template плагіна.</li>
                </ol>
            </div>
        </div>

        <div class="recrm-dashboard-panel">
            <h2>Міні документація</h2>

            <div class="recrm-dashboard-doc-grid">
                <div class="recrm-dashboard-doc-box">
                    <h3>Що вміє плагін</h3>
                    <p>Імпорт нерухомості з XML у WordPress.</p>
                    <p>Створення та оновлення карток об’єктів.</p>
                    <p>Виведення списку нерухомості на сторінках сайту.</p>
                    <p>Підтримка single property сторінки з характеристиками, фото та контактним блоком.</p>
                    <p>SEO-структура для сторінок нерухомості та службові модулі для фронтенду.</p>
                </div>

                <div class="recrm-dashboard-doc-box">
                    <h3>Де використовувати</h3>
                    <p>Шорткоди можна вставляти у:</p>
                    <ul class="recrm-dashboard-list">
                        <li>звичайні сторінки WordPress;</li>
                        <li>Gutenberg блок <strong>Shortcode</strong>;</li>
                        <li>Elementor віджет <strong>Shortcode</strong>;</li>
                        <li>PHP-шаблони через <code>do_shortcode()</code>.</li>
                    </ul>

                    <span class="recrm-dashboard-code">echo do_shortcode('[recrm_properties]');</span>
                </div>
            </div>

            <h3>Шорткоди</h3>

            <table class="recrm-dashboard-table">
                <thead>
                    <tr>
                        <th style="width: 240px;">Шорткод</th>
                        <th>Опис</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[recrm_properties]</code></td>
                        <td>Основний шорткод для виводу архіву / списку об’єктів нерухомості.</td>
                    </tr>
                    <tr>
                        <td><code>[recrm_properties]</code></td>
                        <td>Підходить для окремої сторінки “Нерухомість”, де потрібно показати повний каталог.</td>
                    </tr>
                    <tr>
                        <td><code>[recrm_properties ...]</code></td>
                        <td>Плагін може підтримувати додаткові параметри для коротких або фільтрованих блоків, якщо вони реалізовані у файлах <code>shortcodes.php</code> або <code>archive-properties.php</code>.</td>
                    </tr>
                </tbody>
            </table>

            <div class="recrm-dashboard-note">
                Базовий шорткод <strong>[recrm_properties]</strong> підтверджений. Якщо в обробнику вже реалізовані параметри
                на кшталт <code>limit</code>, <code>deal</code>, <code>type</code> або інші, їх теж можна додати в цю документацію.
                Для точного списку параметрів треба перевірити файли <code>includes/shortcodes.php</code> та <code>includes/archive-properties.php</code>.
            </div>

            <h3>Типові сценарії використання</h3>

            <table class="recrm-dashboard-table">
                <thead>
                    <tr>
                        <th style="width: 220px;">Сторінка</th>
                        <th>Що вставляти</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Сторінка “Нерухомість”</td>
                        <td><code>[recrm_properties]</code> — повний список усіх об’єктів.</td>
                    </tr>
                    <tr>
                        <td>Головна сторінка</td>
                        <td>Короткий блок нерухомості. Якщо у шорткоді реалізовано параметр ліміту — можна використовувати скорочений варіант з обмеженням кількості.</td>
                    </tr>
                    <tr>
                        <td>Тематичні сторінки</td>
                        <td>Продаж, оренда, квартири, будинки — окремими шорткодами або параметрами, якщо така логіка вже є в коді плагіна.</td>
                    </tr>
                </tbody>
            </table>

            <h3>Що перевірити після імпорту</h3>
            <ol class="recrm-dashboard-list">
                <li>Чи правильно підтягнулися фото об’єкта.</li>
                <li>Чи відображаються ціна, площа, кімнати, адреса та інші характеристики.</li>
                <li>Чи відкривається сторінка окремого об’єкта без помилок.</li>
                <li>Чи працює сторінка списку нерухомості з потрібним шорткодом.</li>
                <li>Чи заповнені контакти та форма в розділі “Налаштування”.</li>
            </ol>
        </div>
    </div>
    <?php
}

    public static function boot() {
        static $booted = false;

        if ( $booted ) {
            return;
        }

        $booted = true;
        new self();
    }
}

add_action( 'admin_enqueue_scripts', 'recrm_seo_admin_assets' );

function recrm_seo_admin_assets( $hook ) {
	$allowed_hooks = array( 'post.php', 'post-new.php', 're-crm_page_recrm-seo', 're-crm_page_recrm-settings' );

	if ( ! in_array( $hook, $allowed_hooks, true ) && false === strpos( $hook, 'recrm-seo' ) ) {
		return;
	}

	wp_enqueue_media();

	$settings = recrm_get_seo_settings();

	wp_enqueue_script(
		'recrm-seo-admin',
		RECRM_XML_IMPORT_URL . 'assets/js/seo-admin.js',
		array(),
		RECRM_XML_IMPORT_VERSION,
		true
	);

	wp_localize_script(
		'recrm-seo-admin',
		'recrmSeoData',
		array(
			'brand'               => $settings['brand_name'],
			'city'                => $settings['default_city'],
			'titleTemplate'       => $settings['title_template'],
			'descriptionTemplate' => $settings['description_template'],
			'autoKeyword'         => '1' === $settings['auto_keyword'],
			'autoSlug'            => '1' === $settings['auto_slug'],
			'analysisEnabled'     => '1' === $settings['analysis_enabled'],
			'siteUrl'             => home_url( '/' ),
		)
	);
}
