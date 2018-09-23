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

// Visibility constants
define('WPMAP_VISIBILITY_ONMAP', 'on-map'); // Show on map
define('WPMAP_VISIBILITY_NOTONMAP', 'not-on-map'); // Do not show on map

require_once dirname(__FILE__) . '/src/php/WpMap_AdminPage.php';
require_once dirname(__FILE__) . '/src/php/WpMap_Widget.php';
require_once dirname(__FILE__) . '/src/php/WpMap_PostQuery.php';
require_once dirname(__FILE__) . '/src/php/WpMap_Request.php';

function wpmap_register_widget()
{
    register_widget('WpMap_Widget');
}

function wpmap_register_front_assets()
{
    $bundleName = 'bundle';
    $jsName = WP_DEBUG ? "$bundleName.js" : "$bundleName.min.js";
    $cssName = "$bundleName.css";

    wp_register_script(
        'wpmap_widget_bundle_js',
        plugins_url("/public/widget/$jsName", __FILE__),
        $deps = array(),
        WPMAP_VERSION,
        $inFooter = true
    );
    wp_register_style(
        'wpmap_widget_bundle_css',
        plugins_url("/public/widget/$cssName", __FILE__),
        $deps = array(),
        WPMAP_VERSION,
        $media = null
    );
}

function wpmap_register_admin_assets()
{
    $bundleName = 'bundle-adm';
    $jsName = WP_DEBUG ? "$bundleName.js" : "$bundleName.min.js";
    $cssName = "$bundleName.css";

    wp_register_script(
        'wpmap_admin_bundle_js',
        plugins_url("/public/admin/$jsName", __FILE__),
        $deps = array(),
        WPMAP_VERSION,
        $inFooter = true
    );
    wp_register_style(
        'wpmap_admin_bundle_css',
        plugins_url("/public/admin/$cssName", __FILE__),
        $deps = array(),
        WPMAP_VERSION,
        $media = null
    );
}

function wpmap_configure_admin_menu()
{
    add_menu_page(
        $page_title = 'Blog Map Configuration',
        $menu_title = 'Blog Map',
        $auth_role = 'manage_options',
        $menu_slug = 'wpmap-admin',
        $callback = array('WpMap_AdminPage', 'render'),
        $icon_url = '',
        $position = null
    );
}


add_action('widgets_init', 'wpmap_register_widget');
add_action('wp_enqueue_scripts', 'wpmap_register_front_assets');
add_action('admin_enqueue_scripts', 'wpmap_register_admin_assets');
add_action('admin_menu', 'wpmap_configure_admin_menu');
add_action('plugins_loaded', array('WpMap_Request', 'load'));
WpMap_AdminPage::registerAjaxController();
