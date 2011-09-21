<?php

/*
 * Copyright (C) 2011 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require_once 'lib/classes/UpdateInformation.class.php';



class UpdateInformationCase extends UnitTestCase {


    function setUp() {
    }


    function tearDown() {
    }


    function test_setgetinformation() {
        $old_data = array('test' => "TestValue");
        UpdateInformation::setInformation("test.myfunc", $old_data);
        UpdateInformation::setInformation("test.myfunc2", "strangedata");
        $new_data = UpdateInformation::getInformation();
        $this->assertIsA($new_data, "array");
        $this->assertIsA($new_data['test.myfunc'], "array");
        $this->assertIsA($new_data['test.myfunc2'], "string");
        $this->assertEqual($new_data['test.myfunc']['test'], "TestValue");
        $this->assertEqual($new_data['test.myfunc2'], "strangedata");
    }
    
    //kann man nicht automatischtesten, da abhängig von $_SERVER
    //function test_iscollecting() {}

}


