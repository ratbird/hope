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

abstract class StudipController extends Trails_Controller {

    function before_filter(&$action, &$args) {
        $this->current_action = $action;
        // allow only "word" characters in arguments
        $this->validate_args($args);
        parent::before_filter($action, $args);
    }

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
                    var_dump($arg);
                    if (preg_match('/[^\\w,-.]/', $arg)) {
                        throw new Trails_Exception(400);
                    }
            }
        }

        reset($args);
    }

    /**
     * Returns a URL to a specified route to your Trails application.
     * without first parameter the current action is used
     * if route begins with a / then the current controller ist prepended
     * if second parameter is an array it is passed to URLHeper
     *
     * @param  string   a string containing a controller and optionally an action
     * @param  strings  optional arguments
     *
     * @return string  a URL to this route
     */
    function url_for($to = ''/* , ... */) {
        $args = func_get_args();
        if (is_array($args[1])) {
            $params = $args[1];
            unset($args[1]);
        } else {
            $params = array();
        }
        //preserve fragment
        list($to, $fragment) = explode('#', $to);
        if (!$to) {
            $to = '/' . ($this->parent_controller ? $this->parent_controller->current_action : $this->current_action);
        }
        if ($to[0] === '/') {
            $prefix = str_replace('_', '/', strtolower(strstr(get_class($this->parent_controller ? $this->parent_controller : $this), 'Controller', true)));
            $to = $prefix . $to;
        }
        $args[0] = $to;
        $url = call_user_func_array("parent::url_for", $args);
        if ($fragment) {
            $url .= '#' . $fragment;
        }
        return URLHelper::getURL($url, $params);
    }

    /**
     * Returns an escaped URL to a specified route to your Trails application.
     * without first parameter the current action is used
     * if route begins with a / then the current controller ist prepended
     * if second parameter is an array it is passed to URLHeper
     *
     * @param  string   a string containing a controller and optionally an action
     * @param  strings  optional arguments
     *
     * @return string  a URL to this route
     */
    function link_for($to = ''/* , ... */) {
        return htmlReady(call_user_func_array(array($this, 'url_for'), func_get_args()));
    }

    /**
     * Exception handler called when the performance of an action raises an
     * exception.
     *
     * @param  object     the thrown exception
     */
    function rescue($exception) {
        throw $exception;
    }

    /**
     * Spawns a new infobox variable on this object, if neccessary.
     *
     * @since Stud.IP 2.3
     * */
    protected function populateInfobox() {
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
     *
     * @since Stud.IP 2.3
     * */
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
     *
     * @since Stud.IP 2.3
     * */
    function addToInfobox($category, $text, $icon = 'blank.gif') {
        $this->populateInfobox();

        $infobox = $this->infobox;

        if (!isset($infobox['content'][$category])) {
            $infobox['content'][$category] = array(
                'kategorie' => $category,
                'eintrag' => array(),
            );
        }
        $infobox['content'][$category]['eintrag'][] = compact('icon', 'text');

        $this->infobox = $infobox;
    }

    /**
     * render given data as json, data is converted to utf-8
     *
     * @param unknown $data
     */
    function render_json($data) {
        $this->set_content_type('application/json;charset=utf-8');
        return $this->render_text(json_encode(studip_utf8encode($data)));
    }

    /**
     * relays current request to another controller and returns the response
     * the other controller is given all assigned properties, additional parameters are passed
     * through
     *
     * @param string $to_uri a trails route
     * @return Trails_Response
     */
    function relay($to_uri/* , ... */) {
        $args = func_get_args();
        $uri = array_shift($args);
        list($controller_path, $unconsumed) = '' === $uri ? $this->dispatcher->default_route() : $this->dispatcher->parse($uri);

        $controller = $this->dispatcher->load_controller($controller_path);
        $assigns = $this->get_assigned_variables();
        unset($assigns['controller']);
        foreach ($assigns as $k => $v) {
            $controller->$k = $v;
        }
        $controller->layout = null;
        $controller->parent_controller = $this;
        array_unshift($args, $unconsumed);
        return call_user_func_array(array($controller, 'perform_relayed'), $args);
    }

    /**
     * perform a given action/parameter string from an relayed request
     * before_filter and after_filter methods are not called
     *
     * @see perform
     * @param string $unconsumed
     * @return Trails_Response
     */
    function perform_relayed($unconsumed/* , ... */) {
        $args = func_get_args();
        $unconsumed = array_shift($args);

        list($action, $extracted_args, $format) = $this->extract_action_and_args($unconsumed);
        $this->format = isset($format) ? $format : 'html';
        $this->current_action = $action;
        $args = array_merge($extracted_args, $args);
        $callable = $this->map_action($action);

        if (is_callable($callable)) {
            call_user_func_array($callable, $args);
        } else {
            $this->does_not_understand($action, $args);
        }

        if (!$this->performed) {
            $this->render_action($action);
        }
        return $this->response;
    }

}
