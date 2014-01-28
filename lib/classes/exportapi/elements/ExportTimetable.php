<?php

/**
 * exportTimetable - a timetable element
 *
 * adds a timetable to the export
 * 
 * XML:
 * 
 *         <timetable>
 *          <time>
 *              <min>8</min> //sets the display start
 *              <max>21</max> //sets the display end
 *              <steps>0.5</steps> //sets the display steps
 *              <format>%H:%M</format>  //sets the display format
 *          </time>
 *          <date>
 *              <from>$0</from> // Startdate
 *              <to>$1</to>  // Enddate
 *              <format>%A</format> // Format in timetable       
 *          </date>
 *          <database> // sets the sql for the datacollection
 *              <sql>SELECT *, COUNT( * ) AS teilnehmer FROM resources_objects o
 *                  JOIN resources_assign a USING (resource_id)
 *                  LEFT JOIN termine t ON t.termin_id = a.assign_user_id
 *                  LEFT JOIN seminare s ON t.range_id = s.seminar_id
 *                  LEFT JOIN seminar_user u USING (seminar_id) 
 *                  LEFT JOIN auth_user_md5 au USING(user_id)
 *                  LEFT JOIN seminar_user u2 USING (seminar_id) 
 *                  WHERE o.name = '$2' AND u.status = 'dozent'
 *                  GROUP BY termin_id
 *              </sql>
 *              <begin>begin</begin> // the field of the sql which marks the begin
 *              <end>end</end> // the field of the sql which marks the end
 *          </database>
 *          <data >#VeranstaltungsNummer\n#Name\n#Nachname\nTeilnehmer: #teilnehmer</data> // Output in a field #XXX takes field XXX of the query
 *          <header>Raumbelegungsplan</header> // Output header
 *          <footer>Informationsfeld</footer>   // Output footer
 *      </timetable>
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
class exportTimetable extends ExportElement {

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

    /**
     * {@inheritdoc }
     */
    public function preview($elementNo) {
        return "<br />";
    }

    /**
     * {@inheritdoc }
     */
    public function edit($edit) {
        
    }

    /**
     * {@inheritdoc }
     */
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

    /**
     * Returns the number of days the timetable contains
     * 
     * @return int number of days
     */
    public function getDays() {
        return ceil(($this->dayMax - $this->dayMin) / 86400);
    }

    /**
     * Returns the number of timeslots of the timetable
     * 
     * @return int number of timeslots
     */
    public function getTimes() {
        return ceil(($this->timeMax - $this->timeMin) + 1) / $this->timeSteps;
    }

    /**
     * Returns an array of all timeslot headers
     * 
     * @return array Array of timeslot headers
     */
    public function getTimeAxis() {
        for ($i = $this->timeMin; $i <= $this->timeMax; $i += $this->timeSteps) {
            $result[] = fmod($i, $this->timeDisplaySteps) == 0 ? strftime($this->timeFormat, (($i - 1) * 3600)) : "";
        }
        return $result;
    }

    /**
     * Returns an array of all dayslot headers
     * 
     * @return array of dayslot headers
     */
    public function getDayAxis() {
        for ($i = $this->dayMin; $i <= $this->dayMax; $i += 86400) {
            $result[] = strftime($this->dayFormat, $i);
        }
        return $result;
    }

    /**
     * Fetches all occuances by a sql statement
     */
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
                $this->day[$day][$slot]['end'] = $slotend > $this->getTimes() ? $this->getTimes() : $slotend;
            }
        }
    }

    /**
     * Sets the time of the timetable correctly by xml
     */
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

    /**
     * Sets the date of the timetable correctly by xml
     */
    private function setDate($xml) {
        if ($xml->from)
            $this->dayMin = strtotime($xml->from);
        if ($xml->to)
            $this->dayMax = strtotime($xml->to);
        if ($xml->format)
            $this->dayFormat = (string) $xml->format;
    }

    /**
     * Database settings by xml
     */
    private function setDatabase($xml) {
        if ($xml->sql)
            $this->sql = (string) $xml->sql;
        if ($xml->begin)
            $this->dateBegin = (string) $xml->begin;
        if ($xml->end)
            $this->dateEnd = (string) $xml->end;
    }

    /**
     * Load all content from the resultset and replace params
     */
    private function loadContentFromResult($result) {
        $this->result = $result;
        $data = str_replace('\n', "\n", $this->data);
        $data = preg_replace_callback("/#[A-z]+(#)*/", array($this, 'replaceParams'), $data);
        return htmlspecialchars($data);
    }

    /**
     * Replace params function
     */
    private function replaceParams($hit) {
        $name = trim($hit[0], '#');
        if (array_key_exists($name, $this->result)) {
            return $this->result[$name];
        }
        return $hit[0];
    }

    /**
     * Adds a condition to a sql statement
     */
    private function addCondition($sql) {
        $groupBy = strrchr($sql, "GROUP BY");
        $sql = substr($sql, 0, strlen($groupBy) * (-1));
        if (stristr($sql, 'where') === false) {
            $sql .= " WHERE ";
        } else {
            $sql .= " AND ";
        }
        return $sql . $this->dateBegin . ' >= ' . strtotime("midnight", $this->dayMin) . ' AND ' . $this->dateBegin . ' <= ' . (strtotime("tomorrow", $this->dayMax) - 1) . " " . $groupBy;
    }
}

?>
