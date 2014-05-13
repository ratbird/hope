<?php
/**
 * Folder.class.php - model class for table folder
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Marcus Lunzenauer <mlunzena@uos.de>
 * @author      Rasmus Fuhse <fuhse@data-quest>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.1
 *
 * @property string folder_id database column
 * @property string id alias column for folder_id
 * @property string range_id database column
 * @property string seminar_id database column
 * @property string user_id database column
 * @property string name database column
 * @property string description database column
 * @property string permission database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string priority database column
 */

class DocumentFolder extends SimpleORMap {

    protected static function configure()
    {
        $config['db_table'] = 'folder';
        $config['has_many'] = array(
            'files' => array(
                'class_name' => 'StudipDocument',
                'on_delete' => 'delete',
                'on_store' => 'store'
            )
        );
        parent::configure($config);
    }

    function getPermissions()
    {
        $result = array();
        foreach (array(1=>'visible', 'writable', 'readable', 'extendable') as $bit => $perm) {
            if ($this->permission & $bit)
                $result[] = $perm;
        }
        return $result;
    }
}
