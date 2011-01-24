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
     * Validate arguments based on a list of given types. The types are:
     * 'int', 'float', 'option' and 'string'. If the list of types is NULL
     * or shorter than the argument list, 'option' is assumed for all
     * remaining arguments.
     *
     * @param array   an array of arguments to the action
     * @param array   list of argument types (optional)
     */
    function validate_args(&$args, $types = NULL) {
        foreach ($args as $i => &$arg) {
            $type = isset($types[$i]) ? $types[$i] : 'option';

            switch ($type) {
                case 'int':
                    $arg = (int) $arg;
                    break;

                case 'float':
                    $arg = (float) strtr($arg, ',', '.');
                    break;

                case 'option':
                    if (preg_match('/\\W/', $arg)) {
                        throw new Trails_Exception(400);
                    }
            }
        }
    }

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
