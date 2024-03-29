<?php

defined('ABSPATH') or exit();

class WpMap_AjaxController
{

    private $routes = array();

    public function registerRoutes($routes, $public = false)
    {
        if (is_string($routes)) {
            // $routes is a class
            $routes = call_user_func(array($routes, 'ajaxRoutes'));
        }
        // if an action is public, we still have to register a wp_ajax (on top
        // of a wp_ajax_nopriv) to be able to call it when logged in
        // @todo prefix all routes with the plugin name
        foreach ($routes as $action => $_) {
            add_action("wp_ajax_$action", array($this, 'handleRequest'));
            if ($public) {
                add_action("wp_ajax_nopriv_$action", array($this, 'handleRequest'));
            }
        }

        $this->routes = array_merge($this->routes, $routes);
    }

    public function handleRequest($a = null, $b = null, $c = null, $d = null)
    {
        // above $a, $b ... dummy args for wordpress
        set_error_handler(array('WpMap_AjaxController', 'handleAjaxError'));
        set_exception_handler(array('WpMap_AjaxController', 'handleAjaxException'));
        $action = $_REQUEST['action'];
        $routes = $this->routes;
        if (!isset($routes[$action])) {
            // this should not happen because if the action does not exist
            // WP will not route the request to this controller
            wp_die('@todo err 404');
        }
        $route = $routes[$action];
        $expectedRequestMethod = $route[0];
        list($controllerClass, $controllerMethod) = $route[1];
        // here, the action exists
        $requestMethod = WpMap_Request::getInstance()->getHttpVerb();
        if ($requestMethod !== $expectedRequestMethod) {
            wp_die("@todo err 40X bad method $requestMethod");
        }
        $controller = new $controllerClass();
        try {
            $response = $this->callAction($controller, $controllerMethod, $requestMethod);
            if (is_array($response) || is_object($response) || is_string($response)) {
                header('Content-Type: application/vnd.api+json');
                echo json_encode(array('data' => $response));
            } elseif (null === $response) {
                header('Content-Type: application/vnd.api+json');
                echo json_encode(['data' => 'ok']);
            } else {
                $returned = WP_DEBUG
                    ? var_export($response, true)
                    : '-- value hidden in production --';
                throw new Exception("Bad ajax return value in $action() :\n$returned\n");
            }
            wp_die();
        } catch (Eception $e) {
            wp_die($e->getMessage());
        }
    }

    private function callAction($controller, $method, $httpVerb)
    {
        if (!is_callable(array($controller, $method))) {
            trigger_error("@todo no method $method");
        }
        return $controller->$method(WpMap_Request::getInstance(), $httpVerb);
    }

    public static function handleAjaxException($e)
    {
        if ($e instanceof WpMap_ApiError) {
            header('Content-Type: application/vnd.api+json');
            api_send_errors($e->statusCode(), $e);
        } else {
            if (strpos($_SERVER['HTTP_ACCEPT'], 'json')) {
                header('Content-Type: application/vnd.api+json');
                api_send_errors(500, '(unhandled) ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
        return true;
    }

    public static function handleAjaxError($errno, $errstr, $errfile, $errline)
    {
        $jsonApiError = array(
            'title' => $errstr,
            'meta' => array(
                'errno' => $errno
            )
        );
        if (WP_DEBUG) {
            $jsonApiError['meta']['file'] = $errfile;
            $jsonApiError['meta']['line'] = $errline;
        }
        api_send_errors(500, $jsonApiError);
        return true;
    }
}
