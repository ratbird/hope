<?

/*
DbCalendarWeek.class.php - 0.8.20020520
Klassen fuer Persoenlichen Terminkalender in Stud.IP.
Copyright (C) 2001 Peter Thienel <pthien@gmx.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

//****************************************************************************

require_once $RELATIVE_PATH_CALENDAR . '/lib/DbCalendarDay.class.php';

class DbCalendarWeek {

	var $wdays;     // Object[]
	var $kw;        // Kalenderwoche (String)
	var $ts;        // Timestamp bezogen auf Montag 12:00:00 Uhr (int)
	var $type;      // 5 für 5-Tage-Woche, 7 für gesamte Woche (int)
	
	// Konstruktor
	function DbCalendarWeek (&$calendar, $tmstamp, $type = "LONG", $restrictions = NULL, $sem_ids = NULL) {
		if($type == "SHORT") {
			$this->type = 5;
		} else {
			$this->type = 7;
		}
		
		// Berechnung des Timestamps für Montag 12:00:00 Uhr
		$timestamp = mktime(12, 0, 0, date("n", $tmstamp), date("j", $tmstamp), date("Y", $tmstamp), 0);
		$this->ts = $timestamp - 86400 * (strftime("%u", $timestamp) - 1);
		
		$this->kw = strftime("%W", $this->ts);
		
		for($i = 0; $i < $this->type; $i++) {
			$this->wdays[$i] = new DbCalendarDay($calendar, $this->ts + $i * 86400, NULL, $restrictions, $sem_ids);
		}
	}
	
	// public
	function getStart () {
		return mktime(0, 0, 0, date("n", $this->ts), date("j", $this->ts), date("Y", $this->ts));
	}
	
	// public
	function getEnd () {
		return mktime(0, 0, 0, date("n", $this->ts), date("j", $this->ts) + $this->type, date("Y", $this->ts)) - 1;
	}
	
	// private
	function getTs () {
		return $this->ts;
	}
	
	function getType () {
		return $this->type;
	}
	
	// public
	function serialisiere () {
		$size = sizeof($this->wdays);
		for ($i = 0;$i < $size;$i++)
			$ser .= 'i:' . $i . ';' . $this->wdays[$i]->serialisiere();
		
		// Achtung: kw ist hier ein String mit fester Länge 2!	
		$serialized = 'O:7:"db_week":4:{s:4:"type";i:' . $this->type . ';s:2:"ts";i:'
		            . $this->ts . ';s:2:"kw";s:2:"' . $this->kw . '";s:5:"wdays";a:'
								. $size . ':{' . $ser . '}}';
		return $serialized;
	}
	
	
/*	function bindSeminarEvents ($sem_ids = '', $restrictions = NULL) {
		return true;
	}
	*/
	/*
	function bindSeminarEvents ($sem_ids = '', $restrictions = NULL) {
		$have_events = FALSE;
		for ($i = 0; $i < $this->type; $i++) {
			if ($this->wdays[$i]->bindSeminarEvents($sem_ids, $restrictions))
				$have_events = TRUE;
		}
		
		return $have_events;
	}
	*/
} // class Week

?>