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
class Course_ManagementController extends AuthenticatedController
{

    // see Trails_Controller#before_filter
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if ($_SESSION['SessSemName']['class'] == 'sem') {
            if (SeminarCategories::GetBySeminarId($_SESSION['SessSemName'][1])->studygroup_mode == true) {
                throw new Exception(_('Dies ist eine Studiengruppe und kein Seminar!'));
            }
        }
        PageLayout::setTitle(sprintf(_("%s - Verwaltung"), $_SESSION['SessSemName']['header_line']));
        PageLayout::setHelpKeyword('Basis.Veranstaltungsverwaltung');
    }

    function index_action($section = '')
    {
        Navigation::activateItem('course/admin/main');
        if ($_SESSION['SessSemName']['class'] == 'inst') {
            $this->redirect('course/management/inst');
            return;
        }

        $sem = Seminar::getInstance($_SESSION['SessSemName'][1]);
        $this->visible = $sem->isVisible();
        $this->is_admin = $GLOBALS['perm']->have_studip_perm('tutor', $_SESSION['SessSemName'][1]);
    }

    function inst_action()
    {
        Navigation::activateItem('course/admin/main');
        $this->is_admin = $GLOBALS['perm']->have_studip_perm('admin', $_SESSION['SessSemName'][1]);

    }

    function visible_action($visible)
    {
        $sem = Seminar::getInstance($_SESSION['SessSemName'][1]);
        if ($visible) {
            $sem->visible = 1;
        } else {
            $sem->visible = 0;
        }
        $sem->store();

        $this->redirect('course/management/index');
    }
}
