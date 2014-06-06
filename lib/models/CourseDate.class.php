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
 */

class CourseDate extends SimpleORMap {

    static public function findByIssue_id($issue_id)
    {
        return self::findBySQL("INNER JOIN themen_termine USING (termin_id)
            WHERE themen_termine.issue_id = ?
            ORDER BY date ASC",
            array($issue_id)
        );
    }

    static public function findBySeminar_id($seminar_id)
    {
        return self::findByRange_id($seminar_id);
    }

    static public function findByRange_id($seminar_id, $order_by = "ORDER BY date")
    {
        return parent::findByRange_id($seminar_id, $order_by);
    }

    static public function findByMetadate_id($metadate_id, $order_by = "ORDER BY date")
    {
        return parent::findByRange_id($metadate_id, $order_by);
    }

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
            'on_store' => 'store'
        );
        parent::configure($config);
    }

    public function addTopic($topic)
    {
        $topic = CourseTopic::toObject($topic);
        if (!$this->topics->find($topic->id)) {
            $this->topics[] = $topic;
            return $this->storeRelations('topics');
        }
    }

    public function removeTopic($topic)
    {
        $this->topics->unsetByPk(is_string($topic) ? $topic : $topic->id);
        return $this->storeRelations('topics');
    }

    public function getRoomName()
    {
        return $this->room_assignment->resource_id ? $this->room_assignment->resource->getName() : $this['raum'];
    }

    public function getRoom()
    {
        return $this->room_assignment->resource_id ? $this->room_assignment->resource : null;
    }

}