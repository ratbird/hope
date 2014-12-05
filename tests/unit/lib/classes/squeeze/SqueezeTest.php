<?php
/*
 * Copyright (c) 2011 mlunzena
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

namespace Test;

require_once dirname(__FILE__) . '/../../../bootstrap.php';
require_once 'lib/functions.php';
require_once 'lib/classes/squeeze/squeeze.php';
require_once 'lib/classes/Assets.class.php';


class SqueezeTest extends \PHPUnit_Framework_TestCase
{
    function testPackageAll()
    {
        $tmp = $GLOBALS['STUDIP_BASE_PATH'];
        $GLOBALS['STUDIP_BASE_PATH'] = realpath(dirname(__FILE__) . '/../../../../../');

        \Studip\Squeeze\packageAll(TEST_FIXTURES_PATH."squeeze/assets.yml");

        $GLOBALS['STUDIP_BASE_PATH'] = $tmp;
    }
}
