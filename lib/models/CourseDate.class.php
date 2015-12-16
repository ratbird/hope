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
 * @property User author belongs_to User
 * @property Course course belongs_to Course
 * @property SeminarCycleDate cycle belongs_to SeminarCycleDate
 * @property ResourceAssignment room_assignment has_one ResourceAssignment
 * @property SimpleORMapCollection topics has_and_belongs_to_many CourseTopic
 * @property SimpleORMapCollection statusgruppen has_and_belongs_to_many Statusgruppen
 * @property SimpleORMapCollection dozenten has_and_belongs_to_many User
 */

class CourseDate extends SimpleORMap
{
    /**
     * Returns course dates by issue id.
     *
     * @param String $issue_id Id of the issue
     * @return array with the associated dates
     */
    public static function findByIssue_id($issue_id)
    {
        return self::findBySQL("INNER JOIN themen_termine USING (termin_id)
            WHERE themen_termine.issue_id = ?
            ORDER BY date ASC",
            array($issue_id)
        );
    }

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
        $config['db_table'] = 'termine';
        $config['has_and_belongs_to_many']['topics'] = array(
            'class_name' => 'CourseTopic',
            'thru_table' => 'themen_termine',
            'order_by' => 'ORDER BY priority',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['has_and_belongs_to_many']['statusgruppen'] = array(
            'class_name' => 'Statusgruppen',
            'thru_table' => 'termin_related_groups',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['has_and_belongs_to_many']['dozenten'] = array(
            'class_name' => 'User',
            'thru_table' => 'termin_related_persons',
            'foreign_key' => 'termin_id',
            'thru_key' => 'range_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
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
        $config['has_one']['room_assignment'] = array(
            'class_name'  => 'ResourceAssignment',
            'foreign_key' => 'termin_id',
            'assoc_foreign_key' => 'assign_user_id',
            'on_delete' => 'delete',
        );
        $config['has_one']['room_request'] = array(
            'class_name'        => 'RoomRequest',
            'assoc_foreign_key' => 'termin_id',
            'on_delete'          => 'delete',
        );
        $config['default_values']['date_typ'] = 1;
        parent::configure($config);
    }

    /**
     * Adds a topic to this date.
     *
     * @param mixed $topic Topic definition (might be an id, an array or an
     *                     object)
     * @return number addition of all return values, false if none was called
     */
    public function addTopic($topic)
    {
        $topic = CourseTopic::toObject($topic);
        if (!$this->topics->find($topic->id)) {
            $this->topics[] = $topic;
            return $this->storeRelations('topics');
        }
    }

    /**
     * Removes a topic from this date.
     *
     * @param mixed $topic Topic definition (might be an id, an array or an
     *                     object)
     * @return number addition of all return values, false if none was called
     */
    public function removeTopic($topic)
    {
        $this->topics->unsetByPk(is_string($topic) ? $topic : $topic->id);
        return $this->storeRelations('topics');
    }

    /**
     * Returns the name of the assigned room for this date.
     *
     * @return String containing the room name
     */
    public function getRoomName()
    {
        return $this->room_assignment->resource_id
            ? $this->room_assignment->resource->getName()
            : $this['raum'];
    }

    /**
     * Returns the assigned room for this date as an object.
     *
     * @return mixed Either the object or null if no room is assigned
     */
    public function getRoom()
    {
        return $this->room_assignment->resource_id
            ? $this->room_assignment->resource
            : null;
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
            return strftime('%a., %x (' . _('ganztägig') . ')' , $this->date);
        }

        return strftime('%a., %x, %R', $this->date) . ' - ' . strftime('%R', $this->end_time);
    }

    /**
     * Converts a CourseDate Entry to a CourseExDate Entry
     * returns instance of the new CourseExDate or NULL
     *
     * @return Object CourseExDate
     */
    public function cancelDate()
    {
        $date = $this->toArray();
        unset($date['termin_id']);

        $ex_date = new CourseExDate();
        $ex_date->setData($date);
        if ($ex_date->store()) {
            $this->delete();
            return $ex_date;
        }
        return null;
    }

    /**
     * Stores this date.
     *
     * @return bool indicating whether the date was stored or not
     */
    public function store()
    {
        $old_entry = CourseDate::find($this->termin_id);
        if ($this->isNew() && !isset($this->metadate_id) && parent::store()) {
            log_event('SEM_ADD_SINGLEDATE', $this->range_id, $this->getFullname());
            return true;
        }
        if (!$this->isNew() && !isset($this->metadate_id) && parent::store()) {
            if ($old_entry->date != $this->date || $old_entry->end_time != $this->end_time) {
                log_event('SINGLEDATE_CHANGE_TIME', $this->range_id, $old_entry->getFullname(), $old_entry->getFullname() . ' -> ' . $this->getFullname());
            }
            return true;
        }
        if (parent::store()) {
            return true;
        }
        return false;
    }
}