<?php
/*
 * studip_controller.php - studip controller base class
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

abstract class StudipController extends Trails_Controller
{
    /**
    * Returns a URL to a specified route to your Trails application.
    *
    * @param  string   a string containing a controller and optionally an action
    * @param  strings  optional arguments
    *
    * @return string  a URL to this route
    */
    function url_for($to/*, ...*/) {
        $args = func_get_args();

        // calling parent::url_for() is non-trivial in PHP...
        $parent = new ReflectionMethod('Trails_Controller', 'url_for');
        $url = $parent->invokeArgs($this, $args);

        return URLHelper::getURL($url);
    }

    /**
     * Exception handler called when the performance of an action raises an
     * exception.
     *
     * @param  object     the thrown exception
     */
    function rescue($exception)
    {
        throw $exception;
    }
}
