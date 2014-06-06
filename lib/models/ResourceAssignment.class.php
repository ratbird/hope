<?php
/**
 * ResourceAssignment.class.php
 * model class for table resources_assign
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 */

class ResourceAssignment extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'resources_assign';
        $config['belongs_to']['resource'] = array(
            'class_name' => 'ResourceObject',
            'foreign_key' => 'resource_id',
            'assoc_func' => 'Factory'
        );
        $config['belongs_to']['date'] = array(
            'class_name' => 'CourseDate',
            'foreign_key' => 'assign_user_id',
        );
        $config['belongs_to']['user'] = array(
            'class_name' => 'User',
            'foreign_key' => 'assign_user_id',
        );
        parent::configure($config);
    }
}
