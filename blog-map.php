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

require dirname(__FILE__) . '/src/WpMap_Widget.php';

function wpmap_register_widget() {
    register_widget('WpMap_Widget');
}
add_action('widgets_init', 'wpmap_register_widget');
