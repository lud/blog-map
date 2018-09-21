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
        var_dump($this->getAllPosts());
        echo '</pre>';
    }

    private function getAllPosts()
    {
        global $wpdb;
        $pm1 = 'wpmap_visibility';
        $pm2 = 'wpmap_latlng';
        $r = $wpdb->get_results($wpdb->prepare("
            SELECT
                p.ID
                , p.post_title
                , pm1.meta_value as $pm1
                , pm2.meta_value as $pm2
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm1
                ON p.ID = pm1.post_id
                AND pm1.meta_key = %s
            LEFT JOIN {$wpdb->postmeta} pm2
                ON p.ID = pm2.post_id
                AND pm2.meta_key = %s
            WHERE p.post_type = 'post'
        ",
        $pm1,
        $pm2
    ));
        return $r;
    }
}
