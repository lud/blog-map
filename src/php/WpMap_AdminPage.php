<?php

defined('ABSPATH') or exit();


class WpMap_AdminPage {

    private $defaultQueryPostFields;
    private $defaultQueryMetaKeys;
    private $defaultQueryConditions;

    private function __construct()
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
        $this->defaultQueryMetaKeys = array('wpmap_visibilities', 'wpmap_latlng', 'wpmap_geocoded');
    }

    public static function render()
    {
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
            if (null === $postFields) $postFields = $this->defaultQueryPostFields;
            if (null === $metaKeys) $metaKeys = $this->defaultQueryMetaKeys;
        global $wpdb;
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

    private static function ajaxRoutes()
    {
        return array(
            'getPostsConfig' => array('GET', 'getAdminPosts'),
            'patchPost' => array('PATCH'),
        );
    }

    public static function registerAjaxController()
    {
        $routes = self::ajaxRoutes();
        foreach ($routes as $action => $_) {
            add_action("wp_ajax_$action", array('WpMap_AdminPage', 'ajaxController'));
        }
    }

    public static function ajaxController($a = null, $b = null, $c = null, $d = null)
    {
        $action = $_REQUEST['action'];
        $routes = self::ajaxRoutes();
        if (!isset($routes[$action])) {
            // this should not happen because if the action does not exist
            // WP will not route the request to this controller
            wp_die('@todo err 404');
        }
        $route = $routes[$action];
        $expectedRequestMethod = isset($route[0]) ? $route[0] : 'GET';
        $controllerMethod = isset($route[1]) ? $route[1] : $action;
        // here, the action exists
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
        if ($requestMethod === 'POST'
        && isset($_POST['_method'])
        && in_array($_method = strtoupper($_POST['_method']), array('PUT', 'PATCH'))) {
            $requestMethod = $_method;
        }
        if ($requestMethod !== $expectedRequestMethod) {
            wp_die("@todo err 40X bad method $requestMethod");
        }
        $instance = new WpMap_AdminPage();
        try {
            $response = $instance->callAction($controllerMethod, $requestMethod);
            if (is_string($response)) {
                echo json_encode(array('data' => $response));
            } elseif (is_array($response) || is_object($response)) {
                echo json_encode(array('data' => $response));
            } elseif (null === $response) {
                echo '{data: "ok"}';
            } else {
                if (WP_DEBUG) {
                    var_dump($response);
                }
                throw new Exception("Bad ajax return value in $action");
            }
            wp_die();
        } catch (Eception $e) {
            wp_die($e->getMessage());
        }
    }

    private function callAction($method, $httpVerb)
    {
        if (!is_callable(array($this, $method))) {
            trigger_error("@todo no method $method");
        }
        switch ($httpVerb) {
            case 'GET':
                return $this->$method(WpMap_Request::_GET());
            case 'PATCH':
            case 'PUT':
                $payload = json_decode(WpMap_Request::_POST('payload'), true);
                return $this->$method($payload, $httpVerb);
            default:
                throw new E();
        }
    }

    public function getAdminPosts()
    {
        return $this->queryPosts(null, null, array(
            WpMap_PostQuery::POST_COLUMN_POST_STATUS => array(
                WpMap_PostQuery::POST_STATUS_PUBLISHED,
                WpMap_PostQuery::POST_STATUS_DRAFT,
                WpMap_PostQuery::POST_STATUS_PRIVATE
            )
        ));
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
            self::ensureAthorizedMeta($key, $value);
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

    // private static function ensureAthorizedMeta($key, $value) {
    //     if (!self::isAthorizedMeta($key, $value)) {
    //         $dump = var_export($value, 1);
    //         throw new Exception("Unauthorized meta change '$key' = $dump");
    //     }
    // }

    private static function ensureAthorizedMeta($key, $value) {
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
                    if (preg_match('/[^a-zA-Z0-9_-]/', $map)) {
                        $map = var_export($map, 1);
                        throw new \Exception("Bad map key $map");
                    }
                }
                return true;
            default:
                throw new \Exception("Unauthorized meta key $key");
        }
    }

}
