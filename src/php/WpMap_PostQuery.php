<?php

class WpMap_PostQuery {

    const META_COLUMN_KEY = 'meta_key';
    const META_COLUMN_VALUE = 'meta_value';
    const POST_COLUMN_POST_TYPE = 'post_type';
    const POST_COLUMN_POST_STATUS = 'post_status';
    const POST_FOREIGN_KEY = 'post_id';
    const POST_PRIMARY_KEY = 'ID';
    const POST_TABLE_ALIAS = 'p';
    const POST_TYPE_POST = 'post';
    const POST_TYPE_PAGE = 'page';
    const POST_STATUS_PUBLISHED = 'publish';

    private $wpdb;
    private $postColumns = array();
    private $metaKeys = array();
    private $_metaFieldsCache = null;

    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function select(array $fields)
    {
        foreach ($fields as $alias => $column) {
            // is a string alias is provided we keep it, if its a numeric array
            // key we just use the column as alias
            $alias = is_string($alias) ? $alias : $column;
            $this->postColumns[$alias] = $column;
        }
        return $this;
    }

    public function withMeta(array $keys)
    {
        $this->_metaFieldsCache = null; // clear cache
        $this->metaKeys = array_merge($this->metaKeys, $keys);
        return $this;
    }

    public function all()
    {
        $sql = $this->buildSqlForQuery();
        // @todo cast the types ! posts ids are string ATM
        $recordSet = [];
        foreach ($this->wpdb->get_results($sql) as $record) {
            $recordSet[] = $this->castAllValues($record);
        }
        return $recordSet;
    }

    private function buildMetaFields()
    {
        if ($this->_metaFieldsCache === null) {
            $this->_metaFieldsCache = array();
            foreach ($this->metaKeys as $iMeta => $metaKey) {
                $tableAlias = "pm$iMeta";
                $this->_metaFieldsCache[] = array(
                    'metaKey' => $metaKey,
                    'alias' => $metaKey,
                    'table' => $this->wpdb->postmeta,
                    'field' => self::META_COLUMN_VALUE,
                    'tableAlias' => $tableAlias
                );
            }
        }
        return $this->_metaFieldsCache;
    }

    private function buildSelectClause()
    {

        // Building the SELECT clause : we select each post field, and for each
        // meta field, we select the meta_value fields aliased with the meta key
        // name :
        //
        //   SELECT p.ID , p.post_title         -- post fields
        //        , pm1.meta_value as my_meta   -- first meta field from its tab
        //        , pm2.meta_value as my_meta2  -- second meta field
        //

        // Building all the base data : fields, table and prefixes for joins

        $tpPostColumns = self::setAllTablePrefix(
            $this->postColumns,
            self::POST_TABLE_ALIAS
        );

        $sqlPostColumns = array();

        foreach ($tpPostColumns as $alias => $prefixed) {
            $pf = $prefixed . ' as `' . self::safeSqlAlias($alias) . '`';
            $sqlPostColumns[] = $pf;
        }

        $sqlSELECT = "\nSELECT\n  " . implode(",\n  ", $sqlPostColumns);

        foreach ($this->buildMetaFields() as $mf) {
            $field = self::setTablePrefix($mf['field'], $mf['tableAlias']);
            $alias = $mf['alias'];
            $sqlSELECT .= ",\n  $field as `$alias`";
        }

        return $sqlSELECT;
    }

    private function buildFromClause(array & $statementParamsRef)
    {

        // Building the FROM clause with joins. We use the posts table and then
        // LEFT JOIN for each table alias of each meta key
        //
        //   FROM {$this->wpdb->posts} p
        //     LEFT JOIN wp_postmeta pm1
        //         ON p.ID = pm1.post_id
        //         AND pm1.meta_key = %s
        //     LEFT JOIN wp_postmeta pm2
        //         ON p.ID = pm2.post_id
        //         AND pm2.meta_key = %s
        //


        $sqlFROM = "\nFROM\n  " . $this->wpdb->posts . ' ' . self::POST_TABLE_ALIAS;
        $tpPostsPrimary = self::setTablePrefix(self::POST_PRIMARY_KEY, self::POST_TABLE_ALIAS);
        foreach ($this->buildMetaFields() as $mf) {
            $metaTable = $mf['table'];
            $metaTableAlias = $mf['tableAlias'];
            $tpForeign = self::setTablePrefix(self::POST_FOREIGN_KEY, $metaTableAlias);
            $tpMetaKeyField = self::setTablePrefix(self::META_COLUMN_KEY, $metaTableAlias);

            $sqlFROM .= "\nLEFT JOIN\n  $metaTable $metaTableAlias"
                        . "\n     ON $tpPostsPrimary = $tpForeign"
                        . "\n    AND $tpMetaKeyField = %s"
                        ;
            // As we added a '%s' for the meta key, we must register the value
            // in the statement params
            $statementParamsRef[] = $mf['metaKey'];
        }

        return $sqlFROM;
    }

