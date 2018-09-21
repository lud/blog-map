<?php
/*
Plugin Name: Wordpress Blog Map
Plugin URI: http://my-awesomeness-emporium.com
description: Show you articles on a map
Version: 1.2
Author: Jean-Michel Code
Author URI: http://mrtotallyawesome.com
License: MIT
*/
defined('ABSPATH') or exit();

define('WPMAP_VERSION', WP_DEBUG ? time() : '0.0.1');
require dirname(__FILE__) . '/src/php/WpMap_Widget.php';

function wpmap_register_widget() {
    register_widget('WpMap_Widget');
}
function wpmap_register_assets() {
    $jsName = WP_DEBUG ? 'bundle.js' : 'bundle.min.js';
    wp_register_script(
        'wpmap_widget_bundle_js',
        plugins_url("/public/widget/$jsName", __FILE__),
        $deps = array(),
        WPMAP_VERSION,
        $inFooter = true
    );
    wp_register_style(
        'wpmap_widget_bundle_css',
        plugins_url('/public/widget/bundle.css', __FILE__),
        $deps = array(),
        WPMAP_VERSION,
        $media = null
    );
}

add_action('widgets_init', 'wpmap_register_widget');
add_action('wp_enqueue_scripts', 'wpmap_register_assets');
