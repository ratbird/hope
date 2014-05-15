<?php
/**
 * Datafield
 * model class for table datafields
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string datafield_id database column
 * @property string id alias column for datafield_id
 * @property string name database column
 * @property string object_type database column
 * @property string object_class database column
 * @property string edit_perms database column
 * @property string view_perms database column
 * @property string priority database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string type database column
 * @property string typeparam database column
 * @property string is_required database column
 * @property string description database column
 * @property SimpleORMapCollection entries has_many DatafieldEntryModel
 */
class Datafield extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'datafields';
        $config['has_many']['entries'] = array(
            'class_name' => 'DatafieldEntryModel'
        );
        parent::configure($config);
    }
}
