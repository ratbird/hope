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

    function packageAll($configFile = NULL, $outputDir = NULL)
    {
        global $STUDIP_BASE_PATH;
        $configFile = $configFile ?: "$STUDIP_BASE_PATH/config/assets.yml";
        $configuration = Configuration::load($configFile);
        $packager = new Packager($configuration);

        $packager->cacheAll($outputDir);
    }
}
