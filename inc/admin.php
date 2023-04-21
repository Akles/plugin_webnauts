<?php
add_action('admin_menu', 'register_plugin_menu');

function register_plugin_menu() {
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

function plugin_subscribers_page() {
    echo __('Subscribers Page', 'checkintravel');
}

function plugin_subscriber_page() {
    echo __('Subscriber Page', 'checkintravel');
}

function plugin_payments_page() {
    echo __('Payments Page', 'checkintravel');
}

function plugin_payment_page() {
    echo __('Specific Payment Page', 'checkintravel');
}

function plugin_tariff_settings_page() {
    echo __('Tariff Settings Page', 'checkintravel');
}

function plugin_monopay_settings_page() {
    echo __('Monopay Settings Page', 'checkintravel');
}

function plugin_email_settings_page() {
    echo __('Email Settings Page', 'checkintravel');
}

require_once plugin_dir_path(__FILE__) . 'pages/redirect-settings.php';
