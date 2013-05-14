<?php
# Lifter010: TODO
/*
 * plugin_administration.php - plugin administration model class
 *
 * Copyright (c) 2009  Dennis Reil, Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/datei.inc.php';
require_once 'lib/migrations/db_migration.php';
require_once 'lib/migrations/db_schema_version.php';
require_once 'lib/migrations/migrator.php';
require_once 'lib/plugins/engine/PluginRepository.class.php';

/**
 * Exception class used to report plugin install errors.
 */
class PluginInstallationException extends Exception
{
}

/**
 * Model code for plugin administration tasks.
 */
class PluginAdministration
{
    /**
     * Install a new plugin. Extracts the contents of the uploaded file,
     * checks the manifest, creates the new plugin directory und finally
     * registers the plugin in the database.
     *
     * @param string $filename path to the uploaded file
     */
    public function installPlugin($filename)
    {
        global $SOFTWARE_VERSION;

        $packagedir = get_config('PLUGINS_PATH').'/tmp_'.md5($filename);

        // extract plugin files
        mkdir($packagedir);

        if (unzip_file($filename, $packagedir)) {
            rmdirr($packagedir);
            throw new PluginInstallationException(_('Fehler beim Entpacken des Plugins.'));
        }

        // load the manifest
        $manifest = $this->getPluginManifest($packagedir);

        if ($manifest === NULL) {
            rmdirr($packagedir);
            throw new PluginInstallationException(_('Das Manifest des Plugins fehlt.'));
        }

        // get plugin meta data
        $pluginclass = $manifest['pluginclassname'];
        $origin      = $manifest['origin'];
        $min_version = $manifest['studipMinVersion'];
        $max_version = $manifest['studipMaxVersion'];

        // check for compatible version
        if (isset($min_version) && version_compare($min_version, $SOFTWARE_VERSION) > 0 ||
            isset($max_version) && version_compare($max_version, $SOFTWARE_VERSION) < 0) {
            rmdirr($packagedir);
            throw new PluginInstallationException(_('Das Plugin ist mit dieser Stud.IP-Version nicht kompatibel.'));
        }

        // determine the plugin path
        $basepath = get_config('PLUGINS_PATH');
        $pluginpath = $origin.'/'.$pluginclass;
        $plugindir = $basepath.'/'.$pluginpath;

        $plugin_manager = PluginManager::getInstance();
        $pluginregistered = $plugin_manager->getPluginInfo($pluginclass);

        // is the plugin already installed?
        if (file_exists($plugindir)) {
            if ($pluginregistered) {
                $this->updateDBSchema($plugindir, $packagedir, $manifest);
            }

            rmdirr($plugindir);

            // on NFS file system, removing the plugin may fail (see ticket #1892)
            if (file_exists($plugindir)) {
                $plugindir_old = $plugindir . '.old';
                rmdirr($plugindir_old);
                rename($plugindir, $plugindir_old);
            }
        }

        // move directory to final destination
        if (!file_exists($basepath.'/'.$origin)) {
            mkdir($basepath.'/'.$origin);
        }

        rename($packagedir, $plugindir);

        // create database schema if needed
        $this->createDBSchema($plugindir, $manifest, $pluginregistered);

        // now register the plugin in the database
        $pluginid = $plugin_manager->registerPlugin($manifest['pluginname'], $pluginclass, $pluginpath);

        if ($pluginid === NULL) {
            rmdirr($plugindir);
            throw new PluginInstallationException(_('Das Plugin enth�lt keine g�ltige Plugin-Klasse.'));
        }

        // if we have a homepageplugin register some visibility
        $pluginInfo = $plugin_manager->getPluginInfoById($pluginid);
        if (in_array('HomepagePlugin', $pluginInfo['type'])) {
            Visibility::addPrivacySettingForAll($pluginInfo['name'], "plugin".$pluginInfo['id'], 0, 1, null, $pluginid);
        }

        // register additional plugin classes in this package
        $additionalclasses = $manifest['additionalclasses'];

        if (is_array($additionalclasses)) {
            foreach ($additionalclasses as $class) {
                $plugin_manager->registerPlugin($class, $class, $pluginpath, $pluginid);
            }
        }
    }

