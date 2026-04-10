<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function recrm_handle_import_settings_save() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_POST['recrm_save_import_settings'] ) ) {
        return;
    }

    check_admin_referer( 'recrm_save_import_settings_action', 'recrm_save_import_settings_nonce' );

    $settings = array(
        'xml_url'         => isset( $_POST['xml_url'] ) ? esc_url_raw( wp_unslash( $_POST['xml_url'] ) ) : '',
        'auto_import'     => ! empty( $_POST['auto_import'] ) ? '1' : '0',
        'import_interval' => isset( $_POST['import_interval'] ) ? sanitize_key( wp_unslash( $_POST['import_interval'] ) ) : 'twicedaily',
        'batch_size'      => isset( $_POST['batch_size'] ) ? absint( wp_unslash( $_POST['batch_size'] ) ) : 3,
    );

    recrm_update_import_settings( $settings );
    recrm_sync_import_schedule();

    add_settings_error( 'recrm_import_settings', 'recrm_import_settings_saved', 'Налаштування імпорту збережено.', 'updated' );
}
add_action( 'admin_init', 'recrm_handle_import_settings_save' );

function recrm_xml_import_admin_page() {
    $ajax_nonce  = wp_create_nonce( 'recrm_import_nonce' );
    $settings    = recrm_get_import_settings();
    $last_import = recrm_get_last_import_data();
    $intervals   = recrm_import_intervals();
    $next_run    = wp_next_scheduled( 'recrm_xml_import_cron' );

    settings_errors( 'recrm_import_settings' );
    ?>
    <div class="wrap recrm-admin-page">
        <style>
            .recrm-admin-page * { box-sizing:border-box; }
            .recrm-admin-page .recrm-admin-hero { margin:18px 0 22px; padding:24px 26px; border-radius:22px; background:linear-gradient(135deg,#052e16 0%,#15803d 100%); color:#fff; box-shadow:0 18px 36px rgba(21,128,61,.16); }
            .recrm-admin-page .recrm-admin-hero h1 { margin:0 0 8px; color:#fff; }
            .recrm-admin-page .recrm-admin-hero p { margin:0; max-width:900px; color:rgba(255,255,255,.92); }
            .recrm-admin-page .recrm-admin-grid { display:grid; grid-template-columns:minmax(320px,760px) minmax(280px,1fr); gap:24px; margin-top:20px; }
            .recrm-admin-page .recrm-card { background:#fff; border:1px solid #e5e7eb; border-radius:20px; padding:20px; box-shadow:0 10px 30px rgba(15,23,42,.05); }
            .recrm-admin-page .recrm-card h2 { margin-top:0; }
            .recrm-admin-page .recrm-kpis { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-top:14px; }
            .recrm-admin-page .recrm-kpi { padding:14px; border:1px solid #e5e7eb; border-radius:16px; background:#f8fafc; }
            .recrm-admin-page .recrm-kpi-label { font-size:12px; text-transform:uppercase; color:#64748b; letter-spacing:.05em; }
            .recrm-admin-page .recrm-kpi-value { margin-top:6px; font-size:20px; font-weight:700; }
            @media (max-width: 1080px) { .recrm-admin-page .recrm-admin-grid { grid-template-columns:1fr; } }
        </style>
        <div class="recrm-admin-hero">
            <h1>Імпорт XML</h1>
            <p>На цій сторінці зібрані параметри джерела, статус останнього запуску та ручний запуск імпорту. Ліворуч — керування, праворуч — стан системи.</p>
        </div>
        <h1>Імпорт XML</h1>

        <div id="recrm-import-notice"></div>

        <div class="recrm-admin-grid">
            <div class="recrm-card">
                <h2 style="margin-top:0;">Налаштування джерела</h2>

                <form method="post">
                    <?php wp_nonce_field( 'recrm_save_import_settings_action', 'recrm_save_import_settings_nonce' ); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="xml_url">XML URL</label>
                            </th>
                            <td>
                                <input
                                    type="url"
                                    name="xml_url"
                                    id="xml_url"
                                    class="regular-text"
                                    value="<?php echo esc_attr( $settings['xml_url'] ); ?>"
                                    placeholder="https://site.com/feed.xml"
                                >
                                <p class="description">Це посилання збережеться, і не треба буде вставляти його щоразу заново.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_import">Автооновлення</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_import" id="auto_import" value="1" <?php checked( $settings['auto_import'], '1' ); ?>>
                                    Увімкнути автоматичний імпорт
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="import_interval">Інтервал</label>
                            </th>
                            <td>
                                <select name="import_interval" id="import_interval">
                                    <?php foreach ( $intervals as $interval_key => $interval_label ) : ?>
                                        <option value="<?php echo esc_attr( $interval_key ); ?>" <?php selected( $settings['import_interval'], $interval_key ); ?>>
                                            <?php echo esc_html( $interval_label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="batch_size">Розмір пакета</label>
                            </th>
                            <td>
                                <input type="number" min="1" max="20" name="batch_size" id="batch_size" value="<?php echo esc_attr( (int) $settings['batch_size'] ); ?>">
                                <p class="description">Для слабкого хостингу краще 2–5.</p>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" name="recrm_save_import_settings" value="1" class="button button-primary">
                            Зберегти налаштування
                        </button>
                    </p>
                </form>
            </div>

            <div class="recrm-card">
                <h2 style="margin-top:0;">Статус імпорту</h2>

                <table class="widefat striped" style="max-width:100%;">
                    <tbody>
                        <tr>
                            <td style="width:220px;"><strong>Останній запуск</strong></td>
                            <td><?php echo ! empty( $last_import['time'] ) ? esc_html( $last_import['time'] ) : 'Ще не запускався'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Статус</strong></td>
                            <td><?php echo ! empty( $last_import['status'] ) ? esc_html( $last_import['status'] ) : '—'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Повідомлення</strong></td>
                            <td><?php echo ! empty( $last_import['message'] ) ? esc_html( $last_import['message'] ) : '—'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Створено</strong></td>
                            <td><?php echo isset( $last_import['created'] ) ? (int) $last_import['created'] : 0; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Оновлено</strong></td>
                            <td><?php echo isset( $last_import['updated'] ) ? (int) $last_import['updated'] : 0; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Пропущено</strong></td>
                            <td><?php echo isset( $last_import['skipped'] ) ? (int) $last_import['skipped'] : 0; ?></td>
                        </tr>                      
                        <tr>
                            <td><strong>У кошик</strong></td>
                            <td><?php echo isset( $last_import['trashed'] ) ? (int) $last_import['trashed'] : 0; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Помилки</strong></td>
                            <td><?php echo isset( $last_import['failed'] ) ? (int) $last_import['failed'] : 0; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Джерело</strong></td>
                            <td><?php echo ! empty( $last_import['source'] ) ? esc_html( $last_import['source'] ) : '—'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Наступний автоімпорт</strong></td>
                            <td>
                                <?php
                                if ( ! empty( $settings['auto_import'] ) && $next_run ) {
                                    echo esc_html( wp_date( 'Y-m-d H:i:s', $next_run ) );
                                } elseif ( ! empty( $settings['auto_import'] ) ) {
                                    echo 'Очікує планування';
                                } else {
                                    echo 'Вимкнено';
                                }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="recrm-card" style="margin-top:24px; max-width:760px;">
            <h2 style="margin-top:0;">Ручний імпорт</h2>

            <form id="recrm-import-form" method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="xml_url_manual">XML URL</label>
                        </th>
                        <td>
                            <input
                                type="url"
                                name="xml_url_manual"
                                id="xml_url_manual"
                                class="regular-text"
                                value="<?php echo esc_attr( $settings['xml_url'] ); ?>"
                                placeholder="https://site.com/feed.xml"
                            >
                            <p class="description">Можеш залишити збережене посилання або вставити інше разово.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="xml_file">XML файл</label>
                        </th>
                        <td>
                            <input type="file" name="xml_file" id="xml_file" accept=".xml,text/xml,application/xml">
                            <p class="description">Файл не зберігається, тільки імпортується один раз.</p>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" id="recrm-import-submit" class="button button-primary">Імпортувати зараз</button>
                </p>
            </form>

            <div id="recrm-import-progress-wrap" style="display:none; margin-top:20px; max-width:760px;">
                <div style="height:22px; background:#e5e7eb; border-radius:999px; overflow:hidden;">
                    <div id="recrm-import-progress-bar" style="height:22px; width:0%; background:linear-gradient(90deg,#16a34a,#22c55e); transition:width .25s ease;"></div>
                </div>

                <div id="recrm-import-progress-text" style="margin-top:10px; font-weight:700; font-size:14px;">0%</div>
                <div id="recrm-import-progress-meta" style="margin-top:6px; color:#475569;">Імпорт ще не запущено</div>
            </div>
        </div>

        <div style="margin-top:32px; max-width:900px; background:#fff; border:1px solid #dcdcde; border-radius:10px; padding:20px;">
            <h2 style="margin-top:0;">Міні документація</h2>
            <table class="widefat striped" style="margin-top:16px;">
                <thead>
                    <tr>
                        <th style="width:220px;">Шорткод</th>
                        <th>Опис</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[recrm_properties]</code></td>
                        <td>Виводить архів / список об’єктів нерухомості з фільтрами плагіна.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('recrm-import-form');
        const submitButton = document.getElementById('recrm-import-submit');
        const noticeWrap = document.getElementById('recrm-import-notice');
        const progressWrap = document.getElementById('recrm-import-progress-wrap');
        const progressBar = document.getElementById('recrm-import-progress-bar');
        const progressText = document.getElementById('recrm-import-progress-text');
        const progressMeta = document.getElementById('recrm-import-progress-meta');
        const xmlUrlInput = document.getElementById('xml_url_manual');
        const xmlFileInput = document.getElementById('xml_file');
        const batchSize = <?php echo (int) $settings['batch_size']; ?>;

        if (!form || !submitButton) {
            return;
        }

        let isRunning = false;

        function setNotice(type, message) {
            noticeWrap.innerHTML = '<div class="notice notice-' + type + '"><p>' + message + '</p></div>';
        }

        function clearNotice() {
            noticeWrap.innerHTML = '';
        }

        function setProgress(percent, text, meta) {
            progressWrap.style.display = 'block';
            progressBar.style.width = percent + '%';
            progressText.textContent = text;
            progressMeta.textContent = meta;
        }

        function setRunningState(running) {
            isRunning = running;
            submitButton.disabled = running;
            submitButton.textContent = running ? 'Імпорт виконується...' : 'Імпортувати зараз';
            xmlUrlInput.disabled = running;
            xmlFileInput.disabled = running;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (isRunning) {
                return;
            }

            clearNotice();

            const xmlUrl = (xmlUrlInput.value || '').trim();
            const hasFile = xmlFileInput.files && xmlFileInput.files.length > 0;

            if (!xmlUrl && !hasFile) {
                setNotice('error', 'Вкажи URL XML або вибери XML-файл.');
                return;
            }

            setRunningState(true);
            setProgress(0, '0%', 'Підготовка імпорту...');

            try {
                const startFormData = new FormData();
                startFormData.append('action', 'recrm_start_import');
                startFormData.append('nonce', '<?php echo esc_js( $ajax_nonce ); ?>');

                if (xmlUrl) {
                    startFormData.append('xml_url', xmlUrl);
                }

                if (hasFile) {
                    startFormData.append('xml_file', xmlFileInput.files[0]);
                }

                const startResponse = await fetch(ajaxurl, { method: 'POST', body: startFormData });
                const startData = await startResponse.json();

                if (!startData.success) {
                    throw new Error(startData.data && startData.data.message ? startData.data.message : 'Не вдалося стартувати імпорт.');
                }

                const sessionKey = startData.data.session_key;
                const total = parseInt(startData.data.total || 0, 10);

                setProgress(0, '0%', 'Знайдено обʼєктів: ' + total + '. Починаємо імпорт...');
                await processBatch(sessionKey);
            } catch (error) {
                setNotice('error', error.message);
                setRunningState(false);
            }
        });

        async function processBatch(sessionKey) {
            const batchFormData = new FormData();
            batchFormData.append('action', 'recrm_process_import_batch');
            batchFormData.append('nonce', '<?php echo esc_js( $ajax_nonce ); ?>');
            batchFormData.append('session_key', sessionKey);
            batchFormData.append('batch_size', String(batchSize || 3));

            const response = await fetch(ajaxurl, { method: 'POST', body: batchFormData });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data && data.data.message ? data.data.message : 'Помилка пакету імпорту.');
            }

            const result = data.data;

            setProgress(
                result.percent,
                result.percent + '%',
                'Оброблено: ' + result.current + ' / ' + result.total +
                ' | Успішно: ' + result.done +
                ' | Помилки: ' + result.failed
            );

            if (!result.finished) {
                await processBatch(sessionKey);
                return;
            }

            setProgress(100, '100%', 'Імпорт завершено. Успішно: ' + result.done + ' | Помилки: ' + result.failed);
            setNotice('success', 'Імпорт завершено. Успішно: ' + result.done + '. Помилки: ' + result.failed + '.');
            setRunningState(false);
            window.setTimeout(function () {
                window.location.reload();
            }, 1200);
        }
    });
    </script>
    <?php
}
