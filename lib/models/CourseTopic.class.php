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

class CourseTopic extends SimpleORMap {

    static public function findByTermin_id($termin_id)
    {
        return self::findBySQL("INNER JOIN themen_termine USING (issue_id)
            WHERE themen_termine.termin_id = ?
            ORDER BY priority ASC",
            array($termin_id)
        );
    }

    static public function findBySeminar_id($seminar_id)
    {
        return self::findBySQL("seminar_id = ? ORDER BY priority ", array($seminar_id));
    }

    static public function findByTitle($seminar_id, $name)
    {
        $topics = self::findBySQL("seminar_id = ? AND title = ?", array($seminar_id, $name));
        return $topics[0];
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'themen';
        $config['has_many']['dates'] = array(
            'class_name' => 'CourseDate',
            'assoc_func' => 'findByIssue_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['belongs_to']['folder'] = array(
            'class_name' => 'DocumentFolder',
            'assoc_foreign_key' => "range_id"
        );
        parent::configure($config);
    }

    public function createFolder()
    {
        $folder = $this->folder;
        if ($folder) {
            return $folder;
        } else {
            $folder = new DocumentFolder();
            $folder['range_id'] = $this->getId();
            $folder['name'] = $this['title'];
            $folder['description'] = $this['description'];
            $folder['priority'] = $this['priority'];
            $folder['seminar_id'] = $this['seminar_id'];
            $folder['user_id'] = $GLOBALS['user']->id;
            $folder['permission'] = 15;
            $folder->store();
            return $folder;
        }
    }

}