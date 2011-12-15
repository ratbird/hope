<?php

/*
 * Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'lib/classes/Assets.class.php';


define('STATIC_ASSETS_URL',  'http://www.example.com/public/');
define('DYNAMIC_ASSETS_URL', 'http://www%d.example.com/public/');


/**
 * Testcase for Assets class.
 *
 * @package    studip
 * @subpackage test
 *
 * @author    mlunzena
 * @copyright (c) Authors
 */

class AssetsTestCase extends PHPUnit_Framework_TestCase {


  function setUp() {
    Assets::set_assets_url(STATIC_ASSETS_URL);
  }


  function tearDown() {
  }


  function test_class_should_exist() {
    $this->assertTrue(class_exists('Assets'));
  }


  function test_url_should_return_ASSETS_URL() {
    $this->assertEquals(Assets::url(), STATIC_ASSETS_URL);
  }


  function test_url_should_concats_argument() {
    $this->assertEquals(Assets::url('prototype.js'),
                       STATIC_ASSETS_URL . 'prototype.js');
  }
}


class DynamicAssetsTestCase extends PHPUnit_Framework_TestCase
{


  function setUp() {
    Assets::set_assets_url(DYNAMIC_ASSETS_URL);
  }


  function tearDown() {
  }


  function test_url_without_arg_should_cycle() {
    for ($i = 0; $i < Assets::NUMBER_OF_ALIASES + 1; ++$i)
      $this->assertEquals(Assets::url(),
                         sprintf(DYNAMIC_ASSETS_URL,
                                 $i % Assets::NUMBER_OF_ALIASES));
  }


  function test_url_with_paramater_should_not_cycle() {
    $url = Assets::url('prototype.js');
    $url2 = Assets::url('prototype.js');
    $pattern = sprintf('@http://www[0-%d].example.com/public/@',
                       Assets::NUMBER_OF_ALIASES - 1);
    $this->assertRegexp($pattern, $url);
    $this->assertEquals($url, $url2);
  }
}


class AssetsHelpersTestCase extends PHPUnit_Framework_TestCase
{


  function setUp() {
    Assets::set_assets_url(STATIC_ASSETS_URL);
  }


  function tearDown() {
  }


  function test_image_path_should_add_directory_before_image() {
    $expected = STATIC_ASSETS_URL.'images/logo.png';
    $this->assertEquals(Assets::image_path('logo.png'), $expected);
  }


  function test_image_path_should_add_png_if_no_extension_were_given() {
    $expected = STATIC_ASSETS_URL.'images/logo.png';
    $this->assertEquals(Assets::image_path('logo'), $expected);
  }


  function test_image_path_should_not_touch_absolute_paths() {
    $url = Assets::image_path('/some/logo.png');
    $this->assertEquals(STATIC_ASSETS_URL.'some/logo.png', $url);
  }


  function test_javascript_path_should_add_directory_before_script() {
    $expected = STATIC_ASSETS_URL.'javascripts/prototype.js';
    $this->assertEquals(Assets::javascript_path('prototype.js'), $expected);
  }


  function test_javascript_path_should_add_js_if_no_extension_were_given() {
    $expected = STATIC_ASSETS_URL.'javascripts/prototype.js';
    $this->assertEquals(Assets::javascript_path('prototype'), $expected);
  }


  function test_javascript_path_should_not_touch_absolute_paths() {
    $url = Assets::javascript_path('/some/script.js');
    $this->assertEquals(STATIC_ASSETS_URL.'some/script.js', $url);
  }


  function test_stylesheet_path_should_add_directory_before_script() {
    $expected = STATIC_ASSETS_URL.'stylesheets/print.css';
    $this->assertEquals(Assets::stylesheet_path('print.css'), $expected);
  }


  function test_stylesheet_path_should_add_css_if_no_extension_were_given() {
    $expected = STATIC_ASSETS_URL.'stylesheets/print.css';
    $this->assertEquals(Assets::stylesheet_path('print'), $expected);
  }


