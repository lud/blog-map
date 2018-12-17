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
        throw new \Exception("@todo get post by id");
    }

    public static function ajaxRoutes()
    {
        return array(
            'getPostsConfig' => array('GET', array('WpMap_AdminPage', 'getAdminPosts')),
            'getMapsConfig' => array('GET', array('WpMap_AdminPage', 'getMapsConfig')),
            'patchPostLayer' => array('PATCH', array('WpMap_AdminPage', 'patchPostLayer')),
            'patchPostMeta' => array('PATCH', array('WpMap_AdminPage', 'patchPostMeta')),
            'patchMap' => array('PATCH', array('WpMap_AdminPage', 'patchMap')),
        );
    }

    public function getAdminPosts($input)
    {
        $mapID = $input['mapID'];
        if (!$mapID || !WpMap_AdminPage::isValidMapKey($mapID)) {
            throw new WpMap_ApiError(400, "Invalid mapID $mapID");
        }
        return WpMap_Data::getInstance()->posts($mapID);
    }

    public function getMapsConfig()
    {
        $configs = WpMap_Data::getInstance()->mapsConfigs();
        return WpMap_Serializer::unserializeMaps($configs, $asArray = true);
    }

    public function patchPostLayer($payload)
    {
        $mapID = $payload['mapID'];
        $postID = $payload['postID'];
        $changeset = $payload['changeset'];
        self::findMapOrFail($mapID);
        self::ensurePostExists($payload['postID']);

        foreach ($changeset as $key => $value) {
            self::validateInputPostLayerconf($key, $value);
        }
        $db = WpMap_Data::getInstance();
        foreach ($changeset as $key => $value) {
            $serialized = WpMap_Serializer::serializePostLayerConfValue($key, $value);
            $db->updatePostLayerConf($postID, $mapID, $key, $value);
        }
        return WpMap_Data::getInstance()->postLayer($postID, $mapID);
    }

    public function patchPostMeta($payload)
    {
        $postID = $payload['postID'];
        $changeset = $payload['changeset'];
        self::ensurePostExists($payload['postID']);

        $postID = $payload['postID'];
        $changeset = $payload['changeset'];

        // first loop to validate all
        foreach ($changeset as $key => $value) {
            self::validateInputMeta($key, $value);
        }

        foreach ($changeset as $key => $value) {
            $serialized = WpMap_Serializer::serializePostMeta($key, $value);
            update_post_meta($postID, $key, $serialized);
            switch ($key) {
                case 'wpmap_country_alpha2':
                    delete_post_meta($postID, 'wpmap_geocoded');
                    delete_post_meta($postID, 'wpmap_latlng');
                break;
            }
        }

        return WpMap_Data::getInstance()->postMeta($postID);
    }

    private function findMapOrFail($mapID)
    {
        $db = WpMap_Data::getInstance();
        if (! $map = $db->findMap($mapID)) {
            wp_die('@todo 404');
        }
        return $map;
    }

    private function ensurePostExists($postID)
    {
        $db = WpMap_Data::getInstance();
        if (false === (!!get_post($postID))) {
            wp_die('@todo 404');
        }
    }

    public function patchMap($payload)
    {
        $mapID = $payload['mapID'];
        $map = static::findMapOrFail($mapID);
        $changeset = $payload['changeset'];
        $map->set($changeset);
        $map = WpMap_Serializer::serializeMap($map);
        $map->save();
        $map = WpMap_Serializer::unserializeMap($map);
        return $map->as_array();
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

    private static function validateInputPostLayerconf($key, $value) {
        switch ($key) {
            case 'visible':
                if (! is_integer($value)) {
                    throw new \Exception("Visibilities must be integers");
                } else {
                    return true;
                }
            case 'icon':
                return true;
        }
        throw new WpMap_ApiError(400, "Unauthorized layer key $key");
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
