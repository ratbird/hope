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
    protected $db_table = 'kategorien';

    /**
     *
     */
    public static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    /**
     *
     */
    public static function findByUserId($user_id)
    {
        return self::findBySql(__CLASS__, 'range_id = ' . DBManager::get()->quote($user_id));
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
     */
    public function getValue($field)
    {
        if ($field === 'id') {
            $field = 'kategorie_id';
        }
        return parent::getValue($field);
    }
}
