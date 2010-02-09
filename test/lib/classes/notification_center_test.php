<?php
/*
 * notification_center_test.php - unit tests for the NotificationCenter class
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/classes/NotificationCenter.class.php';
require_once 'vendor/simpletest/mock_objects.php';

interface Observer
{
    public function update($event, $object, $user_data);
}

Mock::generate('Observer');

class NotificationCenterTest extends UnitTestCase
{
    public function setUp()
    {
        $this->observer = new MockObserver();

        NotificationCenter::addObserver($this->observer, 'update', NULL);
        NotificationCenter::addObserver($this->observer, 'update', NULL, $this);
        NotificationCenter::addObserver($this->observer, 'update', 'foo');
        NotificationCenter::addObserver($this->observer, 'update', 'foo', $this);
    }

    public function tearDown()
    {
        NotificationCenter::removeObserver($this->observer);
    }

    public function testAddObserver1()
    {
        NotificationCenter::postNotification('foo', $this);
        $this->observer->expectCallCount('update', 4);
    }

    public function testAddObserver2()
    {
        NotificationCenter::postNotification('bar', $this);
        $this->observer->expectCallCount('update', 2);
    }

    public function testAddObserver3()
    {
        NotificationCenter::postNotification('foo', 'other');
        $this->observer->expectCallCount('update', 2);
    }

    public function testAddObserver4()
    {
        NotificationCenter::postNotification('bar', 'other');
        $this->observer->expectCallCount('update', 1);
    }

    public function testPostNotification()
    {
        $user_data = array(42);

        NotificationCenter::postNotification('foo', $this, $user_data);
        $this->observer->expect('update', array('foo', $this, $user_data));
    }

    public function testRemoveOtherObserver()
    {
        $observer = new MockObserver();

        NotificationCenter::removeObserver($observer);
        NotificationCenter::postNotification('foo', $this);
        $this->observer->expectCallCount('update', 4);
    }

    public function testRemoveObserver1()
    {
        NotificationCenter::removeObserver($this->observer);
        NotificationCenter::postNotification('foo', $this);
        NotificationCenter::postNotification('bar', $this);
        NotificationCenter::postNotification('foo', 'other');
        NotificationCenter::postNotification('bar', 'other');
        $this->observer->expectCallCount('update', 0);
    }

    public function testRemoveObserver2a()
    {
        NotificationCenter::removeObserver($this->observer, 'foo');
        NotificationCenter::postNotification('foo', $this);
        $this->observer->expectCallCount('update', 2);
    }

    public function testRemoveObserver2b()
    {
        NotificationCenter::removeObserver($this->observer, 'foo');
        NotificationCenter::postNotification('bar', $this);
        $this->observer->expectCallCount('update', 2);
    }

    public function testRemoveObserver2c()
    {
        NotificationCenter::removeObserver($this->observer, 'foo');
        NotificationCenter::postNotification('foo', 'other');
        $this->observer->expectCallCount('update', 1);
    }

    public function testRemoveObserver2d()
    {
        NotificationCenter::removeObserver($this->observer, 'foo');
        NotificationCenter::postNotification('bar', 'other');
        $this->observer->expectCallCount('update', 1);
    }

    public function testRemoveObserver3a()
    {
        NotificationCenter::removeObserver($this->observer, NULL, $this);
        NotificationCenter::postNotification('foo', $this);
        $this->observer->expectCallCount('update', 2);
    }

    public function testRemoveObserver3b()
    {
        NotificationCenter::removeObserver($this->observer, NULL, $this);
        NotificationCenter::postNotification('bar', $this);
        $this->observer->expectCallCount('update', 1);
    }

    public function testRemoveObserver3c()
    {
        NotificationCenter::removeObserver($this->observer, NULL, $this);
        NotificationCenter::postNotification('foo', 'other');
        $this->observer->expectCallCount('update', 2);
    }

    public function testRemoveObserver3d()
    {
        NotificationCenter::removeObserver($this->observer, NULL, $this);
        NotificationCenter::postNotification('bar', 'other');
        $this->observer->expectCallCount('update', 1);
    }

    public function testRemoveObserver4a()
    {
        NotificationCenter::removeObserver($this->observer, 'foo', $this);
        NotificationCenter::postNotification('foo', $this);
        $this->observer->expectCallCount('update', 3);
    }

    public function testRemoveObserver4b()
    {
        NotificationCenter::removeObserver($this->observer, 'foo', $this);
        NotificationCenter::postNotification('bar', $this);
        $this->observer->expectCallCount('update', 2);
    }

    public function testRemoveObserver4c()
    {
        NotificationCenter::removeObserver($this->observer, 'foo', $this);
        NotificationCenter::postNotification('foo', 'other');
        $this->observer->expectCallCount('update', 2);
    }

    public function testRemoveObserver4d()
    {
        NotificationCenter::removeObserver($this->observer, 'foo', $this);
        NotificationCenter::postNotification('bar', 'other');
        $this->observer->expectCallCount('update', 1);
    }
}
