<?php
/**
 * StudipLock.class.php
 * 
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/
class StudipLock
{
    private static $current;

    public static function getCurrent()
    {
        return self::$current;
    }

    public static function get($lockname, $timeout = 10)
    {
        if (self::$current !== null) {
            throw new UnexpectedValueException(sprintf('could not acquire new lock, %s still active'));
        }
        $ok = DBManager::get()->fetchColumn("SELECT GET_LOCK(?,?)", array(self::lockname($lockname), $timeout));
        if ($ok) {
            self::$current = $lockname;
        }
        return $ok;
    }

    public static function isFree($lockname)
    {
        return DBManager::get()->fetchColumn("SELECT IS_FREE_LOCK(?)", array(self::lockname($lockname)));
    }
    
    public static function release()
    {
        if (self::$current) {
            return DBManager::get()->fetchColumn("SELECT RELEASE_LOCK(?)", array(self::lockname(self::$current)));
        }
    }
    
    public static function lockname($lockname)
    {
        return $GLOBALS['DB_STUDIP_DATABASE'] . '_' . $lockname;
    }
}