<?php

defined('ABSPATH') or exit();

class WpMap_AdminPage {

    private $defaultQueryPostFields;
    private $defaultQueryMetaKeys;

    public function __construct()
    {
        // This should be class constants but as we want to store arrays, and as
        // worpress wants to be as retrocompatible as possible, we use
        // properties. @todo check minimum PHP version for all features
        $this->defaultQueryPostFields = array(
            'ID',
            'title'  => 'post_title',
            'url'    => 'guid',
            'status' => 'post_status',
            'type'   => 'post_type'
        );
        $this->defaultQueryMetaKeys = array(
            'wpmap_visibilities',
            'wpmap_latlng',
            'wpmap_geocoded',
            'wpmap_country_alpha2'
        );
    }

    public static function render()
    {
        // Before loading the admin panel, we will migrate the plugin to the
        // latest code.
        if (WpMap_Request::_GET('rollback') === 'true')
        {
            WpMap_Migration::rollbackEnv();
        }
        WpMap_Migration::migrateEnv();
        $self = new WpMap_AdminPage();
        return $self->doRender();
    }

    private function doRender()
    {
        wp_enqueue_script('wpmap_admin_bundle_js');
        wp_enqueue_style('wpmap_admin_bundle_css');
        echo "\n".'<div id="wpmap-admin-app"></div>';
    }

    private function queryPosts(array $postFields = null, array $metaKeys = null, array $conditions = array())
    {
        global $wpdb;
        if (null === $postFields) { $postFields = $this->defaultQueryPostFields; }
        if (null === $metaKeys) { $metaKeys = $this->defaultQueryMetaKeys; }
        $query = new WpMap_PostQuery($wpdb);
        return $query
            ->select($postFields)
            ->withMeta($metaKeys)
            ->where($conditions)
            ->all();
    }

    private function getPostById($id)
    {
        $posts = $this->queryPosts(null, null, array('ID' => $id));
        if (!count($posts) === 1) {
            trigger_error('@todo');
        }
        return reset($posts);
    }

    public static function ajaxRoutes()
    {
        return array(
            'getPostsConfig' => array('GET', array('WpMap_AdminPage', 'getAdminPosts')),
            'getMapsConfig' => array('GET', array('WpMap_AdminPage', 'getMapsConfig')),
            'patchPost' => array('PATCH', array('WpMap_AdminPage', 'patchPost')),
        );
    }

    public function getAdminPosts()
    {
        return $this->queryPosts(null, null, array(
            WpMap_PostQuery::POST_COLUMN_POST_STATUS => array(
                WpMap_PostQuery::POST_STATUS_PUBLISHED,
                WpMap_PostQuery::POST_STATUS_DRAFT,
                WpMap_PostQuery::POST_STATUS_PRIVATE
            ),
            WpMap_PostQuery::POST_COLUMN_POST_TYPE => array(
                WpMap_PostQuery::POST_TYPE_PAGE,
                WpMap_PostQuery::POST_TYPE_POST,
            )
        ));
    }

    public function getMapsConfig()
    {
        global $wpdb;
        $table = WpMap_Migration::mapsTableName($wpdb);
        $rs = $wpdb->get_results("SELECT * FROM $table");
        foreach ($rs as &$record) {
            $record->pin_height = (int) $record->pin_height;
            $record->pin_radius = (int) $record->pin_radius;
        }
        return $rs;
    }

    public function patchPost($payload)
    {
        if (!isset($payload['postID'])
         || !is_integer($payload['postID'])
         || ! get_post($payload['postID'])) {
            wp_die('@todo 404');
        }
        $postID = $payload['postID'];
        $changeset = $payload['changeset'];
        $changesetMeta = isset($changeset['meta'])
            ? $changeset['meta']
            : array();

        // -- Meta --

        // first loop to validate all
        foreach ($changesetMeta as $key => $value) {
            self::validateInputMeta($key, $value);
        }

        // then save all
        foreach ($changesetMeta as $key => $value) {
            $serialized = WpMap_PostQuery::serializePostMeta($key, $value);
            update_post_meta($postID, $key, $serialized);
            // For some meta keys we want to have additional behaviour
            switch ($key) {
                case 'wpmap_visibilities':
                    // wpmap_on_map is a "bag" meta to query all posts for a map
                    delete_post_meta($postID, 'wpmap_on_map');
                    foreach ($value as $map => $isVisible) {
                        if ($isVisible === WPMAP_VIS_ONMAP) {
                            update_post_meta($postID, 'wpmap_on_map', $map);
                        }
                    }
                    break;
            }
        }
        return $this->getPostById($postID);
    }

    private static function validateInputMeta($key, $value) {
        switch ($key) {
            case 'wpmap_visibilities':
                if (!is_array($value)) {
                    throw new \Exception("Visibilities must be an array");
                }
                $accepted = array(WPMAP_VIS_ONMAP, WPMAP_VIS_NOTONMAP);
                foreach ($value as $map => $visibility) {
                    if (!in_array($visibility, $accepted)) {
                        $visibility = var_export($visibility, 1);
                        throw new \Exception("Bad visibility $visibility");
                    }
                    if (! self::isValidMapKey($map)) {
                        $map = var_export($map, 1);
                        throw new \Exception("Bad map key $map");
                    }
                }
                return true;
            case 'wpmap_country_alpha2':
            case 'wpmap_geocoded':
                return true;
            case 'wpmap_latlng':
                if (!is_array($value) || count($value) !== 2) {
                    throw new \Exception("latlng must be an array");
                }
                return true;
        }
        throw new WpMap_ApiError(400, "Unauthorized meta key $key");
    }

    public static function isValidMapKey($key)
    {
        if (!is_string($key)) {
            return false;
        }
        $hasUnauthorizedChars = preg_match('/[^a-zA-Z0-9_-]/', $key);
        return !$hasUnauthorizedChars;
    }
}
