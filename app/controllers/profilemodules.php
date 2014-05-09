<?php

/**
 * ProfileModulesController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @author      Florian Bieringer
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */


require_once 'app/controllers/authenticated_controller.php';

/**
 * Controller for the (de-)activation of homepage plugins for every user.
 */
class ProfileModulesController extends AuthenticatedController {

    var $user_id = '';
    var $modules = array();
    var $plugins = array();

    /**
     * This function is called before any output is generated or any other
     * actions are performed. Initializations happen here.
     *
     * @param $action Name of the action to perform
     * @param $args   Arguments for the given action
     */
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
        if ($blubber) {
            $this->plugins[] = $blubber;
        }
        // Get homepage plugins from database.
        $this->plugins = array_merge($this->plugins, PluginEngine::getPlugins('HomepagePlugin'));
        // Show info message if user is not on his own profile
        if ($this->user_id != $GLOBALS['user']->id) {
            $current_user = User::find($this->user_id);
            $message = sprintf(_('Daten von: %s %s (%s), Status: %s'),
                    htmlReady($current_user->Vorname),
                    htmlReady($current_user->Nachname),
                    $current_user->username,
                    $current_user->perms);
            PageLayout::postMessage(MessageBox::info($message));
        }
    }

    /**
     * Generates an overview of installed plugins and provides the possibility
     * to (de-)activate each of them.
     */
    function index_action() {
        // Now loop through all found plugins.
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $id = $plugin->getPluginId();
            $activated = PluginManager::getInstance()->isPluginActivatedForUser($id, $this->user_id);
            // Load plugin data (e.g. name and description)
            $metadata = $plugin->getMetadata();
            $this->modules[$id] = array(
                    'id' => $id,
                    'name' => $plugin->getPluginName(),
                    'description' => $metadata['description'],
                    'homepage' => $metadata['homepage'],
                    'activated' => $activated
                );
        }
    }

    /**
     * Updates the activation status of user's homepage plugins.
     */
    function update_action() {

        CSRFProtection::verifyUnsafeRequest();
        PageLayout::clearMessages();

        $success = '';
        // Plugins
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $id = $plugin->getPluginId();
            $activated = PluginManager::getInstance()->isPluginActivatedForUser($id, $this->user_id);
            if ((!$activated && Request::get('module_'.$id)) || ($activated && !Request::get('module_'.$id))) {
                $updated = PluginManager::getInstance()->setPluginActivated($id, $this->user_id, Request::get('module_'.$id), 'user');
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
