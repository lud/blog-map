<?php

defined('ABSPATH') or exit();

class IOList
{
    public static function out($term)
    {
        if (is_array($term)) {
            array_map(array(__CLASS__, 'out'), $term);
        } elseif (is_string($term)) {
            echo $term;
        } elseif (is_integer($term)) {
            echo chr($term);
        } elseif (null === $term || false === $term) {
            // pass
        } else {
            $export = var_export($term, true);
            throw new \Exception("Cannot output value $export");
        }
    }
}
