<?php

defined('ABSPATH') or exit();

class WpMap_JS
{
    // make a IIFE that will take the _wpmap global object and
    // patch it with the data
    public static function dataToConfigScriptTag(array $data)
    {
        return array(
            '<script type="text/javascript">',
            static::dataToConfigFunction($data),
            '</script>',
        );
    }

    private static function dataToConfigFunction(array $data)
    {
        return array(
            ';(function(wpmap,data){',
            'for (var k in data)',
            'if (Object.prototype.hasOwnProperty.call(data, k))',
            'wpmap[k] = data[k]', // no nested merge
            '}(window._wpmap = window._wpmap || {},',json_encode($data), '));',
        );
    }
}
