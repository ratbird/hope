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


class Compressor
{

    const PATH_TO_JAVA = "java";

    static $checkedJava = NULL;

    function __construct($configuration)
    {
        $this->configuration = $configuration;
        if (!$this->checkJava()) {
            $this->configuration['compress'] = FALSE;
        }
    }

    function hasCheckedJava()
    {
        return self::$checkedJava !== NULL;
    }

    function checkJava()
    {
        # use memoized result
        if (self::hasCheckedJava()) {
            return self::$checkedJava;
        }

        $compatible = $this->getJavaCompatibility($this->pathToJava());

        # memoize result
        self::$checkedJava = $compatible;

        return $compatible;
    }

    function getJavaCompatibility($java)
    {
        exec("$java -version 2>&1", $output, $status);
        if ($status === 0) {
            $matched = preg_match('/\d+\.\d+/', $output[0], $matches);
            if ($matched === 1) {
                return version_compare('1.4', $matches[0], '<=');
            }
        }
        return FALSE;
    }

    function compress($paths)
    {
        $js = $this->concatenateAssets($paths);
        if ($this->configuration['compress']) {
            $js = $this->callCompressor($js);
        }

        return $js;
    }

    function concatenateAssets($paths)
    {
        $files = array_map(array($this, "getFileAsUTF8"), $paths);
        return join("\n", $files);
    }

    function getFileAsUTF8($path)
    {
        $content = file_get_contents(
            $this->configuration['assets_root'] . "/$path");

        if (mb_check_encoding($content, 'WINDOWS-1252')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'WINDOWS-1252');
        }

        return $content;
    }

    function callCompressor($js)
    {
        $java = $this->pathToJava();
        $jar  = $this->pathToJar();

        return $this->procOpen("$java -jar $jar --type js", $js);
    }

    function pathToJava()
    {
        return @$this->configuration['compressor_options']['java'] ?: self::PATH_TO_JAVA;
    }

    function pathToJar()
    {
        global $STUDIP_BASE_PATH;
        return "$STUDIP_BASE_PATH/vendor/yuicompressor/yuicompressor-2.4.6.jar";
    }

    function procOpen($command, $stdin)
    {
        $cwd = $GLOBALS['TMP_PATH'];

        $err = tempnam($cwd, 'squeeze');
        $descriptorspec = array(
            array("pipe", "r"),
            array("pipe", "w"),
            array("file", $err, "a")
        );

        $process = proc_open($command, $descriptorspec, $pipes, $cwd, array());

        if (is_resource($process)) {

            fwrite($pipes[0], $stdin);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $return_value = proc_close($process);

            # an error happened
            if ($return_value) {
                throw new Exception("Compression Error: " .
                                    file_get_contents($err));
            }

            return $output;
        }

        throw new Exception("Compression failed");
    }
}
