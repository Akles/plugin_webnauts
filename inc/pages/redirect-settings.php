<?php

function plugin_redirect_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo __('Redirect Settings', 'checkintravel'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('redirect_settings_group');
            do_settings_sections('redirect_settings_group');
            ?>
            <?php plugin_render_unauthorized_redirect(); ?>
            <?php plugin_render_no_subscription_redirect(); ?>
            <?php plugin_render_exceptions(); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function plugin_render_unauthorized_redirect() {
    $unauthorized_redirect = get_option('unauthorized_redirect');
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __('Unauthorized Access Redirect', 'checkintravel'); ?></th>
            <td>
                <?php wp_dropdown_pages([
                    'name' => 'unauthorized_redirect',
                    'echo' => 1,
                    'show_option_none' => __('&mdash; Select &mdash;', 'checkintravel'),
                    'option_none_value' => '',
                    'selected' => $unauthorized_redirect
                ]); ?>
            </td>
        </tr>
    </table>
    <?php
}

function plugin_render_no_subscription_redirect() {
    $no_subscription_redirect = get_option('no_subscription_redirect');
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __('Authorized Access without Subscription Redirect', 'checkintravel'); ?></th>
            <td>
                <?php wp_dropdown_pages([
                    'name' => 'no_subscription_redirect',
                    'echo' => 1,
                    'show_option_none' => __('&mdash; Select &mdash;', 'checkintravel'),
                    'option_none_value' => '',
                    'selected' => $no_subscription_redirect
                ]); ?>
            </td>
        </tr>
    </table>
    <?php
}

function plugin_render_exceptions() {
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __('Exception Table', 'checkintravel'); ?></th>
            <td>
                <?php plugin_render_exceptions_table(); ?>
                <?php plugin_render_exceptions_selector(); ?>
            </td>
        </tr>
    </table>
    <?php
}

function plugin_register_settings()
{
    register_setting('redirect_settings_group', 'unauthorized_redirect', 'intval');
    register_setting('redirect_settings_group', 'no_subscription_redirect', 'intval');
    register_setting('redirect_settings_group', 'exceptions', 'plugin_sanitize_exceptions');
}

add_action('admin_init', 'plugin_register_settings');

function plugin_sanitize_exceptions($input)
{
    if (is_array($input)) {
        return array_map('intval', $input);
    }
    return [];
}

add_action('wp_ajax_get_posts_by_post_type', 'plugin_get_posts_by_post_type');

function plugin_get_posts_by_post_type()
{
    $post_type = sanitize_text_field($_POST['post_type']);
    $posts = get_posts([
        'post_type' => $post_type,
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish'
    ]);

    echo json_encode($posts);

    wp_die();
}

function plugin_enqueue_admin_scripts($hook)
{
    if ('plugin_page_plugin_redirect_settings' !== $hook) {
        return;
    }
    wp_enqueue_script('plugin-admin-scripts-redirect', plugin_dir_url(__DIR__) . 'src/js/redirect.js', ['jquery'], '1.0.0', true);
    wp_localize_script('plugin-admin-scripts-redirect', 'ajax_object', [
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
}

add_action('admin_enqueue_scripts', 'plugin_enqueue_admin_scripts');

// Добавление метабокса на страницу редактирования поста
function plugin_add_meta_box() {
    add_meta_box(
        'plugin-access-meta-box',
        __('Access Settings', 'checkintravel'),
        'plugin_render_access_meta_box',
        'post',
        'side',
        'default'
    );
}

add_action('add_meta_boxes', 'plugin_add_meta_box');

// Отрисовка метабокса
function plugin_render_access_meta_box($post) {
    wp_nonce_field('plugin_access_meta_box', 'plugin_access_meta_box_nonce');
    $restricted_access = get_post_meta($post->ID, '_plugin_restricted_access', true);
    if (!$restricted_access) {
        $restricted_access = 'on';
    }
    ?>
    <p>
        <input type="checkbox" id="plugin-restricted-access" name="plugin_restricted_access" <?php checked($restricted_access, 'on'); ?>>
        <label for="plugin-restricted-access"><?php echo __('Restricted Access', 'checkintravel'); ?></label>
    </p>
    <?php
}

// Сохранение метаданных при сохранении поста
function plugin_save_access_meta_box($post_id) {
    if (!isset($_POST['plugin_access_meta_box_nonce']) || !wp_verify_nonce($_POST['plugin_access_meta_box_nonce'], 'plugin_access_meta_box')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['plugin_restricted_access'])) {
        $restricted_access = 'on';
    } else {
        $restricted_access = 'off';
    }
    update_post_meta($post_id, '_plugin_restricted_access', $restricted_access);
}

add_action('save_post', 'plugin_save_access_meta_box');

// Получение списка исключений для каждого поста
function plugin_get_exceptions() {
    $exceptions = array();
    $posts = get_posts(array('post_type' => 'post', 'posts_per_page' => -1));
    foreach ($posts as $post) {
        $restricted_access = get_post_meta($post->ID, '_plugin_restricted_access', true);
        if ($restricted_access && $restricted_access == 'on') {
            $exceptions[] = $post->ID;
        }
    }
    return $exceptions;
}

function plugin_is_exception($post_id) {
    $restricted_access = get_post_meta($post_id, '_plugin_restricted_access', true);
    return ($restricted_access == 'on');
}
