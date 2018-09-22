<?php

defined('ABSPATH') or exit();


class WpMap_AdminPage {

    private function __construct()
    {
    }

    public static function render()
    {
        $self = new WpMap_AdminPage();
        return $self->doRender();
    }

    private function doRender()
    {
        wp_enqueue_script('wpmap_admin_bundle_js');
        echo '<pre>';
        $query = $this->getAllPosts();
        var_dump($query);
        echo '</pre>';
    }

    private function getAllPosts()
    {
        global $wpdb;
        $postFields = array('ID', 'post_title', 'post_status', 'post_type');
        $metaKeys = array('wpmap_visibility', 'wpmap_latlng');
        $query = new WpMap_PostQuery($wpdb);
        return $query
            ->select($postFields)
            ->withMeta($metaKeys)
            ->all();
    }

}
