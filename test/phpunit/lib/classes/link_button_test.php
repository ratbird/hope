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
require_once 'lib/classes/URLHelper.php';
require_once 'lib/classes/LinkButton.class.php';

use \Studip\LinkButton;

class LinkButtonTestCase extends PHPUnit_Framework_TestCase
{
    function testGet()
    {
        LinkButton::get();
    }

    function testGetWithLabel()
    {
        $this->assertEquals('' . LinkButton::get('yes'), '<a class="button" href="?">yes</a>');
    }

    function testGetWithLabelAndUrl()
    {
        $this->assertEquals('' . LinkButton::get('yes', 'http://example.net'), '<a class="button" href="http://example.net">yes</a>');
    }

    function testGetWithLabelAndArray()
    {
        $this->assertEquals('' . LinkButton::get('yes', array('a' => 1, 'b' => 2)),
                            '<a a="1" b="2" class="button" href="?">yes</a>');
    }

    function testGetWithLabelUrlAndArray()
    {
        $this->assertEquals('' . LinkButton::get('yes', 'http://example.net', array('a' => 1, 'b' => 2)),
                            '<a a="1" b="2" class="button" href="http://example.net">yes</a>');
    }

    function testGetAccept()
    {
        $this->assertEquals('' . LinkButton::getAccept(),
                            '<a class="accept button" href="?" name="accept">&uuml;bernehmen</a>');
    }

    function testGetCancel()
    {
        $this->assertEquals('' . LinkButton::getCancel(),
                            '<a class="cancel button" href="?" name="cancel">abbrechen</a>');
    }

    function testGetPreOrder()
    {
        $this->assertEquals('' . LinkButton::getPreOrder(),
                            '<a class="pre-order button" href="?" name="pre-order">ok</a>');
    }

    function testGetWithInsaneArguments()
    {
        $this->assertEquals('' . LinkButton::get('>ok<', 'http://example.net?m=&m=', array('mad' => '<S>tu"ff')),
                            '<a class="button" href="http://example.net?m=&amp;m=" mad="&lt;S&gt;tu&quot;ff">&gt;ok&lt;</a>');
    }
}
