<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require_once 'lib/calendar/CalendarView.class.php';
require_once 'lib/classes/PageLayout.php';


class CalendarViewCase extends UnitTestCase {


    function setUp() {
    }


    function tearDown() {
    }


    function test_class_should_exist() {
        $this->assertTrue(class_exists('CalendarView'));
    }

    function test_constructor() {
        $this->assertIsA(new CalendarView(), "CalendarView");
    }

    function test_setHeight() {
        $height = 75;
        $cview = new CalendarView();
        $cview->setHeight($height);
        $this->assertEqual($cview->getHeight(), $height);
    }

    function test_setRange() {
        $start_hour = 6;
        $end_hour = 12;
        $cview = new CalendarView();
        $cview->setRange($start_hour, $end_hour);
        $result = $cview->getRange();
        $this->assertEqual($start_hour, $result[0]);
        $this->assertEqual($end_hour, $result[1]);
    }

    function test_addColumn() {
        $view = new CalendarView();
        $title1 = "Mittwoch";
        $id1 = 3;
        $view->addColumn($title1, "", $id1);
        $title2 = "Donnerstag";
        $id2 = 4;
        $view->addColumn($title2, "", $id2);
        $columns = $view->getColumns();
        $this->assertIsA($columns, "array");
        $this->assertIsA($columns[0], "CalendarColumn");
        $this->assertEqual($columns[0]->getTitle(), $title1);
        $this->assertEqual($columns[0]->getId(), $id1);
        $this->assertIsA($columns[1], "CalendarColumn");
        $this->assertEqual($columns[1]->getTitle(), $title2);
        $this->assertEqual($columns[1]->getId(), $id2);
    }

    public function test_negative_addEntry() {
        $this->expectException("Exception");
        $view = new CalendarView();
        $entry = array(
            'title' => "Test Eintrag",
            'start' => "0800",
            'end' => "0900"
        );
        $view->addEntry($entry);
    }

    public function test_addEntry_getEntries() {
        $view = new CalendarView();
        $id = 3;
        $view->addColumn("Montag", "", $id);
        $entry = array(
            'title' => "Test Eintrag",
            'start' => "0800",
            'end' => "0900"
        );
        $view->addEntry($entry);
        $entries = $view->getEntries();
        $this->assertIsA($entries, "array");
        $this->assertNotNull($entries['day_'.$id]);
    }

    public function test_insertFunction() {
        $view = new CalendarView();
        $js_function_object = 'function () { alert("Watch out, Gringo!"); }';
        $view->setInsertFunction($js_function_object);
        $this->assertEqual($view->getInsertFunction(), $js_function_object);
    }

    //Die anderen Methoden muss Till testen.

}


