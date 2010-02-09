<?php
/*
 * request_test.php - unit tests for the Request class
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/classes/Request.class.php';

class RequestTest extends UnitTestCase
{
    public function setUp ()
    {
        $_GET['a']    = 'test';
        $_POST['b']   = '\\h1"';
        $_GET['c']    = '-23';
        $_POST['d']   = '12.7';
        $_GET['e']    = '3,14';
        $_POST['s_x'] = '0';

        $_GET['v1']  = array('1', '2.4', '3,7');
        $_POST['v2'] = array('on\'e', 'two', 'thr33');

        if (get_magic_quotes_gpc()) {
            $_GET  = Request::addslashes($_GET);
            $_POST = Request::addslashes($_POST);
        }
    }

    public function testURL ()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['REQUEST_URI'] = '/do/it?now=1';

        $this->assertEqual(Request::url(), 'https://www.example.com/do/it?now=1');

        $_SERVER['HTTPS'] = '';
        $_SERVER['SERVER_NAME'] = 'www.example.com';
        $_SERVER['SERVER_PORT'] = '8080';
        $_SERVER['REQUEST_URI'] = '/index.php';

        $this->assertEqual(Request::url(), 'http://www.example.com:8080/index.php');
    }

    public function testArrayAccess ()
    {
        $request = Request::getInstance();

        $this->assertNull($request['null']);
        $this->assertIdentical($request['a'], 'test');
        $this->assertIdentical($request['b'], '\\h1"');
        $this->assertIdentical($request['c'], '-23');
    }

    public function testSetParam ()
    {
        Request::set('yyy', 'xyzzy');
        Request::set('zzz', array(1, 2));

        $this->assertIdentical(Request::get('yyy'), 'xyzzy');
        $this->assertIdentical(Request::getArray('zzz'), array(1, 2));
    }

    public function testStringParam ()
    {
        $this->assertNull(Request::get('null'));
        $this->assertIdentical(Request::get('null', 'foo'), 'foo');
        $this->assertIdentical(Request::get('a'), 'test');
        $this->assertIdentical(Request::get('b'), '\\h1"');
        $this->assertIdentical(Request::get('c'), '-23');
        $this->assertIdentical(Request::get('d'), '12.7');
        $this->assertNull(Request::get('v2'));

        $this->assertNull(Request::quoted('null'));
        $this->assertIdentical(Request::quoted('null', 'foo'), 'foo');
        $this->assertIdentical(Request::quoted('b'), '\\\\h1\\"');
        $this->assertNull(Request::quoted('v2'));
    }

    public function testOptionParam ()
    {
        $this->assertNull(Request::option('null'));
        $this->assertIdentical(Request::option('a'), 'test');
        $this->assertNull(Request::option('b'));
        $this->assertNull(Request::option('v1'));
    }

    public function testIntParam ()
    {
        $this->assertNull(Request::int('null'));
        $this->assertIdentical(Request::int('a'), 0);
        $this->assertIdentical(Request::int('c'), -23);
        $this->assertIdentical(Request::int('d'), 12);
        $this->assertIdentical(Request::int('e'), 3);
        $this->assertNull(Request::int('v1'));
    }

    public function testFloatParam ()
    {
        $this->assertNull(Request::float('null'));
        $this->assertIdentical(Request::float('a'), 0.0);
        $this->assertIdentical(Request::float('c'), -23.0);
        $this->assertIdentical(Request::float('d'), 12.7);
        $this->assertIdentical(Request::float('e'), 3.14);
        $this->assertNull(Request::float('v1'));
    }

    public function testStringArrayParam ()
    {
        $this->assertIdentical(Request::getArray('null'), array());
        $this->assertIdentical(Request::getArray('b'), array());
        $this->assertIdentical(Request::getArray('v1'), array('1', '2.4', '3,7'));
        $this->assertIdentical(Request::getArray('v2'), array('on\'e', 'two', 'thr33'));

        $this->assertIdentical(Request::quotedArray('null'), array());
        $this->assertIdentical(Request::quotedArray('b'), array());
        $this->assertIdentical(Request::quotedArray('v1'), array('1', '2.4', '3,7'));
        $this->assertIdentical(Request::quotedArray('v2'), array('on\\\'e', 'two', 'thr33'));
    }

    public function testOptionArrayParam ()
    {
        $this->assertIdentical(Request::optionArray('null'), array());
        $this->assertIdentical(Request::optionArray('a'), array());
        $this->assertIdentical(Request::optionArray('v1'), array('1'));
        $this->assertIdentical(Request::optionArray('v2'), array(1 => 'two', 2 => 'thr33'));
    }

    public function testIntArrayParam ()
    {
        $this->assertIdentical(Request::intArray('null'), array());
        $this->assertIdentical(Request::intArray('c'), array());
        $this->assertIdentical(Request::intArray('v1'), array(1, 2, 3));
        $this->assertIdentical(Request::intArray('v2'), array(0, 0, 0));
    }

    public function testFloatArrayParam ()
    {
        $this->assertIdentical(Request::floatArray('null'), array());
        $this->assertIdentical(Request::floatArray('c'), array());
        $this->assertIdentical(Request::floatArray('v1'), array(1.0, 2.4, 3.7));
        $this->assertIdentical(Request::floatArray('v2'), array(0.0, 0.0, 0.0));
    }

    public function testSubmitted ()
    {
        $this->assertFalse(Request::submitted('null'));
        $this->assertTrue(Request::submitted('s'));
        $this->assertTrue(Request::submitted('v1'));
    }
}
