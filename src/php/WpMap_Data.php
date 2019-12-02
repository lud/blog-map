<?php

defined('ABSPATH') or exit();

class WpMap_Data
{

    const MAPS_TABLE_NAME = 'wpmap_maps';
    const DEFAULT_ICON = 'star';
    const DEFAULT_BACKGROUND_LAYER = 'OpenTopoMap';
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

    private static $instance;
    private $connectionName;
    private $tablePrefix;

    public static function getInstance()
    {
        if (null === static::$instance) {
            global $wpdb;
            $connectionName = 'wpmap_conn_' . uniqid();
            static::$instance = new static($connectionName, $wpdb->prefix);
        }
        return static::$instance;
    }

    private function __construct($connectionName, $tablePrefix)
    {
        // This is bad because there the connection is global
        ORM::configure('connection_string', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, $connectionName);
        ORM::configure('username', DB_USER, $connectionName);
        ORM::configure('password', DB_PASSWORD, $connectionName);
        $this->connectionName = $connectionName;
        $this->tablePrefix = $tablePrefix;
    }

    public static function mapsTableName(wpdb $wpdb)
    {
        return $wpdb->prefix . WpMap_Data::MAPS_TABLE_NAME;
    }

    public static function postsLayerconfTableName(wpdb $wpdb)
    {
        return $wpdb->prefix . WpMap_Data::POSTS_LAYERCONF_TABLE_NAME;
    }

    public function findMap($mapID)
    {
        $map = $this
            ->forTable(self::MAPS_TABLE_NAME)
            ->where('id', $mapID)
            ->findOne();
        $map = $map ? WpMap_Serializer::unserializeMap($map) : false;
        return $map;
    }

    public function postLayer($postID, $mapID)
    {
        $sql = <<<'SQL'
            SELECT lc.icon as "icon",
                   lc.visible as "visible"
            FROM {POSTS_LAYERCONF_TABLE_NAME} lc
            WHERE lc.post_id = :post_id AND lc.map_id = :map_id
SQL;
        $layer = $this
            ->runQuery($sql, array(
                ':post_id' => $postID,
                ':map_id' => $mapID
            ))
            ->findOne();
        return $this->readLayer($layer, array('icon', 'visible'));
    }

    public function mapsConfigs()
    {
        $table = $this->tprefix(WpMap_Data::MAPS_TABLE_NAME);
        $rs = $this->runQuery("SELECT * FROM $table")->findMany();
        return $rs;
    }

    public function mapPosts($mapID)
    {
        $sql = <<<'SQL'
            SELECT p.ID,
                   p.ID as {POST_KEY},
                   p.post_title as "title",
                   p.guid as "url",
                   p.post_status as "status",
                   p.post_type as "type",
                   lc.icon as "icon",
                   m1.meta_value as "wpmap_latlng",
                   m2.meta_value as "wpmap_country_alpha2",
                   m3.meta_value as "wpmap_geocoded"
            FROM {POSTS_TABLE_NAME} p
            LEFT JOIN {POSTS_LAYERCONF_TABLE_NAME} lc ON p.ID = lc.post_id AND lc.map_id = :map_id
            LEFT JOIN {META_TABLE_NAME} m1 ON p.ID = m1.post_id AND m1.meta_key = 'wpmap_latlng'
            LEFT JOIN {META_TABLE_NAME} m2 ON p.ID = m2.post_id AND m2.meta_key = 'wpmap_country_alpha2'
            LEFT JOIN {META_TABLE_NAME} m3 ON p.ID = m3.post_id AND m3.meta_key = 'wpmap_geocoded'
            WHERE lc.visible = 1
              AND p.post_status = 'publish'
              AND p.post_type IN ('post', 'page')
SQL;
        return $this->runPostQuery($sql, array(':map_id' => $mapID));
    }

