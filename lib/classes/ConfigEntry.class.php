<?php
# Lifter010: TODO
/**
 * ConfigEntry.class.php
 * model class for table user_config
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

class ConfigEntry extends SimpleORMap
{

    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    static function findByField($field)
    {
        return self::findBySql("field=" . DbManager::get()->quote($field) . " ORDER BY is_default DESC");
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
        $this->db_table = 'config';
        $this->default_values['comment'] = '';
        parent::__construct($id);
    }
}