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
    function testCreate()
    {
        $this->assertEquals('' . Button::create(), '<button type="submit" class="button" name="ok">ok</button>');
    }

    function testCreateWithLabel()
    {
        $this->assertEquals('' . Button::create('yes'), '<button type="submit" class="button" name="yes">yes</button>');
    }

    function testCreateWithLabelAndString()
    {
        $this->assertEquals('' . Button::create('yes', 'aName'), '<button type="submit" class="button" name="aName">yes</button>');
    }

    function testCreateWithLabelAndArray()
    {
        $this->assertEquals('' . Button::create('yes', array('a' => 1, 'b' => 2)),
                           '<button type="submit" a="1" b="2" class="button" name="yes">yes</button>');
    }

    function testCreateWithLabelNameAndArray()
    {
        $this->assertEquals('' . Button::create('yes', 'aName', array('a' => 1, 'b' => 2)),
                           '<button type="submit" a="1" b="2" class="button" name="aName">yes</button>');
    }

    function testCreateAccept()
    {
        $this->assertEquals('' . Button::createAccept(),
                           '<button type="submit" class="accept button" name="accept">&uuml;bernehmen</button>');
    }

    function testCreateCancel()
    {
        $this->assertEquals('' . Button::createCancel(),
                           '<button type="submit" class="cancel button" name="cancel">abbrechen</button>');
    }

    function testCreatePreOrder()
    {
        $this->assertEquals('' . Button::createPreOrder(),
                           '<button type="submit" class="pre-order button" name="pre-order">ok</button>');
    }

    function testCreateWithInsaneArguments()
    {
        $this->assertEquals('' . Button::create('>ok<', 'm&m', array('mad' => '<S>tu"ff')),
                           '<button type="submit" class="button" mad="&lt;S&gt;tu&quot;ff" name="m&amp;m">&gt;ok&lt;</button>');
    }
}