    public function posts($mapID)
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
            LEFT JOIN {POSTS_LAYERCONF_TABLE_NAME} lc ON p.ID = lc.post_id AND lc.map_id = :map_id
            LEFT JOIN {META_TABLE_NAME} m1 ON p.ID = m1.post_id AND m1.meta_key = 'wpmap_latlng'
            LEFT JOIN {META_TABLE_NAME} m2 ON p.ID = m2.post_id AND m2.meta_key = 'wpmap_country_alpha2'
            LEFT JOIN {META_TABLE_NAME} m3 ON p.ID = m3.post_id AND m3.meta_key = 'wpmap_geocoded'
            WHERE p.post_status = 'publish'
              AND p.post_type IN ('post', 'page')
SQL;
        return $this->runPostQuery($sql, array(':map_id' => $mapID));
    }

    public function postMeta($postID)
    {
        $sql = <<<'SQL'
            SELECT m1.meta_value as "wpmap_latlng",
                   m2.meta_value as "wpmap_country_alpha2",
                   m3.meta_value as "wpmap_geocoded"
            FROM {POSTS_TABLE_NAME} p
            LEFT JOIN {META_TABLE_NAME} m1 ON p.ID = m1.post_id AND m1.meta_key = 'wpmap_latlng'
            LEFT JOIN {META_TABLE_NAME} m2 ON p.ID = m2.post_id AND m2.meta_key = 'wpmap_country_alpha2'
            LEFT JOIN {META_TABLE_NAME} m3 ON p.ID = m3.post_id AND m3.meta_key = 'wpmap_geocoded'
            WHERE p.ID = :post_id
SQL;
        $layer = $this
            ->runQuery($sql, array(':post_id' => $postID))
            ->findOne();
        return $this->readMeta($layer, array('wpmap_latlng', 'wpmap_country_alpha2', 'wpmap_geocoded'));
    }

    public function updatePostLayerConf($postID, $mapID, $key, $value)
    {
        $sql = <<<'SQL'
            INSERT INTO {POSTS_LAYERCONF_TABLE_NAME}
                (post_id, map_id, {CONF_KEY})
            VALUES
                (:post_id, :map_id, :conf_value)
            ON DUPLICATE KEY UPDATE
                {CONF_KEY} = :conf_value

SQL;
        $sql = str_replace('{POSTS_LAYERCONF_TABLE_NAME}', $this->tprefix(self::POSTS_LAYERCONF_TABLE_NAME), $sql);
        $sql = str_replace('{CONF_KEY}', $key, $sql);

        $query = $this
            ->forTable(self::POSTS_LAYERCONF_TABLE_NAME)
            ->raw_execute($sql, array(
                ':map_id' => $mapID,
                ':post_id' => $postID,
                ':conf_value' => $value,
            ), $this->connectionName);
            return $query;
    }

    // ------------------

    private function setQueryEnv($sql)
    {
        $sql = str_replace('{POST_KEY}', self::POST_KEY, $sql);
        $sql = str_replace('{POSTS_TABLE_NAME}', $this->tprefix(self::POSTS_TABLE_NAME), $sql);
        $sql = str_replace('{META_TABLE_NAME}', $this->tprefix(self::META_TABLE_NAME), $sql);
        $sql = str_replace('{POSTS_LAYERCONF_TABLE_NAME}', $this->tprefix(self::POSTS_LAYERCONF_TABLE_NAME), $sql);
        return $sql;
    }

    private function runQuery($sql, array $queryParams = array())
    {
        $sql = $this->setQueryEnv($sql);
        $query = $this
            ->forTable('__IGNORE__')
            ->raw_query($sql, $queryParams);
        return $query;
    }

    private function runPostQuery($sql, $queryParams)
    {

        $query = $this
            ->runQuery($sql, $queryParams)
            ->findMany();

        $columns = array('ID', 'title', 'url', 'status', 'type');
        $metaKeys = array('wpmap_latlng', 'wpmap_country_alpha2', 'wpmap_geocoded');
        $layerKeys = array('icon', 'visible');

        $records = array();
        foreach ($query as $item) {
            $records[] = static::expandPostRecord($item, $propKeys, $metaKeys, $layerKeys);
        }
        return $records;
    }



    private function expandPostRecord(ORM $record, $columns, $metaKeys, $layerKeys)
    {
        $props = $this->readColumns($record, $columns);
        $meta = $this->readMeta($record, $metaKeys);
        $layer = $this->readLayer($record, $layerKeys);

        return (object) array(
            self::POST_KEY => intval($record->{self::POST_KEY}),
            'props' => $props,
            'layer' => $layer,
            'meta' => $meta
        );
    }

    private function readColumns($record, $keys)
    {
        $result = new stdClass();
        foreach ($keys as $key) {
            $result->$key = WpMap_Serializer::unserializePostColumn($key, $record->$key);
        }
        return $result;
    }

    private function readMeta($record, $keys)
    {
        $result = new stdClass();
        foreach ($keys as $key) {
            $result->$key = WpMap_Serializer::unserializePostMeta($key, $record->$key);
        }
        return $result;
    }

    private function readLayer($record, $keys)
    {
        $result = new stdClass();
        foreach ($keys as $key) {
            $result->$key = is_null($record->$key)
                ? $this->defaultLayerConfValue($key)
                : WpMap_Serializer::unserializePostLayerConf($key, $record->$key)
                ;
        }
        return $result;
    }

    private function defaultLayerConfValue($key)
    {
        switch ($key) {
            case 'icon':
                return self::DEFAULT_ICON;
            case 'visible':
                return 0;
            default:
                throw new \Exception("No default value for layer conf key '$key'");
                break;
        }
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

class WpMap_ORM extends ORM
{

    // Overriding the for_table() method to require a connection_name
    // but we must change the method name as we slightly change the
    // signature
    public static function customForTable($table_name, $connection_name)
    {
        static::_setup_db($connection_name);
        return new static($table_name, array(), $connection_name);
    }
}
