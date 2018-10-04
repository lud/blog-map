<?php

class WpMap_ApiError extends Exception {

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

if (!function_exists('has_string_keys')) {
    function has_string_keys(array $array) {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }
}

if (!function_exists('flatten')) {
    function flatten(array $array) {
        $result = array();
        $array = array_values($array);
        foreach ($array as $value) {
            if (is_array($value)) {
                $results = array_merge($result, flatten($value));
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }
}


function string_to_api_error($errmsg)
{
    $err = new WpMap_ApiError();
    $err->title($errmsg);
    return $err;
}

function array_to_api_error(array $array)
{
    $err = new WpMap_ApiError();
    foreach ($array as $key => $value) {
        $err->set($key, $value);
    }
    return $err;
}

function exception_to_api_error($e)
{
    // @todo if WP_DEBUG send meta backtrace
    return new WpMap_ApiError(500, $e->getMessage());
}

function normalize_api_error($error)
{
    // always return an array of errors
    if (is_string($error)) {
        return array(string_to_api_error($error));
    } elseif (is_array($error)) {
        if (has_string_keys($error)) {
            return array(array_to_api_error($error));
        } else {
            // this is already an array of errors
            return flatten(array_map('normalize_api_error', $error));
        }
    } elseif ($error instanceof WpMap_ApiError) {
        return array($error);
    } elseif ($error instanceof Exception || $error instanceof Error) {
        return array(exception_to_api_error($error));
    } else {
        die("Bad error");
    }
}

function api_send_errors($statusCode, $errors)
{
    status_header($statusCode);
    $errors = normalize_api_error($errors);
    foreach ($errors as $_ => $err) {
        if ($err->statusCode() === null) {
            $err->statusCode($statusCode);
        }
    }
    echo json_encode(array('errors' => $errors));
}


