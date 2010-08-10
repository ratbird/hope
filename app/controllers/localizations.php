<?php
/*
 * Copyright (C) 2010 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * TODO
 */
class LocalizationsController extends Trails_Controller {

    /**
     * Callback function being called before an action is executed. If this
     * function does not return FALSE, the action will be called, otherwise
     * an error will be generated and processing will be aborted. If this function
     * already #rendered or #redirected, further processing of the action is
     * withheld.
     *
     * @param string  Name of the action to perform.
     * @param array   An array of arguments to the action.
     *
     * @return bool
     */
    public function before_filter(&$action, &$args)
    {

        // substitute dashes with underscores
        $action = strtr($action, "-", "_");

        $args = (array) $action;

        // invalid language?
        if (!isset($GLOBALS['INSTALLED_LANGUAGES'][$action])) {
            $action = "not_acceptable";
        }
        else {
            $action = "show";
        }
    }


    function show_action($language = NULL)
    {
        $this->set_content_type('application/javascript; charset=UTF-8');

        $expires = time() + 30 * 60 * 60 * 24;
        $this->response->add_header('Expires', gmdate(DATE_RFC1123, $expires));
        $this->response->add_header('Cache-Control', 'public');
        $this->response->add_header('Pragma', 'public');

        $this->language = $language;
        setLocaleEnv($language, "studip");
    }


    function not_acceptable_action($language = NULL)
    {
        $this->set_status(406);
        $this->set_content_type('application/json');
        $this->render_text(
            json_encode(array_keys($GLOBALS['INSTALLED_LANGUAGES'])));
    }
}
