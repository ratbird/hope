<?php

class ApplicationController extends Trails_Controller{

    function __construct($dispatcher) {
        parent::__construct($dispatcher);
        $this->plugin = $this->dispatcher->current_plugin;
    }

    function before_filter($action, $args) {

        $this->current_action = $action;
        $this->flash = Trails_Flash::instance();
        $this->standard_templates = $GLOBALS['STUDIP_BASE_PATH'] . '/templates/';
        $this->standard_trails_root = $GLOBALS['STUDIP_BASE_PATH'] . '/app/';
        $this->set_layout('layout.php');
        PageLayout::setTitle($this->plugin->getDisplayTitle());

        $this->url = $this->plugin->getPluginUrl(). '/public/';
        PageLayout::addHeadElement('link', array("rel"  => "stylesheet",
                                           "href" => $this->url . "stylesheets/application.css",
                                           "type" => "text/css"
                                           ));
        PageLayout::addHeadElement('script', array("src"  => $this->url . "javascripts/application.js",
                                             "type" => "text/javascript"
                                           ));
    }

    function rescue($exception){
        throw $exception;
    }

    function after_filter($action, $args) {
        page_close();
    }

    function url_for($to = '', $params = array()) {
        if($to === '') {
            $to = substr(strtolower(get_class($this)), 0, -10) . '/' . $this->current_action;
        }
        $url = PluginEngine::getUrl($this->plugin, $params, $to);
        return $url;
    }

    function link_for($to = '', $params = array()) {
        if($to === '') {
            $to = substr(strtolower(get_class($this)), 0, -10) . '/' . $this->current_action;
        }
        return PluginEngine::getLink($this->plugin, $params, $to);
    }

    function flash_set($type, $message, $submessage = array()){
        $old = (array)$this->flash->get('msg');
        $new = array_merge($old, array(array($type, $message, (array)$submessage)));
        $this->flash->set('msg', $new);
    }

    function flash_now($type, $message, $submessage = array()){
        $old = (array)$this->flash->get('msg');
        $new = array_merge($old, array(array($type, $message, (array)$submessage)));
        $this->flash->set('msg', $new);
        $this->flash->discard('msg');
    }

    function render_json($data){
        $this->set_content_type('application/json;charset=utf-8');
        return $this->render_text(json_encode($data));
    }
}
