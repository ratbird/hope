<?php

/*
 * JS packaging and compression inspired by jammit
 *
 * Copyright (c) 2011  <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

namespace {
    // emulate #ctype_digit, unless it exists
    // yaml lib needs it
    if (!function_exists('ctype_digit')) {
        function ctype_digit($text) {
            return preg_match('/^\d+$/', $text);
        }
    }
}

namespace Studip\Squeeze {

    require 'Compressor.php';
    require 'Configuration.php';
    require 'Exception.php';
    require 'Packager.php';


    /**
     * Write all packages specified in $configFile to $outputDir.
     * The default $outputDir is "$STUDIP_BASE_PATH/config/assets.yml"
     *
     * @param string $configFile  path to the config file
     * @param string $outputDir   path to the output directory
     */
    function packageAll($configFile = NULL, $outputDir = NULL)
    {
        global $STUDIP_BASE_PATH;
        $configFile = $configFile ?: "$STUDIP_BASE_PATH/config/assets.yml";
        $configuration = Configuration::load($configFile);
        $packager = new Packager($configuration);

        $packager->cacheAll($outputDir);
    }


    /**
     * Include a single squeeze package depending on \Studip\ENV as
     * individual script elements or as a single one containing the
     * squeezed source code of all files comprising the package.
     *
     * @param Packager $packager  the packager instance to use
     * @param array    $packages  an array containing the names of packages
     *
     * @return an array containing PageLayout style HTML elements
     */
    function includePackages($packager, $packages)
    {
        return array_reduce($packages, function ($memo, $package) use ($packager) {

                return array_merge($memo,
                                   shouldPackage()
                                   ? packageAsCompressedURL($packager, $package)
                                   : packageAsIndividualURLs($packager, $package));
            }, array());
    }

    /**
     * @return bool  TRUE in production mode, FALSE in development or
     *               while debugging (= GET request contains a
     *               'debug_assets' param)
     */
    function shouldPackage()
    {
        return \Studip\ENV !== 'development' && !\Request::submitted('debug_assets');
    }

    /**
     * Include a single squeeze package as individual script elements.
     *
     * @param Packager $packager  the packager instance to use
     * @param string   $package   the name of a package
     *
     * @return an array containing PageLayout style HTML elements
     */
    function packageAsIndividualURLs($packager, $package)
    {
        $elements = array();
        foreach ($packager->individualURLs($package) as $src) {
            $elements[] = array(
                'name'       => 'script',
                'attributes' => compact('src'),
                'content'    => '');
        }
        return $elements;
    }

    /**
     * Include a single squeeze package as a single one containing the
     * squeezed source code of all files comprising the package.
     *
     * @param Packager $packager  the packager instance to use
     * @param string   $package   the name of a package
     *
     * @return an array containing PageLayout style HTML elements
     */
    function packageAsCompressedURL($packager, $package)
    {
        return array(
            array(
                'name'       => 'script',
                'attributes' => array('src' => $packager->packageURL($package), 'charset' => 'utf-8'),
                'content'    => '')
        );
    }
}
