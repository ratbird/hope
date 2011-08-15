<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'StudipCache.class.php';
require_once 'StudipNullCache.class.php';
require_once 'StudipFileCache.class.php';

/**
 * This factory retrieves the instance of StudipCache configured for use in
 * this Stud.IP installation.
 *
 * @package        studip
 * @subpackage lib
 *
 * @author        Marco Diedrich (mdiedric@uos)
 * @author        Marcus Lunzenauer (mlunzena@uos.de)
 * @copyright (c) Authors
 * @since         1.6
 */

class StudipCacheFactory {

    /**
     * the default cache class
     *
     * @var string
     */
    const DEFAULT_CACHE_CLASS = 'StudipFileCache';

    /**
     * singleton instance
     *
     * @var StudipCache
     */
    static private $cache;


    /**
     * config instance
     *
     * @var Config
     */
    static private $config;


    /**
     * Returns the currently used config instance
     *
     * @return Config        an instance of class Config used by this factory to
     *                       determine the class of the actual implementation of
     *                       the StudipCache interface; if no config was set, it
     *                       returns the instance returned by Config#getInstance
     * @see Config
     */
    static function getConfig()
    {
        return is_null(self::$config) ? Config::getInstance() : self::$config;
    }


    /**
     * @param    Config       an instance of class Config which will be used to
     *                        determine the class of the implementation of interface
     *                        StudipCache
     *
     * @return void
     */
    static function setConfig($config)
    {
        self::$config = $config;
    }


    /**
     * Configure the file, class and arguments used for instantiation of the
     * StudipCache instance. After sending this method, the previously used cache
     * instance is voided and a new instance will be created on demand.
     *
     * @param    string             the absolute path to the implementing class
     * @param    string             the name of the class
     * @param    array              an array of custom arguments
     *
     * @return void
     */
    static function configure($file, $class, $arguments)
    {

        # TODO encoding for strings... but probably the caller should care..
        $arguments = json_encode($arguments);

        // strip leading STUDIP_BASE_PATH from file path
        if (strpos($file, $GLOBALS['STUDIP_BASE_PATH']) === 0) {
            $file = substr($file, strlen($GLOBALS['STUDIP_BASE_PATH']) + 1);
        }

        self::unconfigure();

        $cfg = self::getConfig();

        $cfg->create('cache_class', array(
            'comment' => 'Pfad der Datei, die die StudipCache-Klasse enthält',
            'value'   => $class));
        $cfg->create('cache_class_file', array(
            'comment' => 'Klassenname des zu verwendenden StudipCaches',
            'value'   => $file));
        $cfg->create('cache_init_args', array(
            'comment' => 'JSON-kodiertes Array von Argumenten für die Instanziierung der StudipCache-Klasse',
            'value'   => $arguments));

        $cfg->store('cache_class', $class);
        $cfg->store('cache_class_file', $file);
        $cfg->store('cache_init_args', $arguments);
    }


    /**
     * Resets the configuration and voids the cache instance.
     *
     * @return void
     */
    static function unconfigure()
    {

        $cfg = self::getConfig();

        $cfg->delete('cache_class');
        $cfg->delete('cache_class_file');
        $cfg->delete('cache_init_args');

        self::$cache = NULL;
    }


    /**
     * Returns a cache instance.
     *
     * @return StudipCache    the cache instance
     */
    static function getCache()
    {

        if (is_null(self::$cache)) {

            if (!$GLOBALS['CACHING_ENABLE']) {
                return self::$cache = new StudipNullCache();
            }

            try {
                $class = self::loadCacheClass();
                $args = self::retrieveConstructorArguments();
                self::$cache = self::instantiateCache($class, $args);
            } catch (Exception $e) {
                error_log(__METHOD__ . ': ' . $e->getMessage());
                echo MessageBox::error(__METHOD__ . ': ' . $e->getMessage());
                $class = self::DEFAULT_CACHE_CLASS;
                self::$cache = new $class();
            }
        }

        return self::$cache;
    }


    /**
     * Load configured cache class and return its name.
     *
     * @return string  the name of the configured cache class
     */
    static function loadCacheClass()
    {
        $cfg = self::getConfig();
        $cache_class_file = $cfg->getValue('cache_class_file');
        $cache_class      = $cfg->getValue('cache_class');

        # default class
        if (is_null($cache_class)) {
            return self::DEFAULT_CACHE_CLASS;
        }

        # already loaded
        if (class_exists($cache_class)) {
            return $cache_class;
        }

        $loaded = @include $cache_class_file;
        if ($loaded === FALSE || !class_exists($cache_class)) {
            # TODO (mlunzena) a more specific exception would be welcome here
            throw new Exception("Could not find class: '$cache_class'");
        }

        return $cache_class;
    }

    /**
     * Return an array of arguments required for instantiation of the cache
     * class.
     *
     * @return array  the array of arguments
     */
    static function retrieveConstructorArguments()
    {
        $cfg_args = self::getConfig()->getValue('cache_init_args');
        return isset($cfg_args) ? json_decode($cfg_args, TRUE) : array();
    }

    /**
     * Return an instance of a given class using some arguments
     *
     * @param  string  the name of the class
     * @param  array   an array of arguments to be used by the constructor
     *
     * @return StudipCache  an instance of the specified class
     */
    static function instantiateCache($class, $arguments)
    {
        $reflection_class = new ReflectionClass($class);
        return sizeof($arguments)
               ? $reflection_class->newInstanceArgs($arguments)
               : $reflection_class->newInstance();
    }
}

