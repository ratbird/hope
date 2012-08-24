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
    var $guestbook = null;
    var $plugins = array();

    // Global initializations and actions.
    function before_filter(&$action, &$args) {
        global $perm, $user;

        parent::before_filter($action, $args);

        $this->user_id = $user->id;
        if ($perm->have_perm('root') && Request::option('username')) {
            $this->user_id = get_userid(Request::option('username'));
        }

        // Set Navigation
        PageLayout::setHelpKeyword("Basis.ProfileModules");
        PageLayout::setTitle(_("Inhaltselemente konfigurieren"));
        Navigation::activateItem('/profile/modules');

        // Personal guestbook.
        $this->guestbook = new Guestbook($this->user_id, false);
        // Guestbook status.
        $this->modules['guestbook']['name'] = _('Gästebuch');
        $this->modules['guestbook']['description'] = _('Im Gästebuch können andere Nutzerinnen und Nutzer Ihnen Beiträge hinterlassen.');
        // Set checked status if necessary.
        $this->modules['guestbook']['activated'] = $this->guestbook->active ? true : false;

        // Get homepage plugins from database.
        $this->plugins = PluginEngine::getPlugins('HomepagePlugin');
        // Now loop through all found plugins.
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $activated = PluginManager::isPluginActivatedForUser($plugin->pluginid, $this->user_id);
            $this->modules[$plugin->pluginid]['name'] = $plugin->pluginname;
            $this->modules[$plugin->pluginid]['description'] = $plugin->description;
            // Set checked status if necessary.
            $this->modules[$plugin->pluginid]['activated'] = $activated;
        }
    }

    function index_action() {
    }

    // Update activation status.
    function update_action() {
        $success = '';

        // Guestbook
        if (($this->modules['guestbook']['activated'] && !Request::get('module_guestbook')) 
            || (!$this->modules['guestbook']['activated'] && Request::get('module_guestbook'))) {
            $this->guestbook = new Guestbook($this->user_id, false);
            $this->guestbook->switchGuestbook();
            $this->modules['guestbook']['activated'] = !$this->modules['guestbook']['activated'];
        }
        // Plugins
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $activated = PluginManager::isPluginActivatedForUser($plugin->pluginid, $this->user_id);

            if ((!$activated && Request::get('module_'.$plugin->pluginid)) || ($activated && !Request::get('module_'.$plugin->pluginid))) {
                $updated = PluginManager::setPluginActivated($plugin->pluginid, $this->user_id, Request::get('module_'.$plugin->pluginid), 'user');
                $success = ($success === '' ? true : $success) && $updated;
                if ($updated) {
                    $this->modules[$plugin->pluginid]['activated'] = Request::get('module_'.$plugin->pluginid);
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
        $this->redirect(URLHelper::getUrl('dispatch.php/profilemodules', array('username' => get_username($this->user_id))));
    }

}
?>