    /**
     * Download and install a new plugin from the given URL.
     *
     * @param string $plugin_url the URL of the plugin package
     */
    public function installPluginFromURL($plugin_url)
    {
        $temp_name = tempnam(get_config('TMP_PATH'), 'plugin');

        if (!@copy($plugin_url, $temp_name)) {
            throw new PluginInstallationException(_('Das Herunterladen des Plugins ist fehlgeschlagen.'));
        }

        $this->installPlugin($temp_name);
        unlink($temp_name);
    }

    /**
     * Download and install a plugin with the given name from the
     * plugin repository.
     *
     * @param string $pluginname name of the plugin to install
     */
    public function installPluginByName($pluginname)
    {
        $repository = new PluginRepository();
        $plugin = $repository->getPlugin($pluginname);

        if (!isset($plugin)) {
            throw new PluginInstallationException(_('Das Plugin konnte nicht gefunden werden.'));
        }

        $this->installPluginFromURL($plugin['url']);
    }

    /**
     * Uninstall the given plugin from the system. It will remove
     * the database schema and all the plugin's files.
     *
     * @param array $plugin meta data of plugin
     */
    public function uninstallPlugin($plugin)
    {
        $plugin_manager = PluginManager::getInstance();

        // check if there are dependent plugins
        foreach ($plugin_manager->getPluginInfos() as $dep_plugin) {
            if ($dep_plugin['depends'] === $plugin['id']) {
                $plugin_manager->unregisterPlugin($dep_plugin['id']);
            }
        }

        $plugin_manager->unregisterPlugin($plugin['id']);
        $plugindir = get_config('PLUGINS_PATH').'/'.$plugin['path'];
        $manifest = $this->getPluginManifest($plugindir);

        // delete database if needed
        $this->deleteDBSchema($plugindir, $manifest);

        // delete visibility options
        Visibility::removePlugin($plugin['id']);

        rmdirr($plugindir);
    }

