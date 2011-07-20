<?php

/*
 * change_view.php - contains Course_ChangeViewController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.1
 */

require_once 'app/controllers/authenticated_controller.php';

/**
 * This controller realises a redirector for administrative pages
 *
 * @since 2.1
 * @author hackl
 */
class Course_ChangeViewController extends AuthenticatedController
{

    var $changedPerm = 'dozent';

    // see Trails_Controller#before_filter
    function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);

        if ($_SESSION['SessSemName']['class'] == 'sem') {
            if (SeminarCategories::GetBySeminarId($_SESSION['SessSemName'][1])->studygroup_mode == true) {
                throw new Exception(_('Dies ist eine Studiengruppe und kein Seminar!'));
            }
        }
        PageLayout::setTitle(sprintf(_("%s - Ansicht simulieren"), $GLOBALS['SessSemName']["header_line"]));
        PageLayout::setHelpKeyword('Basis.InVeranstaltungAnsichtSimulieren');
    }

    function index_action() {
        //Navigation::activateItem('/course/main/change_view');
    }

    function set_action() {
        session_start();
        if ($_SESSION['seminar_change_view']) {
            session_unregister('seminar_change_view');
        } else {
            session_register('seminar_change_view');
            /*$_SESSION['seminar_change_view'] = array(
                'cid' => Request::option('cid'),
                'perm' => Request::get('change_view_perm')
            );*/
            $_SESSION['seminar_change_view'] = array(
                'cid' => Request::option('cid'),
                'perm' => 'autor'
            );
        }
    }

}
