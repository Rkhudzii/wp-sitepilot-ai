<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'recrm_chatgpt_register_admin_page', 20 );

function recrm_chatgpt_register_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    add_submenu_page(
        'recrm-main',
        'ChatGPT',
        'ChatGPT',
        'manage_options',
        'recrm-chatgpt',
        'recrm_chatgpt_render_admin_page'
    );
}

function recrm_chatgpt_render_admin_page() {
    $settings = recrm_chatgpt_get_settings();
    ?>
    <div class="wrap">
        <h1>ChatGPT</h1>
        <p>Модуль уже підключений до системи модулів. Тут можна зберегти API key і базові параметри для майбутніх AI-функцій.</p>

        <div style="max-width: 960px; margin-top: 18px; display:grid; gap:18px;">
            <div style="padding:16px 18px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:16px; color:#1d4ed8;">
                Цей модуль уже видно у WordPress як окремий модуль. Для оновлення з GitHub у репозиторії має існувати папка <code>modules/chatgpt</code>.
            </div>

            <form method="post" action="options.php" style="background:#fff; border:1px solid #e5e7eb; border-radius:18px; padding:22px;">
                <?php settings_fields( 'recrm_chatgpt_settings_group' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="recrm-chatgpt-api-key">API key</label></th>
                        <td>
                            <input id="recrm-chatgpt-api-key" type="password" name="recrm_chatgpt_settings[api_key]" value="<?php echo esc_attr( $settings['api_key'] ); ?>" class="regular-text" autocomplete="off">
                            <p class="description">Ключ зберігається локально в налаштуваннях WordPress.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="recrm-chatgpt-model">Модель</label></th>
                        <td>
                            <input id="recrm-chatgpt-model" type="text" name="recrm_chatgpt_settings[model]" value="<?php echo esc_attr( $settings['model'] ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="recrm-chatgpt-tone">Тон</label></th>
                        <td>
                            <input id="recrm-chatgpt-tone" type="text" name="recrm_chatgpt_settings[default_tone]" value="<?php echo esc_attr( $settings['default_tone'] ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="recrm-chatgpt-system-prompt">System prompt</label></th>
                        <td>
                            <textarea id="recrm-chatgpt-system-prompt" name="recrm_chatgpt_settings[system_prompt]" rows="6" class="large-text"><?php echo esc_textarea( $settings['system_prompt'] ); ?></textarea>
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Зберегти налаштування ChatGPT' ); ?>
            </form>
        </div>
    </div>
    <?php
}