    /**
     * Read the manifest of the plugin in the given directory.
     * Returns NULL if the manifest cannot be found.
     *
     * @return array    containing the manifest information
     */
    public function getPluginManifest($plugindir)
    {
        $manifest = @file($plugindir . '/plugin.manifest');
        $result = array();

        if ($manifest === false) {
            return NULL;
        }

        foreach ($manifest as $line) {
            list($key, $value) = explode('=', $line);
            $key = trim($key);
            $value = trim($value);

            // skip empty lines and comments
            if ($key === '' || $key[0] === '#') {
                continue;
            }

            if ($key === 'pluginclassname' && isset($result[$key])) {
                $result['additionalclasses'][] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Create the initial database schema for the plugin.
     *
     * @param string  $plugindir  absolute path to the plugin
     * @param array   $manifest   plugin manifest information
     * @param boolean $update     update installed plugin
     */
    private function createDBSchema($plugindir, $manifest, $update)
    {
        $pluginname = $manifest['pluginname'];

        if (isset($manifest['dbscheme']) && !$update) {
            $schemafile = $plugindir.'/'.$manifest['dbscheme'];
            $contents   = file_get_contents($schemafile);
            $statements = preg_split("/;[[:space:]]*\n/", $contents, -1, PREG_SPLIT_NO_EMPTY);
            $db = DBManager::get();
            foreach ($statements as $statement) {
                $db->exec($statement);
            }
        }

        if (is_dir($plugindir.'/migrations')) {
            $schema_version = new DBSchemaVersion($pluginname);
            $migrator = new Migrator($plugindir.'/migrations', $schema_version);
            $migrator->migrate_to(null);
        }
    }

    /**
     * Update the database schema maintained by the plugin.
     *
     * @param string $plugindir      absolute path to the plugin
     * @param string $new_pluginpath absolute path to updated plugin
     * @param array  $manifest       plugin manifest information
     */
    private function updateDBSchema($plugindir, $new_pluginpath, $manifest)
    {
        $pluginname = $manifest['pluginname'];

        if (is_dir($plugindir.'/migrations')) {
            $schema_version = new DBSchemaVersion($pluginname);
            $new_version = 0;

            if (is_dir($new_pluginpath.'/migrations')) {
                $migrator = new Migrator($new_pluginpath.'/migrations', $schema_version);
                $new_version = $migrator->top_version();
            }

            $migrator = new Migrator($plugindir.'/migrations', $schema_version);
            $migrator->migrate_to($new_version);
        }
    }

    /**
     * Delete the database schema maintained by the plugin.
     *
     * @param string $plugindir  absolute path to the plugin
     * @param array  $manifest   plugin manifest information
     */
    private function deleteDBSchema($plugindir, $manifest)
    {
        $pluginname = $manifest['pluginname'];

        if (is_dir($plugindir.'/migrations')) {
            $schema_version = new DBSchemaVersion($pluginname);
            $migrator = new Migrator($plugindir.'/migrations', $schema_version);
            $migrator->migrate_to(0);
        }

        if (isset($manifest['uninstalldbscheme'])) {
            $schemafile = $plugindir.'/'.$manifest['uninstalldbscheme'];
            $contents   = file_get_contents($schemafile);
            $statements = preg_split("/;[[:space:]]*\n/", $contents, -1, PREG_SPLIT_NO_EMPTY);
            $db = DBManager::get();
            foreach ($statements as $statement) {
                $db->exec($statement);
            }
        }
    }

    /**
     * Get a list of all available institutes.
     */
    public function getInstitutes()
    {
        $db = DBManager::get();
        $institutes = array();

        $sql = 'SELECT Institut_id, Name, fakultaets_id, Institut_id = fakultaets_id AS is_fak
                FROM Institute ORDER BY is_fak DESC, Name';
        $result = $db->query($sql)->fetchAll();

        foreach ($result as $row) {
            $inst_id = $row['Institut_id'];
            $fak_id = $row['fakultaets_id'];

            if ($inst_id == $fak_id) {
                $institutes[$inst_id] = array('name' => $row['Name']);
            } else {
                $institutes[$fak_id]['children'][$inst_id] = array('name' => $row['Name']);
            }
        }

        return $institutes;
    }

    /**
     * Get a list of the types of all installed plugins.
     *
     * @return array    list of plugin types
     */
    public function getPluginTypes()
    {
        $plugin_manager = PluginManager::getInstance();
        $plugin_infos   = $plugin_manager->getPluginInfos();
        $plugin_types   = array();

        foreach ($plugin_infos as $plugin) {
            $plugin_types = array_merge($plugin_types, $plugin['type']);
        }

        sort($plugin_types);
        return array_unique($plugin_types);
    }

    /**
     * Fetch update information for a list of plugins. This method
     * returns for each plugin: the plugin name, current version and
     * meta data of the plugin update, if available.
     *
     * @param array  $plugins    array of plugin meta data
     */
    public function getUpdateInfo($plugins)
    {
        $default_repository = new PluginRepository();
        $update_info = array();

        foreach ($plugins as $plugin) {
            $repository = $default_repository;
            $plugindir = get_config('PLUGINS_PATH').'/'.$plugin['path'];
            $manifest = $this->getPluginManifest($plugindir);

            if (isset($manifest['updateURL'])) {
                $repository = new PluginRepository($manifest['updateURL']);
            }

            $meta_data = $repository->getPlugin($manifest['pluginname']);

            if (isset($meta_data) &&
                version_compare($meta_data['version'], $manifest['version']) > 0) {
                $manifest['update'] = $meta_data;
            }

            $update_info[$plugin['id']] = $manifest;
        }

        return $update_info;
    }

    /**
     * Fetch migration information plugins. This method
     * returns for each plugin:
     * current schema version and top migration version, if available.
     *
     * @return array
     */
    public function getMigrationInfo()
    {
        $info = array();
        $plugin_manager = PluginManager::getInstance();
        $plugins = $plugin_manager->getPluginInfos();
        $basepath = get_config('PLUGINS_PATH');
        foreach ($plugins as $id => $plugin) {
            $plugindir = $basepath.'/'.$plugin['path'].'/';
            if (is_dir($plugindir.'/migrations')) {
                $schema_version = new DBSchemaVersion($plugin['name']);
                $migrator = new Migrator($plugindir .'/migrations', $schema_version);
                $info[$id]['migration_top_version'] = $migrator->top_version();
                $info[$id]['schema_version'] = $schema_version->get();
            }
        }
        return $info;
    }

    /**
     * migrate plugin to top migration
     *
     * @param integer $plugin_id
     * @return string output from migrator
     */
    public function migratePlugin($plugin_id)
    {
        $plugin_manager = PluginManager::getInstance();
        $plugin = $plugin_manager->getPluginInfoById($plugin_id);
        $basepath = get_config('PLUGINS_PATH');
        $plugindir = $basepath.'/'.$plugin['path'].'/';
        if (is_dir($plugindir.'/migrations')) {
            $schema_version = new DBSchemaVersion($plugin['name']);
            $migrator = new Migrator($plugindir .'/migrations', $schema_version, true);
            ob_start();
            $migrator->migrate_to(null);
            $log = ob_get_clean();
        }
        return $log;
    }

    /**
     * scans PLUGINS_PATH for plugin.manifest files
     * belonging to not registered plugins
     *
     * @return array with manifest meta data
     */
    public function scanPluginDirectory()
    {
        $found = array();
        $basepath = get_config('PLUGINS_PATH');
        $plugin_manager = PluginManager::getInstance();
        $iterator = new RegexIterator(
                        new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($basepath, FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::UNIX_PATHS)),
                        '/plugin\.manifest$/', RecursiveRegexIterator::MATCH);
        foreach ($iterator as $manifest_file) {
            $manifest = $this->getPluginManifest($manifest_file->getPath());
            if (!$plugin_manager->getPluginInfo($manifest['pluginclassname'])) {
                $manifest['path'] = $manifest_file->getPath();
                $found[] = $manifest;
            }
        }
        return $found;
    }

    /**
     * registers plugin at given path in database
     *
     * @param string $plugindir path to plugin
     * @throws PluginInstallationException
     */
    public function registerPlugin($plugindir)
    {
        $manifest = $this->getPluginManifest($plugindir);
        if (!$manifest) {
            throw new PluginInstallationException(_('Das Manifest des Plugins fehlt.'));
        }

        // get plugin meta data
        $pluginclass = $manifest['pluginclassname'];
        $origin      = $manifest['origin'];
        $min_version = $manifest['studipMinVersion'];
        $max_version = $manifest['studipMaxVersion'];

        // check for compatible version
        if (isset($min_version) && version_compare($min_version, $GLOBALS['SOFTWARE_VERSION']) > 0 ||
            isset($max_version) && version_compare($max_version, $GLOBALS['SOFTWARE_VERSION']) < 0) {
            throw new PluginInstallationException(_('Das Plugin ist mit dieser Stud.IP-Version nicht kompatibel.'));
        }

        // determine the plugin path
        $basepath = get_config('PLUGINS_PATH');
        $pluginpath = $origin.'/'.$pluginclass;

        $plugin_manager = PluginManager::getInstance();
        $pluginregistered = $plugin_manager->getPluginInfo($pluginclass);

        if ($pluginregistered) {
            new PluginInstallationException(_('Das Plugin ist bereits registriert.'));
        }

        // create database schema if needed
        $this->createDBSchema($plugindir, $manifest, $pluginregistered);

        // now register the plugin in the database
        $pluginid = $plugin_manager->registerPlugin($manifest['pluginname'], $pluginclass, $pluginpath);

        // register additional plugin classes in this package
        $additionalclasses = $manifest['additionalclasses'];

        if (is_array($additionalclasses)) {
            foreach ($additionalclasses as $class) {
                $plugin_manager->registerPlugin($class, $class, $pluginpath, $pluginid);
            }
        }
    }
}
