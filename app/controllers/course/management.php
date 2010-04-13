<?php

/*
 * Copyright (C) 2010 - Till Gloeggler <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';

/**
 * This controller realises a redirector for administrative pages 
 * 
 * @since 1.10
 * @author tgloeggl
 * @author aklassen 
 */
class Course_ManagementController extends AuthenticatedController {

    // see Trails_Controller#before_filter
    function before_filter(&$action, &$args) 
    {
        parent::before_filter($action, $args);

        if (SeminarCategories::GetBySeminarId($SessSemName[1])->studygroup_mode == true) {
            throw new Exception(_('Dies ist eine Studiengruppe und kein Seminar!'));
        }
        $GLOBALS['CURRENT_PAGE'] =  _("Veranstaltung verwalten");
        $GLOBALS['HELP_KEYWORD'] = 'Basis.Veranstaltungsverwaltung';
    }

    function index_action($section = '')
    {
        Navigation::activateItem('course/admin/main');

        if ($GLOBALS['SessSemName']['class'] == 'inst') {
            $this->redirect('course/management/inst');
            return;
        }

        $sem = Seminar::getInstance($GLOBALS['SessSemName'][1]);
        $this->visible = $sem->isVisible();
    }

    function inst_action()
    { 
        Navigation::activateItem('course/admin/main');
    }
    
    function visible_action($visible)
    {
        $sem = Seminar::getInstance($GLOBALS['SessSemName'][1]);
        if ($visible) {
            $sem->visible = 1;
        } else {
            $sem->visible = 0;
        }
        $sem->store();

        $this->redirect('course/management/index');
    }
}
