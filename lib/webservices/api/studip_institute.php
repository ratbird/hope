<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * studip_institute.php - base class for institutes
 *
 * Copyright (C) 2006 - Marco Diedrich (mdiedric@uos.de)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("lib/webservices/api/studip_user.php");

class StudipInstituteHelper
{
    function get_users_by_status($institute_id, $status)
    {
        $db = new DB_Seminar();

        $db->query($query = "SELECT au.username FROM Institute i
                                INNER JOIN user_inst ui
                                ON (i.Institut_id = ui.Institut_id)
                                INNER JOIN auth_user_md5 au
                                ON (ui.user_id = au.user_id)
                                WHERE inst_perms = '$status'
                                AND i.Institut_id = '$institute_id'");

        $user_list = array();

        while($db->next_record())
        {
            $user_list [] = $db->f('username');
        }
        return $user_list;
    }

    function get_user_status($username, $institute_id)
    {
        $db = new DB_Seminar();

        $db->query($query = "SELECT au.username, ui.inst_perms as status FROM Institute i
                                INNER JOIN user_inst ui
                                ON (i.Institut_id = ui.Institut_id)
                                INNER JOIN auth_user_md5 au
                                ON (ui.user_id = au.user_id)
                                WHERE au.username = '$username'
                                AND i.Institut_id = '$institute_id'");

        if($db->next_record())
        {
            return $db->f('status');
        }

        return FALSE;
    }

    function get_admins($institute_id)
    {
        return StudipInstituteHelper::get_users_by_status($institute_id, 'admin');
    }

    function get_lecturers($institute_id)
    {
        return StudipInstituteHelper::get_users_by_status($institute_id, 'dozent');
    }

    function get_authors($institute_id)
    {
        return StudipInstituteHelper::get_users_by_status($institute_id, 'autor');
    }

    function get_users($institute_id)
    {
        return StudipInstituteHelper::get_users_by_status($institute_id, 'user');
    }

    function get_higher_level_institute($institute_id)
    {
        $db = new DB_Seminar();

        $db->query("SELECT i2.* FROM Institute i
                                INNER JOIN Institute i2 
                                ON (i.fakultaets_id = i2.Institut_id)
                                WHERE i.Institut_id = '$institute_id'
                                AND i2.fakultaets_id != '$institute_id'");

        $institute_list = array();

        if($db->next_record())
        {
            return $db->f("Institut_id");
        }

        return null;
    }

    function get_admins_upward_recursive($institute_id)
    {
        $institute_list = array(StudipInstituteHelper::get_higher_level_institute($institute_id), $institute_id);
        $admin_list = array();

        foreach($institute_list as $institute_id)
        {
            $admin_list = array_merge($admin_list, StudipInstituteHelper::get_admins($institute_id));
        }
        return $admin_list;
    }

}
