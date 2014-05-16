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
        $record = new CourseTopic();
        $db = DBManager::get();
        $sql = "
            SELECT *
            FROM `" .  $record->db_table . "`
                INNER JOIN themen_termine USING (issue_id)
            WHERE themen_termine.termin_id = ?
            ORDER BY priority ASC
        ";
        $st = $db->prepare($sql);
        $st->execute(array($termin_id));
        $ret = array();
        $c = 0;
        while($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $ret[$c] = new CourseTopic();
            $ret[$c]->setData($row, true);
            $ret[$c]->setNew(false);
            ++$c;
        }
        return $ret;
    }

    static public function findBySeminar_id($seminar_id)
    {
        return self::findBySQL("seminar_id = ? ORDER BY priority ", array($seminar_id));
    }

    function __construct($id = null)
    {
        $this->db_table = 'themen';
        parent::__construct($id);
    }
}