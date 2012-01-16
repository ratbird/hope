<?php
/*
 * Copyright (c) 2011 mlunzena
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

namespace Test;

require_once dirname(__FILE__) . '/../../../bootstrap.php';
require_once 'lib/functions.php';
require_once 'lib/classes/squeeze/squeeze.php';
require_once 'lib/classes/Assets.class.php';


use \Studip\Squeeze\Configuration;
use \Studip\Squeeze\Packager;
use \Studip\Squeeze\Compressor;

class SqueezePackagerTest extends \PHPUnit_Framework_TestCase
{

    const ASSETS_URL = "http://example.net/assets/";


    function getTmpDir()
    {
        return dirname(tempnam("/dummy17", "squeezetest")) . "/squeeze";
    }

    function rmTmpDir()
    {
        $tmp = $this->getTmpDir();
        if (file_exists($tmp)) {
            foreach(glob($tmp . "/*") as $file) {
                unlink($file);
            }
            rmdir($tmp);
        }
    }

    function setUp()
    {

        $this->original_assets_url = \Assets::url();
        \Assets::set_assets_url(self::ASSETS_URL);

        $this->conf = Configuration::load(
            TEST_FIXTURES_PATH."squeeze/assets.yml");

        $this->rmTmpDir();
        $this->output_directory = $this->getTmpDir();

        $this->STUDIP_BASE_PATH = $GLOBALS['STUDIP_BASE_PATH'];
        $GLOBALS['STUDIP_BASE_PATH'] =
            realpath(dirname(__FILE__) . '/../../../../../');
    }

    function tearDown()
    {
        \Assets::set_assets_url($this->original_assets_url);
        $this->rmTmpDir();

        $GLOBALS['STUDIP_BASE_PATH'] = $this->STUDIP_BASE_PATH;
    }

    function testIndividualUrls()
    {
        $packager = new Packager($this->conf);

        $urls = $packager->individualURLs("test");
        $expected = array(
            self::ASSETS_URL . "src/bar.js",
            self::ASSETS_URL . "src/foo.js"
        );

        $this->assertEquals($expected, $urls);
    }

    function testNestedIndividualURLs()
    {

        $packager = new Packager($this->conf);

        $urls = $packager->individualURLs("test_nested");
        $expected = array(
            self::ASSETS_URL . "src/bar.js",
            self::ASSETS_URL . "src/foo.js",
            self::ASSETS_URL . "src/nested/nest1.js",
            self::ASSETS_URL . "src/nested/nest2.js",
        );

        $this->assertEquals($expected, $urls);

    }

    function testPackJavascripts()
    {
        $this->conf["compress"] = FALSE;
        $packager = new Packager($this->conf);

        $pack = $packager->pack('test');
        $this->assertStringEqualsFile(
            TEST_FIXTURES_PATH . 'squeeze/packed/test.js',
            $pack);
    }


    function testPackJavascriptsCompressed()
    {
        $this->conf["compress"] = TRUE;
        $packager = new Packager($this->conf);
        $compressor = $this->getMock("\Studip\Squeeze\Compressor",
                                     array(), array(array()));
        $compressor
            ->expects($this->once())
            ->method('compress')
            ->will($this->returnValue('foo'));
        $packager->compressor = $compressor;

        $pack = $packager->pack('test');
        $this->assertEquals('foo', $pack);
    }

    function testPackageURL()
    {
        $packager = new Packager($this->conf);
        $expected = "http://example.com/assets/squeezed/test.js";
        $this->assertEquals($expected, $packager->packageURL("test"));
    }

    function testCaching()
    {
        $packager = new Packager($this->conf);

        $expectedContent = $packager->pack("test");

        $filename = $this->output_directory . "/test.js";
        $packager->cache("test", $this->output_directory);
        $this->assertStringEqualsFile($filename, $expectedContent);
    }

    function testCachingWithTime()
    {
        $packager = new Packager($this->conf);

        $filename = $this->output_directory . "/test.js";
        $ten_seconds_earlier = time() - 10;
        $packager->cache("test", $this->output_directory, $ten_seconds_earlier);
        $this->assertEquals($ten_seconds_earlier, filemtime($filename));
    }

    function testCachingAll()
    {
        $packager = new Packager($this->conf);
        $packager->cacheAll($this->output_directory);
        $this->assertTrue(
            file_exists($this->output_directory . "/test.js"));
        $this->assertTrue(
            file_exists($this->output_directory . "/test_nested.js"));
    }

    function testStalePackagesWithoutCachedPackages()
    {
        $packager = new Packager($this->conf);

        $packages = $packager->stalePackages($this->output_directory);

        $this->assertCount(2, $packages);
        $this->assertContains("test", $packages);
        $this->assertContains("test_nested", $packages);
    }

    function testStalePackagesAfterCaching()
    {
        $packager = new Packager($this->conf);

        $now = time();
        $ten_seconds_later = $now + 10;

        $packager->cache("test", $this->output_directory, $now);
        $packager->cache("test_nested", $this->output_directory, $now);

        $packages = $packager->stalePackages($this->output_directory,
                                             $ten_seconds_later);

        $this->assertCount(2, $packages);
    }

    function testStalePackagesWithTouchedConfigFile()
    {
        $packager = new Packager($this->conf);

        $packager->cache("test", $this->output_directory);
        $packager->cache("test_nested", $this->output_directory);

        $packages = $packager->stalePackages($this->output_directory);
        $this->assertCount(0, $packages);
    }

    function testStalePackagesWithNewerSourceFile()
    {
        $packager = new Packager($this->conf);

        $packager->cache("test", $this->output_directory, 0);
        $packager->cache("test_nested", $this->output_directory, 0);

        $packages = $packager->stalePackages($this->output_directory);
        $this->assertCount(2, $packages);
    }
}
