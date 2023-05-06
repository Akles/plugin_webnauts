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
            <?php plugin_render_unauthorized_redirect(); ?>
            <?php plugin_render_no_subscription_redirect(); ?>
            <?php plugin_render_login_page(); ?>
            <?php plugin_render_post_types_exceptions(); ?>
            <?php plugin_render_exceptions(); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function plugin_render_login_page() {
    $login_page = get_option('login_page', 0);
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __('Login Page', 'checkintravel'); ?></th>
            <td>
                <?php wp_dropdown_pages([
                    'name' => 'login_page',
                    'echo' => 1,
                    'show_option_none' => __('&mdash; Select &mdash;', 'checkintravel'),
                    'option_none_value' => '',
                    'selected' => $login_page
                ]); ?>
            </td>
        </tr>
    </table>
    <?php
}

function plugin_render_unauthorized_redirect()
{
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

function plugin_render_no_subscription_redirect()
{
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



function plugin_render_exceptions()
{
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


function plugin_render_exceptions_table()
{
    ?>
    <div id="exceptions-table-wrapper">
        <input type="text" id="exceptions-search" placeholder="<?php echo __('Search', 'checkintravel'); ?>">
        <table id="exceptions-table" class="widefat fixed striped">
            <thead>
            <tr>
                <th scope="col" class="sortable"><?php echo __('Title', 'checkintravel'); ?></th>
                <th scope="col" class="sortable"><?php echo __('Date Created', 'checkintravel'); ?></th>
                <th scope="col" class="sortable"><?php echo __('Post Type', 'checkintravel'); ?></th>
                <th scope="col" class="sortable"><?php echo __('Categories', 'checkintravel'); ?></th>
                <th scope="col"><?php echo __('Actions', 'checkintravel'); ?></th>
            </tr>
            </thead>
            <tbody id="exceptions-table-body">
            <!-- AJAX will populate this tbody with the appropriate rows -->
            </tbody>
            <tfoot>
            <tr>
                <th scope="col" class="sortable"><?php echo __('Title', 'checkintravel'); ?></th>
                <th scope="col" class="sortable"><?php echo __('Date Created', 'checkintravel'); ?></th>
                <th scope="col" class="sortable"><?php echo __('Post Type', 'checkintravel'); ?></th>
                <th scope="col" class="sortable"><?php echo __('Categories', 'checkintravel'); ?></th>
                <th scope="col"><?php echo __('Actions', 'checkintravel'); ?></th>
            </tr>
            </tfoot>
        </table>
        <div id="exceptions-pagination">
            <!-- AJAX will generate and manage the pagination here -->
        </div>
    </div>
    <?php
}

function plugin_render_exceptions_selector()
{
    $post_types = get_post_types(['public' => true], 'objects');
    ?>
    <div id="exceptions-selector-wrapper">
        <label for="exceptions-post-type"><?php echo __('Filter by Post Type:', 'checkintravel'); ?></label>
        <select id="exceptions-post-type">
            <option value=""><?php echo __('All', 'checkintravel'); ?></option>
            <?php foreach ($post_types as $post_type) : ?>
                <option value="<?php echo esc_attr($post_type->name); ?>"><?php echo esc_html($post_type->labels->singular_name); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

function plugin_ajax_get_posts()
{
    check_ajax_referer('plugin_get_posts', 'security');

    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
    $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
    $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

    $args = [
        'post_type' => $post_type ? $post_type : 'any',
        'posts_per_page' => 100,
        'paged' => $paged,
        'meta_key' => 'exception_redirection',
        'orderby' => $orderby,
        'order' => $order,
    ];

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);

    $response = [
        'posts' => [],
        'total_posts' => (int)$query->found_posts,
        'max_num_pages' => (int)$query->max_num_pages,
    ];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_categories = wp_get_post_categories(get_the_ID(), ['fields' => 'names']);
            $post_type_object = get_post_type_object(get_post_type());

            $response['posts'][] = [
                'ID' => get_the_ID(),
                'title' => get_the_title(),
                'edit_link' => get_edit_post_link(),
                'date' => get_the_date(),
                'post_type' => $post_type_object->labels->singular_name,
                'categories' => implode(', ', $post_categories),
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success($response);
}

add_action('wp_ajax_plugin_get_posts', 'plugin_ajax_get_posts');


function plugin_ajax_remove_exception()
{
    check_ajax_referer('plugin_remove_exception', 'security');

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

    if ($post_id) {
        delete_post_meta($post_id, 'exception_redirection');
        wp_send_json_success(['message' => __('Post removed from exceptions.', 'checkintravel')]);
    } else {
        wp_send_json_error(['message' => __('Invalid post ID.', 'checkintravel')]);
    }
}

add_action('wp_ajax_plugin_remove_exception', 'plugin_ajax_remove_exception');

function plugin_enqueue_admin_scripts($hook)
{
    if ($hook === 'plugin_page_plugin_redirect_settings') { // The correct hook for your settings page
        // Register and enqueue the main JavaScript file
        wp_register_script('plugin-redirect-js', plugin_dir_url(__DIR__) . 'src/js/redirect.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('plugin-redirect-js');

        // Localize the script to pass data to the JavaScript file
        wp_localize_script('plugin-redirect-js', 'pluginRedirectData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'getPostsNonce' => wp_create_nonce('plugin_get_posts'),
            'removeExceptionNonce' => wp_create_nonce('plugin_remove_exception'),
        ]);

    }
}

add_action('admin_enqueue_scripts', 'plugin_enqueue_admin_scripts');


function plugin_save_exceptions_meta_box_data($post_id)
{
    // Verify the nonce field
    if (!isset($_POST['plugin_exceptions_nonce']) || !wp_verify_nonce($_POST['plugin_exceptions_nonce'], 'plugin_save_exceptions')) {
        return;
    }

    // Check if the current user can edit the post
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save or remove the exception data
    if (isset($_POST['plugin_exceptions_checkbox']) && $_POST['plugin_exceptions_checkbox'] === 'on') {
        update_post_meta($post_id, 'exception_redirection', 1);
    } else {
        delete_post_meta($post_id, 'exception_redirection');
    }
}

add_action('save_post', 'plugin_save_exceptions_meta_box_data');

function plugin_exceptions_meta_box_callback($post)
{
    // Add a nonce field for security
    wp_nonce_field('plugin_save_exceptions', 'plugin_exceptions_nonce');

    // Check if the post is in exceptions
    $is_exception = get_post_meta($post->ID, 'exception_redirection', true);

    // Render the checkbox
    ?>
    <label for="plugin_exceptions_checkbox">
        <input type="checkbox" name="plugin_exceptions_checkbox"
               id="plugin_exceptions_checkbox" <?php checked($is_exception); ?>>
        <?php _e('Mark this post as an exception', 'checkintravel'); ?>
    </label>
    <?php
}

function plugin_add_exceptions_meta_box()
{
    $screens = get_option('post_types_exceptions', []); // Get the selected post types from settings
    foreach ($screens as $screen) {
        add_meta_box(
            'plugin_exceptions_meta_box',
            __('Add to Exceptions', 'checkintravel'),
            'plugin_exceptions_meta_box_callback',
            $screen,
            'side',
            'default'
        );
    }
}

add_action('add_meta_boxes', 'plugin_add_exceptions_meta_box');

function plugin_render_post_types_exceptions()
{
    $selected_post_types = get_option('post_types_exceptions', []);
    $post_types = get_post_types(['public' => true], 'objects');
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo __('Post Types for Exceptions', 'checkintravel'); ?></th>
            <td>
                <?php foreach ($post_types as $post_type) : ?>
                    <label>
                        <input type="checkbox" name="post_types_exceptions[]"
                               value="<?php echo $post_type->name; ?>" <?php checked(in_array($post_type->name, $selected_post_types)); ?>>
                        <?php echo $post_type->labels->singular_name; ?>
                    </label><br>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>
    <?php
}


