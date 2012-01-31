<?php
# Lifter010: TODO
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
     * remaining arguments. 'option' differs from Request::option() in
     * that it also accepts the charaters '-' and ',' in addition to all
     * word charaters.
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
                    if (preg_match('/[^\\w,-]/', $arg)) {
                        throw new Trails_Exception(400);
                    }
            }
        }

        reset($args);
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

    /**
     * Spawns a new infobox variable on this object, if neccessary.
     **/
    private function populateInfobox() {
        if (!isset($this->infobox)) {
            $this->infobox = array(
                'picture' => 'blank.gif',
                'content' => array()
            );
        }
    }

    /**
     * Sets the header image for the infobox.
     *
     * @param String $image Image to display, path is relative to :assets:/images
     **/
    function setInfoBoxImage($image) {
        $this->populateInfobox();

        $this->infobox['picture'] = $image;
    }

    /**
     * Adds an item to a certain category section of the infobox. Categories
     * are created in the order this method is invoked. Multiple occurences of
     * a category will add items to the category.
     *
     * @param String $category The item's category title used as the header
     *                         above displayed category - write spoken not
     *                         tech language ^^
     * @param String $text     The content of the item, may contain html
     * @param String $icon     Icon to display in front the item, path is
     *                         relative to :assets:/images
     **/
    function addToInfobox($category, $text, $icon = 'blank.gif') {
        $this->populateInfobox();

        $infobox = $this->infobox;

        if (!isset($infobox['content'][$category])) {
            $infobox['content'][$category] = array(
                'kategorie' => $category,
                'eintrag'   => array(),
            );
        }
        $infobox['content'][$category]['eintrag'][] = compact('icon', 'text');

        $this->infobox = $infobox;
    }

}
