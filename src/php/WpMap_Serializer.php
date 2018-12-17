<?php

defined('ABSPATH') or exit();

class WpMap_Serializer
{
    public static function serializeMapColumnValue($key, $value)
    {
        switch ($key) {
            case 'pin_config':
                if (!is_array($value)) {
                    $value = array();
                }
                $value = json_encode($value);
                break;
            case 'background':
                break;
            default:
                throw new InvalidArgumentException("Serialize unauthorized key $key");
        }
        return $value;
    }

    public static function unserializeMapColumnValue($key, $value)
    {
        switch ($key) {
            case 'pin_config':
                $value = json_decode($value);
                break;
        }
        return $value;
    }

    public static function serializePostMeta($key, $value)
    {
        switch ($key) {
            case 'wpmap_latlng':
                $value = json_encode($value);
                break;
        }
        return $value;
    }

    public static function serializePostLayerConfValue($key, $value)
    {
        return $value;
    }

    public static function unserializePostMeta($key, $value)
    {
        switch ($key) {
            case 'wpmap_latlng':
                $value = json_decode($value);
                break;
        }
        return $value;
    }

    public static function unserializePostColumn($column, $value)
    {
        switch ($column) {
            case 'ID':
                return intval($value);
        }
        return $value;
    }

    public static function unserializePostLayerConf($key, $value)
    {
        switch ($key) {
            case 'visible':
                return intval($value);
        }
        return $value;
    }

}
