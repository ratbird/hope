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

class SqueezeConfigurationTest extends \PHPUnit_Framework_TestCase
{

   function testShouldLoadFile()
    {
        $configuration = Configuration::load(
            TEST_FIXTURES_PATH."squeeze/assets.yml");
        $this->assertInstanceOf("\Studip\Squeeze\Configuration",
                                $configuration);
    }

   function testShouldKnowItsConfigFile()
    {
        $configuration = Configuration::load(
            TEST_FIXTURES_PATH."squeeze/assets.yml");
        $this->assertEquals(TEST_FIXTURES_PATH."squeeze/assets.yml",
                            $configuration["config_path"]);
    }

    /**
     * @dataProvider providerFileContent
     */
   function testShouldLoadFileWithCorrectContent($key, $expectedValue)
   {
       $configuration = Configuration::load(
           TEST_FIXTURES_PATH."squeeze/assets.yml");

       $this->assertEquals($expectedValue, $configuration[$key]);
   }

   function providerFileContent()
   {
       return array(
           array("assets_root",   TEST_FIXTURES_PATH . "squeeze"),
           array("package_path",  TEST_FIXTURES_PATH . "squeeze/squeezed"),
           array("package_url",   "http://example.com/assets/squeezed"),
           array("compress",      true));
   }

   function testShouldCreateWithAnEmptyConstructor()
   {
       $configuration = new Configuration();
       $this->assertStringEndsWith("lib/classes/squeeze/Configuration.php",
                                   $configuration['config_path']);
   }

    /**
     * @dataProvider providerDefaults
     */
   function testShouldInstantiateWithDefaultContent($key, $expectedValue)
   {
       $configuration = new Configuration();

       $this->assertEquals($expectedValue, $configuration[$key]);
   }

   function providerDefaults()
   {
       global $ABSOLUTE_PATH_STUDIP, $ABSOLUTE_URI_STUDIP;

       return array(
           array("assets_root",  "${ABSOLUTE_PATH_STUDIP}assets"),
           array("package_path", "${ABSOLUTE_PATH_STUDIP}assets/squeezed"),
           array("package_url",  "${ABSOLUTE_URI_STUDIP}assets/squeezed"),
           array("compress",     true));
   }


   function testOverridingDefaults()
   {
       $conf = new Configuration(array("compress" => false));
       $this->assertEquals(false, $conf["compress"]);
   }
}
