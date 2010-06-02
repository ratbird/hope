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
        $config = Config::get();
        $allconfigs = array();
        foreach($config->getFields() as $field) {
            $metadata = $config->getMetadata($field);
            $metadata['value'] = $config->$field;
            $metadata['config_id'] = $field;
            $allconfigs[$metadata['section']]['section'] = $metadata['section'];
            $allconfigs[$metadata['section']]['data'][] = $metadata;
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
        $config = Config::get();
        foreach($config->getFields(null, null, trim($search_key)) as $field) {
            $metadata = $config->getMetadata($field);
            $metadata['value'] = $config->$field;
            $metadata['config_id'] = $field;
            $result[] = $metadata;
        }
        return $result;
    }


    /**
     * Search the user configuration from the user_config or give all parameter
     * with range=user
     *
     * @param   string $user_id
     * @param   string $give_all
     *
     * @return array()
     */
    public static function searchUserConfiguration($user_id = NULL, $give_all = false)
    {
        $config = Config::get();
        $allconfigs = array();
        if (!is_null($user_id)) {
            $uconfig = UserConfig::get($user_id);
            foreach($uconfig as $field => $value) {
                $data = $config->getMetadata($field);
                if(!count($data)) {
                    $data['field'] = $field;
                    $data['type'] = 'string';
                    $data['description'] = 'missing in table `config`';
                }
                $data['value'] = $value;
                $data['fullname'] = get_fullname($user_id);
                $allconfigs[] = $data;
            }
        }

        if ($give_all) {
            foreach($config->getFields('user') as $field) {
                $metadata = $config->getMetadata($field);
                $metadata['value'] = $config->$field;
                $metadata['config_id'] = $field;
                $allconfigs[] = $metadata;
            }
        }
        return $allconfigs;
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
        $uconfig = UserConfig::get($user_id);
        $config = Config::get();
        $data = $config->getMetadata($field);
        if(!count($data)) {
            $data['field'] = $field;
            $data['type'] = 'string';
            $data['description'] = 'missing in table `config`';
        }
        $data['value'] = $uconfig->$field;
        $data['fullname'] = get_fullname($user_id);
        return $data;
    }


    /**
     * Show all information for one configuration parameter
     *
     * @param   string $config_id
     */
    public static function getConfigInfo($config_id = NULL)
    {
        if (!is_null($config_id)) {
            $config = Config::get();
            $metadata = $config->getMetadata($config_id);
            $metadata['value'] = $config->$config_id;
            $metadata['config_id'] = $metadata['field'];
            return $metadata;
        }
        return NULL;
    }
}