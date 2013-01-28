<?
/*
 *  Copyright (c) 2010 André Noack <noack@data-quest.de>
 *  Copyright (c) 2012 Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

/**
 * Special controller for trailsplugins
 */
class ApplicationController extends Trails_Controller{

    /**
     * Constructor, sets $this->plugin as plugin-object
     * @param TrailsDispatcher $dispatcher
     */
    function __construct($dispatcher) {
        parent::__construct($dispatcher);
        $this->plugin = $this->dispatcher->current_plugin;
    }

    /**
     * Sets layout, adds css
     * @param type $action
     * @param type $args
     */
    function before_filter($action, $args) {
        $this->current_action = $action;
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));

        $this->assets_url = $this->plugin->getPluginUrl(). '/assets/';
        PageLayout::addHeadElement("link",
            array(
                "href" => $this->assets_url.'stylesheets/blubberforum.css',
                "rel" => "stylesheet"
            ),
        "");
    }

    /**
     * Throws an exception on error
     * @param Exception $exception
     * @throws Exception
     */
    function rescue($exception) {
        throw $exception;
    }

    /**
     * closes the page
     * @param type $action
     * @param type $args
     */
    function after_filter($action, $args) {
        page_close();
    }

    /**
     * New render-method to render an array into json.
     * @param array $data
     * @return void
     */
    function render_json($data) {
        $this->set_content_type('application/json;charset=utf-8');
        return $this->render_text(json_encode($data));
    }
}
