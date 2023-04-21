<?php
/*
Plugin Name: Webnauts Pro Plugin
Plugin URI: https://webnauts.pro/plugin/
Description: Плагин для ограничения доступа к контенту на вашем сайте
Version: 1.0
Author: Webnauts Pro
Author URI: https://webnauts.pro/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

function custom_plugin_activate()
{
    // пустая функция
}

register_activation_hook(__FILE__, 'custom_plugin_activate');

function custom_plugin_deactivate()
{
    // пустая функция
}

register_deactivation_hook(__FILE__, 'custom_plugin_deactivate');


function custom_plugin_settings_page()
{
    add_menu_page(
        'Plugin Settings',
        'Plugin',
        'manage_options',
        'custom-plugin-settings',
        'custom_plugin_settings_page_callback',
    );
}

add_action('admin_menu', 'custom_plugin_settings_page');

function custom_plugin_settings_page_callback()
{
    $post_types = get_post_types();
    $selected_post_types = get_option('custom_redirect_post_types', array());

    ?>
    <div class="wrap">
        <h2>Custom Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('custom-plugin-settings');
            do_settings_sections('custom-plugin-settings');
            echo '<p><label for="custom_redirect_page">' . __('Страница, на которую будет редиректиться пользователь:') . '</label></p>';
            echo '<p>' . wp_dropdown_pages(array('name' => 'custom_redirect_page', 'selected' => get_option('custom_redirect_page'), 'echo' => 0)) . '</p>';
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="custom_redirect_page">Для каких типов записей включить настройки перенаправления:</label></th>
                    <td>
                        <?php
                        $count = 0;
                        $col_count = 4;
                        $col_width = 100 / $col_count;
                        foreach ($post_types as $post_type) {
                            if ($count % $col_count == 0) {
                                echo '<div style="display:inline-block; width:calc(' . $col_width . '% - 20px); margin-right:20px; margin-bottom: 10px;">';
                            }
                            ?>
                            <label>
                                <input type="checkbox"
                                       name="custom_redirect_post_types[]"
                                       value="<?php echo $post_type; ?>"
                                    <?php if (in_array($post_type, $selected_post_types)) {
                                        echo 'checked';
                                    } ?>>
                                <?php echo $post_type; ?>
                            </label>
                            <br>
                            <?php
                            if ($count % $col_count == $col_count - 1) {
                                echo '</div>';
                            }
                            $count++;
                        }
                        if ($count % $col_count != 0) {
                            echo '</div>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function custom_plugin_register_settings() {
    register_setting('custom-plugin-settings', 'custom_redirect_post_types');
}
add_action('admin_init', 'custom_plugin_register_settings');

function custom_plugin_add_meta_box()
{
    $post_types = get_option('custom_redirect_post_types', array());

    foreach ($post_types as $post_type) {
        add_meta_box(
            'custom_redirect_box',
            'Настройки перенаправления',
            'custom_redirect_box_callback',
            $post_type,
            'side',
            'default'
        );
    }
}

function custom_redirect_box_callback($post)
{
    $custom_redirect_enabled = get_post_meta($post->ID, '_custom_redirect_enabled', true);

    wp_nonce_field('custom_redirect_box_nonce', 'custom_redirect_box_nonce');
    ?>
    <p>
        <label for="custom-redirect-enabled">Разрешить просмотр без авторизации</label>
        <br>
        <input type="checkbox"
               id="custom-redirect-enabled"
               name="custom_redirect_enabled"
               value="1"
        <?php if ($custom_redirect_enabled) {
            echo 'checked';
        } ?>>
    </p>
    <?php
}

function custom_plugin_save_post_meta($post_id)
{
    if (!isset($_POST['custom_redirect_box_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['custom_redirect_box_nonce'], 'custom_redirect_box_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['custom_redirect_enabled'])) {
        update_post_meta($post_id, '_custom_redirect_enabled', true);
    } else {
        delete_post_meta($post_id, '_custom_redirect_enabled');
    }
}
add_action('add_meta_boxes', 'custom_plugin_add_meta_box');
add_action('save_post', 'custom_plugin_save_post_meta');
function custom_redirect_users()
{
    $redirect_page_id = get_option('custom_redirect_page');
    $redirect_post_types = get_option('custom_redirect_post_types', array());

    if (!is_user_logged_in() && !is_front_page()) {
        if (!empty($redirect_post_types)) {
            $post = get_post();
            if ($post) {
                $post_type = $post->post_type;
                if (in_array($post_type, $redirect_post_types)) {
                    $custom_redirect_enabled = get_post_meta($post->ID, '_custom_redirect_enabled', true);
                    if (!$custom_redirect_enabled) {
                        wp_redirect(wp_login_url(get_permalink()));
                        exit;
                    }
                }
            }
        }

        if (!is_page($redirect_page_id)) {
            if (!empty($redirect_page_id)) {
                wp_redirect(get_permalink($redirect_page_id));
            } else {
                wp_redirect(home_url());
            }
            exit;
        }
    }
}

add_action('template_redirect', 'custom_redirect_users');


