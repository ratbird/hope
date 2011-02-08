<?php
/*
 * csrf_protection_test.php - unit tests for the Request class
 *
 * Copyright (c) 2011 mlunzena
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/classes/CSRFProtection.php';

class CSRFProtectionTokenTest extends UnitTestCase
{
    function setUp()
    {
        if (session_id() === '') {
            session_id("test-session");
        }
        $this->original_session = $_SESSION;
        $_SESSION = array();
    }

    function tearDown()
    {
        $_SESSION = $this->original_session;
    }

    function testTokenGeneration()
    {
        $this->assertEqual(sizeof($_SESSION), 0);
        CSRFProtection::token();
        $this->assertEqual(sizeof($_SESSION), 1);
    }

    function testTokenIdentity()
    {
        $this->assertEqual(CSRFProtection::token(), CSRFProtection::token());
    }

    function testTokenSessionDifference()
    {
        $token1 = CSRFProtection::token();

        $_SESSION = array();

        $token2 = CSRFProtection::token();

        $this->assertNotEqual($token1, $token2);
    }

    function testTokenIsAString()
    {
        $token = CSRFProtection::token();
        $this->assertIsA($token, "string");
    }

    function testTokenTag()
    {
        $token = CSRFProtection::token();
        $this->assertTrue(strpos(CSRFProtection::tokenTag(), $token) !== FALSE);
    }
}

class CSRFRequestTest extends UnitTestCase
{

    function setUp()
    {
        if (session_id() === '') {
            session_id("test-session");
        }
        $this->original_state = array($_SESSION, $_POST, $_SERVER);
        $_SESSION = array();
        $_POST = array();
        $this->token = CSRFProtection::token();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = null;
    }

    function tearDown()
    {
        list($_SESSION, $_POST, $_SERVER) = $this->original_state;
    }

    function testInvalidUnsafeRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->expectException('InvalidSecurityTokenException');
        CSRFProtection::verifyUnsafeRequest();
    }

    function testValidUnsafeRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['security_token'] = $this->token;
        CSRFProtection::verifyUnsafeRequest();
    }

    function testSafeRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->expectException('MethodNotAllowedException');
        CSRFProtection::verifyUnsafeRequest();
    }

    function testSafeXHR()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XmlHttpRequest';
        $this->expectException('MethodNotAllowedException');
        CSRFProtection::verifyUnsafeRequest();
    }

    function testUnsafeXHRWithoutToken()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XmlHttpRequest';
        unset($_POST['security_token']);
        CSRFProtection::verifyUnsafeRequest();
    }

    function testUnsafeXHRWithToken()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XmlHttpRequest';
        $_POST['security_token'] = $this->token;
        CSRFProtection::verifyUnsafeRequest();
    }
}
