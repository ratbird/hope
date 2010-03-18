<?php

/*
 * Copyright (C) 2010 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/visual.inc.php';

class VisualFunctionsTest extends UnitTestCase {

  function testFormatReady() {
    $expected = "<b>some code</b>";
    $this->assertEqual(formatReady("*some*code*"), $expected);
  }

  function testHtmlReady() {
    $pairs = array(
      'abc'    => 'abc',
      'הצü'    => '&auml;&ouml;&uuml;',
      '<'      => '&lt;',
      '"'      => '&quot;',
      "'"      => '&#039;',
      '&amp;'  => '&amp;amp;',
      '&#039;' => '&#039;',
      ''       => '',
      null     => null
    );
    foreach ($pairs as $string => $expected) {
      $this->assertEqual(htmlReady($string), $expected);
    }
  }
}

