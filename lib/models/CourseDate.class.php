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
        return self::findBySQL("range_id = ? ORDER BY date ", array($seminar_id));
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'termine';
        $config['has_many']['topics'] = array(
            'class_name' => 'CourseTopic',
            'assoc_func' => 'findByTermin_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['has_many']['statusgruppen'] = array(
            'class_name' => 'Statusgruppen',
            'assoc_func' => 'findByTermin_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['has_many']['dozenten'] = array(
            'class_name' => 'User',
            'assoc_func' => 'findDozentenByTermin_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        parent::configure($config);
    }

    public function addTopic($topic)
    {
        if (!is_a($topic, "CourseTopic")) {
            $topic = CourseTopic::find($topic);
        }
        if (!$topic) {
            throw new Exception("Thema existiert nicht.");
        }
        $statement = DBManager::get()->prepare("
            INSERT IGNORE INTO themen_termine
            SET issue_id = :issue_id,
                termin_id = :termin_id
        ");
        return $statement->execute(array(
            'issue_id' => $topic->getId(),
            'termin_id' => $this->getId()
        ));
    }

    public function removeTopic($topic)
    {
        if (!is_a($topic, "CourseTopic")) {
            $topic = CourseTopic::find($topic);
        }
        if (!$topic) {
            throw new Exception("Thema existiert nicht.");
        }
        $statement = DBManager::get()->prepare("
            DELETE FROM themen_termine
            WHERE issue_id = :issue_id
                AND termin_id = :termin_id
        ");
        return $statement->execute(array(
            'issue_id' => $topic->getId(),
            'termin_id' => $this->getId()
        ));
    }

    public function getResourceInfo()
    {
        $sd = new Singledate($this->getId());
        $resource_id = $sd->getResourceID();
        $room_name = $sd->getRoom() ?: $this['raum'];
        return array($room_name, $resource_id);
    }

}