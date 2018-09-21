<?php

defined('ABSPATH') or exit();


class WpMap_Widget extends WP_Widget {

    public function __construct() {
        // Instantiate the parent object
        parent::__construct(false, 'Blog Map');
    }

    public function form($instance)
    {

    }

    public function update($newInstance, $oldInstance)
    {

    }

    public function widget($args, $instance)
    {

        echo $args['before_widget'];
        echo $args['before_title'];

        echo $args['after_title'];
        echo $args['after_widget'];
    }
}
