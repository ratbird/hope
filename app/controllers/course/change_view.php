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
 * @since       2.2
 */

require_once 'app/controllers/authenticated_controller.php';

/**
 * This controller realises a redirector for administrative pages
 *
 * @since 2.2
 * @author hackl
 */
class Course_ChangeViewController extends AuthenticatedController
{

    // see Trails_Controller#before_filter
    function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);

        $course_id = Request::option('cid');
        if (isset($_SESSION['seminar_change_view_' . $course_id])) {
            unset($_SESSION['seminar_change_view_' . $course_id]);
            // Reset simulated view, redirect to administration page.
            $this->redirect(URLHelper::getURL('dispatch.php/course/management'));
        } elseif (get_object_type($course_id, array('sem'))
        && !SeminarCategories::GetBySeminarId($course_id)->studygroup_mode
        && in_array($GLOBALS['perm']->get_studip_perm($course_id), words('tutor dozent'))) {
            // Set simulated view, redirect to overview page.
            $_SESSION['seminar_change_view_' . $course_id] = 'autor';
            $this->redirect(URLHelper::getURL('seminar_main.php'));
        } else {
            throw new Trails_Exception(400);
        }
    }
}
