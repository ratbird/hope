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
require_once 'authenticated_controller.php';

class StartController extends AuthenticatedController
{
    /**
     * Callback function being called before an action is executed.
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;Charset=windows-1252');
        }

        Navigation::activateItem('/start');
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
    function index_action($action = false, $widgetId = null)
    {
        $this->left = array();
        $this->right = array();
        

        $this->left = WidgetHelper::getUserWidgets($GLOBALS['user']->id, 0);
        $this->right = WidgetHelper::getUserWidgets($GLOBALS['user']->id, 1);
        
        if (!(count($this->left) + count($this->right)) ) {
            WidgetHelper::setInitialPositions();
        }
        
        WidgetHelper::setActiveWidget(Request::get('activeWidget'));

        $sidebar = Sidebar::get();
        $sidebar->setImage(Assets::image_path("sidebar/home-sidebar.png"));
        $sidebar->setTitle(_("Meine Startseite"));
        
        $nav = new NavigationWidget();
        $nav->setTitle(_('Sprungmarken'));
        foreach (array_merge($this->left, $this->right) as $widget) {
            $nav->addLink($widget->getPluginName(),
                          $this->url_for('start#widget-' . $widget->widget_id));
        }
        $sidebar->addWidget($nav);
        
        $actions = new ActionsWidget();
        $actions->addLink(_('Neues Widget hinzufügen'),
                          $this->url_for('start/add'),
                          'icons/16/blue/add.png')->asDialog();
        
        // Root may set initial positions
        if ($GLOBALS['perm']->have_perm('root')) {
            $html = "<form action='".$this->url_for('start/storeAsDefault')."'><select name='usergroup'>";
            foreach ($GLOBALS['perm']->permissions as $permission => $useless) {
                $html .= "<option value='$permission'>$permission</option>";
            }
            $html .= "</select><br>";
            $html .= Studip\Button::create(_('Übernehmen'));
            $html .= "</form>";
            $defaulter = new SidebarWidget();
            $defaulter->setTitle(_('Als Standard setzen für'));
            $defaulter->addElement(new WidgetElement($html));
            $sidebar->addWidget($defaulter);
        }
        
        $sidebar->addWidget($actions);
    }
    
    /**
     *  This actions adds a new widget to the start page
     *
     * @return void
     */
    public function add_action()
    {
        if (Request::isPost()) {
            $ticket   = Request::get('studip_ticket');
            $widget   = Request::int('widget_id');
            $position = Request::int('position');

            $post_url = '';
            if (check_ticket($ticket)) {
                $id = WidgetHelper::addWidget($widget, $GLOBALS['user']->id);
                $post_url = '#widget-' . $id;
            }
            $this->redirect('start' . $post_url);
        }

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
    function delete_action($id)
    {
        if (Request::isPost()) {
            if (Request::submitted('yes')) {
                $name = WidgetHelper::getWidgetName($id);
                if (WidgetHelper::removeWidget($id, $name, $GLOBALS['user']->id)) {
                    $message = sprintf(_('Widget "%s" wurde entfernt.'), $name);
                    PageLayout::postMessage(MessageBox::success($message));
                } else {
                    $message = sprintf(_('Widget "%s" konnte nicht entfernt werden.'), $name);
                    PageLayout::postMessage(MessageBox::error($message));
                }
            }
        } else {
            $message = sprintf(_('Sind Sie sicher, dass Sie das Widget "%s" von der Startseite entfernen möchten?'),
                               WidgetHelper::getWidgetName($id));
            $this->flash['question'] = createQuestion2($message, array(), array(), $this->url_for('start/delete/' . $id));
        }
        $this->redirect('start');
    }

    /**
     *  Action to store the widget placements
     *
     * @return void
     */
    function storeNewOrder_action()
    {
        WidgetHelper::storeNewPositions(Request::get('widget'), Request::get('position'), Request::get('column'));
        $this->render_nothing();
    }
    
    /**
     * 
     */
    function storeAsDefault_action() {
        $GLOBALS['perm']->check('root');
        $group = Request::get('usergroup');
        WidgetHelper::setAsInitialPositions($GLOBALS['user']->id, $group);
        $this->redirect('start');
    }
}
