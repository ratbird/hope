<?php
# Lifter007: TODO
/*
 * StudIPPlugin.class.php - generic plugin base class
 *
 * Copyright (c) 2009 - Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

abstract class StudIPPlugin {

    /**
     * plugin meta data
     */
    protected $plugin_info;

    /**
     * plugin constructor
     * TODO bindtextdomain()
     */
    public function __construct() {
        $plugin_manager = PluginManager::getInstance();
        $this->plugin_info = $plugin_manager->getPluginInfo(get_class($this));
    }

    /**
     * Return the ID of this plugin.
     */
    public function getPluginId() {
        return $this->plugin_info['id'];
    }

    /**
     * Return the name of this plugin.
     */
    public function getPluginName() {
        return $this->plugin_info['name'];
    }

    /**
     * Return the filesystem path to this plugin.
     */
    public function getPluginPath() {
        return 'plugins_packages/' . $this->plugin_info['path'];
    }

    /**
     * Return the URL of this plugin. Can be used to refer to resources
     * (images, style sheets, etc.) inside the installed plugin package.
     */
    public function getPluginURL() {
        return $GLOBALS['ABSOLUTE_URI_STUDIP'] . $this->getPluginPath();
    }

    /**
     * Get the activation status of this plugin in the given context.
     * This also checks the plugin default activations.
     *
     * @param $context   context range id
     */
    public function isActivated($context) {
        $plugin_id = $this->getPluginId();
        $plugin_manager = PluginManager::getInstance();

        return $plugin_manager->isPluginActivated($plugin_id, $context);
    }

    /**
     * Return a warning message to be printed before deactivation of
     * this plugin in the given context.
     *
     * @param $context   context range id
     */
    public function deactivationWarning($context) {
        return NULL;
    }

    /**
     * This method dispatches all actions.
     *
     * @param string   part of the dispatch path that was not consumed
     *
     * @return void
     */
    public function perform($unconsumed_path) {
        $args = explode('/', $unconsumed_path);
        $action = $args[0] !== '' ? array_shift($args).'_action' : 'show_action';

        if (!method_exists($this, $action)) {
            throw new Exception(_('unbekannte Plugin-Aktion: ') . $action);
        }

        call_user_func_array(array($this, $action), $args);
    }
}
