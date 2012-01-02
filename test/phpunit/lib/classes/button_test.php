<?php

/*
 * Copyright (C) 2011 - <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'lib/functions.php';
require_once 'lib/classes/Button.class.php';

use \Studip\Button;

class ButtonTestCase extends PHPUnit_Framework_TestCase
{
    function testGet()
    {
        $this->assertEquals('' . Button::get(), '<button type="submit" name="ok">ok</button>');
    }

    function testGetWithLabel()
    {
        $this->assertEquals('' . Button::get('yes'), '<button type="submit" name="yes">yes</button>');
    }

    function testGetWithLabelAndString()
    {
        $this->assertEquals('' . Button::get('yes', 'aName'), '<button type="submit" name="aName">yes</button>');
    }

    function testGetWithLabelAndArray()
    {
        $this->assertEquals('' . Button::get('yes', array('a' => 1, 'b' => 2)),
                           '<button type="submit" a="1" b="2" name="yes">yes</button>');
    }

    function testGetWithLabelNameAndArray()
    {
        $this->assertEquals('' . Button::get('yes', 'aName', array('a' => 1, 'b' => 2)),
                           '<button type="submit" a="1" b="2" name="aName">yes</button>');
    }

    function testGetAccept()
    {
        $this->assertEquals('' . Button::getAccept(),
                           '<button type="submit" class="accept" name="accept">&uuml;bernehmen</button>');
    }

    function testGetCancel()
    {
        $this->assertEquals('' . Button::getCancel(),
                           '<button type="submit" class="cancel" name="cancel">abbrechen</button>');
    }

    function testGetPreOrder()
    {
        $this->assertEquals('' . Button::getPreOrder(),
                           '<button type="submit" class="pre-order" name="pre-order">ok</button>');
    }

    function testGetWithInsaneArguments()
    {
        $this->assertEquals('' . Button::get('>ok<', 'm&m', array('mad' => '<S>tu"ff')),
                           '<button type="submit" mad="&lt;S&gt;tu&quot;ff" name="m&amp;m">&gt;ok&lt;</button>');
    }
}
