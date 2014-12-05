<?php
/**
 * configuration.php - model class for the configuration
 *
 * @author      Nico M�ller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     GPL2 or any later version
 * @category    Stud.IP
 * @package     admin
 * @since       2.0
 */
class ConfigurationModel
{
    /*
     * Get all config-files
     */
    public static function getConfig($section = null, $name = null)
    {
        $config = Config::get();
        $allconfigs = array();
        foreach ($config->getFields('global', $section, $name) as $field) {
            $metadata = $config->getMetadata($field);
            $metadata['value'] = $config->$field;
            $allconfigs[$metadata['section']][] = $metadata;
        }
        return $allconfigs;
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
    public static function searchUserConfiguration($user_id = null, $give_all = false)
    {
        $config = Config::get();
        $allconfigs = array();
        if (!is_null($user_id)) {
            $user = User::find($user_id);

            $uconfig = UserConfig::get($user_id);
            foreach ($uconfig as $field => $value) {
                $data = $config->getMetadata($field);
                if(!count($data)) {
                    $data['field'] = $field;
                    $data['type'] = 'string';
                    $data['description'] = 'missing in table `config`';
                }
                $data['value'] = $value;
                $data['fullname'] = $user->getFullname();
                $allconfigs[] = $data;
            }
        }

        if ($give_all) {
            foreach ($config->getFields('user') as $field) {
                $metadata = $config->getMetadata($field);
                $metadata['value'] = $config->$field;
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
        if (!count($data)) {
            $data['field'] = $field;
            $data['type'] = 'string';
            $data['description'] = 'missing in table `config`';
        }
        $data['value'] = $uconfig->$field;
        $data['fullname'] = User::find($user_id)->getFullname();
        return $data;
    }


    /**
     * Show all information for one configuration parameter
     *
     * @param string $field
     */
    public static function getConfigInfo($field)
    {
        $config = Config::get();
        $metadata = $config->getMetadata($field);
        $metadata['value'] = $config->$field;
        return $metadata;
    }
}