<?php
/**
 * StudipPerm.php - Permission checks for Stud.IP
 *
 * This class forwards all requests to the global perm class
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.4
 */
class StudipPerm
{

    /**
     * Forward any static call to global perm
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return $GLOBALS['perm']->$name($arguments);
    }

    /*##########
      IDE Helper
     ###########*/

    /**
     * Checks if the current user has the required privileges
     *
     * @param $permission root,admin,dozent,tutor,autor,user
     * @throws AccessDeniedException If the user does not have the required privileges
     */
    public static function check($permission) {
        $GLOBALS['perm']->check($permission);
    }

    /**
     * Checks if the current user has the required privileges
     *
     * @param $permission root,admin,dozent,tutor,autor,user
     * @param md5 $user_id The user id if not checked for the current user
     * @return boolean True if the user has the privileges, false if not
     */
    public static function have($permission, $user_id = null) {
        return $GLOBALS['perm']->have_perm($permission, $user_id = null);
    }

    /**
     * Checks if the current user has the required privileges at a course or institute
     *
     * @param $permission root,admin,dozent,tutor,autor,user
     * @param $range_id The range_id of the object for right check
     * @param md5 $user_id The user id if not checked for the current user
     * @return boolean True if the user has the privileges, false if not
     */
    public static function haveAt($permission, $range_id, $user_id = null) {
        return $GLOBALS['perm']->have_perm($permission,$range_id, $user_id = null);
    }
}