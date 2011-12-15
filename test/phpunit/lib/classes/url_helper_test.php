<?php
/*
 * url_helper_test.php - unit tests for the URLHelper class
 *
 * Copyright (c) 2008  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'lib/classes/URLHelper.php';

class URLHelperTest extends PHPUnit_Framework_TestCase
{
    public function tearDown ()
    {
        // remove all link params
        foreach (URLHelper::getLinkParams() as $param => $value) {
            URLHelper::removeLinkParam($param);
        }

        URLHelper::setBaseURL(NULL);
    }

    public function testNoLinkParam ()
    {
        $this->assertEquals(URLHelper::getURL(''), '?');
        $this->assertEquals(URLHelper::getURL('x'), 'x');
        $this->assertEquals(URLHelper::getURL('#x'), '?#x');

        URLHelper::setBaseURL('/dir/');

        $this->assertEquals(URLHelper::getURL(''), '?');
        $this->assertEquals(URLHelper::getURL('#x'), '?#x');
        $this->assertEquals(URLHelper::getURL('?a=b'), '?a=b');
    }

    public function testAddLinkParam ()
    {
        $this->assertEquals(URLHelper::getLinkParams(), array());

        URLHelper::addLinkParam('foo', 'bar');
        $this->assertEquals(URLHelper::getLinkParams(), array('foo' => 'bar'));

        URLHelper::addLinkParam('answer', 42);
        $this->assertEquals(URLHelper::getLinkParams(),
                           array('foo' => 'bar', 'answer' => '42'));
    }

    public function testBindLinkParam ()
    {
        $_REQUEST['var'] = 'old';

        URLHelper::bindLinkParam('var', $var);
        $this->assertEquals($var, 'old');

        $var = 'new';
        $this->assertEquals(URLHelper::getURL(''), '?var=new');
    }

    public function testRemoveLinkParam ()
    {
        URLHelper::addLinkParam('foo', 'bar');
        URLHelper::addLinkParam('answer', 42);
        URLHelper::removeLinkParam('foo');

        $this->assertEquals(URLHelper::getURL(''), '?answer=42');
    }

    public function testGetURL ()
    {
        URLHelper::addLinkParam('null', NULL);
        URLHelper::addLinkParam('empty', array());
        URLHelper::addLinkParam('foo', 'bar');

        $url = 'abc?a=b&c=d#top';
        $expected = 'abc?foo=bar&a=b&c=d#top';
        $this->assertEquals(URLHelper::getURL($url), $expected);

        $url = 'abc#top';
        $params = array('a' => 'b', 'c' => 'd');
        $expected = 'abc?foo=bar&a=b&c=d#top';
        $this->assertEquals(URLHelper::getURL($url, $params), $expected);

        $url = 'abc?foo=test';
        $expected = 'abc?foo=test';
        $this->assertEquals(URLHelper::getURL($url), $expected);

        $url = 'abc';
        $params = array('foo' => 'test');
        $expected = 'abc?foo=test';
        $this->assertEquals(URLHelper::getURL($url, $params), $expected);

        $url = 'abc?baz=on';
        $params = array('baz' => 'off');
        $expected = 'abc?foo=bar&baz=off';
        $this->assertEquals(URLHelper::getURL($url, $params), $expected);

        $url = 'abc?foo=baz';
        $params = array('foo' => 'test');
        $expected = 'abc?foo=test';
        $this->assertEquals(URLHelper::getURL($url, $params), $expected);
    }

    public function testGetLink ()
    {
        URLHelper::addLinkParam('foo', '& ;');
        URLHelper::addLinkParam('bar', '"\'');

        $url = 'abc?a=%26&c="d#1';
        $expected = 'abc?foo=%26+%3B&amp;bar=%22%27&amp;a=%26&amp;c=%22d#1';
        $this->assertEquals(URLHelper::getLink($url), $expected);
    }

    public function testSetBaseURL ()
    {
        $this->assertEquals(URLHelper::getLink('foo/bar'), 'foo/bar');
        $this->assertEquals(URLHelper::getLink('/foo/bar'), '/foo/bar');
        $this->assertEquals(URLHelper::getLink('http://www.studip.de/foo/bar'),
                           'http://www.studip.de/foo/bar');

        URLHelper::setBaseURL('/dir/');

        $this->assertEquals(URLHelper::getLink('foo/bar'), '/dir/foo/bar');
        $this->assertEquals(URLHelper::getLink('/foo/bar'), '/foo/bar');
        $this->assertEquals(URLHelper::getLink('http://www.studip.de/foo/bar'),
                           'http://www.studip.de/foo/bar');

        URLHelper::setBaseURL('http://cnn.com/test/');

        $this->assertEquals(URLHelper::getLink('foo/bar'), 'http://cnn.com/test/foo/bar');
        $this->assertEquals(URLHelper::getLink('/foo/bar'), 'http://cnn.com/foo/bar');
        $this->assertEquals(URLHelper::getLink('http://www.studip.de/foo/bar'),
                           'http://www.studip.de/foo/bar');
    }
}
