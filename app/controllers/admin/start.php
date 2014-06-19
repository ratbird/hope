<?
/**
 * start.php - controller class for the start page administration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Klaßen <andre.klassen@elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 * @since       3.1
 */
require_once 'app/controllers/authenticated_controller.php';


class Admin_StartController extends AuthenticatedController
{
    function before_filter (&$action, &$args)
    {
        parent::before_filter($action, $args);

        global $perm, $template_factory;
        $perm->check('root');



        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
        }


        PageLayout::setTitle(_('Konfiguration der Startseite'));
        Navigation::activateItem('/admin/config/start');
    }


    /**
     * index_action show start page administration
     *
     * @param string context
     *
     * @return void
     */

    function index_action() {


        $this->all_widgets = PluginEngine::getPlugins('PortalPlugin');
        $this->perms = array_keys($GLOBALS['perm']->permissions);
        $this->selected_perm = Request::get('selected_perm', 'autor');

        $this->choices = $this->all_widgets;
        $this->widgets = WidgetHelper::getInitialPositions($this->selected_perm);

        $this->left = array();
        $this->right = array();

        foreach ($this->widgets as $widget) {
            if ($widget['column'] == 0) {
                $this->left[$widget['row']] = WidgetHelper::getWidget($widget['pluginid']);
            }
        }
        ksort($this->left);
    }

    function storeSettings_action() {
            $perm = Request::get('perm');
            $ids = Request::get('ids');
            if (!empty($ids)) {
                $ids = explode(',', $ids);
                WidgetHelper::storeInitialPositions(0, $ids, $perm);
            }
            $this->render_nothing();
    }
}
?>
