<?php
/**
 * DatafieldEntryModel
 * model class for table datafields_entries
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Andr� Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string datafield_id database column
 * @property string range_id database column
 * @property string content database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string sec_range_id database column
 * @property string name computed column
 * @property string id computed column read/write
 * @property Datafield datafield belongs_to Datafield
 */

class DatafieldEntryModel extends SimpleORMap
{
    /**
     * returns datafields belonging to given model
     * if a datafield entry not exists yet, a new DatafieldEntryModel is returned
     * second param filters for a given datafield id
     *
     * @param SimpleORMap $model Course,Institute,User,CourseMember or InstituteMember
     * @param string $datafield_id
     * @return array of DatafieldEntryModel
     */
    public static function findByModel(SimpleORMap $model, $datafield_id = null)
    {
        $mask = array("user" => 1, "autor" => 2, "tutor" => 4, "dozent" => 8, "admin" => 16, "root" => 32);

        if (is_a($model, "Course")) {
            $object_class = SeminarCategories::GetByTypeId($model->status)->id;
            $object_type = 'sem';
            $range_id = $model->getId();
        } elseif(is_a($model, "Institute")) {
            $object_class = $model->type;
            $object_type = 'inst';
            $range_id = $model->getId();
        } elseif(is_a($model, "User")) {
            $object_class = $mask[$model->perms];
            $object_type = 'user';
            $range_id = $model->getId();
        } elseif(is_a($model, "CourseMember")) {
            $object_class = $mask[$model->status];
            $object_type = 'usersemdata';
            $range_id = $model->user_id;
            $sec_range_id = $model->seminar_id;
        } elseif(is_a($model, "InstituteMember")) {
            $object_class = $mask[$model->inst_perms];
            $object_type = 'userinstrole';
            $range_id = $model->user_id;
            $sec_range_id = $model->institut_id;
        }

        if (!$object_type) {
            throw new InvalidArgumentException('Wrong type of model: ' . get_class($model));
        }
        if ($datafield_id !== null) {
            $one_datafield = " AND a.datafield_id = " .DBManager::get()->quote($datafield_id);
        }
        $query = "SELECT a.*, b.*,a.datafield_id,b.datafield_id as isset_content ";
        $query .= "FROM datafields a LEFT JOIN datafields_entries b ON (a.datafield_id=b.datafield_id AND range_id = ? AND sec_range_id = ?) ";
        $query .= "WHERE object_type = ? AND ((object_class & ?) OR object_class IS NULL) $one_datafield ORDER BY object_class, priority";

        $st = DBManager::get()->prepare($query);
        $st->execute(array(
            (string) $range_id,
            (string) $sec_range_id,
            $object_type,
            (int) $object_class
        ));
        $ret = array();
        $c = 0;
        $df_entry = new DatafieldEntryModel();
        $df = new Datafield();
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $ret[$c] = clone $df_entry;
            $ret[$c]->setData($row, true);
            if (!$row['isset_content']) {
                $ret[$c]->setValue('range_id', (string)$range_id);
                $ret[$c]->setValue('sec_range_id', (string)$sec_range_id);
            }
            $ret[$c]->setNew(!$row['isset_content']);
            $cloned_df = clone $df;
            $cloned_df->setData($row, true);
            $cloned_df->setNew(false);
            $ret[$c]->setValue('datafield', $cloned_df);
            ++$c;
        }
        return $ret;
    }

    function __construct($id = array())
    {
        $this->db_table = 'datafields_entries';
        $this->belongs_to = array('datafield' => array('class_name' => 'Datafield',
                                                        'foreign_key' => 'datafield_id'));
        $df_getter = function ($record, $field) { return $record->getRelationValue('datafield', $field);};
        $this->additional_fields['name'] = array('get' => $df_getter);
        parent::__construct($id);
    }

    /**
     * returns matching "old-style" DataFieldEntry object
     *
     * @return DataFieldEntry
     */
    function getTypedDatafield()
    {
        $structure = new DataFieldStructure($this->datafield->toArray());
        $range_id = $this->sec_range_id ? array($this->range_id, $this->sec_range_id) : $this->range_id;
        $df = DataFieldEntry::createDataFieldEntry($structure, $range_id, $this->getValue('content'));
        $self = $this;
        $observer =
            function ($event, $object, $user_data) use ($self)
            {
                if ($user_data['changed']) {
                    $self->restore();
                }
            };
        NotificationCenter::addObserver($observer, '__invoke', 'DatafieldDidUpdate', $df);
        return $df;
    }
}
