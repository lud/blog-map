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

    public static function ajaxRoutes()
    {
        return array(
            'getMapData' => array('GET', array('WpMap_Widget', 'getMapData'))
        );
    }

    public function getMapData($query)
    {
        $mapID = isset($query['mapID']) ? $query['mapID'] : null;
        if (!$mapID || !WpMap_AdminPage::isValidMapKey($mapID)) {
            throw new WpMap_ApiError(400, "Invalid mapID $mapID");
        }
        $postFields = array(
            'ID',
            'title'  => 'post_title',
            'url'    => 'guid',
            'status' => 'post_status',
            'type'   => 'post_type'
        );
        $metaKeys = array(
            'wpmap_on_map' => $mapID,
            'wpmap_latlng',
        );
        // @todo allow those who can see private or drafs to get those posts ?
        $conditions = array(
            WpMap_PostQuery::POST_COLUMN_POST_STATUS => array(
                WpMap_PostQuery::POST_STATUS_PUBLISHED,
                // WpMap_PostQuery::POST_STATUS_DRAFT,
                // WpMap_PostQuery::POST_STATUS_PRIVATE
            ),
            WpMap_PostQuery::POST_COLUMN_POST_TYPE => array(
                WpMap_PostQuery::POST_TYPE_PAGE,
                WpMap_PostQuery::POST_TYPE_POST,
            )
        );
        global $wpdb;
        $query = new WpMap_PostQuery($wpdb);
        return $query
            ->select($postFields)
            ->withMeta($metaKeys)
            ->where($conditions)
            ->all();
    }
}
