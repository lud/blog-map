<?php

defined('ABSPATH') or exit();

class WpMap_Serializer
{
    public static function serializeMapColumnValue($key, $value)
    {
        switch ($key) {
            case 'pin_config':
                if (!is_array($value)) {
                    throw new \Exception("Cannot serialize pin conf : $value");
                }
                $value = json_encode($value);
                break;
            case 'background':
            case 'id':
            case 'name':
            case 'panel_bgcolor':
            case 'panel_textcolor':
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
                $value = json_decode($value, true);
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
                $value = json_decode($value, true);
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

    public static function unserializeMaps(array $maps, $asArray = false)
    {
        $results = [];
        foreach ($maps as $map) {
            $map = self::unserializeMap($map);
            if ($asArray) {
                $map = $map->as_array();
            }
            $results[] = $map;
        }
        return $results;
    }

    public static function unserializeMap($map)
    {
        foreach ($map->as_array() as $key => $value) {
            $map->$key = self::unserializeMapColumnValue($key, $value);
        }
        return $map;
    }

    public static function serializeMap($map)
    {
        foreach ($map->as_array() as $key => $value) {
            $map->$key = self::serializeMapColumnValue($key, $value);
        }
        return $map;
    }
}
