<?php

defined('ABSPATH') or exit();

class WpMap_Migration {

    const VERSION_OPT_KEY = 'wpmap_migrations';

    private $mapTableName;
    private $wpdb;

    private static function migrations()
    {
        return array(
            'v0.0.1-createMapsTable' => array('createMapsTable', 'dropMapsTable'),
            'v0.0.1-createDefaultMap' => 'createDefaultMap',
            'v0.0.2-createPostsLayerconfTable' => array('createPostsLayerconfTable', 'dropPostsLayerconfTable'),
        );
    }

    public static function fromEnv()
    {
        global $wpdb;
        return new self($wpdb);
    }

    public static function migrateEnv()
    {
        self::fromEnv()->migrate();
    }

    public static function rollbackEnv()
    {
        self::fromEnv()->rollback();
    }

    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function getInstalledVersions()
    {
        if (get_option(self::VERSION_OPT_KEY, false) === false) {
            add_option(self::VERSION_OPT_KEY, array(), false, false);
        }
        // returns an array of options with given key
        return get_option(self::VERSION_OPT_KEY);
    }

    public function setInstalledVersions($versions)
    {
        update_option(self::VERSION_OPT_KEY, $versions);
    }

    public function registerMigration($key)
    {
        $versions = $this->getInstalledVersions();
        $versions[]= $key;
        $this->setInstalledVersions($versions);
    }

    public function unregisterMigration($key)
    {
        $versions = $this->getInstalledVersions();
        if (($index = array_search($key, $versions)) !== false) {
            unset($versions[$index]);
        }
        $this->setInstalledVersions($versions);
    }

    public function migrate()
    {
        global $wpdb;
        $installeds = $this->getInstalledVersions();
        // as soon as a migration fails, we stop the entire process
        try {
            foreach (self::migrations() as $key => $migration) {
                if (!in_array($key, $installeds)) {
                    error_log("[WpMap_Migration] apply $key");
                    $this->runMigration($key, $migration);
                    $this->registerMigration($key);
                    error_log("[WpMap_Migration] apply $key success");
                }
            }
        } catch (Exception $e) {
            // Here we must log/display the error somewhere ...
            $msg = $e->getMessage();
            error_log("[WpMap_Migration] $msg");
            throw $e;
        }
    }

    public function rollback()
    {
        global $wpdb;
        $installeds = $this->getInstalledVersions();
        // as soon as a migration fails, we stop the entire process
        $migrations = array_reverse(self::migrations());
        try {
            foreach ($migrations as $key => $migration) {
                if (in_array($key, $installeds)) {
                    error_log("[WpMap_Migration] rollback $key");
                    $this->rollbackMigration($key, $migration);
                    $this->unregisterMigration($key);
                    error_log("[WpMap_Migration] rollback $key success");
                }
            }
        } catch (Exception $e) {
            // Here we must log/display the error somewhere ...
            $msg = $e->getMessage();
            error_log("[WpMap_Migration] $msg");
            // on rollback we rethrow because we do not want to let the system
            // inconsistent
            throw $e;
        }
    }

    public function runMigration($key, $migration)
    {
        global $wpdb;
        $method = is_string($migration) ? $migration : $migration[0];
        if (! $this->{$method}($wpdb)) {
            throw new Exception("$key apply failure, method returned falsy");
        }
    }

    public function rollbackMigration($key, $migration)
    {
        global $wpdb;
        if (is_string($migration)) {
            // no rollback method, skip
        } elseif (isset($migration[1])) {
            $method = $migration[1];
            if (! $this->{$method}($wpdb)) {
                throw new Exception("$key rollback failure, method returned falsy");
            }
        }
        // else if no [1] index, skip
    }

    // -----------------------------------------------------------------------
    // MIGRATIONS
    // -----------------------------------------------------------------------


    private function createMapsTable(wpdb $wpdb)
    {
        $table = WpMap_Data::mapsTableName($wpdb);
        $charset_collate = $wpdb->get_charset_collate();

        $defaultPin = '{"height": 34, "radius": 14, "fillColor": "#7babdf", "strokeColor": "#0088aa"}';
        $defaultBackground = WpMap_Data::DEFAULT_BACKGROUND_LAYER;
        $sqlCreate = "CREATE TABLE $table (
          id VARCHAR(32) NOT NULL,
          name TINYTEXT NULL,
          pin_config VARCHAR(255) DEFAULT '$defaultPin',
          background VARCHAR(32) DEFAULT '$defaultBackground',
          PRIMARY KEY  (id)
        ) $charset_collate;";

        return !!$wpdb->query($sqlCreate);
    }

    private function dropMapsTable(wpdb $wpdb)
    {
        $table = WpMap_Data::mapsTableName($wpdb);
        $sql = "DROP TABLE IF EXISTS $table;";
        return $wpdb->query($sql);
    }

    private function createDefaultMap(wpdb $wpdb)
    {
        $inserted = $wpdb->insert(WpMap_Data::mapsTableName($wpdb), array(
                'id' => 'default-map',
                'name' => 'Default Map',
            )
        );
        return $inserted;
    }

    private function createPostsLayerconfTable(wpdb $wpdb)
    {
        $table = WpMap_Data::postsLayerconfTableName($wpdb);
        $charset_collate = $wpdb->get_charset_collate();
        $defaultIcon = WpMap_Data::DEFAULT_ICON;

        $sqlCreate = "CREATE TABLE $table (
          post_id BIGINT(20) NOT NULL,
          map_id VARCHAR(32) NOT NULL,
          visible BOOLEAN NOT NULL DEFAULT 0,
          icon VARCHAR(32) NOT NULL DEFAULT '$defaultIcon',
          PRIMARY KEY  (post_id, map_id)
        ) $charset_collate;";

        return !!$wpdb->query($sqlCreate);
    }

    private function dropPostsLayerconfTable(wpdb $wpdb)
    {
        $table = WpMap_Data::postsLayerconfTableName($wpdb);
        $sql = "DROP TABLE IF EXISTS $table;";
        return $wpdb->query($sql);
    }


}

