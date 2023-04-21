<?php

function plugin_redirect_settings_page()
{
    ?>
    <div class="wrap">
        <h1><?php echo __('Redirect Settings', 'checkintravel'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('redirect_settings_group');
            do_settings_sections('redirect_settings_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo __('Unauthorized Access Redirect', 'checkintravel'); ?></th>
                    <td>
                        <?php
                        $unauthorized_redirect = get_option('unauthorized_redirect');
                        wp_dropdown_pages([
                            'name' => 'unauthorized_redirect',
                            'echo' => 1,
                            'show_option_none' => __('&mdash; Select &mdash;', 'checkintravel'),
                            'option_none_value' => '',
                            'selected' => $unauthorized_redirect
                        ]);
                        ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __('Authorized Access without Subscription Redirect', 'checkintravel'); ?></th>
                    <td>
                        <?php
                        $no_subscription_redirect = get_option('no_subscription_redirect');
                        wp_dropdown_pages([
                            'name' => 'no_subscription_redirect',
                            'echo' => 1,
                            'show_option_none' => __('&mdash; Select &mdash;', 'checkintravel'),
                            'option_none_value' => '',
                            'selected' => $no_subscription_redirect
                        ]);
                        ?>
                    </td>
                </tr>
            </table>

            <table id="exception-table" class="wp-list-table widefat striped">
                <thead>
                <tr>
                    <th><?php echo __('Post Type', 'checkintravel'); ?></th>
                    <th><?php echo __('Post', 'checkintravel'); ?></th>
                    <th><?php echo __('Actions', 'checkintravel'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $exceptions = get_option('exceptions');
                if ($exceptions) {
                    foreach ($exceptions as $exception) {
                        ?>
                        <tr>
                            <td>
                                <input type="hidden" name="exceptions[]" value="<?php echo esc_attr($exception); ?>">
                                <?php echo get_post_type_object(get_post_type($exception))->labels->singular_name; ?>
                            </td>
                            <td>
                                <?php echo get_the_title($exception); ?>
                            </td>
                            <td>
                                <button class="button button-secondary remove-row"><?php echo __('Remove', 'checkintravel'); ?></button>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
            <button class="button button-primary"
                    id="add-exception"><?php echo __('Add Exception', 'checkintravel'); ?></button>
            <div id="exception-selector" style="display:none;">
                <select id="post-type-selector">
                    <option value=""><?php echo __('&mdash; Select Post Type &mdash;', 'checkintravel'); ?></option>
                    <?php
                    $post_types = get_post_types(['public' => true], 'objects');
                    foreach ($post_types as $post_type) {
                        echo '<option value="' . $post_type->name . '">' . $post_type->labels->singular_name . '</option>';
                    }
                    ?>
                </select>
                <select id="post-selector" style="display:none;">
                    <option value=""><?php echo __('&mdash; Select Post &mdash;', 'checkintravel'); ?></option>
                </select>
                <button class="button button-primary" id="confirm-selection"
                        style="display:none;"><?php echo __('Add', 'checkintravel'); ?></button>
            </div>
            <?php submit_button(); ?>
        </form>
    </div>
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


function plugin_add_meta_box() {
    $post_types = get_post_types(['public' => true], 'names');
    foreach ($post_types as $post_type) {
        add_meta_box(
            'plugin-access-meta-box',
            __('Access Settings', 'checkintravel'),
            'plugin_render_access_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}

add_action('add_meta_boxes', 'plugin_add_meta_box');

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
