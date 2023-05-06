<?php
add_action('admin_menu', 'register_plugin_menu');

function register_plugin_menu()
{
    add_menu_page(__('Plugin', 'checkintravel'), __('Plugin', 'checkintravel'), 'manage_options', 'plugin_subscribers', 'plugin_subscribers_page');
    add_submenu_page('plugin_subscribers', __('Subscribers', 'checkintravel'), __('Subscribers', 'checkintravel'), 'manage_options', 'plugin_subscribers', 'plugin_subscribers_page');
    add_submenu_page(null, __('Subscriber Page', 'checkintravel'), __('Subscriber Page', 'checkintravel'), 'manage_options', 'plugin_subscriber_page', 'plugin_subscriber_page');
    add_submenu_page('plugin_subscribers', __('Payments', 'checkintravel'), __('Payments', 'checkintravel'), 'manage_options', 'plugin_payments', 'plugin_payments_page');
    add_submenu_page(null, __('Specific Payment Page', 'checkintravel'), __('Specific Payment Page', 'checkintravel'), 'manage_options', 'plugin_payment_page', 'plugin_payment_page');
    add_submenu_page('plugin_subscribers', __('Tariff Settings', 'checkintravel'), __('Tariff Settings', 'checkintravel'), 'manage_options', 'plugin_tariff_settings', 'plugin_tariff_settings_page');
    add_submenu_page('plugin_subscribers', __('Monopay Settings', 'checkintravel'), __('Monopay Settings', 'checkintravel'), 'manage_options', 'plugin_monopay_settings', 'plugin_monopay_settings_page');
    add_submenu_page('plugin_subscribers', __('Email Settings', 'checkintravel'), __('Email Settings', 'checkintravel'), 'manage_options', 'plugin_email_settings', 'plugin_email_settings_page');
    add_submenu_page('plugin_subscribers', __('Redirect Settings', 'checkintravel'), __('Redirect Settings', 'checkintravel'), 'manage_options', 'plugin_redirect_settings', 'plugin_redirect_settings_page');
}

function plugin_subscribers_page()
{
    echo __('Subscribers Page', 'checkintravel');
}

function plugin_subscriber_page()
{
    echo __('Subscriber Page', 'checkintravel');
}

function plugin_payments_page()
{
    echo __('Payments Page', 'checkintravel');
}

function plugin_payment_page()
{
    echo __('Specific Payment Page', 'checkintravel');
}


function plugin_email_settings_page()
{
    echo __('Email Settings Page', 'checkintravel');
}

require_once plugin_dir_path(__FILE__) . 'pages/redirect-settings.php';
require_once plugin_dir_path(__FILE__) . 'pages/tariff-settings.php';
require_once plugin_dir_path(__FILE__) . 'pages/mono-settings.php';
require_once plugin_dir_path(__FILE__) . 'pages/redirect-conditions.php';

function plugin_register_settings()
{
    register_setting('redirect_settings_group', 'unauthorized_redirect', 'intval');
    register_setting('redirect_settings_group', 'no_subscription_redirect', 'intval');
    register_setting('redirect_settings_group', 'login_page', 'intval');
    register_setting('redirect_settings_group', 'post_types_exceptions', 'array');
    register_setting('tariff_duration_group', 'tariff_duration', 'intval');
    register_setting('tariff_duration_group', 'tariff_duration_type', 'sanitize_text_field');
    register_setting('tariff_duration_group', 'tariff_cost', 'intval');

    // Monobank settings
    register_setting('plugin_monopay_settings', 'monobank_token', 'sanitize_text_field');

}

add_action('admin_init', 'plugin_register_settings');

function plugin_display_admin_notices()
{
    $monobank_test_mode = get_option('monobank_test_mode');

    if ($monobank_test_mode) {
        echo '<div class="notice notice-warning is-dismissible"><p>';
        printf(__('Monobank is currently set to test mode. Remember to disable it in <a href="%s">Monopay Settings</a> before going live.', 'checkintravel'), admin_url('admin.php?page=plugin_monopay_settings'));
        echo '</p></div>';
    }

    $monobank_public_key = get_option('monobank_token');

    if (!$monobank_public_key) {
        echo '<div class="notice notice-error is-dismissible"><p>';
        echo __('Please fill in Monobank Public Key', 'checkintravel');
        echo '</p></div>';
    } else {
        $curl = curl_init();

        $url = 'https://api.monobank.ua/personal/client-info';
        $headers = array(
            'X-Token: ' . $monobank_public_key
        );

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);


        if ($http_code !== 200) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo __('Monobank Public Key is invalid', 'checkintravel');
            echo '</p></div>';
        }
    }
}

add_action('admin_notices', 'plugin_display_admin_notices');

function register_order_post_type() {
    $labels = array(
        'name' => __('Orders', 'checkintravel'),
        'singular_name' => __('Order', 'checkintravel'),
        'add_new' => __('Add New', 'checkintravel'),
        'add_new_item' => __('Add New Order', 'checkintravel'),
        'edit_item' => __('Edit Order', 'checkintravel'),
        'new_item' => __('New Order', 'checkintravel'),
        'view_item' => __('View Order', 'checkintravel'),
        'search_items' => __('Search Orders', 'checkintravel'),
        'not_found' => __('No orders found', 'checkintravel'),
        'not_found_in_trash' => __('No orders found in Trash', 'checkintravel'),
        'menu_name' => __('Orders', 'checkintravel'),
    );

    $args = array(
        'labels' => $labels,
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'order'),
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title'),
    );

    register_post_type('order', $args);
}
add_action('init', 'register_order_post_type');