    private static function buildWhereInClause($field, $count)
    {
        if (is_array($count)) {
            $count = count($count);
        }
        if ($count === 0) { throw new Exception("Must have values"); }
        if ($count === 1) {
            return "$field = %s";
        } else {
            $placeholders = implode(',', array_fill(0, $count, '%s'));
            return "$field IN ($placeholders)";
        }
    }

    private static function setDefaultWhereClauseFilters(array $filters)
    {
        $defaults = array(
            'postTypes' => array(self::POST_TYPE_POST),
            'postStatuses' => array(self::POST_STATUS_PUBLISHED)
        );
        $filters = array_merge($defaults, $filters);
        // ensure some filters contain arrays
        $arrayValues = array('postTypes', 'postStatuses');
        foreach ($arrayValues as $key) {
            $filters[$key] = (array) $filters[$key];
        }
        return $filters;
    }

    private static function buildWhereClause(array & $statementParamsRef, $filters = array())
    {

        $filters = self::setDefaultWhereClauseFilters($filters);

        // -- post types

        $tpPostType = self::setTablePrefix(self::POST_COLUMN_POST_TYPE, self::POST_TABLE_ALIAS);
        $sqlINtypes = self::buildWhereInClause($tpPostType, $filters['postTypes']);
        self::arrayPushAll($statementParamsRef, $filters['postTypes']);

        // -- post statuses

        $tpPostStatus = self::setTablePrefix(self::POST_COLUMN_POST_STATUS, self::POST_TABLE_ALIAS);
        $sqlINstatuses = self::buildWhereInClause($tpPostStatus, $filters['postStatuses']);
        self::arrayPushAll($statementParamsRef, $filters['postStatuses']);

        $sqlWHERE = "\nWHERE\n     $sqlINtypes\n AND $sqlINstatuses";
        return $sqlWHERE;
    }

    private function buildSqlForQuery()
    {
        $postColumns = $this->postColumns;
        $metaKeys = $this->metaKeys;

        // $statementParamsRef bindings for SQL prepare, will be passed by
        // reference and augmented;
        $statementParamsRef = array();

        $sqlSELECT = $this->buildSelectClause();
        $sqlFROM = $this->buildFromClause($statementParamsRef);
        $sqlWHERE = $this->buildWhereClause($statementParamsRef);

        $sql = implode("", array($sqlSELECT, $sqlFROM, $sqlWHERE)) . "\n";

        $prepared = call_user_func_array(
            array($this->wpdb, 'prepare'),
            array_merge(array($sql), $statementParamsRef)
        );
        var_dump($prepared);
        return $prepared;
    }

    private static function setAllTablePrefix($fields, $table) {
        $tps = array();
        foreach ($fields as $k => $f) {
            $tps[$k] = self::setTablePrefix($f, $table);
        }
        return $tps;
    }

    private static function setTablePrefix($field, $table) {
        return "$table.$field";
    }

    public static function arrayPushAll(&$arrayByRef, $values)
    {
        foreach ($values as $val) {
            $arrayByRef[] = $val;
        }
    }

    private static function safeSqlAlias(string $alias) {
        if (preg_match('/[^a-zA-Z0-9_]/', $alias)) {
            throw new Exception("Alias $alias is invalid");
        }
        return $alias;
    }

    private function castAllValues($record)
    {
        foreach ($this->postColumns as $alias => $column) {
            $record->$alias = $this->castPostColumn($column, $record->$alias);
        }
        foreach ($this->metaKeys as $key) {
            $record->$key = $this->castMetaValue($key, $record->$key);
        }
        return $record;
    }

    protected function castPostColumn($column, $value)
    {
        echo "cast col $column: $value\n";
        switch ($column) {
            case 'ID':
                return intval($value);
        }
        return $value;
    }

    protected function castMetaValue($key, $value)
    {
        echo "cast meta $key: $value\n";
        switch ($key) {
            case 'wpmap_visibility':
                return json_decode($value);
        }
        return $value;
    }
}

