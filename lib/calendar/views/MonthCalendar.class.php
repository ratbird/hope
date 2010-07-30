<?
/**
* MonthCalendar.class.php
* 
* 
*
* @author		Peter Thienel <thienel@data-quest.de>,
*           Suchi & Berg GmbH <info@data-quest.de>
* @version		$Id: MonthCalendar.class.php,v 1.2 2009/07/03 10:47:35 anoack Exp $
* @access		public
* @modulegroup	CalendarViews
* @module		MonthCalendar
* @package	calendar
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// week.inc.php
//
// Copyright (c) 2006 Peter Tienel <thienel@data-quest.de>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+
 

require_once($ABSOLUTE_PATH_STUDIP . $RELATIVE_PATH_CALENDAR . "/lib/CalendarMonth.class.php");

class MonthCalendar {
	
	var $instances;
	var $atime;
	var $amonth;
	var $show_navigation;
	var $show_weeknumbers;
	var $global_time_variable;
	var $day_link;

	function MonthCalendar ($atime = NULL) {
		static $instances = 0;
		
		$this->instances = $instances;
		$instances++;
		
		if (is_null($atime)) {
			$this->atime = time();
		} else {
			$this->atime = $atime;
		}
		$this->amonth =& new CalendarMonth($this->atime);
		$this->showNavigation();
		$this->showWeekNumbers();
		$this->setGlobalTimeVariable('');
	}
	
	function showNavigation ($show = TRUE) {
		if (is_bool($show) && $show) {
			$this->show_navigation = TRUE;
		} else {
			$this->show_navigation = FALSE;
		}
	}
	
	function showWeekNumbers ($show = TRUE) {
		if (is_bool($show) && $show) {
			$this->show_weeknumbers = TRUE;
		} else {
			$this->show_weeknumbers = FALSE;
		}
	}
	
	
	/**
	* Sets the name of a variable 
	*
	* @access public
	* @param string $week_link the the link
	*/
	function setGlobalTimeVariable ($time_variable) {
		$this->globalTimeVariable = $time_variable;
	}
	
	
	/**
	* Sets the link behind the days
	*
	* @access public
	* @param string $week_link the the link
	*/
	function setDayLink ($day_link) {
		$this->day_link = $day_link;
	}
	
	
	/**
	* Sets the link behind the week numbers
	*
	* @access public
	* @param string $week_link the the link
	*/
	function setWeekLink ($week_link) {
		$this->week_link = $week_link;
	}	
		
	
	function toString () {
		$now = mktime(12, 0, 0, date('n', time()), date('j', time()), date('Y', time()), 0);
		$width = '25';
		$height = '25';
	
		$ret = "<table valign=\"top\" class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\">\n";
		$ret .= "<tr><td class=\"steelgroup0\" align=\"center\">\n";
		$ret .= "<table border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";
		$ret .= "<tr>\n";
		
		// navigation arrows left
		$ret .= "<td align=\"center\" class=\"steelgroup0\" valign=\"top\">\n";
		if (! $this->show_navigation) {
			$ret .= '&nbsp;';
		} else {
			$ret .= "<a href=\"$href$ptime&m_cal_time[{$this->instances}]=";
			$ret .= mktime(0, 0, -1, $amonth->mon, 15, $amonth->year - 1) . "\">";
			$ret .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous_double_small.gif\"";
			$ret .= tooltip(_("ein Jahr zurück")) . "></a>";
			$ret .= "<a href=\"$href$ptime&imt=" . ($amonth->getStart() - 1) . "\">";
			$ret .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous_small.gif\"";
			$ret .= tooltip(_("einen Monat zurück")) . "></a>\n";
		}
		$ret .= "</td>\n";
	
		// month and year
		$ret .= '<td class="precol1w" colspan="'. ((! $this->show_weeknumbers)? 5 : 6). '" align="center">';
		$ret .= sprintf("%s %s</td>\n",
				htmlentities(strftime("%B", $amonth->getStart()), ENT_QUOTES), $amonth->getYear());
	
		// navigation arrows right
		$ret .= '<td class="steelgroup0" align="center" valign="top">';
		if (! $this->show_navigation) {
			$ret .= '&nbsp;';
		} else {
			$ret .=	"<a href=\"$href$ptime&imt=" . ($amonth->getEnd() + 1) . '">';
			$ret .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_next_small.gif\"";
			$ret .= tooltip(_("einen Monat vor")) . "></a>";
			$ret .= "<a href=\"$href$ptime&imt=";
			$ret .= (mktime(0, 0, 1, $amonth->mon, 1, $amonth->year + 1)) . '">';
			$ret .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_next_double_small.gif\"";
			$ret .= tooltip(_("ein Jahr vor")) . "></a>\n";
		}
		$ret .= "</td></tr>\n";
	
		// weekdays
		$ret .= "<tr>\n";
		$day_names_german = array('MO', 'DI', 'MI', 'DO', 'FR', 'SA', 'SO');
		foreach ($day_names_german as $day_name_german)
			$ret .= "<td align=\"center\" class=\"precol2w\" width=\"$width\">" . wday("", "SHORT", $day_name_german) . "</td>\n";
		if ($this->show_weeknumbers)
			$ret .= "<td class=\"precol2w\" width=\"$width\">&nbsp;</td>";
		$ret .= "</tr>\n</table></td></tr>\n<tr><td class=\"blank\">";
		$ret .= "<table class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">";
	
		// Im Kalenderblatt ist links oben immer Montag. Das muss natuerlich nicht der
		// Monatserste sein. Es muessen evtl. noch Tage des vorangegangenen Monats
		// am Anfang und des folgenden Monats am Ende angefuegt werden.
		$adow = date('w', $amonth->getStart());
		if ($adow == 0)
			$adow = 6;
		else
			$adow--;
		$first_day = $amonth->getStart() - $adow * 86400 + 43200;
		// Ist erforderlich, um den Maerz richtig darzustellen
		// Ursache ist die Sommer-/Winterzeit-Umstellung
		$cor = 0;
		if ($amonth->mon == 3)
			$cor = 1;
			
		$last_day = ((42 - ($adow + date("t", $amonth->getStart()))) % 7 + $cor) * 86400
		 	        + $amonth->getEnd() - 43199;
							
		for ($i = $first_day, $j = 0; $i <= $last_day; $i += 86400, $j++) {
			$aday = date("j", $i);
			// Tage des vorangehenden und des nachfolgenden Monats erhalten andere
			// style-sheets
			$style = '';
			if (($aday - $j - 1 > 0) || ($j - $aday  > 6))
				$style = 'light';
			
			// Feiertagsueberpruefung
			$hday = holiday($i);
			
			if ($j % 7 == 0)
				$ret .= '<tr>';
			
			if (abs($now - $i) < 43199 && !((! $this->show_navigation) && $style == 'light'))
				$ret .= "<td class=\"celltoday\" ";
			elseif (date('m', $i) != $amonth->mon)
				$ret .= "<td class=\"lightmonth\"";
			else
				$ret .= "<td class=\"month\"";
				
			$ret .= "align=\"center\" width=\"$width\" height=\"$height\">";
			
			$js_inc = '';
			if (is_array($js_include)) {
				$js_inc = " onClick=\"{$js_include['function']}(";
				if (sizeof($js_include['parameters']))
					$js_inc .= implode(", ", $js_include['parameters']) . ", ";
				$js_inc .= "'" . date('m', $i) . "', '$aday', '" . date('Y', $i) . "')\"";
			}
			if (abs($atime - $i) < 43199 )
				$aday = "<span style=\"border-width: 2px; border-style: solid; "
						. "border-color: #DD0000; padding: 2px;\">$aday</span>";
	
			if (($j + 1) % 7 == 0) {
				if ((! $this->show_navigation) && $style == 'light') {
					$ret .= '&nbsp;'; // Tag gehört nicht zu diesem Monat
				} else {
					$ret .= "<a class=\"{$style}sdaymin\" href=\"$href$i\"";
					if ($hday['name'])
						$ret .= ' ' . tooltip($hday['name']);
					$ret .= "$js_inc>$aday</a>";
				}
				$ret .= "</td>\n";
	
				if ($this->show_weeknumbers) {
					$ret .= " <td class=\"steel1\" align=\"center\" width=\"$width\" height=\"$height\">";
					if ($this->show_navigation) $ret .= "<a href=\"./calendar.php?cmd=showweek&atime=$i\">";
					$ret .= "<font class=\"kwmin\">" . strftime("%V", $i) . "</font>";
					if ($this->show_navigation) $ret .= '</a>';
					$ret .= '</td>';
				}
				$ret .= "</tr>\n";
			}
			else {
				if ((! $this->show_navigation) && $style == 'light') {
					$ret .= '&nbsp;'; // Tag gehört nicht zu diesem Monat
				} else {
					// unterschiedliche Darstellung je nach Art des Tages (Rang des Feiertages)
					switch ($hday['col']) {
						case 1:
							$ret .= "<a class=\"{$style}daymin\" href=\"$href$i\" ";
							$ret .= tooltip($hday['name']) . "$js_inc>$aday</a>";
							break;
						case 2:
						case 3;
							$ret .= "<a class=\"{$style}hdaymin\" href=\"$href$i\" ";
							$ret .= tooltip($hday['name']) . "$js_inc>$aday</a>";
							break;
						default:
							$ret .= "<a class=\"{$style}daymin\" href=\"$href$i\"$js_inc>$aday</a>";
					}
				}
				$ret .= "</td>\n";
			}
		}
		$ret .= "</table>\n</td></tr>\n";
		$ret .= "</table>\n";
		return $ret;
	}
}
			