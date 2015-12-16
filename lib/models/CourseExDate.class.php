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

class CourseExDate extends SimpleORMap
{

    /**
     * Returns course dates by course id
     *
     * @param String $seminar_id Id of the course
     * @return array with the associated dates
     */
    public static function findBySeminar_id($seminar_id)
    {
        return self::findByRange_id($seminar_id);
    }

    /**
     * Return course dates by range id (which is in many cases the course id)
     *
     * @param String $seminar_id Id of the course
     * @param String $order_by   Optional order definition
     * @return array with the associated dates
     */
    public static function findByRange_id($seminar_id, $order_by = 'ORDER BY date')
    {
        return parent::findByRange_id($seminar_id, $order_by);
    }

    /**
     * Configures this model.
     *
     * @param Array $config Configuration array
     */
    protected static function configure($config = array())
    {
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
        $dummy_null = function () { return null; };
        $config['additional_fields']['topics']['get'] = $dummy_relation;
        $config['additional_fields']['statusgruppen']['get'] = $dummy_relation;
        $config['additional_fields']['dozenten']['get'] = $dummy_relation;
        $config['additional_fields']['room_assignment']['get'] = $dummy_null;
        $config['additional_fields']['room_request']['get'] = $dummy_null;
        $config['default_values']['date_typ'] = 1;
        parent::configure($config);
    }

    /**
     * Returns the name of the assigned room for this date.
     *
     * @return String that is always empty
     */
    public function getRoomName()
    {
        return '';
    }

    /**
     * Returns the assigned room for this date as an object.
     *
     * @return null. always. canceled dates need no room.
     */
    public function getRoom()
    {
        return null;
    }

    /**
     * Returns the name of the type of this date.
     *
     * @param String containing the type name
     */
    public function getTypeName()
    {
        return $GLOBALS['TERMIN_TYP'][$this->date_typ]['name'];
    }

    /**
     * Returns the full qualified name of this date.
     *
     * @param String $format Optional format type (only 'default' is
     *                       supported by now)
     * @return String containing the full name of this date.
     */
    public function getFullname($format = 'default')
    {
        if (!$this->date || $format !== 'default') {
            return '';
        }

        if (($this->end_time - $this->date) / 60 / 60 > 23) {
            return strftime('%a., %x' . ' (' . _('ganztägig') . ')' , $this->date) . " (" . _("fällt aus") . ")";
        }

        return strftime('%a., %x, %R', $this->date) . ' - ' . strftime('%R', $this->end_time) . " (" . _("fällt aus") . ")";
    }

    /**
     * Converts a CourseExDate Entry to a CourseDate Entry
     * returns instance of the new CourseDate or NULL
     * @return Object CourseDate
     */
    public function unCancelDate()
    {
        $ex_date = $this->toArray();

        //REMOVE content
        unset($ex_date['content']);
        //REMOVE termin_id from ex_termin
        unset($ex_date['termin_id']);

        $date = new CourseDate();
        $date->setData($ex_date);
        if ($date->store()) {
            log_event('SEM_UNDELETE_SINGLEDATE', $this->termin_id, $this->range_id, 'Cycle_id: ' . $this->metadate_id);
            $this->delete();
            return $date;
        }
        return null;
    }
}
