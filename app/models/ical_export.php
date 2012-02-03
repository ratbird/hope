<?php
/**
 * ical_export.php - provides functions to handle the export of events
 * over a short url
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel, data-quest GmbH <thienel@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
class IcalExport
{

    private static $id_length = 8;

    /**
     * Sets the lentgh of the key
     *
     * @param int $length
     */
    public static function setKeyLength($length)
    {
        self::$id_length = $length;
    }

    /**
     * Returns a new key.
     *
     * @return string
     */
    public static function makeKey()
    {
        $length = self::$id_length;
        $ret = '';
        $rejected = array(
            'A' => 2,
            'E' => 2,
            'I' => 2,
            'O' => 2,
            'U' => 2,
            'a' => 2,
            'e' => 2,
            'i' => 2,
            'o' => 2,
            'u' => 2);
        while ($length--) {
            while (1) {
                $rnd = rand(48, 122);
                if ($rnd < 48)
                    continue;
                if ($rnd > 57 && $rnd < 65)
                    continue;
                if ($rnd > 90 && $rnd < 97)
                    continue;
                if ($rnd > 122)
                    continue;
                $char = chr($rnd);
                if ($rejected[$char] > 1) {
                    continue;
                }
                $rejected[$char]++;
                $ret .= $char;
                break;
            }
        }
        return $ret;
    }

    /**
     * Returns the key by given user_id. Returns false if no valid key was found.
     *
     * @param string $user_id
     * @return mixed
     */
    public static function getKeyByUser($user_id)
    {
        $stmt = DBManager::get()->prepare('SELECT short_id FROM ical_export WHERE user_id = ?');
        $stmt->execute(array($user_id));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['short_id'];
        } else {
            return false;
        }
    }

    /**
     * Returns user_id by given key. Returns false if no valid user_id was found.
     *
     * @param type $short_id
     * @return mixed
     */
    public static function getUserIdByKey($short_id)
    {
        $stmt = DBManager::get()->prepare('SELECT user_id FROM ical_export WHERE short_id = ?');
        $stmt->execute(array($short_id));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['user_id'];
        } else {
            return false;
        }
    }

    /**
     * Sets a new key for the user with the given user_id.
     *
     * @param type $user_id
     * @return string the new key
     */
    public static function setKey($user_id)
    {
        $short_id = self::getKeyByUser($user_id);
        if ($short_id) {
            self::deleteKey($user_id);
        }
        $short_id = self::makeKey();
        $stmt = DBManager::get()->prepare('INSERT INTO ical_export VALUES(?, ?)');
        $stmt->execute(array($short_id, $user_id));
        return $short_id;
    }

    /**
     * Deletes the key for the user with the given user_id.
     *
     * @param type $user_id
     */
    public static function deleteKey($user_id)
    {
        $stmt = DBManager::get()->prepare('DELETE FROM ical_export WHERE user_id = ?');
        $stmt->execute(array($user_id));
    }

}