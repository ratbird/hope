<?php
# Lifter010: TODO
/*
 * PluginRepository.class.php - query plugin meta data
 *
 * Copyright (c) 2008  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/classes/StudipCacheFactory.class.php';

/**
 * Class used to locate plugins available from a plugin repository.
 */
class PluginRepository
{
    /**
     * list and meta data of available plugins
     */
    private $plugins = array();

    /**
     * Initialize a new PluginRepository and read meta data from
     * the given URL or the default list of URLs (if $url is NULL).
     */
    public function __construct($url = NULL)
    {
        if (isset($url)) {
            $this->readMetadata($url);
        } else {
            foreach ($GLOBALS['PLUGIN_REPOSITORIES'] as $url) {
                $this->readMetadata($url);
            }
        }
    }

    /**
     * Read plugin meta data from the given URL (using XML format).
     * The structure of the XML is:
     *
     * <plugins>
     *   <plugin name="DummyPlugin"
     *     <release
     *           version="2.0"
     *           url="http://plugins.example.com/dummy-2.0.zip"
     *           studipMinVersion="1.4"
     *           studipMaxVersion="1.9">
     *   </plugin>
     *   [...]
     * </plugins>
     */
    public function readMetadata($url)
    {
        global $SOFTWARE_VERSION;

        $cache = StudipCacheFactory::getCache();
        $cache_key = 'plugin_metadata/'.$url;
        $metadata = $cache->read($cache_key);

        if ($metadata === false) {
            $metadata = @file_get_contents($url);

            if ($metadata === false) {
                throw new Exception(sprintf(_('Fehler beim Zugriff auf %s'), $url));
            }

            $cache->write($cache_key, $metadata, 3600);
        }

        $xml = new SimpleXMLElement($metadata);

        if (!isset($xml->plugin)) {
            $cache->expire($cache_key);
            throw new Exception(_('Keine Plugin Meta-Daten gefunden'));
        }

        foreach ($xml->plugin as $plugin) {
            foreach ($plugin->release as $release) {
                $min_version = $release['studipMinVersion'];
                $max_version = $release['studipMaxVersion'];

                if (isset($min_version) &&
                      version_compare($min_version, $SOFTWARE_VERSION) > 0 ||
                    isset($max_version) &&
                      version_compare($max_version, $SOFTWARE_VERSION) < 0) {
                    // plugin is not compatible, so skip it
                    continue;
                }

                $meta_data = array(
                    'version'     => utf8_decode($release['version']),
                    'url'         => utf8_decode($release['url']),
                    'description' => utf8_decode($plugin['description']),
                    'plugin_url'  => utf8_decode($plugin['homepage']),
                    'image'       => utf8_decode($plugin['image']),
                    'score'       => utf8_decode($plugin['score'])
                );

                $this->registerPlugin(utf8_decode($plugin['name']), $meta_data);
            }
        }
    }

    /**
     * Register a new plugin in this repository.
     *
     * @param $name       string plugin name
     * @param $meta_data  array of plugin meta data
     */
    protected function registerPlugin($name, $meta_data)
    {
        $old_data = $this->plugins[$name];

        if (!isset($old_data) ||
            version_compare($meta_data['version'], $old_data['version']) > 0) {
            $this->plugins[$name] = $meta_data;
        }
    }

    /**
     * Get meta data for the plugin with the given name (if available).
     * Always chooses the newest compatible version of the plugin.
     *
     * @return array meta data for plugin (or NULL)
     */
    public function getPlugin($name)
    {
        return $this->plugins[$name];
    }

    /**
     * Get meta data for all plugins whose names contain the given
     * string. You may omit the search string to get a list of all
     * available plugins. Returns the newest compatible version of
     * each plugin.
     *
     * @return array array of meta data for matching plugins
     */
    public function getPlugins($search = NULL)
    {
        $result = array();

        foreach ($this->plugins as $name => $data) {
            if ($search === NULL || $search === '' ||
                is_int(stripos($name, $search)) ||
                is_int(stripos($data['description'], $search))) {
                $result[$name] = $data;
            }
        }

        return $result;
    }
}
