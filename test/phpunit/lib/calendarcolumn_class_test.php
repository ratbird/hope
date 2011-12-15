<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require_once dirname(__FILE__) . '/../bootstrap.php';
require_once 'lib/calendar/CalendarColumn.class.php';
require_once 'lib/functions.php';
require_once 'lib/classes/URLHelper.php';



class CalendarColumnCase extends PHPUnit_Framework_TestCase {


    function setUp() {
    }


    function tearDown() {
    }


    function test_class_should_exist() {
        $this->assertTrue(class_exists('CalendarColumn'));
    }

    function test_create() {
        $this->assertInstanceOf("CalendarColumn", CalendarColumn::create());
    }

    function test_get_id() {
        $id = "test_id";
        $column = new CalendarColumn($id);
        $this->assertEquals($column->getId(), $id);
    }

    function test_set_id() {
        $id = "test_id";
        $column = new CalendarColumn("falsche id");
        $column->setId($id);
        $this->assertEquals($column->getId(), $id);
    }

    function test_set_title() {
        $title = "test_title";
        $column = new CalendarColumn();
        $column->setTitle($title);
        $this->assertEquals($column->getTitle(), $title);
    }

    function test_set_url() {
        $url = URLHelper::getURL("about.php", array("username" => get_username()));
        $column = CalendarColumn::create()->setURL($url);
        $this->assertEquals($column->getURL(), $url);
    }

    function test_add_entry() {
        $entry = array('start' => "0800", 'end' => "1000", 'title' => "test_title");
        $column = CalendarColumn::create()->addEntry($entry);
        $entry = array('start' => "1200", 'end' => "1230", 'title' => "test_title_number_2");
        $column->addEntry($entry);
        $entries = $column->getEntries();
        $this->assertInternalType("array", $entries);
        $this->assertEquals(count($entries), 2);
        $this->assertNotEquals($entries[0], $entry);
        $this->assertEquals($entries[1], $entry);
        $this->assertInternalType("array", $entries[1]);
    }

    function test_wrong_entry() {
        $this->setExpectedException('InvalidArgumentException');
        $entry1 = array('start' => "0800", 'end' => "1000");
        $entry2 = array('start' => "1000", 'title' => "test_title");
        $entry3 = array('end' => "1500", 'title' => "test_title");
        $column = CalendarColumn::create()->addEntry($entry1);
        $column = CalendarColumn::create()->addEntry($entry2);
        $column = CalendarColumn::create()->addEntry($entry3);
    }

    function test_add_entries() {
        $entries = array(
            array('start' => "0800", 'end' => "1000", 'title' => "test_title"),
            array('start' => "1200", 'end' => "1400", 'title' => "test_title")
        );
        $column = CalendarColumn::create()->addEntries($entries);
        $this->assertInternalType('array', $column->getEntries());
    }

    function test_erase_entries() {
        $entry = array('start' => "0800", 'end' => "1000", 'title' => "test_title");
        $column = CalendarColumn::create()->addEntry($entry);
        $column->eraseEntries();
        $entries = $column->getEntries();
        $this->assertInternalType("array", $entries);
        $this->assertEquals(count($entries), 0);
    }


    //Die anderen Methoden muss Till testen.

}
