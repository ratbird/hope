<?php
/**
 * ObjectVisit.php
 * model class for table ObjectVisit
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.1
 */
class ObjectVisit extends SimpleORMap
{

    /**
     * {@inheritdoc }
     */
    protected static function configure($config = array()) {
        $config['db_table'] = 'object_user_visits';
        parent::configure($config);
    }


    /**
     * Visits an object
     * 
     * @param String $object_id The object id
     * @param String $type The defined type of the object (enum in database)
     * @param String $user_id User_id (default: current user)
     */
    public static function visit($object_id, $type, $user_id = null) {
        
        // Find user
        $user_id = $user_id ? : $GLOBALS['user']->id;
        
        // Create visit
        $visit = new self(array($object_id, $user_id, $type));
        $visit->visitdate = $visit->visitdate ? : time();
        $visit->last_visitdate = time();
        
        // Increase views on object if new visit
        if ($visit->isNew()) {
            $views = new ObjectView($object_id);
            $views->views++;
            $views->store();
        }
        
        // And store it to the database
        $visit->store();
    }
    
    /**
     * Checks if an object is already visited
     * 
     * @param String $object_id The object id
     * @param int $chdate The timestamp of the last change of the object
     * @param String $user_id User_id (default: current user)
     * @return boolean true if already visited
     */
    public static function visited($object_id, $chdate = 0, $user_id = null) {
        
        // Find user
        $user_id = $user_id ? : $GLOBALS['user']->id;
        return DBManager::get()->fetchOne("SELECT 1 FROM object_user_visits WHERE object_id = ? AND user_id = ? AND last_visitdate >= ?", array($object_id, $user_id, $chdate));
    }
}
