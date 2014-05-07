<?php
/**
 * UserStudyCourse.class.php
 * model class for table user_studiengang
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string user_id database column
 * @property string studiengang_id database column
 * @property string semester database column
 * @property string abschluss_id database column
 * @property string degree_name computed column read/write
 * @property string studycourse_name computed column read/write
 * @property string id computed column read/write
 * @property User user belongs_to User
 * @property Degree degree belongs_to Degree
 * @property StudyCourse studycourse belongs_to StudyCourse
 */
class UserStudyCourse extends SimpleORMap
{

    private $additional_data = array();

    public static function findByUser($user_id)
    {
        $db = DbManager::get();
        $st = $db->prepare("SELECT user_studiengang.*, abschluss.name as degree_name,
                            studiengaenge.name as studycourse_name
                            FROM user_studiengang
                            LEFT JOIN abschluss USING (abschluss_id)
                            LEFT JOIN studiengaenge USING (studiengang_id)
                            WHERE user_id = ? ORDER BY studycourse_name");
        $st->execute(array($user_id));
        $ret = array();
        $c = 0;
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $ret[$c] = new self;
            $ret[$c]->setData($row, true);
            $ret[$c]->setNew(false);
            ++$c;
        }
        return $ret;
    }

    public static function findByStudyCourseAndDegree($study_course_id, $degree_id)
    {
        return self::findBySql("studiengang_id = ? AND abschluss_id = ?", array($study_course_id, $degree_id));
    }

    function __construct($id = array())
    {
        $this->db_table = 'user_studiengang';
        $this->belongs_to = array(
                'user' => array('class_name' => 'User',
                                'foreign_key' => 'user_id'),
                'degree' => array('class_name' => 'Degree',
                                'foreign_key' => 'abschluss_id'),
                'studycourse' => array('class_name' => 'StudyCourse',
                                'foreign_key' => 'studiengang_id')
        );
        $this->additional_fields['degree_name']['get'] = 'getAdditionalValue';
        $this->additional_fields['studycourse_name']['get'] = 'getAdditionalValue';
        $this->additional_fields['degree_name']['set'] = 'setAdditionalValue';
        $this->additional_fields['studycourse_name']['set'] = 'setAdditionalValue';
        $this->registerCallback('before_initialize', 'initializeAdditionalData');
        parent::__construct($id);
    }

    function initializeAdditionalData()
    {
        $this->additional_data = array();
    }

    function getAdditionalValue($field)
    {
        if (!array_key_exists($this->additional_data[$field])) {
            list($relation, $relation_field) = explode('_', $field);
            $this->setAdditionalValue($field, $this->getRelationValue($relation, $relation_field));
        }
        return $this->additional_data[$field];
    }

    function setAdditionalValue($field, $value)
    {
        return $this->additional_data[$field] = $value;
    }

}
