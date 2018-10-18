<?php

defined('ABSPATH') or exit();

class WpMap_Data {

    const MAPS_TABLE_NAME = 'wpmap_maps';

    private $wpdb;
    private $mapsTable;

    public static function create()
    {
        global $wpdb;
        // This is bad because there the connection is global
        ORM::configure('connection_string', 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET);
        ORM::configure('username', DB_USER);
        ORM::configure('password', DB_PASSWORD);
        return new WpMap_Data($wpdb);
    }

    private function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->mapsTable = self::mapsTableName($wpdb);
    }

    public static function mapsTableName(wpdb $wpdb)
    {
        return $wpdb->prefix . WpMap_Data::MAPS_TABLE_NAME;
    }

    private function forTable($table)
    {
        $table = $this->wpdb->prefix . $table;
        return ORM::for_table($table);
    }

    public function findMap($mapID)
    {
        return $this
            ->forTable(self::MAPS_TABLE_NAME)
            ->where('id', $mapID)
            ->findOne();
    }

    public static function serializePostColumnValue($key, $value)
    {
        switch ($key) {
            case 'pin_config':
                if (!is_array($value)) {
                    $value = array();
                }
                $value = json_encode($value);
                break;
            default:
                throw new InvalidArgumentException("Unauthorized key $key");
        }
        return $value;
    }


}
