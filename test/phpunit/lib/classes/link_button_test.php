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
    function testCreate()
    {
        LinkButton::create();
    }

    function testCreateWithLabel()
    {
        $this->assertEquals('' . LinkButton::create('yes'), '<a class="button" href="?">yes</a>');
    }

    function testCreateWithLabelAndUrl()
    {
        $this->assertEquals('' . LinkButton::create('yes', 'http://example.net'), '<a class="button" href="http://example.net">yes</a>');
    }

    function testCreateWithLabelAndArray()
    {
        $this->assertEquals('' . LinkButton::create('yes', array('a' => 1, 'b' => 2)),
                            '<a a="1" b="2" class="button" href="?">yes</a>');
    }

    function testCreateWithLabelUrlAndArray()
    {
        $this->assertEquals('' . LinkButton::create('yes', 'http://example.net', array('a' => 1, 'b' => 2)),
                            '<a a="1" b="2" class="button" href="http://example.net">yes</a>');
    }

    function testCreateAccept()
    {
        $this->assertEquals('' . LinkButton::createAccept(),
                            '<a class="accept button" href="?" name="accept">&uuml;bernehmen</a>');
    }

    function testCreateCancel()
    {
        $this->assertEquals('' . LinkButton::createCancel(),
                            '<a class="cancel button" href="?" name="cancel">abbrechen</a>');
    }

    function testCreatePreOrder()
    {
        $this->assertEquals('' . LinkButton::createPreOrder(),
                            '<a class="pre-order button" href="?" name="pre-order">ok</a>');
    }

    function testCreateWithInsaneArguments()
    {
        $this->assertEquals('' . LinkButton::create('>ok<', 'http://example.net?m=&m=', array('mad' => '<S>tu"ff')),
                            '<a class="button" href="http://example.net?m=&amp;m=" mad="&lt;S&gt;tu&quot;ff">&gt;ok&lt;</a>');
    }
}
