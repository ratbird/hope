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

require_once 'lib/classes/URLHelper.php';

class URLHelperTest extends UnitTestCase
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
        $this->assertEqual(URLHelper::getURL(''), '?');
        $this->assertEqual(URLHelper::getURL('x'), 'x');
        $this->assertEqual(URLHelper::getURL('#x'), '?#x');

        URLHelper::setBaseURL('/dir/');

        $this->assertEqual(URLHelper::getURL(''), '?');
        $this->assertEqual(URLHelper::getURL('#x'), '?#x');
        $this->assertEqual(URLHelper::getURL('?a=b'), '?a=b');
    }

    public function testAddLinkParam ()
    {
        $this->assertEqual(URLHelper::getLinkParams(), array());

        URLHelper::addLinkParam('foo', 'bar');
        $this->assertEqual(URLHelper::getLinkParams(), array('foo' => 'bar'));

        URLHelper::addLinkParam('answer', 42);
        $this->assertEqual(URLHelper::getLinkParams(),
                           array('foo' => 'bar', 'answer' => '42'));
    }

    public function testBindLinkParam ()
    {
        $_REQUEST['var'] = 'old';

        URLHelper::bindLinkParam('var', $var);
        $this->assertEqual($var, 'old');

        $var = 'new';
        $this->assertEqual(URLHelper::getURL(''), '?var=new');
    }

    public function testRemoveLinkParam ()
    {
        URLHelper::addLinkParam('foo', 'bar');
        URLHelper::addLinkParam('answer', 42);
        URLHelper::removeLinkParam('foo');

        $this->assertEqual(URLHelper::getURL(''), '?answer=42');
    }

    public function testGetURL ()
    {
        URLHelper::addLinkParam('null', NULL);
        URLHelper::addLinkParam('empty', array());
        URLHelper::addLinkParam('foo', 'bar');

        $url = 'abc?a=b&c=d#top';
        $expected = 'abc?foo=bar&a=b&c=d#top';
        $this->assertEqual(URLHelper::getURL($url), $expected);

        $url = 'abc#top';
        $params = array('a' => 'b', 'c' => 'd');
        $expected = 'abc?foo=bar&a=b&c=d#top';
        $this->assertEqual(URLHelper::getURL($url, $params), $expected);

        $url = 'abc?foo=test';
        $expected = 'abc?foo=test';
        $this->assertEqual(URLHelper::getURL($url), $expected);

        $url = 'abc';
        $params = array('foo' => 'test');
        $expected = 'abc?foo=test';
        $this->assertEqual(URLHelper::getURL($url, $params), $expected);

        $url = 'abc?baz=on';
        $params = array('baz' => 'off');
        $expected = 'abc?foo=bar&baz=off';
        $this->assertEqual(URLHelper::getURL($url, $params), $expected);

        $url = 'abc?foo=baz';
        $params = array('foo' => 'test');
        $expected = 'abc?foo=test';
        $this->assertEqual(URLHelper::getURL($url, $params), $expected);
    }

    public function testGetLink ()
    {
        URLHelper::addLinkParam('foo', '& ;');
        URLHelper::addLinkParam('bar', '"\'');

        $url = 'abc?a=%26&c="d#1';
        $expected = 'abc?foo=%26+%3B&amp;bar=%22%27&amp;a=%26&amp;c=%22d#1';
        $this->assertEqual(URLHelper::getLink($url), $expected);
    }

    public function testSetBaseURL ()
    {
        $this->assertEqual(URLHelper::getLink('foo/bar'), 'foo/bar');
        $this->assertEqual(URLHelper::getLink('/foo/bar'), '/foo/bar');
        $this->assertEqual(URLHelper::getLink('http://www.studip.de/foo/bar'),
                           'http://www.studip.de/foo/bar');

        URLHelper::setBaseURL('/dir/');

        $this->assertEqual(URLHelper::getLink('foo/bar'), '/dir/foo/bar');
        $this->assertEqual(URLHelper::getLink('/foo/bar'), '/foo/bar');
        $this->assertEqual(URLHelper::getLink('http://www.studip.de/foo/bar'),
                           'http://www.studip.de/foo/bar');

        URLHelper::setBaseURL('http://cnn.com/test/');

        $this->assertEqual(URLHelper::getLink('foo/bar'), 'http://cnn.com/test/foo/bar');
        $this->assertEqual(URLHelper::getLink('/foo/bar'), 'http://cnn.com/foo/bar');
        $this->assertEqual(URLHelper::getLink('http://www.studip.de/foo/bar'),
                           'http://www.studip.de/foo/bar');
    }
}
