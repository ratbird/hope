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
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->modules = array();

        // Set Navigation
        PageLayout::setHelpKeyword("Basis.ProfileModules");
        PageLayout::setTitle(_("Inhaltselemente konfigurieren"));
        Navigation::activateItem('/profile/modules');

        // Get current user.
        $this->username = Request::username('username', $GLOBALS['user']->username);
        $this->user_id  = get_userid($this->username);

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
                               htmlReady($current_user->username),
                               htmlReady($current_user->perms));
            PageLayout::postMessage(MessageBox::info($message));
        }

        $this->setupSidebar();
    }

    /**
     * Creates the sidebar.
     */
    private function setupSidebar()
    {
        $sidebar = Sidebar::get();
        $sidebar->setImage('sidebar/plugin-sidebar.png');
        $sidebar->setTitle(PageLayout::getTitle());

        $widget = new ActionsWidget();
        $widget->addLink(_('Alle Inhaltselemente aktivieren'),
                         $this->url_for('profilemodules/reset/true'),
                         'icons/16/blue/accept.png');
        $widget->addLink(_('Alle Inhaltselemente deaktivieren'),
                         $this->url_for('profilemodules/reset'),
                         'icons/16/blue/decline.png');
        $sidebar->addWidget($widget);
    }

    /**
     * Generates an overview of installed plugins and provides the possibility
     * to (de-)activate each of them.
     */
    public function index_action()
    {
        $manager = PluginManager::getInstance();
        
        // Now loop through all found plugins.
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $id = $plugin->getPluginId();
            $activated = $manager->isPluginActivatedForUser($id, $this->user_id);
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
    public function update_action()
    {
        CSRFProtection::verifyUnsafeRequest();

        $manager = PluginManager::getInstance();
        $modules = Request::optionArray('modules');

        $success = null;
        // Plugins
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $id = $plugin->getPluginId();

            $state_before = $manager->isPluginActivatedForUser($id, $this->user_id);
            $state_after  = in_array($id, $modules);

            if ($state_before !== $state_after) {
                $updated = $manager->setPluginActivated($id, $this->user_id, $state_after, 'user');

                $success = $success || $updated;
            }
        }

        if ($success === true) {
            $message = MessageBox::success(_('Ihre Änderungen wurden gespeichert.'));
        } elseif ($success === false) {
            $message = MessageBox::error(_('Ihre Änderungen konnten nicht gespeichert werden.'));
        }
        if ($message) {
            PageLayout::postMessage($message);
        }

        $this->redirect($this->url_for('profilemodules/index', array('username' => $this->username)));
    }

    /**
     * Resets/deactivates all profile modules.
     */
    public function reset_action($state = false)
    {
        $manager = PluginManager::getInstance();
        foreach ($this->plugins as $plugin) {
            // Check local activation status.
            $id = $plugin->getPluginId();

            $manager->setPluginActivated($plugin->getPluginId(), $this->user_id, $state, 'user');
        }

        PageLayout::postMessage(MessageBox::success(_('Ihre Änderungen wurden gespeichert.')));
        $this->redirect($this->url_for('profilemodules/index', array('username' => $this->username)));
    }
}
