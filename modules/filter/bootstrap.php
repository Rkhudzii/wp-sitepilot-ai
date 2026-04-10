<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Працює тільки разом з модулем property.
if ( ! function_exists( 'recrm_register_property_post_type' ) ) {
    return;
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/query.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/render-results.php';
require_once __DIR__ . '/render-form.php';
require_once __DIR__ . '/ajax.php';
require_once __DIR__ . '/shortcode.php';

/**
 * Сумісність зі старим підключенням файлу archive-properties.php
 */
require_once __DIR__ . '/archive-properties.php';

add_action('wp_head', function () {
    ?>
    <script>
        (function () {
            try {
                var savedY = sessionStorage.getItem('recrmFilterScrollY');
                if (savedY === null) {
                    return;
                }

                if ('scrollRestoration' in history) {
                    history.scrollRestoration = 'manual';
                }

                var y = parseInt(savedY, 10) || 0;

                window.scrollTo(0, y);

                document.addEventListener('DOMContentLoaded', function () {
                    window.scrollTo(0, y);
                }, { once: true });

                window.addEventListener('load', function () {
                    window.scrollTo(0, y);
                    sessionStorage.removeItem('recrmFilterScrollY');
                }, { once: true });
            } catch (e) {}
        })();
    </script>
    <?php
}, 1);