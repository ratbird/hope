<?php
/*
 * navigation_test.php - unit tests for the Navigation class
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/navigation/Navigation.php';

class NavigationTest extends UnitTestCase
{
    public function setUp ()
    {
        Navigation::setRootNavigation(new Navigation(''));
    }

    public function testTitle ()
    {
        $navigation = new Navigation('test');
        $this->assertEqual($navigation->getTitle(), 'test');

        $navigation->setTitle('frob');
        $this->assertEqual($navigation->getTitle(), 'frob');
    }

    public function testImage ()
    {
        $navigation = new Navigation('test', 'foo.php');
        $this->assertNull($navigation->getImage());

        $navigation->setImage('foo.png', array('alt' => 'foo'));
        $assets_img = Assets::url('images/foo.png');
        $this->assertTrue($navigation->isVisible(true));
        $this->assertEqual($navigation->getImage(),
                           array('src' => $assets_img, 'alt' => 'foo'));
    }

    public function testURL ()
    {
        $navigation = new Navigation('test', 'foo.php');
        $this->assertEqual($navigation->getURL(), 'foo.php');

        $navigation->setURL('bar.php', array('fuzz' => 'yes'));
        $this->assertEqual($navigation->getURL(), 'bar.php?fuzz=yes');
        $this->assertTrue($navigation->isEnabled());

        $navigation->setEnabled(false);
        $this->assertFalse($navigation->isEnabled());
        $this->assertFalse($navigation->isVisible(true));
        $this->assertTrue($navigation->isVisible());

        $navigation->setURL(NULL);
        $this->assertFalse($navigation->isVisible());
    }

    public function testNavigation ()
    {
        $navigation = new Navigation('test');
        $nav1 = new Navigation('foo', NULL);
        $nav2 = new Navigation('bar', 'bar.php');
        $nav3 = new Navigation('baz', 'baz.php');
        $nav4 = new Navigation('egg', 'egg.php');
        $this->assertNull($navigation->getURL());
        $this->assertEqual($navigation->getSubNavigation(), array());

        $navigation->addSubNavigation('a1', $nav1);
        $navigation->addSubNavigation('a2', $nav2);
        $navigation->addSubNavigation('a3', $nav3);
        $nav2->addSubNavigation('b1', $nav4);
        $this->assertFalse($navigation->isActive());
        $this->assertEqual($navigation->getURL(), 'bar.php');
        $this->assertEqual($navigation->getSubNavigation(),
                           array('a1' => $nav1, 'a2' => $nav2, 'a3' => $nav3));

        $nav4->setActive($nav4);
        $this->assertTrue($navigation->isActive());
        $this->assertReference($navigation->activeSubNavigation(), $nav2);
        $this->assertReference($nav2->activeSubNavigation(), $nav4);

        $navigation->removeSubNavigation('a3');
        $navigation->insertSubNavigation('a3', $nav3, 'a2');
        $navigation->removeSubNavigation('a1');
        $nav2->insertSubNavigation('a1', $nav1, '');
        $this->assertEqual($navigation->getURL(), 'baz.php');
        $this->assertEqual($navigation->getSubNavigation(),
                           array('a3' => $nav3, 'a2' => $nav2));
        $this->assertEqual($nav2->getSubNavigation(),
                           array('b1' => $nav4, 'a1' => $nav1));
    }

    public function testNavigationTree ()
    {
        $navigation = new Navigation('test');
        $nav1 = new Navigation('foo', NULL);
        $nav2 = new Navigation('bar', 'bar.php');
        $nav3 = new Navigation('baz', 'baz.php');
        $nav4 = new Navigation('egg', 'egg.php');
        $this->assertFalse(Navigation::hasItem('/test/a2'));

        Navigation::addItem('/test', $navigation);
        Navigation::addItem('/test/a1', $nav1);
        Navigation::addItem('/test/a2', $nav2);
        Navigation::addItem('/test/a3', $nav3);
        Navigation::addItem('/test/a2/b1', $nav4);
        $this->assertTrue(Navigation::hasItem('/test/a2'));
        $this->assertFalse(Navigation::getItem('/test')->isActive());
        $this->assertEqual(Navigation::getItem('/test')->getURL(), 'bar.php');
        $this->assertEqual(Navigation::getItem('/test')->getSubNavigation(),
                           array('a1' => $nav1, 'a2' => $nav2, 'a3' => $nav3));

        Navigation::activateItem('/test/a2/b1');
        $this->assertTrue(Navigation::getItem('/test')->isActive());
        $this->assertTrue(Navigation::getItem('/test/a2')->isActive());
        $this->assertTrue(Navigation::getItem('/test/a2/b1')->isActive());

        Navigation::removeItem('/test/a3');
        Navigation::insertItem('/test/a3', $nav3, 'a2');
        Navigation::removeItem('/test/a1');
        Navigation::insertItem('/test/a2/a1', $nav1, '');
        $this->assertEqual(Navigation::getItem('/test')->getURL(), 'baz.php');
        $this->assertEqual(Navigation::getItem('/test')->getSubNavigation(),
                           array('a3' => $nav3, 'a2' => $nav2));
        $this->assertEqual(Navigation::getItem('/test/a2')->getSubNavigation(),
                           array('b1' => $nav4, 'a1' => $nav1));
    }

    public function testExceptionOnGet ()
    {
        $this->expectException('InvalidArgumentException');
        Navigation::getItem('/test');
    }

    public function testExceptionOnAdd ()
    {
        $navigation = new Navigation('test');
        $this->expectException('InvalidArgumentException');
        Navigation::addItem('/test/foo', $navigation);
    }
}
