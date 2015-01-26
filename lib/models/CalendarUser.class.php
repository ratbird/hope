<?php
/**
 * CalendarUser.class.php - Model for users with access to other users calendar.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.2
 */

class CalendarUser extends SimpleORMap
{
    
    protected static function configure($config = array())
    {
        $config['db_table'] = 'calendar_user';
        
        $config['has_one']['owner'] = array(
            'class_name' => 'User',
            'foreign_key' => 'owner_id',
            'assoc_foreign_key' => 'user_id'
        );
        $config['belongs_to']['user'] = array(
            'class_name' => 'User',
            'foreign_key' => 'user_id'
        );
        
        $config['additional_fields']['nachname']['get'] = function ($cu) {
            return $cu->user->nachname;
        };
        $config['additional_fields']['vorname']['get'] = function ($cu) {
            return $cu->user->vorname;
        };
        
        parent::configure($config);
        
    }
    
    public function setPerm($permission)
    {
        if ($permission == Calendar::PERMISSION_READABLE) {
            $this->permission = Calendar::PERMISSION_READABLE;
        } else if ($permission == Calendar::PERMISSION_WRITABLE) {
            $this->permission = Calendar::PERMISSION_WRITABLE;
        } else {
            throw new InvalidArgumentException(
                'Calendar permission must be of type PERMISSION_READABLE or PERMISSION_WRITABLE.');
        }
    }
    
    public static function getUsers($user_id, $permission = null)
    {
        $permission_array = array(Calendar::PERMISSION_READABLE,
                Calendar::PERMISSION_WRITABLE);
        if (!$permission) {
            $permission = $permission_array;
        } else if (!in_array($permission, $permission_array)) {
            throw new InvalidArgumentException(
                'Calendar permission must be of type PERMISSION_READABLE or PERMISSION_WRITABLE.');
        } else {
            $permission = array($permission);
        }
        return SimpleORMapCollection::createFromArray(CalendarUser::findBySQL(
                'owner_id = ? AND permission IN(?)',
                array($user_id, $permission)));
        
    }
    
    public static function getOwners($user_id, $permission = null)
    {
        $permission_array = array(Calendar::PERMISSION_READABLE,
                Calendar::PERMISSION_WRITABLE);
        if (!$permission) {
            $permission = $permission_array;
        } else if (!in_array($permission, $permission_array)) {
            throw new InvalidArgumentException(
                'Calendar permission must be of type PERMISSION_READABLE or PERMISSION_WRITABLE.');
        } else {
            $permission = array($permission);
        }
        return SimpleORMapCollection::createFromArray(CalendarUser::findBySQL(
                'user_id = ? AND permission IN(?)',
                array($user_id, $permission)));
        
    }
}