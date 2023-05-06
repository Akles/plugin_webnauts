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
require_once plugin_dir_path(__FILE__) . 'inc/admin.php';
function plugin_activate() {
    add_option('tariff_duration', 1); // 1 год
    add_option('tariff_duration_type', 'year');
    add_option('tariff_cost', 10); // 10 долларов
}

register_activation_hook(__FILE__, 'plugin_activate');
