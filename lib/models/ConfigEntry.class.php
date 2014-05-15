<?php
/**
 * ConfigEntry.class.php
 * model class for table user_config
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string config_id database column
 * @property string id alias column for config_id
 * @property string parent_id database column
 * @property string field database column
 * @property string value database column
 * @property string is_default database column
 * @property string type database column
 * @property string range database column
 * @property string section database column
 * @property string position database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string description database column
 * @property string comment database column
 * @property string message_template database column
 */

class ConfigEntry extends SimpleORMap
{

    static function findByField($field)
    {
        return self::findBySql("field=" . DbManager::get()->quote($field) . " ORDER BY is_default DESC");
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'config';
        $config['default_values']['comment'] = '';
        parent::configure($config);
    }
}
