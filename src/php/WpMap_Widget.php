<?php

defined('ABSPATH') or exit();

class WpMap_Widget extends WP_Widget {

    public function __construct()
    {
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

        // Define a global value to pass the ajax controller URL
        wp_localize_script( 'wpmap_widget_bundle_js', '_wpmap_loc', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));

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
                    el: document.getElementById('<?php echo $randomWidgetId; ?>'),
                    mapID: 'default-map'
                })
            }(this._wpmap = this._wpmap || {maps: []}))
        </script>
        <?php
        echo $args['after_widget'];
    }

    public static function ajaxRoutes()
    {
        return array(
            'getMapData' => array('GET', array('WpMap_Widget', 'getMapData')),
            'getPostInfos' => array('GET', array('WpMap_Widget', 'getPostInfos')),
        );
    }

    public function getMapData($input)
    {
        $mapID = $input['mapID'];
        if (!$mapID || !WpMap_AdminPage::isValidMapKey($mapID)) {
            throw new WpMap_ApiError(400, "Invalid mapID $mapID");
        }
        $posts = WpMap_Data::getInstance()->posts($mapID, $drafts = true);
        return $this->postsToFeatureCollection($posts);
    }

    public function getPostInfos($input) {
        $postID = $input['postID'];
        // get_the_excerpt/1 requires to be called within theloop if the post
        // has no excerpt to be able to generate an excerpt from the post
        // content. The bug is because get_the_content expects to be called
        // within the loop. So we have to simulate the loop by assigning the
        // global $post AND setup_postdata()
        global $post;
        $post = get_post($postID);
        setup_postdata($post);
        $excerpt = preg_replace('~<a.*</a>~', '', get_the_excerpt());
        $date = get_the_date();
        return compact('excerpt', 'date');
    }

    public static function postsToFeatureCollection($posts)
    {
        $fc = array('type' => 'FeatureCollection', 'features' => array());
        foreach ($posts as $post) {
            $feature = array(
                'type' => 'Feature',
                'properties' => $post->props,
                'geometry' => array(
                    'type' => 'Point',
                    'coordinates' => latlngToLonlat($post->meta->wpmap_latlng)
                ),
            );
            $fc['features'][]= $feature;
        }
        return $fc;
    }
}