  function test_stylesheet_path_should_not_touch_absolute_paths() {
    $url = Assets::stylesheet_path('/some/style.css');
    $this->assertEquals(STATIC_ASSETS_URL.'some/style.css', $url);
  }


  function test_img_should_return_img_tag_with_alt_attribute() {
    $expected = '<img alt="Logo" src="'.STATIC_ASSETS_URL.'images/logo.png">';
    $this->assertEquals(Assets::img('logo.png'), $expected);
  }


  function test_img_should_respect_alt_attribute() {
    $expected = '<img alt="logo" src="'.STATIC_ASSETS_URL.'images/logo.png">';
    $this->assertEquals(Assets::img('logo.png', array('alt' => 'logo')),
                       $expected);
  }


  function test_img_should_respect_size_attribute() {
    $expected = '<img alt="Logo" height="20" src="'.STATIC_ASSETS_URL.'images/logo.png" width="10">';
    $this->assertEquals(Assets::img('logo.png', array('size' => '10@20')),
                       $expected);
  }


  function test_img_should_respect_other_attributes() {
    $expected = '<img a="1" alt="Logo" b="2" src="'.STATIC_ASSETS_URL.'images/logo.png">';
    $this->assertEquals(Assets::img('logo.png', array('a' => '1', 'b' => 2)),
                       $expected);
  }


  function test_script_should_return_script_tag() {
    $expected = '<script src="'.STATIC_ASSETS_URL.'javascripts/prototype.js"></script>' . "\n";
    $this->assertEquals(Assets::script('prototype'), $expected);
  }


  function test_script_should_return_multiple_script_tags() {
    $expected  = '<script src="'.STATIC_ASSETS_URL.'javascripts/prototype.js"></script>' . "\n";
    $expected .= '<script src="'.STATIC_ASSETS_URL.'javascripts/scriptaculous.js"></script>' . "\n";
    $this->assertEquals(Assets::script('prototype', 'scriptaculous'), $expected);
  }

  function test_script_should_respect_url_parameters() {
    $expected  = '<script '.
                 'src="'.STATIC_ASSETS_URL.'javascripts/scriptaculous.js?load=effects,dragdrop">'.
                 '</script>' . "\n";
    $this->assertEquals(Assets::script('scriptaculous.js?load=effects,dragdrop'), $expected);
  }


  function test_stylesheet_should_return_link_tag() {
    $expected = '<link href="'.STATIC_ASSETS_URL.'stylesheets/blue.css" media="screen" rel="stylesheet">' . "\n";
    $this->assertEquals(Assets::stylesheet('blue'), $expected);
  }


  function test_stylesheet_should_return_multiple_link_tags() {
    $expected  = '<link href="'.STATIC_ASSETS_URL.'stylesheets/blue.css" media="screen" rel="stylesheet">' . "\n";
    $expected .= '<link href="'.STATIC_ASSETS_URL.'stylesheets/green.css" media="screen" rel="stylesheet">' . "\n";
    $expected .= '<link href="'.STATIC_ASSETS_URL.'stylesheets/red.css" media="screen" rel="stylesheet">' . "\n";
    $this->assertEquals(Assets::stylesheet('blue', 'green', 'red'), $expected);
  }


  function test_stylesheet_should_respect_options() {
    $expected  = '<link href="'.STATIC_ASSETS_URL.'stylesheets/blue.css" media="all" rel="stylesheet">' . "\n";
    $expected .= '<link href="'.STATIC_ASSETS_URL.'stylesheets/green.css" media="all" rel="stylesheet">' . "\n";
    $expected .= '<link href="'.STATIC_ASSETS_URL.'stylesheets/red.css" media="all" rel="stylesheet">' . "\n";
    $this->assertEquals(Assets::stylesheet('blue', 'green', 'red', array('media' => 'all')), $expected);
  }
}
