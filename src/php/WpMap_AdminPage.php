<?php

defined('ABSPATH') or exit();

class WpMap_AdminPage {

    public function __construct()
    {
    }

    public static function render()
    {
        $req = WpMap_Request::getInstance();
        if ($req->find('rollback') === 'true')
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
    private function getPostById($id)
    {
        throw new \Exception("@todo");
    }

    public static function ajaxRoutes()
    {
        return array(
            'getPostsConfig' => array('GET', array('WpMap_AdminPage', 'getAdminPosts')),
            'getMapsConfig' => array('GET', array('WpMap_AdminPage', 'getMapsConfig')),
            'patchPost' => array('PATCH', array('WpMap_AdminPage', 'patchPost')),
            'patchMap' => array('PATCH', array('WpMap_AdminPage', 'patchMap')),
        );
    }

    public function getAdminPosts($input)
    {
        $mapID = $input['mapID'];
        if (!$mapID || !WpMap_AdminPage::isValidMapKey($mapID)) {
            throw new WpMap_ApiError(400, "Invalid mapID $mapID");
        }
        return WpMap_Data::getInstance()->posts($mapID, $drafts = true);
    }

    public function getMapsConfig()
    {
        global $wpdb;
        $table = WpMap_Data::mapsTableName($wpdb);
        $rs = $wpdb->get_results("SELECT * FROM $table");
        foreach ($rs as &$record) {
            $record->pin_config = json_decode($record->pin_config);
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
        $changesetPostLayerconf = isset($changeset['layer'])
            ? $changeset['layer']
            : array();
        // -- Meta --

        // first loop to validate all
        foreach ($changesetMeta as $key => $value) {
            self::validateInputMeta($key, $value);
        }
        foreach ($changesetPostLayerconf as $key => $value) {
            self::validateInputPostLayerconf($key, $value);
        }

        // then save all

        foreach ($changesetMeta as $key => $value) {
            $serialized = WpMap_Data::serializePostMeta($key, $value);
            update_post_meta($postID, $key, $serialized);
        }

        foreach ($changesetPostLayerconf as $key => $value) {
            $serialized = WpMap_Data::serializePostLayerconfValue($key, $value);
            update_post_meta($postID, $key, $serialized);
        }

        return $this->getPostById($postID);
    }

    public function patchMap($payload)
    {

        $mapID = $payload['mapID'];
        $changeset = $payload['changeset'];
        $db = WpMap_Data::getInstance();
        if (! $map = $db->findMap($mapID)) {
            wp_die('@todo 404');
        }
        foreach ($changeset as $key => &$value) {
            $value = WpMap_Data::serializeMapColumnValue($key, $value);
        }
        $map->set($changeset);
        $map->save();
        $data = $map->as_array();
        foreach ($data as $key => &$value) {
            $value = WpMap_Data::unserializeMapColumnValue($key, $value);
        }
        return $data;
    }

    private static function validateInputMeta($key, $value) {
        switch ($key) {
            case 'OFF_wpmap_visibilities':
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
