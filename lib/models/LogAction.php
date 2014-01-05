<?php
/**
 * LogAction
 * model class for table log_actions
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * 
 */
class LogAction extends SimpleORMap
{
    function __construct($id = null)
    {
        $this->db_table = 'log_actions';
        $this->has_many = array('events' => array('class_name' => 'LogEvent'));
        parent::__construct($id);
    }
    
    public function isActive()
    {
        return $this->active ? true : false;
    }
    
    /**
     * Returns an associative array of all actions with at least one event.
     * The array contains the action_id and the description. It is ordered by
     * the first part of the actions name and the description.
     * 
     * @return array Assoc array of actions.
     */
    public static function getUsed()
    {
        $db = DBManager::get();

        $sql = "SELECT action_id, description, SUBSTRING_INDEX(name, '_', 1) AS log_group
                FROM log_actions WHERE EXISTS
                (SELECT * FROM log_events WHERE log_events.action_id = log_actions.action_id)
                ORDER BY log_group, description";

        $result = $db->query($sql);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function register()
    {
        
    }
}
