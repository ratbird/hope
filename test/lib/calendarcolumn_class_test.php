<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require_once 'lib/calendar/CalendarColumn.class.php';



class CalendarColumnCase extends UnitTestCase {


    function setUp() {
    }


    function tearDown() {
    }


    function test_class_should_exist() {
        $this->assertTrue(class_exists('CalendarColumn'));
    }

    function test_create() {
        $this->assertIsA(CalendarColumn::create(), "CalendarColumn");
    }

    function test_get_id() {
        $id = "test_id";
        $column = new CalendarColumn($id);
        $this->assertEqual($column->getId(), $id);
    }

    function test_set_id() {
        $id = "test_id";
        $column = new CalendarColumn("falsche id");
        $column->setId($id);
        $this->assertEqual($column->getId(), $id);
    }

    function test_set_title() {
        $title = "test_title";
        $column = new CalendarColumn();
        $column->setTitle($title);
        $this->assertEqual($column->getTitle(), $title);
    }

    function test_set_url() {
        $url = URLHelper::getURL("about.php", array("username" => get_username()));
        $column = CalendarColumn::create()->setURL($url);
        $this->assertEqual($column->getURL(), $url);
    }

    function test_add_entry() {
        $entry = array('start' => "0800", 'end' => "1000", 'title' => "test_title");
        $column = CalendarColumn::create()->addEntry($entry);
        $entry = array('start' => "1200", 'end' => "1230", 'title' => "test_title_number_2");
        $column->addEntry($entry);
        $entries = $column->getEntries();
        $this->assertIsA($entries, "array");
        $this->assertEqual(count($entries), 2);
        $this->assertNotEqual($entries[0], $entry);
        $this->assertEqual($entries[1], $entry);
        $this->assertIsA($entries[1], "array");
    }

    function test_wrong_entry() {
        $this->expectException("Exception");
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
        $this->assertIsA($column->getEntries(), "array");
    }

    function test_erase_entries() {
        $entry = array('start' => "0800", 'end' => "1000", 'title' => "test_title");
        $column = CalendarColumn::create()->addEntry($entry);
        $column->eraseEntries();
        $entries = $column->getEntries();
        $this->assertIsA($entries, "array");
        $this->assertEqual(count($entries), 0);
    }


    //Die anderen Methoden muss Till testen.

}


