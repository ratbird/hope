<?php

/*
 * Copyright (C) 2009 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/classes/CourseAvatar.class.php';
require_once 'lib/classes/StudygroupAvatar.class.php';
require_once 'lib/classes/Seminar.class.php';
require_once 'lib/trails/AuthenticatedController.php';

/**
 * This controller is used to manipulate the avatar of a course.
 *
 * @author    mlunzena
 */
class Course_AvatarController extends AuthenticatedController
{

    # see Trails_Controller#before_filter
    function before_filter(&$action, &$args) {
        global $SEM_TYPE, $SEM_CLASS;

        parent::before_filter($action, $args);

        $this->course_id = current($args);
        if ($this->course_id === '' || get_object_type($this->course_id) !== 'sem'
            || !$GLOBALS['perm']->have_studip_perm("tutor", $this->course_id)) {
            $this->set_status(403);
            return FALSE;
        }

        $GLOBALS['body_id'] = 'custom_avatar';
        $GLOBALS['CURRENT_PAGE'] = getHeaderLine($this->course_id) . ' - ' .
                           _('Bild ändern');

        $sem = Seminar::getInstance($this->course_id);
        $this->studygroup_mode = $SEM_CLASS[$SEM_TYPE[$sem->status]["class"]]["studygroup_mode"];

        # choose base layout w/o infobox and set tabs
        $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');

        if ($this->studygroup_mode) {
            Navigation::activateItem('/course/studygroup/admin');
        } else {
            Navigation::activateItem('/admin/course/details');
        }
        $this->set_layout($layout);
    }

    /**
     * This method is called to show the form to upload a new avatar for a
     * course.
     *
     * @return void
     */
    function update_action()
    {
        // nothing to do
    }

    /**
     * This method is called to upload a new avatar for a course.
     *
     * @return void
     */
    function put_action()
    {
        try {
            CourseAvatar::getAvatar($this->course_id)->createFromUpload('avatar');
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->render_action("update");
        }
    }

    /**
     * This method is called to remove an avatar for a course.
     *
     * @return void
     */
    function delete_action()
    {
        CourseAvatar::getAvatar($this->course_id)->reset();
        if ($this->studygroup_mode) {
            $this->redirect(URLHelper::getUrl('dispatch.php/course/studygroup/edit/' . $this->course_id));
        } else {
            $this->redirect(URLHelper::getUrl('admin_seminare1.php?s_id=' . $this->course_id));
        }
    }
}
