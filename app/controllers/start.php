<?php
/*
 * start.php - start page controller
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   André Klaßen <klassen@elan-ev.de>
 * @author   Nadine Werner <nadine.werner@uni-osnabrueck.de>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Stud.IP
 * @since    3.1
 */

require_once 'lib/functions.php';
require_once 'studip_controller.php';
require_once 'lib/classes/Request.class.php';
require_once 'lib/classes/WidgetHelper.php';
require_once 'app/controllers/authenticated_controller.php';

class StartController extends AuthenticatedController {

    /**
     * Callback function being called before an action is executed.
     */
    function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        global $user;
        $this->user = $user;

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
        }
        Navigation::activateItem('/start/');
        PageLayout::setTabNavigation(NULL); // disable display of tabs
        PageLayout::setHelpKeyword("Basis.Startseite"); // set keyword for new help
        PageLayout::setTitle(_('Startseite'));
    }

    /**
     * Entry point of the controller that displays the start page of Stud.IP
     *
     * @param string $action
     * @param string $widgetId
     *
     * @return void
     */
    function index_action($action = false, $widgetId = NULL) {

        Navigation::activateItem('/start');

        $this->left = array();
        $this->right = array();
        $this->widgets = WidgetHelper::getUserWidgets($this->user->id);

        if (empty($this->widgets)){
            $this->widgets = WidgetHelper::getInitialPositions($GLOBALS['perm']->get_perm());
            $idl = array();
            foreach ($this->widgets as $widget) {
                if ($widget['column'] == 0) {
                    $idl[$widget['row']] = $widget['pluginid'];
                }
            }

            WidgetHelper::addInitialPositions(0, $idl, $this->user->id);
            $this->widgets = WidgetHelper::getUserWidgets($this->user->id);
        }

        foreach ($this->widgets as $pos => $widget) {
            $this->left[$pos] = $widget;
        }

        ksort($this->left);
        WidgetHelper::setActiveWidget(Request::get('activeWidget'));

        $this->suitable_widgets = PluginEngine::getPlugins('PortalPlugin');
        
        if($action == 'delete' && isset($widgetId)) {
            $template = $GLOBALS['template_factory']->open('shared/question');
            $template->set_attribute('approvalLink', $this->url_for('start/delete/' . $widgetId . '/true/' . get_ticket()));
            $template->set_attribute('disapprovalLink', $this->url_for('start/'));
            $template->set_attribute('question',sprintf(_("Sind Sie sicher, dass Sie das Widget »%s« von der Startseite entfernen möchten?"),
                                      WidgetHelper::getWidgetName($widgetId)));
            $this->flash['question'] = $template->render();
        }
    }
    
    /**
     *  This actions adds a new widget to the start page
     *
     * @param string $choice representing the chosen widgetId
     * @param string $side where the widget should be paced (used in later versions)
     * @param string $studipticket
     *
     * @return void
     */
    function add_widget_action($choice, $side = 0, $studipticket = false) {
        if(check_ticket($studipticket)){
            WidgetHelper::addWidget($choice, $this->user->id);
        }
        $this->redirect('start');
    }

    /**
     *  This actions removes a new widget from the start page
     *
     * @param string $widgetId
     * @param string $approveDelete
     * @param string $studipticket
     *
     * @return void
     */
    function delete_action($widgetId, $approveDelete = false, $studipticket = false) {
        
        if ($approveDelete && check_ticket($studipticket)) {

            $name = WidgetHelper::getWidgetName($widgetId);
            
            if(WidgetHelper::removeWidget($widgetId,$name,$this->user->id)) {

            } else {
                $this->flash['error'] = sprintf(_("Widget »%s« konnte nicht entfernt werden."), $name);
            }

            $this->redirect('start');
        }
    }

    /**
     *  Action to store the widget placements
     *
     * @return void
     */
    function storeNewOrder_action() {

        if($_POST['ids']){
             $idArray = explode(',',$_POST['ids']);
             WidgetHelper::storeNewPositions($idArray);
        }
        $this->render_nothing();
    }
}
?>
