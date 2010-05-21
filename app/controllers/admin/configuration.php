<?php
/**
 * configuration.php - controller class for the configuration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 * @since       Stud.IP version 1.12
 */

//Imports
require_once 'lib/trails/AuthenticatedController.php';
require_once 'app/models/configuration.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/user_visible.inc.php';

// classes required for global-module-settings
require_once('lib/classes/AdminModules.class.php');
require_once('lib/classes/Config.class.php');

class Admin_ConfigurationController extends AuthenticatedController
{
    /**
     * Common tasks for all actions.
     */
    public function before_filter(&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        // user must have root permission
        $perm->check('root');

        // set navigation
        Navigation::activateItem('/admin/config/configuration');
    }

    /**
     * Maintenance view for the configuration parameters
     *
     * @param   string $section
     */
    function configuration_action($section = null)
    {
        $config_filter = Request::option('config_filter', null);
        if ($config_filter == '-1') {
            $config_filter = null;
        }
        $this->config_filter = $config_filter;
        // set variables for view
        $GLOBALS['CURRENT_PAGE'] = _('Verwaltung von Systemkonfigurationen');

        $this->allconfigs = ConfigurationModel::getConfig();
        $this->current_section = $section;
    }

    /**
     * Searchview: filter = field
     */
    function results_configuration_action()
    {
        if (Request::submitted('search_config')) {
            if (Request::get('')) {
                $this->search_filter = ConfigurationModel::searchConfig(Request::get('search_config'));
            } else {
                $this->flash['error'] = _("Bitte geben Sie einen Suchparameter ein.");
                $this->redirect('admin/configuration/configuration');
            }
        }
        $GLOBALS['CURRENT_PAGE'] = _('Verwaltung von Systemkonfigurationen');
    }

    /**
     * Editview: Edit the configuration parameters: value, comment, section
     *
     * @param   md5 $config_id
     */
    function edit_configuration_action($config_id)
    {
        if (Request::submitted('uebernehmen')) {
            if (Request::get('value') || Request::get('value')== 0) {
                $conf_value = Request::get('value');
                $conf_sec = Request::get('section');
                $conf_sec_new = Request::get('section_new');
                $conf_comment = Request::get('comment');

                if (!empty($conf_sec_new)) {
                    $conf_sec = $conf_sec_new;
                }

                ConfigurationModel::saveEditConfiguration($config_id, $conf_value,$conf_sec, $conf_comment);
                $this->flash['success'] = sprintf(_("Der Konfigurationseintrag %s wurde erfolgreich übernommen!"), Request::get('field'));
                $this->redirect('admin/configuration/configuration/'.$conf_sec);
            } else {
                $this->flash['error'] = _("Im value-Feld wurde nichts eingetragen!");
            }
        }

        // set variables for view
        $this->edit = ConfigurationModel::getConfigInfo($config_id);
        $this->allconfigs = ConfigurationModel::getConfig();
        $GLOBALS['CURRENT_PAGE'] = _("Konfigurationsparameter editieren");
        $this->infobox = $this-> getInfobox();

        //ajax
        if (@$_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest') {
            $this->via_ajax = true;
            $this->set_layout(null);
        }
    }

    /**
     * Userview: Show all user-parameter for a user or show the system user-parameter
     *
     * @param   string $give_all
     */
    function user_configuration_action($give_all = NULL)
    {
        if (Request::submitted('user_id')) {
            $this->user_id = Request::get('user_id');
            if ($this->user_id) {
                $this->search_users = ConfigurationModel::searchUserConfiguration($this->user_id);
            } else {
                $this->flash['error'] = _("Es liegen keine Informationen vor!");
            }
        }

        if ($give_all == 'giveAll' || Request::submitted('user_id') != true) {
            $this->give_alls = ConfigurationModel::searchUserConfiguration($this->user_id, true);
        }

        if ($give_all == 'update') {
            ConfigurationModel::updateUserConfiguration(Request::get('user_id'), Request::get('value'), Request::get('field'));
            $this->flash['success'] = sprintf(_("Der Konfigurationseintrag: %s wurde erfolgreich geändert!"), Request::get('field'));
        }

        $GLOBALS['CURRENT_PAGE'] = _("Verwalten von Nutzerkonfigurationen");
    }

    /**
     * Editview: Change user-parameter for one user (value)
     *
     * @param   md5 $user_id
     * @param   md5 $field
     */
    function edit_user_config_action($user_id, $field)
    {
        if ($field && $user_id) {
            $this->search_user = ConfigurationModel::showUserConfiguration($user_id, $field);
        } else {
            false;
        }

        $GLOBALS['CURRENT_PAGE'] = _("Konfigurationsparameter editieren");
        $this->infobox = $this->getInfobox();

        //ajax
        if (@$_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest') {
            $this->via_ajax = true;
            $this->set_layout(null);
        }
    }


    /**
     * Create the messagebox
     */
    private function getInfobox()
    {
        $infobox = array('picture' => 'infoboxes/config.jpg');
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/configuration/configuration').'">'._('Konfiguration').'</a>',
            "icon" => "icon-cont.gif"
        );
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/configuration/user_configuration').'">'._('Nutzerparameter abrufen').'</a>',
            "icon" => "icon-cont.gif"
        );

        $infobox['content'] = array(
            array(
                'kategorie' => _("Aktionen"),
                'eintrag'   => $aktionen
                ),
            array(
                'kategorie' => _("Hinweise"),
                'eintrag'   => array(
                    array(
                        "text" => _("Sie können hier Parameter der Systemkonfiguration direkt verändern. Sie können sowohl auf System- als auch Nutzervariablen zugreifen."),
                        "icon" => "ausruf_small2.gif"
                        )
                    )
                )
            );
        return $infobox;
    }
}