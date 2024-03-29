<?php
/*
Plugin Name: Blog Map
Plugin URI:
description: Show you articles on a map
Version: 1.2
Author: Ludovic Demblans
Author URI:
License: MIT
*/
defined('ABSPATH') or exit();
// @todo pick a version
define('WPMAP_VERSION', WP_DEBUG ? time() : '0.0.1');

// Visibility constants
define('WPMAP_VIS_ONMAP', 1); // Show on map
define('WPMAP_VIS_NOTONMAP', 0); // Do not show on map

require_once dirname(__FILE__) . '/src/php/helpers.php';

function wpmap_autoloader($class)
{
    if (strpos($class, 'WpMap_') === 0) {
        require_once dirname(__FILE__) . "/src/php/$class.php";
    } elseif('ORM' === $class) {
        require_once dirname(__FILE__) . "/idiorm.php";
    } elseif('IOList' === $class) {
        require_once dirname(__FILE__) . "/src/php/$class.php";
    }
}

function wpmap_register_widget()
{
    register_widget('WpMap_Widget');
}


function wpmap_add_front_assets()
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


function wpmap_register_script($name, $basename, $pluginAbspath) {
    $jsName = WP_DEBUG ? "$basename.js" : "$basename.min.js";
    wp_register_script(
        $name,
        plugins_url("$pluginAbspath/$jsName", __FILE__),
        $deps = array(),
        WPMAP_VERSION,
        $inFooter = true
    );
}

function wpmap_register_style($name, $basename, $pluginAbspath) {
    // add minified files in prod
    $cssName = "$basename.css";
    wp_register_style(
        $name,
        plugins_url("$pluginAbspath/$cssName", __FILE__),
        $deps = array(),
        WPMAP_VERSION,
        $media = null
    );
}


function wpmap_add_admin_assets($hook)
{
    switch ($hook) {
        case 'post.php':
            wpmap_register_script('wpmap_post_admin_bundle_js', 'bundle-post-admin', '/public/admin');
            wp_enqueue_script('wpmap_post_admin_bundle_js');
            wpmap_register_style('wpmap_post_admin_bundle_css', 'bundle-post-admin', '/public/admin');
            wp_enqueue_style('wpmap_post_admin_bundle_css');
        break;
    }
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

function wpmap_add_meta_boxes() {
    add_meta_box(
        'wpmap_post_geotagging',
        'Geotagging',
        array('WpMap_PostEdit', 'render'),
        array('post', 'page'),
        'normal',
        'default'
    );
}

spl_autoload_register('wpmap_autoloader');
add_action('widgets_init', 'wpmap_register_widget');
add_action('wp_enqueue_scripts', 'wpmap_add_front_assets');
add_action('admin_enqueue_scripts', 'wpmap_add_admin_assets');
add_action('admin_menu', 'wpmap_configure_admin_menu');
add_action('plugins_loaded', array('WpMap_Request', 'load'));
add_action('add_meta_boxes', 'wpmap_add_meta_boxes');
register_activation_hook(__FILE__, array('WpMap_Migration', 'migrateEnv'));
register_uninstall_hook(__FILE__, array('WpMap_Migration', 'rollbackEnv'));
$ajaxController = new WpMap_AjaxController();
// @todo check if in admin before registering admin pages
$ajaxController->registerRoutes('WpMap_AdminPage');
$ajaxController->registerRoutes('WpMap_Widget', true);
