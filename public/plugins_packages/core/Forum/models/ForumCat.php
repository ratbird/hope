<?php
/**
 * ForumCat.php - Class to handle categories for areas
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

class ForumCat {
    
    /**
     * Return a list of all available categories. Empty categories are excluded 
     * by default
     * 
     * @param string $seminar_id    the seminar_id the retrieve the categories for
     * @param string $exclude_null  if false, empty categories are returned as well
     * @return array list of categories
     */
    static function getList($seminar_id, $exclude_null = true)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM forum_categories AS fc
            LEFT JOIN forum_categories_entries AS fce USING (category_id)
            WHERE seminar_id = ? "
            . ($exclude_null ? 'AND fce.topic_id IS NOT NULL ' : '')
            . "ORDER BY fc.pos ASC, fce.pos ASC");

        $stmt->execute(array($seminar_id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Returns the name of the associated category for an area denoted by the
     * passed topic_id
     * 
     * @param string $topic_id
     * @return string  the name of the category
     */
    static function getCategoryNameForArea($topic_id)
    {
        $stmt = DBManager::get()->prepare("SELECT fc.entry_name FROM forum_categories AS fc
            LEFT JOIN forum_categories_entries AS fce USING (category_id)
            WHERE fce.topic_id = ?");
        $stmt->execute(array($topic_id));
        
        return $stmt->fetchColumn();
    }


    /**
     * Adds a new category with the passed name to the passed seminar and
     * returns the id of the newly created category
     * 
     * @param string $seminar_id
     * @param string $name  the name of the new category
     * @return string  the id of the newly created category
     */
    static function add($seminar_id, $name)
    {
        $stmt = DBManager::get()->prepare("INSERT INTO forum_categories
            (category_id, seminar_id, entry_name)
            VALUES (?, ?, ?)");

        $category_id = md5(uniqid(rand()));
        
        $stmt->execute(array($category_id, $seminar_id, $name));
        
        return $category_id;
    }


    /**
     * Remove the category with the passed id. The seminar_id is used only
     * to be certain.
     * 
     * @param string $category_id  The ID of the category to be deleted
     * @param string $seminar_id  Seminar-ID the category belongs to
     */
    static function remove($category_id, $seminar_id)
    {
        // delete the category itself
        $stmt = DBManager::get()->prepare("DELETE FROM
            forum_categories
            WHERE category_id = ?");
        $stmt->execute(array($category_id));
        
        // set all entries to default category
        $stmt = DBManager::get()->prepare("UPDATE
            forum_categories_entries
            SET category_id = ?, pos = 999
            WHERE category_id = ?");
        $stmt->execute(array($seminar_id, $category_id));
    }

    
    /**
     * Set the position for the passed category to the passed value
     * 
     * @param string $category_id  the ID of the category to update
     * @param int $pos             the new position
     */
    static function setPosition($category_id, $pos)
    {
        $stmt = DBManager::get()->prepare("UPDATE
            forum_categories
            SET pos = ? WHERE category_id = ?");
        $stmt->execute(array($pos, $category_id));        
    }

    
    /**
     * Add the passed area to the passed category and remove it from all
     * other categories.
     * 
     * @param string $category_id  the ID of the category
     * @param string $area_id      the ID of the area to add the category to
     */
    static function addArea($category_id, $area_id)
    {
        // remove area from all other categories
        $stmt = DBManager::get()->prepare("DELETE FROM
            forum_categories_entries
            WHERE topic_id = ?");
        $stmt->execute(array($area_id));

        // add area to this category, make sure it is at the end
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM
            forum_categories_entries
            WHERE category_id = ?");
        $stmt->execute(array($category_id));
        $new_pos = $stmt->fetchColumn() + 1;

        $stmt = DBManager::get()->prepare("REPLACE INTO
            forum_categories_entries
            (category_id, topic_id, pos) VALUES (?, ?, ?)");
        $stmt->execute(array($category_id, $area_id, $new_pos));
    }
    
    
    /**
     * Remove the passed area from all categories.
     * 
     * @param string $area_id  the ID of the area to be removed
     */
    static function removeArea($area_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM
            forum_categories_entries
            WHERE topic_id = ?");
        $stmt->execute(array($area_id));
    }

    
    /**
     * Set the position for the passed category to the passed value
     * 
     * @param string $area_id  the ID of the area to update
     * @param int    $pos      the new position
     */
    static function setAreaPosition($area_id, $pos)
    {
        $stmt = DBManager::get()->prepare("UPDATE
            forum_categories_entries
            SET pos = ? WHERE topic_id = ?");
        $stmt->execute(array($pos, $area_id));        
    }
    
    
    /**
     * Set the name for the passed category
     * 
     * @param string $category_id  the ID of the category to update
     * @param string $name         the name to set
     */
    static function setName($category_id, $name)
    {
        $stmt = DBManager::get()->prepare("UPDATE
            forum_categories
            SET entry_name = ? WHERE category_id = ?");
        $stmt->execute(array($name, $category_id));
    }
    
    /**
     * Return the data for the passed category_id
     * 
     * @param type $category_id
     * 
     * @return array the data for the passed category_id
     */
    static function get($category_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM forum_categories
            WHERE category_id = ?");
        $stmt->execute(array($category_id));
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}