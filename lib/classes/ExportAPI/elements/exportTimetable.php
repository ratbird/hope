<?php

/**
 * export_newline - a linefeed element
 *
 * adds a linefeed to the export
 * 
 * XML:
 * 
 * <newline />
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
class exportTimetable extends exportElement {

    public $header;
    public $footer;
    public $timeMin = 8;
    public $timeMax = 22;
    public $timeSteps = 1;
    public $timeDisplaySteps = 1;
    public $timeFormat = "%H:%M";
    public $dayMin = 1208584800;
    public $dayMax = 1209830400;
    public $dayFormat = "%A";
    public $dateBegin = "date";
    public $dateEnd = "end_time";
    public $content;
    public $data;
    public $rich = false;

    public function preview($elementNo) {
        return "<br />";
    }

    public function edit($edit) {
        
    }

    public function load($xml) {
        parent::load($xml);
        $this->setTime($xml->time);
        $this->setDate($xml->date);
        $this->setDatabase($xml->database);
        $this->data = (string) $xml->data;
        $this->rich = $xml->data->attributes()->rich;
        $this->header = (string) $xml->header;
        $this->footer = (string) $xml->footer;
        $this->getFromSQL();
    }

    private function setTime($xml) {
        if ($xml->min)
            $this->timeMin = (int) $xml->min;
        if ($xml->max)
            $this->timeMax = (int) $xml->max;
        if ($xml->steps)
            $this->timeSteps = (float) $xml->steps;
        if ($xml->format)
            $this->timeFormat = (string) $xml->format;
    }

    private function setDate($xml) {
        if ($xml->from)
            $this->dayMin = strtotime($xml->from);
        if ($xml->to)
            $this->dayMax = strtotime($xml->to);
        if ($xml->format)
            $this->dayFormat = (string) $xml->format;
    }
    
    private function setDatabase($xml) {
        if ($xml->sql)
            $this->sql = (string) $xml->sql;
        if ($xml->begin)
            $this->dateBegin = (string) $xml->begin;
        if ($xml->end)
            $this->dateEnd = (string) $xml->end;
    }

    public function getDays() {
        return ceil(($this->dayMax - $this->dayMin) / 86400);
    }
    
    public function getTimes() {
        return ceil(($this->timeMax - $this->timeMin) + 1) / $this->timeSteps;
    }

    public function getTimeAxis() {
        for ($i = $this->timeMin; $i <= $this->timeMax; $i += $this->timeSteps) {
            $result[] = fmod($i, $this->timeDisplaySteps) == 0 ? strftime($this->timeFormat, (($i - 1) * 3600)) : "";
        }
        return $result;
    }

    public function getDayAxis() {
        for ($i = $this->dayMin; $i <= $this->dayMax; $i += 86400) {
            $result[] = strftime($this->dayFormat, $i);
        }
        return $result;
    }

    public function getFromSQL() {
        $db = DBManager::get();
        $sql = $this->addCondition($this->sql);
        $stmt = $db->query($sql);
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $day = ($result[$this->dateBegin] - $this->dayMin) / 86400;
            $beginOfDay = strtotime("midnight", $result[$this->dateBegin]);
            $dayTime = $result[$this->dateBegin] - $beginOfDay;
            $dayTimeEnd = $result[$this->dateEnd] - $beginOfDay;
            if ($dayTime / 3600 >= $this->timeMin && $dayTime / 3600 <= $this->timeMax) {
                $slot = (floor($dayTime / 3600 / $this->timeSteps)) - ($this->timeMin / $this->timeSteps);
                $slotend = (floor($dayTimeEnd / 3600 / $this->timeSteps)) - ($this->timeMin / $this->timeSteps) - 1;
                $this->day[$day][$slot]['content'] = $this->loadContentFromResult($result);
                $this->day[$day][$slot]['end'] = $slotend > $this->getTimes() ? $this->getTimes() :$slotend;
            }
        }
    }
    
    private function loadContentFromResult($result) {
        $this->result = $result;
        $data = str_replace('\n', "\n", $this->data);
        $data = preg_replace_callback("/#[A-z]+(#)*/", array($this, 'replaceParams'), $data);
        return htmlspecialchars($data);
    }
    
    private function replaceParams($hit) {
        $name = trim($hit[0], '#');
        if (array_key_exists($name, $this->result)) {
            return $this->result[$name];
        }
        return $hit[0];
    }

    private function addCondition($sql) {
        $groupBy = strrchr($sql, "GROUP BY");
        $sql = substr($sql, 0, strlen($groupBy) * (-1));
        if (stristr($sql, 'where') === false) {
            $sql .= " WHERE ";
        } else {
            $sql .= " AND ";
        }
        return $sql . $this->dateBegin . ' >= ' .  strtotime("midnight", $this->dayMin) . ' AND ' . $this->dateBegin . ' <= ' . (strtotime("tomorrow", $this->dayMax) - 1)." ".$groupBy;
    }

}
?>
