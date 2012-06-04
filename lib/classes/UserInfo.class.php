<?php
/**
 * UserInfo.class.php
 * model class for table user_info
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'SimpleORMap.class.php';

class UserInfo extends SimpleORMap
{

    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'user_info';
        $this->default_values['publi'] = '';
        $this->default_values['schwerp'] = '';
        parent::__construct($id);
    }
}
