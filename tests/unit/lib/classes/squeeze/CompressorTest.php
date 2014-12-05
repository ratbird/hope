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
require_once 'lib/classes/squeeze/squeeze.php';


use \Studip\Squeeze\Configuration;
use \Studip\Squeeze\Compressor;

class SqueezeCompressorTest extends \PHPUnit_Framework_TestCase
{

    function skipTestWithoutJava()
    {
        if (in_array($this->getName(),
                     array("testCompress", "testCallCompressor"))) {
            $compressor = new Compressor(new Configuration());
            if (!$compressor->getJavaCompatibility("java")) {
                $this->markTestSkipped('TODO Skip');
            }
        }
    }


    function setUp()
    {
        $this->skipTestWithoutJava();

        $this->STUDIP_BASE_PATH = $GLOBALS['STUDIP_BASE_PATH'];
        $GLOBALS['STUDIP_BASE_PATH'] = realpath(dirname(__FILE__) . '/../../../../../');
    }

    function tearDown()
    {
        $GLOBALS['STUDIP_BASE_PATH'] = $this->STUDIP_BASE_PATH;
    }

    /**
     * @dataProvider javaCompatibility
     */
    function testCheckJava($version, $compatible)
    {
        $conf = new Configuration();
        $conf['compressor_options'] = array("java" => "echo $version");

        $compressor = new Compressor($conf);
        $this->assertEquals($compatible, $compressor->hasJava());
    }

    function javaCompatibility()
    {
        return json_decode('[["1.4", true], ["1.3", false]]');
    }

    function testCompress()
    {
        $conf = Configuration::load(
            TEST_FIXTURES_PATH."squeeze/assets.yml");
        $compressor = new Compressor($conf);
        $paths = array("src/bar.js", "src/foo.js");

        $this->assertStringEqualsFile(
            TEST_FIXTURES_PATH . 'squeeze/compressed/test.js',
            $compressor->compress($paths));
    }

    function testCallCompressor()
    {
        $compressor = new Compressor(new Configuration());
        $js = "function A() { this.stuff = 42; }";
        $expected = "function A(){this.stuff=42};";
        $this->assertEquals($expected, $compressor->callCompressor($js));
    }

    function testCallCompressorWithSyntaxError()
    {
        $this->setExpectedException('\Studip\Squeeze\Exception');
        $compressor = new Compressor(new Configuration());
        $js = "function A()";
        $compressor->callCompressor($js);
    }

}
