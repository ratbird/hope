<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/*
 * studip_contentmodule.php - base class for content modules
 *
 * Copyright (C) 2006 - Marco Diedrich (mdiedric@uos.de)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class StudipContentmoduleHelper
{
    function find_seminars_using_contentmodule($system_type, $module_id)
    {
        $db =& new DB_Seminar();
        $db->query("SELECT s.Seminar_id FROM seminare s
                                LEFT JOIN object_contentmodules oc
                                ON (s.Seminar_id = oc.object_id)
                                WHERE oc.module_id = '$module_id'
                                AND oc.system_type = '$system_type';");

        $seminar_ids = array();

        while ($db->next_record())
        {
            $seminar_ids [] = $db->f("Seminar_id");
        }
        return $seminar_ids;
    }

    function find_institutes_using_contentmodule($system_type, $module_id)
    {
        $db =& new DB_Seminar();
        $db->query($query = "SELECT i.Institut_id FROM Institute i
                                LEFT JOIN object_contentmodules oc
                                ON (i.Institut_id = oc.object_id)
                                WHERE oc.module_id = '$module_id'
                                AND oc.system_type = '$system_type';");

        $institute_ids = array();

        while ($db->next_record())
        {

            $institute_ids [] = $db->f("Institut_id");
        }
        return $institute_ids;
    }

}

