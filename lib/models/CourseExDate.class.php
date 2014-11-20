<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author     Rasmus Fuhse <fuhse@data-quest.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string termin_id database column
 * @property string id alias column for termin_id
 * @property string range_id database column
 * @property string autor_id database column
 * @property string content database column
 * @property string description database column
 * @property string date database column
 * @property string end_time database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string date_typ database column
 * @property string topic_id database column
 * @property string raum database column
 * @property string metadate_id database column
 * @property string resource_id database column
 * @property string topics computed column
 * @property string statusgruppen computed column
 * @property string dozenten computed column
 * @property User author belongs_to User
 * @property Course course belongs_to Course
 * @property SeminarCycleDate cycle belongs_to SeminarCycleDate
 */

class CourseExDate extends SimpleORMap {

    static public function findBySeminar_id($seminar_id)
    {
        return self::findByRange_id($seminar_id);
    }

    static public function findByRange_id($seminar_id, $order_by = "ORDER BY date")
    {
        return parent::findByRange_id($seminar_id, $order_by);
    }

    protected static function configure($conf = array())
    {
        $config = array();
        $config['db_table'] = 'ex_termine';
        $config['belongs_to']['author'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'autor_id'
        );
        $config['belongs_to']['course'] = array(
            'class_name'  => 'Course',
            'foreign_key' => 'range_id'
        );
        $config['belongs_to']['cycle'] = array(
            'class_name'  => 'SeminarCycleDate',
            'foreign_key' => 'metadate_id'
        );
        $dummy_relation = function () { return new SimpleCollection(); };
        $config['additional_fields']['topics']['get'] = $dummy_relation;
        $config['additional_fields']['statusgruppen']['get'] = $dummy_relation;
        $config['additional_fields']['dozenten']['get'] = $dummy_relation;
        $config['default_values']['date_typ'] = 1;
        parent::configure($config);
    }

    public function getRoomName()
    {
        return "";
    }

    public function getRoom()
    {
        return null;
    }

    public function getTypeName() {
        global $TERMIN_TYP;
        return $TERMIN_TYP[$this->date_typ]['name'];
    }

    public function getFullname($format = 'default') {
        if ($this->date) {
            if ($format === 'default') {
                if ((($this->end_time - $this->date) / 60 / 60) > 23) {
                    return strftime('%a., %x' . ' (' . _('ganztägig') . ')' , $this->date) . " (" . _("fällt aus") . ")";
                } else {
                    return strftime('%a., %x, %R', $this->date) . ' - ' . strftime('%R', $this->end_time) . " (" . _("fällt aus") . ")";
                }
            }
        } else {
            return '';
        }
    }

}
