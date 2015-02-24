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
 * @property string assign_id database column
 * @property string id alias column for assign_id
 * @property string resource_id database column
 * @property string assign_user_id database column
 * @property string user_free_name database column
 * @property string begin database column
 * @property string end database column
 * @property string repeat_end database column
 * @property string repeat_quantity database column
 * @property string repeat_interval database column
 * @property string repeat_month_of_year database column
 * @property string repeat_day_of_month database column
 * @property string repeat_week_of_month database column
 * @property string repeat_day_of_week database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string comment_internal database column
 * @property ResourceObject resource belongs_to ResourceObject
 * @property CourseDate date belongs_to CourseDate
 * @property User user belongs_to User
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

    function delete()
    {
        $old_assign_object = new AssignObject($this->id);
        $ret = parent::delete();
        $old_assign_object->delete();
        return $ret;
    }
}
