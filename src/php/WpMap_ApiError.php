<?php

defined('ABSPATH') or exit();

class WpMap_ApiError extends Exception
{

    public function __construct($statusCode = 500, $title = null)
    {
        if (null === $title) {
            // use WP data
            $title = get_status_header_desc($statusCode);
        }
        $this->statusCode($statusCode);
        $this->title($title);
    }

    public function __call($method, $args)
    {
        $count = count($args);
        if (0 === $count) {
            return $this->get($method);
        } elseif (1 === $count) {
            return $this->set($method, $args[0]);
        } else {
            die("WpMap_ApiError args $count");
        }
    }

    public function get($key, $default = null)
    {
        if (isset($this->{$key})) {
            return $this->{$key};
        } else {
            return $default;
        }
    }

    public function set($key, $value)
    {
        $this->{$key} = $value;
        return $this;
    }
}
