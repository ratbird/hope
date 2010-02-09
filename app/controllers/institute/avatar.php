<?php

/*
 * Copyright (C) 2009 - Marcus Lunzenauer <mlunzena@uos.de>
 * André Noack <noack@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/InstituteAvatar.class.php';


/**
 * This controller is used to manipulate the avatar of a course.
 *
 * @author    mlunzena
 */
class Institute_AvatarController extends AuthenticatedController
{

    # see Trails_Controller#before_filter
    function before_filter(&$action, &$args) {

        parent::before_filter($action, $args);

        include 'lib/seminar_open.php';

        # user must be logged in
        $GLOBALS['auth']->login_if(
            $GLOBALS['auth']->auth['uid'] == 'nobody');

        $this->institute_id = current($args);
        if ($this->institute_id === '' || !in_array(get_object_type($this->institute_id), words('inst fak'))
            || !$GLOBALS['perm']->have_studip_perm("admin", $this->institute_id)) {
            $this->set_status(403);
            return FALSE;
        }

        $GLOBALS['body_id'] = 'custom_avatar';
        $GLOBALS['CURRENT_PAGE'] = getHeaderLine($this->institute_id) . ' - ' .
                           _('Bild ändern');

        Navigation::activateItem('/admin/institute/details');

        # choose base layout w/o infobox and set tabs
        $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
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
            InstituteAvatar::getAvatar($this->institute_id)->createFromUpload('avatar');
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
        InstituteAvatar::getAvatar($this->institute_id)->reset();
        $this->redirect(URLHelper::getUrl('admin_institut.php?i_id=' . $this->institute_id));
    }
}
