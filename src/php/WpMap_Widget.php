<?php

defined('ABSPATH') or exit();

class WpMap_Widget extends WP_Widget {

    const NO_MAP_ID = '__NO_MAP__';

    public function __construct()
    {
        // Instantiate the parent object
        parent::__construct(false, 'Blog Map');
    }

    private function formDefaults()
    {
        $configs = WpMap_Data::getInstance()->mapsConfigs();
        $noMapId = self::NO_MAP_ID;
        $defaultMap = count($configs) ? $configs['0']['id'] : $noMapId;
        $defaults = array('mapID' => $defaultMap);
        return $defaults;
    }

    public function form($instance)
    {

        // Form is called on admin page load with empty instance to get a
        // default form, so we need to add defaults
        $instance = array_merge($this->formDefaults(), $instance);

        $configs = WpMap_Data::getInstance()->mapsConfigs();
        $optionsIOList = array();
        array_unshift($configs, array('id' => self::NO_MAP_ID, 'name' => '-- Hide Map --'));
        foreach ($configs as $config) {
            $selected = $instance['mapID'] === $config['id'];
            $optionsIOList[] = array(
                '<option ',
                    'value="', $config['id'], '"',
                    ($selected ? ' selected ' : null),
                '>',
                $config['name'],
                '</option>');
        }
        $fieldId = $this->get_field_id('mapID');
        $fieldName = $this->get_field_name('mapID');
        // $formstyle = count($configs) > 1 ? '' : 'display:none;';
        $formstyle = '';
        ?>
            <p>
                <label for="<?php echo $fieldId; ?>" style="<?php echo $formstyle; ?>">
                     Map
                    <select id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>">
                        <?php IOList::out($optionsIOList); ?>
                    </select>
                </label>
            </p>
        <?php
    }

    public function update($newInstance, $oldInstance)
    {
        $instance = $oldInstance;
        $mapID = $newInstance['mapID'];
        $map = WpMap_Data::getInstance()->findMap($mapID);
        if (!$map) {
            $mapID = self::NO_MAP_ID;
        }
        $instance['mapID'] = $mapID;
        return $instance;
    }

    public function widget($args, $instance)
    {
        $mapID = $instance['mapID'];
        $map = WpMap_Data::getInstance()->findMap($mapID)->as_array();
        if (!$map) {
            if ($mapID !== self::NO_MAP_ID) {
                echo "Map $mapID does not exist";
            }
            return;
        }
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
        <div style="height: 300px;" id="<?php echo $randomWidgetId; ?>"></div>
        <script type="text/javascript">
            ;(function(config){
                config.maps.push({
                    el: document.getElementById('<?php echo $randomWidgetId; ?>'),
                    mapID: 'default-map',
                    config: <?php echo json_encode($map) . "\n"; ?>
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
        $posts = WpMap_Data::getInstance()->mapPosts($mapID, $drafts = false);
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
                'properties' => (object) array_merge((array) $post->props, (array) $post->layer),
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
