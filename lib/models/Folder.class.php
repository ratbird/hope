<?php
/**
 * Folder.class.php - model class for table folder
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.1
 *
 * @property string institut_id database column
 * @property string id alias column for institut_id
 * @property string name database column
 * @property string fakultaets_id database column
 * @property string strasse database column
 * @property string plz database column
 * @property string url database column
 * @property string telefon database column
 * @property string email database column
 * @property string fax database column
 * @property string type database column
 * @property string modules database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string lit_plugin_name database column
 * @property string srienabled database column
 * @property string lock_rule database column
 * @property string is_fak computed column
 * @property SimpleORMapCollection members has_many InstituteMember
 * @property SimpleORMapCollection home_courses has_many Course
 * @property SimpleORMapCollection sub_institutes has_many Institute
 * @property Institute faculty belongs_to Institute
 * @property SimpleORMapCollection courses has_and_belongs_to_many Course
 */

class Folder extends SimpleORMap
{
    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'folder';
        $this->additional_fields['is_fak']['get'] = function($me) {return $me->fakultaets_id == $me->institut_id;};
        $this->has_many = array(
            'files' => array(
                'class_name' => 'StudipDocument',
                'assoc_func' => 'findByInstitute',
                'on_delete' => 'delete',
                'on_store' => 'store'
            )
        );
        parent::__construct($id);
    }

}
