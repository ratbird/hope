<?php
# Lifter010: TODO
/**
 * SemesterData.class.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Mark Sievers <msievers@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'lib/classes/Semester.class.php';

class SemesterData
{
    /**
     * get an instance of this class
     *
     * @param boolean $refresh_cache
     * @return object SemesterData
     */
    static function GetInstance($refresh_cache = false)
    {
        static $semester_object;

        if ($refresh_cache) {
            $semester_object = null;
        }
        if (is_object($semester_object)) {
            return $semester_object;
        } else {
            $semester_object = new SemesterData();
            return $semester_object;
        }
    }

    static function GetSemesterArray()
    {
        static $all_semester;

        if (is_null($all_semester)) {
            $semester = SemesterData::GetInstance();
            $all_semester = $semester->getAllSemesterData();
            array_unshift($all_semester,0);
            $all_semester[0] = array("name" => sprintf(_("vor dem %s"),$all_semester[1]['name']),'past' => true);
        }
        return $all_semester;
    }

    static function GetSemesterIndexById($semester_id)
    {
        $index = false;
        foreach(SemesterData::GetSemesterArray() as $i => $sem){
            if($sem['semester_id'] == $semester_id) {
                $index = $i;
                break;
            }
        }
        return $index;
    }

    static function GetSemesterIdByIndex($semester_index)
    {
        $old_style_semester = SemesterData::GetSemesterArray();
        return isset($old_style_semester[$semester_index]['semester_id']) ? $old_style_semester[$semester_index]['semester_id'] : null;
    }

    static function GetSemesterIdByDate($timestamp)
    {
        $one_semester = SemesterData::GetInstance()->getSemesterDataByDate($timestamp);
        return isset($one_semester['semester_id']) ? $one_semester['semester_id'] : null;
    }

    static function GetSemesterSelector($select_attributes = null, $default = 0, $option_value = 'semester_id', $include_all = true)
    {
        $semester = SemesterData::GetSemesterArray();
        unset($semester[0]);
        if ($include_all) $semester[] = array('name' => _("alle"), 'semester_id' => 0);
        $semester = array_reverse($semester, true);
        if (!$select_attributes['name']) $select_attributes['name'] = 'sem_select';
        $out = chr(10) . '<select ';
        foreach ($select_attributes as $key => $value) {
            $out .= ' ' . $key .'="'.$value.'" ';
        }
        $out .= '>';
        foreach($semester as $sem_key => $one_sem){
            $one_sem['key'] = $sem_key;
            $out .= "\n<option value=\"{$one_sem[$option_value]}\" "
                . ($one_sem[$option_value] == $default ? "selected" : "")
                . ">" . htmlReady($one_sem['name']) . "</option>";
        }
        $out .= chr(10) . '</select>';
        return $out;
    }

    function getAllSemesterData()
    {
        $ret = array();
        foreach (Semester::getAll() as $semester) {
            $ret[] = $semester->toArray();
        }
        return $ret;
    }

    function deleteSemester($semester_id)
    {
        $ret = Semester::find($semester_id)->delete();
        Semester::getAll(true);
        return $ret;
    }

    function getSemesterData($semester_id)
    {
        $ret = Semester::find($semester_id);
        return $ret ? $ret->toArray() : false;
    }

    function getSemesterDataByDate($timestamp)
    {
        $ret = Semester::findByTimestamp($timestamp);
        return $ret ? $ret->toArray() : false;
    }

    function getCurrentSemesterData()
    {
        $ret = Semester::findCurrent();
        return $ret ? $ret->toArray() : false;
    }

    function getNextSemesterData($timestamp = false)
    {
        $ret = Semester::findNext($timestamp);
        return $ret ? $ret->toArray() : false;
    }

    function insertNewSemester($semesterdata)
    {
        $semester = new Semester();
        $semester->setData(remove_magic_quotes($semesterdata));
        if ($semester->store()) {
            Semester::getall(true);
            return $semester->getId();
        } else {
            return false;
        }
    }

    /**
     * update an existing semester
     *
     * @param array() $semesterdata
     * @return boolean
     */
    function updateExistingSemester($semesterdata)
    {
        $semester = Semester::find($semesterdata['semester_id']);
        if ($semester) {
            $semester->setData(remove_magic_quotes($semesterdata));
            if ($semester->store()) {
                Semester::getall(true);
                return true;
            } else {
                return false;
            }
        }
    }
}
