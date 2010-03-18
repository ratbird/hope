<?php

/*
 * Copyright (C) 2010 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/functions.php';

class FunctionsTest extends UnitTestCase {

  function testWords() {
    $string = "one two three";
    $this->assertEqual(words($string), array('one', 'two', 'three'));
  }

  function testWordsWithEmptyString() {
    $string = "";
    $this->assertEqual(words($string), array());
  }
}

