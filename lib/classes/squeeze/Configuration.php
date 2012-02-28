<?php
/*
 *
 * Copyright (c) 2011  <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

namespace Studip\Squeeze;

require_once 'lib/classes/StudipCacheFactory.class.php';

/**
 * This class is used to configure the Squeeze packager. You can
 * either use a yaml file or a plain PHP array.
 */
class Configuration implements \ArrayAccess
{

    /**
     * Create an instance of this class by using a plain PHP array.
     *
     * @param array $conf   an array containing the configuration
     * settings
     * @param string $path  the path to the config file that was used
     *                      (NULL if instantiated via constructor)
     */
    function __construct($conf = array(), $path = NULL)
    {

        global $ABSOLUTE_PATH_STUDIP, $ABSOLUTE_URI_STUDIP;

        $defaults = array(

            'assets_root'  => "${ABSOLUTE_PATH_STUDIP}assets",

            # TODO richtiger Pfad?
            'package_path' => "${ABSOLUTE_PATH_STUDIP}assets/squeezed",
            'package_url'  => "${ABSOLUTE_URI_STUDIP}assets/squeezed",

            'javascripts'  => array(),
            'compress'     => true,

            'compressor_options' => array()
        );

        $this->settings = array_merge($defaults, $conf);
        $this->settings['config_path'] = $path ?: __FILE__;
    }

    /**
     * ArrayAccess: Check whether the given offset exists.
     */
    function offsetExists($offset)
    {
        return array_key_exists($offset, $this->settings);
    }

    /**
     * ArrayAccess: Get the value at the given offset.
     */
    function offsetGet($offset)
    {
        return @$this->settings[$offset] ;
    }

    /**
     * ArrayAccess: Set the value at the given offset.
     */
    function offsetSet($offset, $value)
    {
        if (array_key_exists($offset, $this->settings)) {
            $this->settings[$offset] = $value;
        }
    }

    /**
     * ArrayAccess: Delete the value at the given offset.
     */
    function offsetUnset($offset)
    {
        throw new RuntimeException("Unsetting properties forbidden");
    }

    /**
     * Create a configuration object by parsing a yaml file.
     * The result of the parser is cached for 10 seconds. You can
     * force an uncached parsing by setting the 2nd param to true.
     *
     * @param string $path   the file to parse
     * @param bool   $force  cache unless TRUE
     *
     * @return Configuration  an instance of this class
     */
    static function load($path, $force = FALSE)
    {
        $parsed = $force
            ? self::parseFile($path)
            : self::parseAndCacheFile($path);

        return new Configuration($parsed, $path);
    }

    static private function parseAndCacheFile($path)
    {
        $cache = \StudipCacheFactory::getCache();
        $parsed = unserialize($cache->read('squeeze/' . $path));

        if (!$parsed) {
            $parsed = self::parseFile($path);

            # write to cache and expire in 10 seconds
            $cache->write('squeeze/' . $path, serialize($parsed), 10);
        }

        return $parsed;
    }

    static private function parseFile($path)
    {
        ob_start();
        require $path;
        $config = ob_get_clean();

        require_once 'vendor/yaml/lib/sfYamlParser.php';
        $yaml = new \sfYamlParser();

        return $yaml->parse($config);
    }
}
