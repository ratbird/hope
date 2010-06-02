<?php
/**
 * configuration.php - model class for the configuration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 * @since       Stud.IP version 1.12
 */

/**
 * @uses        DBManager
 *
 */
class ConfigurationModel
{
    /*
     * Get all config-files
     */
    public static function getConfig()
    {
        $query = "SELECT section, config_id, parent_id, field, value, is_default, "
               . "type, `range`, position, description "
               . "FROM config "
               . "ORDER BY section ASC, field ASC";
        $temp = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);

        $allconfigs = array();
        foreach ($temp as $row)
        {
            if (!isset($allconfigs[$row['section']])) {
                $allconfigs[$row['section']] = array(
                    'section' => $row['section'],
                    'data' => array(),
                );
            }
            array_push($allconfigs[ $row['section'] ]['data'], $row);
        }
        return $allconfigs;
    }

    /**
     * Search for the key (field)
     *
     * @param   string $search_key
     *
     * @return  array() list of config-entries
     */
    public static function searchConfig($search_key)
    {
        if (!is_null($search_key)) {
            $query = "SELECT section, config_id, field, value, is_default, "
                   . "type, `range`, position, description "
                   . "FROM config "
                   . "WHERE field LIKE ? "
                   . "ORDER BY field ASC, value ASC";
            $stmt = DBManager::get()->prepare($query);
            $stmt->execute(array('%'.$search_key.'%'));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return NULL;
    }

    /**
     * Save the modified configuration data
     *
     * @param   string $config_id
     * @param   string $conf_value
     * @param   string $conf_sec
     * @param   string $conf_comment
     */
    public static function saveEditConfiguration($config_id, $conf_value, $conf_sec, $conf_comment)
    {
        $query = "UPDATE config "
               . "SET value = ?, section = ?, mkdate = ?, chdate = ?, comment = ? "
               . "WHERE config_id = ?";
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute(array($conf_value, $conf_sec, time(), time(), $conf_comment, $config_id ));
    }

    /**
     * Search the user configuration from the user_config or give all parameter
     * with `range`=user
     *
     * @param   string $user_id
     * @param   string $give_all
     *
     * @return array()
     */
    public static function searchUserConfiguration($user_id = NULL, $give_all = false)
    {
        if (!is_null($user_id)) {
            $query = "SELECT DISTINCT uc.field,uc.value,c.type, c.description, CONCAT_WS(' ', au.Vorname, au.Nachname) as fullname "
                   . "FROM user_config uc "
                   . "LEFT JOIN config c USING(field) "
                   . "LEFT JOIN auth_user_md5 au USING (user_id) "
                   . "WHERE user_id = ? ";
            $stmt = DBManager::get()->prepare($query);
            $stmt->execute(array($user_id ));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($give_all) {
            $query = "SELECT config_id, field, value, description, type, section "
                   . "FROM config "
                   . "WHERE `range` = 'user' "
                   //. "AND is_default = '1' "
                   . "ORDER BY field";
            $stmt = DBManager::get()->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return NULL;
    }

    /**
     * Show the user configuration for one parameter
     *
     * @param   string $user_id
     * @param   string $field
     *
     * @return  array()
     */
    public static function showUserConfiguration($user_id, $field)
    {
        $query = "SELECT uc.field,uc.value,c.type, c.description, CONCAT_WS(' ', au.Vorname, au.Nachname) as fullname, uc.user_id "
               . "FROM user_config uc "
               . "LEFT JOIN config c USING(field) "
               . "LEFT JOIN auth_user_md5 au USING (user_id) "
               . "WHERE uc.user_id = ? AND uc.field = ?";
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute(array($user_id, $field));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Updating the user configuration (value)
     *
     * @param   string $user_id
     * @param   string $value
     * @param   string $field
     */
    public static function updateUserConfiguration($user_id,$value,$field)
    {
        if (!is_null($user_id)) {
            $query = "UPDATE user_config "
                   . "SET value = ?, chdate = ? "
                   . "WHERE user_id = ? AND field = ?";
            $stmt = DBManager::get()->prepare($query);
            $stmt->execute(array($value, time(), $user_id, $field));
        }
    }

    /**
     * Show all information for one configuration parameter
     *
     * @param   string $config_id
     */
    public static function getConfigInfo($config_id = NULL)
    {
        if (!is_null($config_id)) {
            $query = "SELECT * FROM config WHERE config_id = '{$config_id}'";
            return DBManager::get()->query($query)->fetch(PDO::FETCH_ASSOC);
        }
        return NULL;
    }
}