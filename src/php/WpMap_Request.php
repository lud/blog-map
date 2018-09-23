<?php

class WpMap_Request {
    static private $single;

    private $get;
    private $post;
    private $cookie;
    private $request;

    private function __construct()
    {
        //Store the original GET and POST data + cookies.
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->request = $_REQUEST;

        //If magic quotes are actually enabled in PHP,
        //we'll need to remove the slashes.
        if ( get_magic_quotes_gpc() ) {
            $this->get    = stripslashes_deep($this->get);
            $this->post   = stripslashes_deep($this->post);
            $this->cookie = stripslashes_deep($this->cookie);
            $this->request = stripslashes_deep($this->request);
        }
    }

    public static function load()
    {
        self::$single = new WpMap_Request();
    }

    public static function _POST($key = null)
    {
        if ($key) {
            return self::$single->post[$key];
        }
        return self::$single->post;
    }

    public static function _GET($key = null)
    {
        if ($key) {
            return self::$single->get[$key];
        }
        return self::$single->get;
    }
}
