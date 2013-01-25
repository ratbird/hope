<?php

/*
 * homepageplugins.php - 
 *
 * Copyright (C) 2011 - Florian Bieringer, Thomas Hackl <thomas.hackl@uni-passau.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';

class ProfileModulesController extends AuthenticatedController {

    var $user_id = '';
    var $modules = array();
    var $plugins = array();

    // Global initializations and actions.
    function before_filter(&$action, &$args) {
        global $user;

        parent::before_filter($action, $args);

        $this->modules = array();

        // Set Navigation
        PageLayout::setHelpKeyword("Basis.ProfileModules");
        PageLayout::setTitle(_("Inhaltselemente konfigurieren"));
        Navigation::activateItem('/profile/modules');

        // Get current user.
        $this->user_id = get_userid(Request::username('username', $user->username));
        $this->username = Request::username('username', $user->username);

        $this->plugins = array();
        $blubber = PluginEngine::getPlugin('Blubber');
        // Add blubber to plugin list so status can be updated.
        $this->plugins[] = $blubber;
        // Get homepage plugins from database.
        $this->plugins = array_merge($this->plugins, PluginEngine::getPlugins('HomepagePlugin'));
    }

    function index_action() {
        // Now loop through all found plugins.
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $id = $plugin->getPluginId();
            $activated = PluginManager::isPluginActivatedForUser($id, $this->user_id);
            $this->modules[$id] = array(
                    'id' => $id,
                    'name' => $plugin->getPluginName(),
                    'description' => $plugin->description,
                    'activated' => $activated
                );
        }
    }

    // Update activation status.
    function update_action() {
        $success = '';

        // Plugins
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $id = $plugin->getPluginId();
            $activated = PluginManager::isPluginActivatedForUser($id, $this->user_id);
            if ((!$activated && Request::get('module_'.$id)) || ($activated && !Request::get('module_'.$id))) {
                $updated = PluginManager::setPluginActivated($id, $this->user_id, Request::get('module_'.$id), 'user');
                $success = ($success === '' ? true : $success) && $updated;
                if ($updated) {
                    $this->modules[$id]['activated'] = Request::get('module_'.$id);
                }
            }
        }

        if ($success !== '') {
            if ($success) {
                $this->flash['message'] = _("Ihre Änderungen wurden gespeichert.");
            } else {
                $this->flash['error'] = _("Ihre Änderungen konnten nicht gespeichert werden.");
            }
        }
        $this->redirect(URLHelper::getURL('dispatch.php/profilemodules/index', array('username' => $this->username)));
    }

}
?>
