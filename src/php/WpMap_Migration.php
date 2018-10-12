<?php

defined('ABSPATH') or exit();

class WpMap_Migration {

    const MAPS_TABLE_NAME = 'wpmap_maps';
    const VERSION_OPT = 'wpmap_migrations';

    private $mapTableName;
    private $wpdb;

    private static function migrations()
    {
        return array(
            'v0.0.1-createMapTable' => array('createMapTable', 'dropMapTable'),
            'v0.0.1-createDefaultMap' => 'createDefaultMap',
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

    public static function mapsTableName(wpdb $wpdb)
    {
        return $wpdb->prefix . self::MAPS_TABLE_NAME;
    }

    public function getInstalledVersions()
    {
        if (get_option(self::VERSION_OPT, false) === false) {
            add_option(self::VERSION_OPT, array(), false, false);
        }
        return get_option(self::VERSION_OPT);
    }

    public function setInstalledVersions($versions)
    {
        update_option(self::VERSION_OPT, $versions);
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


    private function createMapTable(wpdb $wpdb)
    {
        $table = $this->mapsTableName($wpdb);
        $charset_collate = $wpdb->get_charset_collate();

        $sqlCreate = "CREATE TABLE $table (
          id VARCHAR(32) NOT NULL,
          name TINYTEXT NULL,
          pin_height TINYINT UNSIGNED DEFAULT 30,
          pin_radius TINYINT UNSIGNED DEFAULT 12,
          background VARCHAR(32) DEFAULT 'OpenTopoMap',
          PRIMARY KEY  (id)
        ) $charset_collate;";

        return !!$wpdb->query($sqlCreate);
    }

    private function dropMapTable(wpdb $wpdb)
    {
        $table = $this->mapsTableName($wpdb);
        $sql = "DROP TABLE IF EXISTS $table;";
        return $wpdb->query($sql);
    }

    private function createDefaultMap(wpdb $wpdb)
    {
        $inserted = $wpdb->insert($this->mapsTableName($wpdb), array(
                'id' => 'default-map',
                'name' => 'Default Map',
            )
        );
        return $inserted;
    }


}

