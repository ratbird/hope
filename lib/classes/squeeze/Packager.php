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

class Packager
{

    function __construct($configuration)
    {
        $this->configuration = $configuration;
        $this->compressor    = new Compressor($configuration);
        $this->packages      = $this->createPackages(
            $configuration['javascripts'] ?: array());
    }

    function individualURLs($package)
    {
        $package = $this->packageFor($package);
        return $package["urls"];
    }

    function pack($package)
    {
        $pack = $this->packageFor($package);
        return $this->compressor->compress($pack['paths']);
    }

    function packageURL($package)
    {
        return $this->configuration['package_url'] . "/$package.js";
    }

    function cache($package, $outputDir, $time = NULL)
    {
        if (!file_exists($outputDir)) {
            if (!mkdir($outputDir, 0777, TRUE) || !is_writable($outputDir)) {
                throw new Exception("Could not write to '$outputDir'");
            }
        }

        $filename = "$outputDir/$package.js";

        if (file_put_contents($filename, $this->pack($package)) === FALSE) {
            throw new Exception("Could not write to '$filename'");
        }

        if (isset($time)) {
            touch($filename, $time);
        }
    }

    function cacheAll($outputDir = NULL, $time = NULL)
    {
        $outputDir  = $outputDir ?: $this->configuration['package_path'];
        foreach($this->stalePackages($outputDir) as $package) {
            $this->cache($package, $outputDir);
        }
    }

    function stalePackages($outputDir, $configTime = NULL)
    {
        # use given time or mtime of the config file
        $configTime = $configTime
            ?: filemtime($this->configuration['config_path']);

        # TODO mlunzena: remove this as soon as closure object binding
        #                is available in PHP 5.4
        $packager = $this;
        return array_filter(array_keys($this->packages),
            function ($name) use ($outputDir, $configTime, $packager) {

            # package is stale, because ...

            # ... cached package does not exist
            $cachedPackage = "$outputDir/$name.js";
            if (!file_exists($cachedPackage)) {
                return TRUE;
            }

            # ... mtime of cached package is older than mtime of
            #     configuration file
            $since = filemtime($cachedPackage);
            if ($configTime > $since) {
                return TRUE;
            }

            # ... mtime of a file contained in the package
            #     is older than the mtime of cached package
            $pack = $packager->packageFor($name);
            foreach ($pack['paths'] as $path) {
                $src = $packager->configuration['assets_root'] . "/$path";
                if (filemtime($src) > $since) {
                    return TRUE;
                }
            }
        });
    }

    function pathToURL($path)
    {
        return \Assets::url($path);
    }

    function globFiles($glob)
    {
        $old = getcwd();
        chdir($this->configuration['assets_root']);
        $paths = glob($glob);
        chdir($old);

        if (!sizeof($paths)) {
            # TODO mlunzena: Wirklich als Exception?
            throw new Exception("No assets match '$glob'");
        }
        sort($paths);
        return $paths;
    }

    function createPackages($config)
    {
        $packages = array();

        if (!$config) {
            return $packages;
        }

        # TODO mlunzena: remove this as soon as closure object binding
        #                is available in PHP 5.4
        $self = $this;

        foreach ($config as $name => $globs) {
            if (!$globs) {
                $globs = array();
            }

            $paths = array_map(function ($glob) use ($self) {
                    return $self->globFiles($glob);
                }, array_flatten($globs));

            $paths = array_unique(array_flatten($paths));

            $packages[$name] = array(
                "paths" => $paths,
                "urls"  => array_map(array($this, 'pathToURL'), $paths)
            );
        }
        return $packages;
    }

    function packageFor($package)
    {
        if (isset($this->packages[$package])) {
            return $this->packages[$package];
        }
        throw new Exception("Configuration does not contain package '$package'");
    }
}
