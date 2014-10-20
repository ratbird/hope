<?php
# Lifter010: TODO
/**
 * plugin.php - plugin administration controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/plugin_administration.php';

class Admin_PluginController extends AuthenticatedController
{
    private $plugin_admin;

    /**
     * Common tasks for all actions.
     */
    public function before_filter(&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        // user must have root permission
        $perm->check('root');

        // set page title and navigation
        PageLayout::setTitle(_('Verwaltung von Plugins'));
        Navigation::activateItem('/admin/config/plugins');

        $this->plugin_admin = new PluginAdministration();
    }

    /**
     * Validate ticket (passed via request environment).
     * This method always checks Request::quoted('ticket').
     *
     * @throws InvalidArgumentException  if ticket is not valid
     */
    private function check_ticket()
    {
        if (!check_ticket(Request::option('studip_ticket'))) {
            throw new InvalidArgumentException(_('Das Ticket für diese Aktion ist ungültig.'));
        }

    }

    /**
     * Try to get update information for a list of plugins. If no
     * update information is available, an error message is set in
     * this controller and an empty array is returned.
     *
     * @param array     array of plugin meta data
     */
    private function get_update_info($plugins)
    {
        try {
            return $this->plugin_admin->getUpdateInfo($plugins);
        } catch (Exception $ex) {
            $this->error = _('Informationen über Plugin-Updates sind nicht verfügbar.');
            $this->error_detail = array($ex->getMessage());

            // Read current information from local files
            $update_info = array();
            $plugin_manager = PluginManager::getInstance();
            foreach ($plugins as $plugin) {
                $plugin_path = get_config('PLUGINS_PATH') . '/' . $plugin['path'];
                $manifest    = $plugin_manager->getPluginManifest($plugin_path);
                $update_info[$plugin['id']] = array('version' => $manifest['version']);
            }
            return $update_info;
        }
    }

    /**
     * Display the list of installed plugins and show all available
     * updates (if any).
     */
    public function index_action()
    {
        $plugin_manager = PluginManager::getInstance();
        $plugin_filter = Request::option('plugin_filter', '');
        $type = $plugin_filter != '' ? $plugin_filter : NULL;

        $this->plugins       = $plugin_manager->getPluginInfos($type);
        $this->plugin_types  = $this->plugin_admin->getPluginTypes();
        $this->update_info   = $this->get_update_info($this->plugins);
        $this->plugin_filter = $plugin_filter;
        $this->migrations    = $this->plugin_admin->getMigrationInfo();

        foreach ($this->update_info as $id => $info) {
            if (isset($info['update']) && !$this->plugins[$id]['depends']) {
                ++$this->num_updates;
            }
        }
    }

    /**
     * Save the modified plugin configuration (status and position).
     */
    public function save_action()
    {
        $plugin_manager = PluginManager::getInstance();
        $plugin_filter = Request::option('plugin_filter', '');
        $type = $plugin_filter != '' ? $plugin_filter : NULL;
        $plugins = $plugin_manager->getPluginInfos($type);

        $this->check_ticket();

        // update enabled/disabled status and position if set
        foreach ($plugins as $plugin){
            $enabled = Request::int('enabled_' . $plugin['id'], 0);
            $navpos = Request::int('position_' . $plugin['id']);

            $plugin_manager->setPluginEnabled($plugin['id'], $enabled);

            if (isset($navpos)) {
                $plugin_manager->setPluginPosition($plugin['id'], max($navpos, 1));
            }
        }

        $this->flash['message'] = _('Die Änderungen wurden gespeichert.');
        $this->redirect('admin/plugin?plugin_filter=' . $plugin_filter);
    }

    /**
     * Compare two plugins by their score (used for sorting).
     */
    private function compare_score($plugin1, $plugin2)
    {
        return $plugin2['score'] - $plugin1['score'];
    }

    /**
     * Search the list of available plugins or display the most
     * recommended plugins if the user did not trigger a search.
     */
    public function search_action()
    {
        $search = Request::get('search');

        // search for plugins in all repositories
        try {
            $repository = new PluginRepository();
            $search_results = $repository->getPlugins($search);
        } catch (Exception $ex) {
            $search_results = array();
        }

        $plugins = PluginManager::getInstance()->getPluginInfos();

        // filter out already installed plugins
        foreach ($plugins as $plugin) {
            if (isset($search_results[$plugin['name']])) {
                unset($search_results[$plugin['name']]);
            }
        }

        if ($search === NULL) {
            // sort plugins by score
            uasort($search_results, array($this, 'compare_score'));
            $search_results = array_slice($search_results, 0, 6);
        } else {
            // sort plugins by name
            uksort($search_results, 'strnatcasecmp');
        }

        $this->search         = $search;
        $this->search_results = $search_results;
        $this->plugins        = $plugins;
        $this->unknown_plugins = $this->plugin_admin->scanPluginDirectory();
    }

    /**
     * Install a given plugin, either by URL (from the repository)
     * or using a file uploaded by the administrator.
     */
    public function install_action()
    {
        $plugin_url = Request::get('plugin_url');

        $this->check_ticket();

        try {
            if (isset($plugin_url)) {
                $this->plugin_admin->installPluginFromURL($plugin_url);
            } else if (get_config('PLUGINS_UPLOAD_ENABLE')) {
                // process the upload and register plugin in the database
                $upload_file = $_FILES['upload_file']['tmp_name'];
                $this->plugin_admin->installPlugin($upload_file);
            }

            $this->flash['message'] = _('Das Plugin wurde erfolgreich installiert.');
        } catch (PluginInstallationException $ex) {
            $this->flash['error'] = $ex->getMessage();
        }

        if (isset($upload_file)) {
            unlink($upload_file);
        }

        $this->redirect('admin/plugin');
    }

    /**
     * Ask for confirmation from the user before deleting a plugin.
     *
     * @param integer   id of plugin to delete
     */
    public function ask_delete_action($plugin_id)
    {
        $plugin_manager = PluginManager::getInstance();

        $plugin = $plugin_manager->getPluginInfoById($plugin_id);

        $this->plugins       = $plugin_manager->getPluginInfos();
        $this->plugin_types  = $this->plugin_admin->getPluginTypes();
        $this->update_info   = $this->get_update_info($this->plugins);

        if (!$plugin['core']) {
            $this->delete_plugin = $this->plugins[$plugin_id];
        }

        $this->render_action('index');
    }

    /**
     * Completely delete a plugin from the system.
     *
     * @param integer   id of plugin to delete
     */
    public function delete_action($plugin_id)
    {
        $plugin_manager = PluginManager::getInstance();
        $plugin_filter = Request::option('plugin_filter', '');
        $plugin = $plugin_manager->getPluginInfoById($plugin_id);

        $this->check_ticket();

        if (isset($plugin) && !$plugin['core']) {
            $this->plugin_admin->uninstallPlugin($plugin);
        }

        $this->redirect('admin/plugin?plugin_filter=' . $plugin_filter);
    }

    /**
     * Download a ZIP file containing the given plugin.
     *
     * @param integer   id of plugin to download
     */
    public function download_action($plugin_id)
    {
        $plugin_manager = PluginManager::getInstance();
        $plugin = $plugin_manager->getPluginInfoById($plugin_id);

        // prepare file name for download
        $pluginpath = get_config('PLUGINS_PATH') . '/' . $plugin['path'];
        $manifest = $plugin_manager->getPluginManifest($pluginpath);
        $filename = $plugin['class'] . '-' . $manifest['version'] . '.zip';
        $filepath = get_config('TMP_PATH') . '/' . $filename;

        create_zip_from_directory($pluginpath, $filepath);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Pragma: public');

        $this->render_nothing();

        readfile($filepath);
        unlink($filepath);
    }

    /**
     * Install updates for all selected plugins.
     */
    public function install_updates_action()
    {
        $plugins = PluginManager::getInstance()->getPluginInfos();
        $plugin_filter = Request::option('plugin_filter', '');
        $update_info = $this->plugin_admin->getUpdateInfo($plugins);

        $update = Request::intArray('update');
        $update_status = array();

        $this->check_ticket();

        // update each plugin in turn
        foreach ($update as $id) {
            if (isset($update_info[$id]['update'])) {
                try {
                    $update_url = $update_info[$id]['update']['url'];
                    $this->plugin_admin->installPluginFromURL($update_url);
                } catch (PluginInstallationException $ex) {
                    $update_errors[] = sprintf('%s: %s', $plugins[$id]['name'], $ex->getMessage());
                }
            }
        }

        // collect and report errors
        if (isset($update_errors)) {
            $this->flash['error'] = ngettext(
                'Beim Update ist ein Fehler aufgetreten:',
                'Beim Update sind Fehler aufgetreten:', count($update_errors));
            $this->flash['error_detail'] = $update_errors;
        } else {
            $this->flash['message'] = _('Update erfolgreich installiert.');
        }

        $this->redirect('admin/plugin?plugin_filter=' . $plugin_filter);
    }

    /**
     * Show a page describing this plugin's meta data and description,
     * if available.
     */
    public function manifest_action($plugin_id) {
        $plugin_manager = PluginManager::getInstance();
        $plugin = $plugin_manager->getPluginInfoById($plugin_id);

        // retrieve manifest
        $pluginpath = get_config('PLUGINS_PATH') . '/' . $plugin['path'];
        $manifest = $plugin_manager->getPluginManifest($pluginpath);

        $this->plugin   = $plugin;
        $this->manifest = $manifest;
    }

    /**
     * Display the default activation set for this plugin.
     */
    public function default_activation_action($plugin_id) {
        $plugin_manager = PluginManager::getInstance();
        $plugin = $plugin_manager->getPluginInfoById($plugin_id);
        $selected_inst = $plugin_manager->getDefaultActivations($plugin_id);

        $this->plugin_name   = $plugin['name'];
        $this->plugin_id     = $plugin_id;
        $this->selected_inst = $selected_inst;
        $this->institutes    = $this->plugin_admin->getInstitutes();
    }

    /**
     * Change the default activation for this plugin.
     */
    public function save_default_activation_action($plugin_id) {
        $plugin_manager = PluginManager::getInstance();
        $selected_inst = Request::optionArray('selected_inst');

        $this->check_ticket();

        // save selected institutes (if any)
        $plugin_manager->setDefaultActivations($plugin_id, $selected_inst);

        if (count($selected_inst) == 0) {
            $this->flash['message'] = _('Die Default-Aktivierung wurde ausgeschaltet.');
        } else {
            $this->flash['message'] = ngettext(
                'Für die ausgewählte Einrichtung wurde das Plugin standardmäßig aktiviert.',
                'Für die ausgewählten Einrichtungen wurde das Plugin standardmäßig aktiviert.',
                count($selected_inst));
        }

        $this->redirect('admin/plugin/default_activation/' . $plugin_id);
    }

    /**
     * migrate a plugin to top version
     *
     * @param integer   id of plugin to migrate
     */
    public function migrate_action($plugin_id)
    {
        $plugin_manager = PluginManager::getInstance();
        $plugin_filter = Request::option('plugin_filter', '');
        $plugin = $plugin_manager->getPluginInfoById($plugin_id);
        $log = $this->plugin_admin->migratePlugin($plugin_id);
        if ($log) {
            PageLayout::postMessage(MessageBox::success(_('Die Migration wurde durchgeführt.'), array_map('htmlReady', explode("\n", trim($log)))));
        } else {
            PageLayout::postMessage(MessageBox::error(_('Die Migration konnte nicht durchgeführt werden.')));
        }
        $this->redirect('admin/plugin?plugin_filter=' . $plugin_filter);
    }

    /**
     * register a plugin in database when it
     * already exists in file system
     *
     * @param integer   number of found plugin
     */
    public function register_action($number)
    {
        CSRFProtection::verifyUnsafeRequest();
        $unknown_plugins = $this->plugin_admin->scanPluginDirectory();
        $plugin = $unknown_plugins[$number];

        try {
            $this->plugin_admin->registerPlugin($plugin['path']);
            $this->flash['message'] = _('Das Plugin wurde erfolgreich installiert.');
        } catch (PluginInstallationException $ex) {
            $this->flash['error'] = $ex->getMessage();
        }
        $this->redirect('admin/plugin');
    }

    public function edit_automaticupdate_action($plugin_id)
    {
        $this->plugin = PluginManager::getInstance()->getPluginInfoById($plugin_id);
        if (Request::isPost()) {
            CSRFProtection::verifyUnsafeRequest();
            $this->check_ticket();
            $token = $this->plugin['automatic_update_secret'] ?: md5(uniqid());
            $statement = DBManager::get()->prepare("
                UPDATE plugins
                SET automatic_update_url = :url,
                    automatic_update_secret = :secret
                WHERE pluginid = :id
            ");
            $statement->execute(array(
                'id' => $plugin_id,
                'url' => Request::get("automatic_update_url"),
                'secret' => Request::get("use_security_token") ? $token : null
            ));
            PageLayout::postMessage(MessageBox::success(_("Daten gespeichert.")));
            if (Request::get("use_security_token")) {
                PageLayout::postMessage(MessageBox::info(_("Unten können Sie den Security Token jetzt heraus kopieren.")));
            }
            $this->redirect("admin/plugin/edit_automaticupdate/".$plugin_id);
        }
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->response->add_header('X-Title', sprintf(_("Automatisches Update für %s"), $this->plugin['name']));
            $this->set_content_type('text/html;charset=windows-1252');
        }
    }

}
