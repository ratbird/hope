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

    protected static function configure($config = array()) {
        $config['db_table'] = 'object_user_visits';
        parent::configure($config);
    }


    public static function visit($object_id, $type, $user_id = null) {
        
        // Find user
        $user_id = $user_id ? : $GLOBALS['user']->id;
        
        // Create visit
        $visit = new self(array($object_id, $user_id, $type));
        $visit->visitdate = $visit->visitdate ? : time();
        $visit->last_visitdate = time();
        $visit->store();
    }
    
    public static function visited($object_id, $user_id = null) {
        // Find user
        $user_id = $user_id ? : $GLOBALS['user']->id;
        return DBManager::get()->fetchOne("SELECT 1 FROM object_user_visits WHERE object_id = ? AND user_id = ?", array($object_id, $user_id));
    }
}
