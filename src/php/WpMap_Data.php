<?php

defined('ABSPATH') or exit();

class WpMap_Data {

    const MAPS_TABLE_NAME = 'wpmap_maps';
    const META_TABLE_NAME = 'postmeta';
    const POSTS_TABLE_NAME = 'posts';
    const META_COLUMN_KEY = 'meta_key';
    const META_COLUMN_VALUE = 'meta_value';
    const POST_COLUMN_POST_STATUS = 'post_status';
    const POST_COLUMN_POST_TYPE = 'post_type';
    const POST_FOREIGN_KEY = 'post_id';
    const MAP_FOREIGN_KEY = 'map_id';
    const POST_KEY = '_id';
    const POST_PRIMARY_KEY = 'ID';
    const POST_STATUS_DRAFT = 'draft';
    const POST_STATUS_PRIVATE = 'private';
    const POST_STATUS_PUBLISHED = 'publish';
    const POST_TABLE_ALIAS = 'p';
    const POST_TYPE_PAGE = 'page';
    const POST_TYPE_POST = 'post';
    const POSTS_LAYERCONF_TABLE_NAME = 'wpmap_posts_layerconf';

    private $wpdb;

    static private $instance;
    private $connectionName;
    private $tablePrefix;

    public static function getInstance()
    {
        if (null === static::$instance) {
            global $wpdb;
            $connectionName = 'wpmap_conn_'.uniqid();
            static::$instance = new static($connectionName, $wpdb->prefix);
        }
        return static::$instance;
    }

    private function __construct($connectionName, $tablePrefix)
    {
        // This is bad because there the connection is global
        ORM::configure('connection_string', 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET, $connectionName);
        ORM::configure('username', DB_USER, $connectionName);
        ORM::configure('password', DB_PASSWORD, $connectionName);
        $this->connectionName = $connectionName;
        $this->tablePrefix = $tablePrefix;
    }

    public static function mapsTableName()
    {
        return static::getInstance()->tprefix(WpMap_Data::MAPS_TABLE_NAME);
    }

    public static function postsLayerconfTableName()
    {
        return static::getInstance()->tprefix(WpMap_Data::POSTS_LAYERCONF_TABLE_NAME);
    }

    public function findMap($mapID)
    {
        return $this
            ->forTable(self::MAPS_TABLE_NAME)
            ->where('id', $mapID)
            ->findOne();
    }

    public function posts($mapID, $drafts = false)
    {
        $sql = <<<'SQL'
            SELECT p.ID,
                   p.ID as {POST_KEY},
                   p.post_title as "title",
                   p.guid as "url",
                   p.post_status as "status",
                   p.post_type as "type",
                   lc.icon as "icon",
                   lc.visible as "visible",
                   m1.meta_value as "wpmap_latlng",
                   m2.meta_value as "wpmap_country_alpha2",
                   m3.meta_value as "wpmap_geocoded"
            FROM {POSTS_TABLE_NAME} p
            LEFT JOIN {POSTS_LAYERCONF_TABLE_NAME} lc ON p.ID = lc.post_id AND lc.map_id = :mapid
            LEFT JOIN {META_TABLE_NAME} m1 ON p.ID = m1.post_id AND m1.meta_key = 'wpmap_latlng'
            LEFT JOIN {META_TABLE_NAME} m2 ON p.ID = m2.post_id AND m2.meta_key = 'wpmap_country_alpha2'
            LEFT JOIN {META_TABLE_NAME} m3 ON p.ID = m3.post_id AND m3.meta_key = 'wpmap_geocoded'
SQL;

        $sql = str_replace('{POST_KEY}', self::POST_KEY, $sql);
        $sql = str_replace('{POSTS_TABLE_NAME}', $this->tprefix(self::POSTS_TABLE_NAME), $sql);
        $sql = str_replace('{META_TABLE_NAME}', $this->tprefix(self::META_TABLE_NAME), $sql);
        $sql = str_replace('{POSTS_LAYERCONF_TABLE_NAME}', $this->tprefix(self::POSTS_LAYERCONF_TABLE_NAME), $sql);

        $propKeys = array('ID', 'title', 'url', 'status', 'type');
        $metaKeys = array('wpmap_latlng', 'wpmap_country_alpha2', 'wpmap_geocoded');
        $layerKeys = array('icon', 'visible');

        $query = $this
            ->forTable(self::POSTS_TABLE_NAME)
            ->raw_query($sql, array(':mapid' => $mapID));
        $query = $query->findMany();
        $records = array();
        foreach ($query as $item) {
            $records[] = static::expandRecord($item, $propKeys, $metaKeys, $layerKeys);
        }
        return $records;
    }

    // ------------------

    private function expandRecord(ORM $record, $propKeys, $metaKeys, $layerKeys)
    {
        $props = new stdClass;
        $meta = new stdClass;
        $layer = new stdClass;
        foreach ($propKeys as $column) {
            $props->$column = WpMap_Serializer::unserializePostColumn($column, $record->$column);
        }
        foreach ($metaKeys as $mkey) {
            $meta->$mkey = WpMap_Serializer::unserializePostMeta($mkey, $record->$mkey);
        }
        foreach ($layerKeys as $lkey) {
            $layer->$lkey = $record->$lkey;
        }
        return (object) array(
            self::POST_KEY => intval($record->{self::POST_KEY}),
            'props' => $props,
            'layer' => $layer,
            'meta' => $meta
        );
    }

    private function forTable($table)
    {
        $table = $this->tprefix($table);
        return WpMap_ORM::customForTable($table, $this->connectionName);
    }

    private function tprefix($table)
    {
        return $this->tablePrefix . $table;
    }
}

class WpMap_ORM extends ORM {

    // public function quote($term) {
    //     return $this->_quote_identifier($term);
    // }

    public static function customForTable($table_name, $connection_name) {
        static::_setup_db($connection_name);
        return new static($table_name, array(), $connection_name);
    }
}
