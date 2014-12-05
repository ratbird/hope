<?php
/**
 * configuration.php - controller class for the configuration
 *
 * @author  Jan-Hendrik Willms <tleilax+stuip@gmail.com>
 * @author  Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author  Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license GPL2 or any later version
 * @package admin
 * @since   2.0
 */

//Imports
require_once 'app/controllers/authenticated_controller.php';
require_once 'app/models/configuration.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/user_visible.inc.php';

class Admin_ConfigurationController extends AuthenticatedController
{
    /**
     * Common before filter for all actions.
     *
     * @param String $action Called actions
     * @param Array  $args   Passed arguments
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // user must have root permission
        $GLOBALS['perm']->check('root');

        // set navigation
        Navigation::activateItem('/admin/config/configuration');

        $this->setupSidebar(strpos($action, 'user') !== false);
    }

    /**
     * Maintenance view for the configuration parameters
     *
     * @param mixed $section Open section
     */
    public function configuration_action($open_section = null)
    {
        PageLayout::setTitle(_('Verwaltung von Systemkonfigurationen'));

        // Display only one section?
        $section = Request::option('section');
        if ($section == '-1') {
            $section = null;
        }

        // Search for specific entries?
        $needle = trim(Request::get('needle')) ?: null;
        if ($needle) {
            $this->subtitle = _('Suchbegriff:') . ' "' . $needle . '"';
        }

        // set variables for view
        $this->only_section = $section;
        $this->open_section = $open_section ?: $section;
        $this->needle       = $needle;
        $this->sections     = ConfigurationModel::getConfig($section, $needle);

        $this->title     = _('Verwaltung von Systemkonfigurationen');
        $this->linkchunk = 'admin/configuration/edit_configuration?id=';
        $this->has_sections = true;

        if ($needle && empty($this->sections)) {
            $message = sprintf(_('Es wurden keine Ergebnisse zu dem Suchbegriff "%s" gefunden.'), $needle);
            PageLayout::postMessage(MessageBox::error($message));
            $this->redirect('admin/configuration/configuration');
        }
    }

    /**
     * Editview: Edit the configuration parameters: value, comment, section
     */
    public function edit_configuration_action()
    {
        PageLayout::setTitle(_('Konfigurationsparameter editieren'));

        $field = Request::get('id');
        $value = Request::get('value');

        if (Request::isPost()) {
            CSRFProtection::verifyUnsafeRequest();

            if ($this->validateInput($field, $value)) {
                $section = Request::get('section_new') ?: Request::get('section');
                $comment = Request::get('comment');

                Config::get()->store($field, compact(words('value section comment')));

                $message = sprintf(_('Der Konfigurationseintrag "%s" wurde erfolgreich übernommen!'), $field);
                PageLayout::postMessage(MessageBox::success($message));

                $this->redirect('admin/configuration/configuration/' . $section);
            }
        }

        // set variables for view
        $this->config     = ConfigurationModel::getConfigInfo($field);
        $this->allconfigs = ConfigurationModel::getConfig();
    }

    /**
     * Userview: Show all user-parameter for a user or show the system user-parameter
     *
     * @param mixed $give_all
     */
    public function user_configuration_action($give_all = null)
    {
        PageLayout::setTitle(_('Verwalten von Nutzerkonfigurationen'));

        $user_id = Request::option('user_id');
        if ($user_id) {
            $this->configs   = ConfigurationModel::searchUserConfiguration($user_id);
            $this->title     = sprintf(_('Vorhandene Konfigurationsparameter für den Nutzer "%s"'),
                                       User::find($user_id)->getFullname());
            $this->linkchunk = 'admin/configuration/edit_user_config/' . $user_id . '?id=';
        } else {
            $this->configs   = ConfigurationModel::searchUserConfiguration(null, true);
            $this->title     = _('Globale Konfigurationsparameter für alle Nutzer');
            $this->linkchunk = 'admin/configuration/edit_configuration/?id=';
        }
        $this->has_sections = false;
    }

