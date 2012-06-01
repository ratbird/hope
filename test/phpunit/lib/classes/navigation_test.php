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

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'lib/classes/Assets.class.php';
require_once 'lib/classes/NotificationCenter.class.php';
require_once 'lib/classes/URLHelper.php';
require_once 'lib/navigation/Navigation.php';

class NavigationTest extends PHPUnit_Framework_TestCase
{
    public function setUp ()
    {
        Navigation::setRootNavigation(new Navigation(''));
    }

    public function testBadgeNumber ()
    {
        $navigation = new Navigation('badge-test');
        $this->assertFalse($navigation->hasBadgeNumber());
        $this->assertEquals($navigation->getBadgeNumber(), 0);

        $navigation->setBadgeNumber(23);
        $this->assertTrue($navigation->hasBadgeNumber());
        $this->assertEquals($navigation->getBadgeNumber(), 23);
    }

    public function testTitle ()
    {
        $navigation = new Navigation('test');
        $this->assertEquals($navigation->getTitle(), 'test');

        $navigation->setTitle('frob');
        $this->assertEquals($navigation->getTitle(), 'frob');
    }

    public function testImage ()
    {
        $navigation = new Navigation('test', 'foo.php');
        $this->assertNull($navigation->getImage());

        $navigation->setImage('foo.png', array('alt' => 'foo'));
        $assets_img = Assets::url('images/foo.png');
        $this->assertTrue($navigation->isVisible(true));
        $this->assertEquals($navigation->getImage(),
                           array('src' => $assets_img, 'alt' => 'foo'));
    }

    public function testActiveImage ()
    {
        $navigation = new Navigation('test', 'foo.php');
        $navigation->setImage('foo.png');
        $navigation->setActiveImage('bar.png');

        $this->assertEquals($navigation->getImage(),
                           array('src' => Assets::url('images/foo.png')));

        $navigation->setActive(true);
        $this->assertEquals($navigation->getImage(),
                           array('src' => Assets::url('images/bar.png')));
    }

    public function testURL ()
    {
        $navigation = new Navigation('test', 'foo.php');
        $this->assertEquals($navigation->getURL(), 'foo.php');

        $navigation->setURL('bar.php', array('fuzz' => 'yes'));
        $this->assertEquals($navigation->getURL(), 'bar.php?fuzz=yes');
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
        $this->assertEquals($navigation->getSubNavigation(), array());

        $navigation->addSubNavigation('a1', $nav1);
        $navigation->addSubNavigation('a2', $nav2);
        $navigation->addSubNavigation('a3', $nav3);
        $nav2->addSubNavigation('b1', $nav4);
        $this->assertFalse($navigation->isActive());
        $this->assertEquals($navigation->getURL(), 'bar.php');
        $this->assertEquals($navigation->getSubNavigation(),
                           array('a1' => $nav1, 'a2' => $nav2, 'a3' => $nav3));

        $nav4->setActive(true);
        $this->assertTrue($navigation->isActive());
        $this->assertSame($navigation->activeSubNavigation(), $nav2);
        $this->assertSame($nav2->activeSubNavigation(), $nav4);

        $navigation->removeSubNavigation('a3');
        $navigation->insertSubNavigation('a3', $nav3, 'a2');
        $navigation->removeSubNavigation('a1');
        $nav2->insertSubNavigation('a1', $nav1, '');
        $this->assertEquals($navigation->getURL(), 'baz.php');
        $this->assertEquals($navigation->getSubNavigation(),
                           array('a3' => $nav3, 'a2' => $nav2));
        $this->assertEquals($nav2->getSubNavigation(),
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
        $this->assertEquals(Navigation::getItem('/test')->getURL(), 'bar.php');
        $this->assertEquals(Navigation::getItem('/test')->getSubNavigation(),
                           array('a1' => $nav1, 'a2' => $nav2, 'a3' => $nav3));

        Navigation::activateItem('/test/a2/b1');
        $this->assertTrue(Navigation::getItem('/test')->isActive());
        $this->assertTrue(Navigation::getItem('/test/a2')->isActive());
        $this->assertTrue(Navigation::getItem('/test/a2/b1')->isActive());

        Navigation::removeItem('/test/a3');
        Navigation::insertItem('/test/a3', $nav3, 'a2');
        Navigation::removeItem('/test/a1');
        Navigation::insertItem('/test/a2/a1', $nav1, '');
        $this->assertEquals(Navigation::getItem('/test')->getURL(), 'baz.php');
        $this->assertEquals(Navigation::getItem('/test')->getSubNavigation(),
                           array('a3' => $nav3, 'a2' => $nav2));
        $this->assertEquals(Navigation::getItem('/test/a2')->getSubNavigation(),
                           array('b1' => $nav4, 'a1' => $nav1));
    }

    public function testExceptionOnGet ()
    {
        $this->setExpectedException('InvalidArgumentException');
        Navigation::getItem('/test');
    }

    public function testExceptionOnAdd ()
    {
        $navigation = new Navigation('test');
        $this->setExpectedException('InvalidArgumentException');
        Navigation::addItem('/test/foo', $navigation);
    }
}

class NavigationNotificationTest extends PHPUnit_Framework_TestCase
{

    public function testNotificationOnActivation ()
    {
        $navigation = new Navigation('test');
        Navigation::addItem('/test', $navigation);

        $observer = $this->getMock("NotificationObserver");
        $observer->expects($this->once())
            ->method('update')
            ->with($this->equalTo('NavigationDidActivateItem'),
                   $this->equalTo('/test'));

        NotificationCenter::addObserver($observer,
                                        'update',
                                        'NavigationDidActivateItem',
                                        '/test');

        Navigation::activateItem('/test');
    }
}

class NotificationObserver {

    function update($event, $subject, $user_data)
    {
        # will never run
        throw new RuntimeException();
    }
}
