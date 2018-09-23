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
        wp_enqueue_style('wpmap_admin_bundle_css');
        echo "\n".'<div id="wpmap-admin-app"></div>';
    }

    private function getAllPosts()
    {
        global $wpdb;
        $postFields = array('ID', 'title' => 'post_title', 'url' => 'guid', 'status' => 'post_status', 'type' => 'post_type');
        $metaKeys = array('wpmap_visibility', 'wpmap_latlng', 'wpmap_geocoded');
        $query = new WpMap_PostQuery($wpdb);
        return $query
            ->select($postFields)
            ->withMeta($metaKeys)
            ->all();
    }

    private static function ajaxRoutes()
    {
        return array(
            'getPostsConfig' => array('GET'),
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
        // here, the action exists
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
        if ($requestMethod === 'POST'
        && isset($_POST['_method'])
        && in_array($_method = strtoupper($_POST['_method']), array('PUT', 'PATCH'))) {
            $requestMethod = $_method;
        }
        if ($requestMethod !== $route[0]) {
            wp_die("@todo err 40X bad method $requestMethod");
        }
        $instance = new WpMap_AdminPage();
        try {
            $response = $instance->callAction($action, $requestMethod);
            if (is_string($response)) {
                echo json_encode(array('data' => $response), JSON_PRETTY_PRINT);
            } elseif (is_array($response)) {
                echo json_encode(array('data' => $response), JSON_PRETTY_PRINT);
            } elseif (null === $response) {
                echo '{data: "ok"}';
            } else {
                throw new Exception("Bad ajax return value in $method");
            }
            wp_die();
        } catch (Eception $e) {
            wp_die($e->getMessage());
        }
    }

    private function callAction($method, $httpVerb)
    {
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

    public function getPostsConfig()
    {
        return $this->getAllPosts();
    }

    public function patchPost($payload)
    {
        $changeset = $payload['changeset'];
        $changesetMeta = isset($changeset['_meta'])
            ? $changeset['_meta']
            : array();
        unset($changeset['_meta']);

        // -- Meta --

        // first loop to validate all
        foreach ($changesetMeta as $key => $value) {
            self::ensureAthorizedMeta($key, $value);
        }

        // then save all
        $postID = $payload['postID'];
        foreach ($changesetMeta as $key => $value) {
            update_post_meta($postID, $key, $value);
        }

    }

    private static function ensureAthorizedMeta($key, $value) {
        $auth = false;
        switch ($key) {
            case 'wpmap_visibility':
                $auth = in_array($value, array(
                    WPMAP_VISIBILITY_ONMAP,
                    WPMAP_VISIBILITY_NOTONMAP
                ));
                break;
        }
        if (!$auth) {
            throw new Exception("Unauthorized meta change '$key' = '$value'");
        }
    }

}