    /**
     * Editview: Change user-parameter for one user (value)
     *
     * @param String $user_id
     */
    public function edit_user_config_action($user_id)
    {
        PageLayout::setTitle(_('Konfigurationsparameter editieren'));

        $field = Request::get('id');
        
        if (Request::isPost()) {
            CSRFProtection::verifyUnsafeRequest();
            
            $value = Request::get('value');
            if ($this->validateInput($field, $value)) {
                UserConfig::get($user_id)->store($field, $value);

                $message = sprintf(_('Der Konfigurationseintrag: %s wurde erfolgreich geändert!'), $field);
                PageLayout::postMessage(MessageBox::success($message));

                $this->redirect('admin/configuration/user_configuration?user_id=' . $user_id);
            }
        }
        
        $this->config  = ConfigurationModel::showUserConfiguration($user_id, $field);
        $this->user_id = $user_id;
        $this->field   = $field;
        $this->value   = $this->flash['value'] ?: null;
    }

    /**
     * Validates given input
     *
     * @param String $field Config field to validate
     * @param String $value Value that has been input
     * @return boolean indicating whether the value is valid
     */
    protected function validateInput($field, &$value)
    {
        $config = Config::get()->getMetadata($field);

        // Step 1: Prepare input
        if ($config['type'] === 'array') {
            $value = json_decode(studip_utf8encode($value), true);
        }

        // Step 2: Validate
        if (strlen($value) === 0) {
            $error = _('Es wurde kein gültiger Wert eingetragen!');
        } elseif ($config['type'] === 'integer' && !is_numeric($value)) {
            $error = _('Bitte geben Sie bei Parametern vom Typ "integer" nur Zahlen ein!');
        } elseif ($config['type'] === 'array' && !is_array($value)) {
            $error = _('Bitte geben Sie bei Parametern vom Typ "array" ein Array oder Objekt in korrekter JSON Notation ein!');
        } else {
            return true;
        }

        PageLayout::postMessage(MessageBox::error($error));

        return false;
    }

    /**
     * Sets up the sidebar
     *
     * @param bool $user_section Adjust sidebar to user section?
     */
    protected function setupSidebar($user_section = false)
    {
        // Basic info and layout
        $sidebar = Sidebar::Get();
        $sidebar->setTitle(_('Konfiguration'));
        $sidebar->setImage(Assets::image_path('sidebar/admin-sidebar.png'));

        // Views
        $views = new ViewsWidget();
        $views->addLink(_('Globale Konfiguration'),
                        $this->url_for('admin/configuration/configuration'))
              ->setActive(!$user_section);
        $views->addLink(_('Nutzerkonfiguration'),
                        $this->url_for('admin/configuration/user_configuration'))
              ->setActive($user_section);
        $sidebar->addWidget($views);

        // Add section selector when not in user mode
        if (!$user_section) {
            $options = array();
            foreach (ConfigurationModel::getConfig() as $key => $value) {
                $options[$key] = $key ?: '- ' . _('Ohne Kategorie') . ' -';
            }
            $widget = new SelectWidget(_('Anzeigefilter'),
                                       $this->url_for('admin/configuration/configuration'),
                                       'section', 'get');
            $widget->addElement(new SelectElement(-1, _('alle anzeigen')));
            $widget->setOptions($options);
            $sidebar->addWidget($widget);
        }

        // Add specific searches (specific user when in user mode, keyword
        // otherwise)
        if ($user_section) {
            $search = new SearchWidget($this->url_for('admin/configuration/user_configuration'));
            $search->addNeedle(_('Nutzer suchen'), 'user_id', true,
                               new StandardSearch('user_id'),
                               'function () { $(this).closest("form").submit(); }');
        } else {
            $search = new SearchWidget($this->url_for('admin/configuration/configuration'));
            $search->addNeedle(_('Suchbegriff'), 'needle', true);
        }
        $sidebar->addWidget($search);
    }
}
