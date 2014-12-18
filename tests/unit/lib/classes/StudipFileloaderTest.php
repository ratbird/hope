<?php

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'lib/classes/StudipFileloader.php';



/**
 * Testcase for StudipFileloader class.
 *
 * @package    studip
 * @subpackage test
 *
 * @author    mlunzena
 * @copyright (c) Authors
 */

class StudipFileloaderTestCase extends PHPUnit_Framework_TestCase {

    function setUp() {
        ArrayFileStream::set_filesystem(
            array(
                'pathto' => array(
                    'config-1.php' => '<? $CONF = 17; '
                  , 'config-2.php' => '<? $CONF = 17 + $offset; '
        )));

    if (!stream_wrapper_register("var", "ArrayFileStream")) {
      new Exception("Failed to register protocol");
    }
  }

  function tearDown() {
    stream_wrapper_unregister("var");
  }


  function test_should_inject_vars() {
      $container = array();
      StudipFileloader::load('var://pathto/config-1.php', $container);
      $this->assertEquals(array('CONF' => 17), $container);
  }

  function test_should_inject_vars_twice() {

      foreach (range(1,2) as $i) {
          $container = array();
          StudipFileloader::load('var://pathto/config-1.php', $container);
      }
      $this->assertEquals(array('CONF' => 17), $container);
  }

  function test_should_use_optional_bindings()
  {
      $container = array();
      $offset = 25;
      StudipFileloader::load('var://pathto/config-2.php', $container, compact('offset'));
      $this->assertEquals(array('CONF' => 42), $container);

  }

  function test_should_balk_upon_file_not_found()
  {
      $exception_catched = false;

      // workaround for different phpunit versions, i.e. 3.7 and > 4.0
      // the exceptions thrown differ in these versions
      try {
        StudipFileloader::load('var://pathto/not-there.php', $container);
      } catch (PHPUnit_Framework_Error_Warning $e) {
          $exception_catched = true;
      } catch (PHPUnit_Framework_Exception $e) {
          $exception_catched = true;
      }

      $this->assertTrue($exception_catched);
  }
}
