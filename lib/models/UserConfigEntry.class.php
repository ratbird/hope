<?php
# Lifter010: TODO
/**
 * UserConfigEntry.class.php
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

class UserConfigEntry extends SimpleORMap
{

    static function findByFieldAndUser($field, $user_id)
    {
        $found = self::findBySql("field = ? AND user_id = ?", func_get_args());
        return isset($found[0]) ? $found[0] : null;
    }

    static function deleteByUser($user_id)
    {
        return self::deleteBySQL("user_id = ?", func_get_args());
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'user_config';
        $this->default_values['comment'] = '';
        parent::__construct($id);
    }
}