<?php
/*
 * Copyright (c) 2011  Rasmus Fuhse
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Class to set information that should be given to javascript.
 *
 * For a plugin to hand the information "test" to the javascript-function
 * STUDIP.myplugin.myfunction just put the line:
 *  UpdateInformation::setInformation("myplugin.myfunction", "test");
 *
 * @author Rasmus Fuhse
 */
class UpdateInformation {

    static protected $infos = array();

    /**
     * Gives information to the buffer for the javascript. The first parameter is
     * the name of the corresponding javascript-function minus the "STUDIP"
     * and the second parameter is the value handed to that function.
     * @param string $js_function : "test.testfunction" to get the JS-function "STUDIP.test.testfunction(information);"
     * @param mixed $information : anything that could be translated into a json-object
     */
    static public function setInformation($js_function, $information) {
        self::$infos[$js_function] = $information;
    }

    /**
     * returns the information to give it to javascript
     * @return array
     */
    static public function getInformation() {
        return self::$infos;
    }
}

