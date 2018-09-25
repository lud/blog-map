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
    const POST_STATUS_DRAFT = 'draft';
    const POST_STATUS_PRIVATE = 'private';
    const POST_KEY = '_id';

    private $wpdb;
    private $postColumns = array();
    private $metaKeys = array();
    private $whereConditions = array();
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
            self::safeSqlField($column);
            self::safeSqlField($alias);
            $this->postColumns[$alias] = $column;
        }
        return $this;
    }

    public function withMeta(array $keys)
    {
        foreach ($keys as $k) {
            self::safeSqlField($k);
        }
        $this->_metaFieldsCache = null; // clear cache
        $this->metaKeys = array_merge($this->metaKeys, $keys);
        return $this;
    }

    public function where(array $conditions)
    {
        foreach ($conditions as $key => $value) {
            if (is_integer($key)
             && is_array($value)
             && isset($value['column'])
             && isset($value['unsafeValue'])) {
                // already a formated condition
                self::safeSqlField($value['column']);
                $this->whereConditions[] = $value;
            } elseif (is_string($key)) {
                self::safeSqlField($key);
                $this->whereConditions[] = array(
                    'column' => $key,
                    'unsafeValue' => $value
                );
            } else {
                trigger_error('@todo bad condition');
            }
        }
        return $this;
    }

    public function all()
    {
        $sql = $this->buildSqlForQuery();
        // @todo cast the types ! posts ids are string ATM
        $recordSet = [];
        foreach ($this->wpdb->get_results($sql) as $record) {
            $recordSet[] = $this->recordToPost($record);
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

        $tpPrimary = self::setTablePrefix(
            self::POST_PRIMARY_KEY,
            self::POST_TABLE_ALIAS
        );

        $tpPostColumns[self::POST_KEY] = $tpPrimary;
        $sqlPostColumns = array();

        foreach ($tpPostColumns as $alias => $prefixed) {
            $pf = $prefixed . ' as `' . $alias . '`';
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

    private static function buildWhereClause(array & $statementParamsRef, $filters = array())
    {

        $sqlWHERE = "\nWHERE 1=1";

        // -- post conditions

        $usedColumns = array();

        if (count($filters)) {
            foreach ($filters as $condition) {
                $column = $condition['column'];
                $usedColumns[$column] = true;
                $sqlItem = self::buildSqlWhereItem($condition, $statementParamsRef);
                $sqlWHERE .= "\n  AND $sqlItem";
             }
        }

        // -- post types

        // if (!isset($usedColumns[self::POST_COLUMN_POST_TYPE])) {
        //     $sqlINtypes = self::buildSqlWhereItem(
        //         array(
        //             'column' => self::POST_COLUMN_POST_TYPE,
        //             'unsafeValue' => array(self::POST_TYPE_POST)),
        //         $statementParamsRef);
        //     $sqlWHERE .= "\n  AND $sqlINtypes";
        //  }

        // // -- post statuses

        // if (!isset($usedColumns[self::POST_COLUMN_POST_STATUS])) {
        //     $sqlINstatuses = self::buildSqlWhereItem(
        //         array(
        //             'column' => self::POST_COLUMN_POST_STATUS,
        //             'unsafeValue' => array(self::POST_STATUS_PUBLISHED)),
        //         $statementParamsRef);
        //     $sqlWHERE .= "\n  AND $sqlINstatuses";
        // }

        return $sqlWHERE;
    }

    private static function buildSqlWhereItem($condition, &$statementParamsRef)
    {
        $column = $condition['column'];
        $tpCol = $tpPostStatus = self::setTablePrefix($column, self::POST_TABLE_ALIAS);
        $unsafeValue = $condition['unsafeValue'];
        if (is_array($unsafeValue)) {
            $sqlIN = self::buildWhereInClause($tpCol, $unsafeValue);
            self::arrayPushAll($statementParamsRef, $unsafeValue);
            return $sqlIN;
        } else {
            $sqlEqual = "$tpCol = %s";
            $statementParamsRef[] = $unsafeValue;
            return $sqlEqual;
        }
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

    private function buildSqlForQuery()
    {
        $postColumns = $this->postColumns;
        $metaKeys = $this->metaKeys;

        // $statementParamsRef bindings for SQL prepare, will be passed by
        // reference and augmented;
        $statementParamsRef = array();

        $sqlSELECT = $this->buildSelectClause();
        $sqlFROM = $this->buildFromClause($statementParamsRef);
        $sqlWHERE = $this->buildWhereClause($statementParamsRef, $this->whereConditions);

        $sql = implode("", array($sqlSELECT, $sqlFROM, $sqlWHERE)) . "\n";

        $prepared = call_user_func_array(
            array($this->wpdb, 'prepare'),
            array_merge(array($sql), $statementParamsRef)
        );

        // rr($prepared);

        return $prepared;
    }

    private static function setAllTablePrefix($fields, $table) {
        $tps = array();
        foreach ($fields as $k => $f) {
            $tps[$k] = self::setTablePrefix($f, $table);
        }
        return $tps;
    }

    private static function setTablePrefix($field, $table)
    {
        return "$table.$field";
    }

    public static function arrayPushAll(&$arrayByRef, $values)
    {
        foreach ($values as $val) {
            $arrayByRef[] = $val;
        }
    }

    private static function safeSqlField(string $alias)
    {
        if (preg_match('/[^a-zA-Z0-9_]/', $alias)) {
            throw new Exception("Alias $alias is invalid");
        }
        return $alias;
    }

    private function recordToPost($record)
    {
        $props = new stdClass;;
        foreach ($this->postColumns as $alias => $column) {
            $props->$alias = $this->unserializePostColumn($column, $record->$alias);
        }
        $meta = new stdClass;;
        foreach ($this->metaKeys as $key) {
            $meta->$key = $this->unserializePostMeta($key, $record->$key);
        }
        return (object) array(
            self::POST_KEY => intval($record->{self::POST_KEY}),
            'props' => $props,
            'meta' => $meta
        );
    }

    public static function unserializePostColumn($column, $value)
    {
        switch ($column) {
            case 'ID':
                return intval($value);
        }
        return $value;
    }

    public static function serializePostMeta($key, $value)
    {
        switch ($key) {
            case 'wpmap_visibilities':
                if (!is_array($value)) {
                    $value = array();
                }
                $value = json_encode($value);
        }
        return $value;
    }

    public static function unserializePostMeta($key, $value)
    {
        switch ($key) {
            case 'wpmap_visibilities':
                $value = json_decode($value);
                if (null === $value) return array();
        }
        return $value;
    }
}

