<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * help_content.php - Stud.IP-Help Content controller
 *
 * Copyright (C) 2013 - Arne Schröder <schroeder@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Arne Schröder <schroeder@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     help
*/

require_once 'lib/functions.php';
require_once 'app/controllers/authenticated_controller.php';

class HelpContentController extends AuthenticatedController
{
    
    /**
     * Callback function being called before an action is executed.
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // AJAX request, so no page layout.
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        // Open base layout for normal view
        } else {
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
        }
        $this->set_content_type('text/html;charset=windows-1252');
        $this->help_admin = $GLOBALS['perm']->have_perm('root') || RolePersistence::isAssignedRole($GLOBALS['user']->id, 'Hilfe-Administrator(in)');
    }

    /**
     * Administration page for tours
     */
    function admin_overview_action()
    {
        // check permission
        if (!$GLOBALS['auth']->is_authenticated() || $GLOBALS['user']->id === 'nobody') {
            throw new AccessDeniedException();
        }
        $GLOBALS['perm']->check('root');

        // initialize
        PageLayout::setTitle(_('Verwalten von Hilfe-Texten'));
        PageLayout::setHelpKeyword('Basis.HelpContentAdmin');
        // set navigation
        Navigation::activateItem('/admin/config/help_content');

        if (Request::get('help_content_filter') == 'set') {
            $this->help_content_searchterm = Request::option('help_content_filter_term');
        }
        if (Request::submitted('reset_filter')) {
            $this->help_content_searchterm = '';
        }
        if (Request::submitted('apply_help_content_filter')) {
            if (Request::get('help_content_searchterm') AND (strlen(trim(Request::get('help_content_searchterm'))) < 3))
                PageLayout::postMessage(MessageBox::error(_('Der Suchbegriff muss mindestens 3 Zeichen lang sein.')));
            if (strlen(trim(Request::get('help_content_searchterm'))) >= 3) {
                $this->help_content_searchterm = htmlReady(Request::get('help_content_searchterm'));
                $this->filter_text = sprintf(_('Angezeigt werden Hilfe-Texte zum Suchbegriff "%s".'), $this->help_content_searchterm);
            }
        }

        // load help content
        $this->help_contents = HelpContent::GetContentByFilter($this->help_content_searchterm);

        // save settings
        if (Request::submitted('save_help_content_settings')) {
            foreach($this->help_contents as $help_content_id => $help_content) {
                // set status as chosen
                if ((Request::get('help_content_status_'.$help_content_id) == '1') AND (!$this->help_contents[$help_content_id]->visible)) {
                    $this->help_contents[$help_content_id]->visible = 1;
                    $this->help_contents[$help_content_id]->store();
                } elseif ((Request::get('help_content_status_'.$help_content_id) != '1') AND ($this->help_contents[$help_content_id]->visible)) {
                    $this->help_contents[$help_content_id]->visible = 0;
                    $this->help_contents[$help_content_id]->store();
                }
            }
        }
    }

    /**
     * edit help content
     */
    function edit_action($id)
    {
        if (!$this->help_admin) {
            return $this->render_nothing();
        }
        // Output as dialog (Ajax-Request) or as Stud.IP page?
        if ($this->via_ajax) {
            header('X-Title: ' . _('Hilfe-Text bearbeiten'));
        }
        CSRFProtection::verifySecurityToken();
        if ($id == 'new') {
            $this->help_content = new HelpContent();
            $this->help_content->route = trim(Request::get('help_content_route'));
            $this->help_content->global_content_id = $this->content_id = md5(uniqid('help_content',1));
            $this->help_content->studip_version = $GLOBALS['SOFTWARE_VERSION'];
            $this->help_content->position = 1;
            $this->help_content->custom = 1;
        } else
            $this->help_content = HelpContent::GetContentByID($id);
        if (is_object($this->help_content)) {
            if (Request::submitted('save_help_content')) {
                if ($id != 'new' AND $this->help_content->isNew())
                    throw new AccessDeniedException(_('Der Hilfe-Text mit der angegebenen Route existiert nicht.'));
                $this->help_content->content      = trim(Request::get('help_content_content'));
                $this->help_content->route        = trim(Request::get('help_content_route'));
                $this->help_content->author_email = $GLOBALS['user']->Email;
                $this->help_content->installation_id = $GLOBALS['STUDIP_INSTALLATION_ID'];
                $this->help_content->store();
                header('X-Dialog-Close: 1');
            }
        }

        // prepare edit dialog
        $this->help_content_id = $id;
    }

    /**
     * delete help content
     */
    function delete_action($id)
    {
        if (!$this->help_admin) {
            return $this->render_nothing();
        }
        // Output as dialog (Ajax-Request) or as Stud.IP page?
        if ($this->via_ajax) {
            header('X-Title: ' . _('Hilfe-Text löschen'));
        }
        CSRFProtection::verifySecurityToken();
        $this->help_content = HelpContent::GetContentByID($id);
        if (is_object($this->help_content)) {
            if (Request::submitted('delete_help_content')) {
                $this->help_content->delete();
                header('X-Dialog-Close: 1');
            }
        }

        // prepare delete dialog
        $this->help_content_id = $id;
    }
}