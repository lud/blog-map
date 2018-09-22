<?php

defined('ABSPATH') or exit();


class WpMap_AdminPage {

    private function __construct()
    {
    }

    public static function render()
    {
        $self = new WpMap_AdminPage();
        return $self->doRender();
    }

    private function doRender()
    {
        wp_enqueue_script('wpmap_admin_bundle_js');
        echo '<pre>';
        $query = $this->getAllPosts();
        var_dump(        $query);
        echo '</pre>';
    }

    private function getAllPosts()
    {
        global $wpdb;
        $postFields = array('ID', 'post_title');
        $metaKeys = array('wpmap_visibility', 'wpmap_latlng');
        $sql = $this->buildSqlForQuery($postFields, $metaKeys);
        return $wpdb->get_results($sql);
    }

    private static function buildSqlForQuery($postFields, $metaKeys)
    {
        global $wpdb;

        // @todo no magic string 'ID' here, get it from a constant or var
        $POST_PRIMARY_KEY = 'ID'; // in posts table
        $POST_FOREIGN_KEY = 'post_id'; // in meta table
        $META_KEY_FIELD = 'meta_key'; // in meta table
        $META_VALUE_FIELD = 'meta_value';

        // Building all the base data : fields, table and prefixes for joins

        $postsTabAlias = 'p'; // The alias/prefix for the posts table
        $tpPostFields = self::setAllTablePrefix($postFields, $postsTabAlias);

        $metaFields = array();
        foreach ($metaKeys as $iMeta => $metaKey) {
            $tableAlias = "pm$iMeta";
            $metaFields[] = array(
                'metaKey' => $metaKey,
                'alias' => $metaKey,
                'table' => $wpdb->postmeta,
                'field' => $META_VALUE_FIELD,
                'tableAlias' => $tableAlias
            );
        }

        $statementParams = array(); // bindings for SQL prepare

        // Building the SELECT clause : we select each post field, and for each
        // meta field, we select the meta_value fields aliased with the meta key
        // name :
        //
        //   SELECT p.ID , p.post_title         -- post fields
        //        , pm1.meta_value as my_meta   -- first meta field from its tab
        //        , pm2.meta_value as my_meta2  -- second meta field
        //

        $sqlSELECT = 'SELECT ' . implode(', ', $tpPostFields);
        foreach ($metaFields as $mf) {
            $field = self::setTablePrefix($mf['field'], $mf['tableAlias']);
            $alias = $mf['alias'];
            $sqlSELECT .= ", $field as $alias";
        }

        // Building the FROM clause with joins. We use the posts table and then
        // LEFT JOIN for each table alias of each meta key
        //
        //   FROM {$wpdb->posts} p
        //     LEFT JOIN wp_postmeta pm1
        //         ON p.ID = pm1.post_id
        //         AND pm1.meta_key = %s
        //     LEFT JOIN wp_postmeta pm2
        //         ON p.ID = pm2.post_id
        //         AND pm2.meta_key = %s
        //

        $sqlFROM = 'FROM ' . $wpdb->posts . ' ' . $postsTabAlias;
        $tpPostsPrimary = self::setTablePrefix($POST_PRIMARY_KEY, $postsTabAlias);
        foreach ($metaFields as $mf) {

            $field = self::setTablePrefix($mf['field'], $mf['tableAlias']);
            $metaTable = $mf['table'];
            $metaTableAlias = $mf['tableAlias'];
            $alias = $mf['alias'];
            $tpForeign = self::setTablePrefix($POST_FOREIGN_KEY, $metaTableAlias);
            $tpMetaKeyField = self::setTablePrefix($META_KEY_FIELD, $metaTableAlias);
            $sqlSELECT .= ", $field as $alias";

            $sqlFROM .= "\nLEFT JOIN $metaTable $metaTableAlias"
                        . "\n\t  ON $tpPostsPrimary = $tpForeign"
                        . "\n\t AND $tpMetaKeyField = %s"
                        ;
            // As we added a '%s' for the meta key, we must register the value
            // in the statement params
            $statementParams[] = $mf['metaKey'];
        }

        $sql = implode("\n", array($sqlSELECT, $sqlFROM));

        $prepared = call_user_func_array(
            array($wpdb, 'prepare'),
            array_merge(array($sql), $statementParams)
        );
        return $prepared;
    }

    private static function setAllTablePrefix($fields, $table) {
        $tps = array();
        foreach ($fields as $f) {
            $tps[] = self::setTablePrefix($f, $table);
        }
        return $tps;
    }

    private static function setTablePrefix($field, $table) {
        return "$table.$field";
    }
}
