<?php
# Lifter010: TODO
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

        $modified = filemtime(dirname(__FILE__) . '/../views/localizations/show.php');
        $this->response->add_header('Last-Modified', date("r", $modified));

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $modified) {
                $this->set_status(304, "Not modified.");
                $this->render_nothing();
                return;
            }
        }

        $this->language = $language;
        setLocaleEnv($language, "studip");

        // make this instance available to the view to use
        // the helper methods
        $this->plugin = $this;
    }


    function not_acceptable_action($language = NULL)
    {
        $this->set_status(406);
        $this->set_content_type('application/json; charset=UTF-8');
        $languages = array_keys($GLOBALS['INSTALLED_LANGUAGES']);
        $this->render_text(json_encode($this->utf8EncodeArray($languages)));
    }


    /**
     * Return an UTF-8 encoded (one dimensional!) array
     *
     * @param array   the original array w/ latin-1 encoded keys and values
     * @return array  an array w/ utf-8 encoded keys and values
     */
    function utf8EncodeArray(array $src)
    {
        $new = array();
        foreach ($src as $k => $v) {
            $new[is_int($k) ? $k : utf8_encode($k)] = utf8_encode($v);
        }
        return $new;
    }
}
