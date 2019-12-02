<?php

defined('ABSPATH') or exit();

class WpMap_Request implements ArrayAccess
{
    private static $singleton;

    private $get;
    private $post;
    private $cookie;
    private $request;
    private $input;

    private function __construct()
    {
        //Store the original GET and POST data + cookies.
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->request = $_REQUEST;

        // If magic quotes are actually enabled in PHP,
        // we'll need to remove the slashes.
        if (get_magic_quotes_gpc()) {
            $this->get     = stripslashes_deep($this->get);
            $this->post    = stripslashes_deep($this->post);
            $this->cookie  = stripslashes_deep($this->cookie);
            $this->request = stripslashes_deep($this->request);
        }

        switch ($verb = $this->getHttpVerb()) {
            case 'GET':
                $this->input = $this->get;
                break;
            case 'POST':
                $this->input = $this->post;
                break;
            case 'PATCH':
            case 'PUT':
                if (!isset($this->post['payload'])) {
                    throw new Exception("This http method ($verb) requires a payload");
                }
                $payload = json_decode($this->post['payload'], true);
                $this->input = $payload;
                break;
            default:
                $this->input = array();
                break;
        }
    }

    public static function load()
    {
        // this function is called by our plugin before all server vars are
        // changed by wordpress with magic quotes ...
        self::getInstance();
    }

    public static function getInstance()
    {
        if (null === self::$singleton) {
            self::$singleton = new WpMap_Request();
        }
        return self::$singleton;
    }

    public function getHttpVerb()
    {
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
        if (
            $requestMethod === 'POST'
            && isset($_POST['_method'])
            && in_array($_method = strtoupper($_POST['_method']), array('PUT', 'PATCH'))
        ) {
            $requestMethod = $_method;
        }
        return $requestMethod;
    }

    public function offsetExists($key)
    {
        return isset($this->input[$key]);
    }

    public function offsetGet($key)
    {
        return $this->input($key);
    }

    public function offsetSet($key, $value)
    {
        throw new Exception("Request is immutable");
    }

    public function offsetUnset($key)
    {
        throw new Exception("Request is immutable");
    }

    public function input($key)
    {
        if (!$this->offsetExists($key)) {
            throw new InvalidArgumentException("Input key missing : $key");
        }
        return $this->input[$key];
    }

    public function find($key, $default = null)
    {
        if (!$this->offsetExists($key)) {
            return $default;
        }
        return $this->input[$key];
    }
}
