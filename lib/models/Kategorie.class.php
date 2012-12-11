<?
/*
 * Kategorie model
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

class Kategorie extends SimpleORMap
{

    /**
     *
     */
    public static function findByUserId($user_id)
    {
        return self::findByRange_id($user_id);
    }

    /**
     * 
     */
    public static function increatePrioritiesByUserId($user_id)
    {
        $query = "UPDATE kategorien SET priority = priority + 1 WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        return $statement->rowCount() > 0;
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'kategorien';
        $this->default_values['content'] = '';
        parent::__construct($id);
    }
}
