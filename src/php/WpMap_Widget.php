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

        wp_enqueue_style('wpmap_widget_bundle_css');
        wp_enqueue_script('wpmap_widget_bundle_js');

        $randomWidgetId = uniqid('wpmap-');
        $widgetTitle = 'Blog Map';

        echo $args['before_widget'];
        echo $args['before_title'];
        echo $widgetTitle;
        echo $args['after_title'];
        ?>
        <div id="<?php echo $randomWidgetId; ?>"></div>
        <script type="text/javascript">
            ;(function(config){
                config.maps.push({
                    el: document.getElementById('<?php echo $randomWidgetId; ?>')
                })
            }(this._wpmap = this._wpmap || {maps: []}))
        </script>
        <?php
        echo $args['after_widget'];
    }
}
