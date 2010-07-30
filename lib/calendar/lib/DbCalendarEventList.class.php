<?

/*
DbCalendarEventList.class.php
Klassen fuer Persoenlichen Terminkalender in Stud.IP.
Copyright (C) 2001 Peter Thienel <pthienel@data-quest.de>

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

global $RELATIVE_PATH_CALENDAR, $CALENDAR_DRIVER;

require_once $RELATIVE_PATH_CALENDAR . '/lib/CalendarEvent.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/SeminarEvent.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/lib/calendar_misc_func.inc.php';
require_once $RELATIVE_PATH_CALENDAR . "/lib/driver/$CALENDAR_DRIVER/list_driver.inc.php";
require_once $RELATIVE_PATH_CALENDAR . '/lib/DbCalendarDay.class.php';
require_once $RELATIVE_PATH_CALENDAR . '/calendar_visual.inc.php';

class DbCalendarEventList {

	var $start;           // Startzeit als Unix-Timestamp (int)
	var $end;             // Endzeit als Unix-Timestamp (int)
	var $ts;              // der "genormte" Timestamp s.o. (int)
	var $events;          // Termine (Object[])
	var $show_private;    // Private Termine anzeigen ? (boolean)
	var $wdays;
	var $calendar;

	// Konstruktor
	// bei Aufruf ohne Parameter: Termine von jetzt bis jetzt + 8 Tage
	function DbCalendarEventList (&$calendar, $start = NULL, $end = NULL, $sort = TRUE, $sem_ids = NULL, $restrictions = NULL) {
		global $user;

		if (is_null($start)) {
			$start = time();
		}
		if (is_null($end)) {
			$end = mktime(23, 59, 59, date('n', $start), date('j', $start) + 8, date('Y', $start));
		}

		$this->start = $start;
		$this->end = $end;
		$this->ts = mktime(12, 0, 0, date('n', $this->start), date('j', $this->start), date('Y', $this->start), 0);
		$end_ts = mktime(12, 0, 0, date('n', $this->end), date('j', $this->end), date('Y', $this->end), 0);
		for ($ts = $this->ts; $ts < $end_ts; $ts += 86400) {
			$this->wdays[$ts] = new DbCalendarDay($calendar, $ts, NULL, $restrictions, $sem_ids);
		}

		foreach ((array) $this->wdays as $wday) {
			foreach ($wday->events as $event) {
				if ($event->getStart() <= $this->end && $event->getEnd() >= $this->start
				&& ($calendar->havePermission(CALENDAR_PERMISSION_READABLE) || $event->properties['CLASS'] == 'PUBLIC' || $calendar->getRange() == CALENDAR_RANGE_SEM)) {
					$event_key = $event->getId() . $event->getStart();
					$this->events["$event_key"] = $event;
				}
			}
		}

		if ($sort)
			$this->sort();
		$this->calendar =& $calendar;
	}

	// public
	function getStart () {
		return $this->start;
	}

	// public
	function getEnd () {
		return $this->end;
	}

	// public
	function numberOfEvents () {
		return sizeof($this->events);
	}

	function existEvent () {
		return sizeof($this->events) > 0 ? TRUE : FALSE;
	}

	// public
	function nextEvent () {
		if(list(,$ret) = each($this->events));
			return $ret;
		return FALSE;
	}


	function sort () {
		if ($this->events)
			usort($this->events, "cmp_list");
	}

	function &getAllEvents () {
		return $this->events;
	}

	function printout ($width = '70%') {
		global $auth, $forum;

		if (!$this->existEvent()) {
			return FALSE;
		}

		$open = $_REQUEST['dopen'];

		echo "\n\n<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"blank\" width=\"$width\">";
		echo "\n<tr><td>\n";
		// Ausgabe der Kopfzeile
		echo "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\" align=\"center\">";
		echo "\n<tr><td class=\"topic\" align=\"left\" width=\"99%\">\n";
		echo "<img src=\"{$GLOBALS['ASSETS_URL']}images/meinetermine.gif\" border=\"0\" ";
		echo tooltip(_("Termine. Klicken Sie auf den Pfeil, um die Beschreibung des Termins zu lesen."));
		echo " align=\"absmiddle\"><b>&nbsp;&nbsp;";

		if (date('Ymd', $this->start) == date('Ymd', time())) {
			printf(_("Termine der n�chsten %s Tage"), sizeof($this->wdays));
		} else {
			printf(_("Termine in der Zeit vom %s bis zum %s"), strftime('%x', $this->start), strftime('%x', $this->end));
		}
		echo "</b></td>";
		echo "\n<td align=\"right\" class=\"topic\" width=\"1%\" nowrap=\"nowrap\">&nbsp;</td></tr>\n";

		// Ausgabe der Daten
		echo "<tr><td class=\"blank\" colspan=\"2\">";

		while ($termin = $this->nextEvent()) {

			if ($forum['jshover'] == 1 && $auth->auth['jscript'] && $termin->havePermission(CALENDAR_EVENT_PERM_READABLE)) {
				$icon = '&nbsp;<img src="' . $GLOBALS['ASSETS_URL']. 'images/termin-icon.gif" border="0" alt="Termin" ' . js_hover($termin) . '>';
			} else {
				$icon = '&nbsp;<img src="' . $GLOBALS['ASSETS_URL'] . 'images/termin-icon.gif" border="0" alt="Termin">';
			}

			$have_write_permission = ((strtolower(get_class($termin)) == 'seminarevent' && $termin->haveWritePermission())
					|| (strtolower(get_class($termin)) != 'seminarevent'));

			$zusatz = "";
			if(strtolower(get_class($termin)) == 'seminarevent') {
				$zusatz .= "<a href=\"seminar_main.php?auswahl=" . $termin->getSeminarId()
							. "\"><font size=\"-1\">".htmlReady(mila($termin->getSemName(), 22))
							. "&nbsp;</font></a>";
			} elseif (strtolower(get_class($termin)) == 'seminarcalendarevent')  {
				$zusatz .= "<a href=\"seminar_main.php?auswahl=" . $termin->getSeminarId()
							. "\"><font size=\"-1\">".htmlReady(mila($termin->getSemName(), 22))
							. "&nbsp;</font></a>";
			}

			$titel = "";
			$length = 70;
			if (date("Ymd", $termin->getStart()) == date("Ymd", time())) {
				$titel .= _("Heute") . date(", H:i", $termin->getStart());
			} else {
				$titel .= substr(strftime("%a,", $termin->getStart()),0,2);
				$titel .= date(". d.m.Y, H:i", $termin->getStart());
				$length = 55;
			}

			if (date("Ymd", $termin->getStart()) != date("Ymd", $termin->getEnd())) {
				$titel .= " - ".substr(strftime("%a,",$termin->getEnd()),0,2);
				$titel .= date(". d.m.Y, H:i", $termin->getEnd());
				$length = 55;
			} else {
				$titel .= " - ".date("H:i", $termin->getEnd());
			}

			if (strtolower(get_class($termin)) == 'seminarevent') {
				//Beschneiden des Titels
				$titel .= ", " . htmlReady(mila($termin->getTitle(), $length - 10));
			} else {
				//Beschneiden des Titels
				$titel .= ", " . htmlReady(mila($termin->getTitle(), $length));
			}

			//Dokumente zaehlen
			$num_docs = 0;
			if ($show_docs && strtolower(get_class($termin)) == 'seminarevent') {
				$num_docs = doc_count($termin->getId());

				if ($num_docs) {
					$db = new DB_Seminar();
					$db->query("SELECT folder_id FROM folder WHERE range_id ='" . $termin->getId() . "' ");
					$db->next_record();
					$zusatz .= "<a href=\"seminar_main.php?auswahl=" . $termin->getSeminarId()
								. "&redirect_to=folder.php&cmd=tree&open=" . $db->f("folder_id")
								. "#anker\"><img src=\"{$GLOBALS['ASSETS_URL']}images/icon-disc.gif\" ";
					$zusatz .= tooltip(sprintf(_("%s Dokument(e) vorhanden"), $num_docs));
					$zusatz	.= " border=\"0\" align=absmiddle>";
					if ($num_docs > 5)
						$tmp_num_docs = 5;
					else
						$tmp_num_docs = $num_docs;
					for ($i = 1; $i < $tmp_num_docs; $i++)
						$zusatz .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/file1b.gif\" alt=\"\" border=\"0\" align=\"absmiddle\">";

					$zusatz .= "</a>";
				}
			}

			if ($termin->getChangeDate() > $LastLogin)
				$new = TRUE;
			else
				$new = FALSE;

			// Zur Identifikation von auf- bzw. zugeklappten Terminen muss zusaetzlich
			// die Startzeit ueberprueft werden, da die Wiederholung eines Termins die
			// gleiche ID besitzt.
			$app_ident = $termin->getId() . $termin->getStart();
			if ($open != $app_ident) {
				$link = $PHP_SELF."?dopen=".$app_ident."&cmd=showlist&atime=$atime#a";
			} else {
				$link = $PHP_SELF."?dclose=true&cmd=showlist&atime=$atime";
			}

			if ($link) {
				$titel = "<a href=\"$link\" class=\"tree\" >".$titel."</a>";
			}

			echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr>";

			if (!($termin->havePermission(CALENDAR_EVENT_PERM_READABLE))) {
				$zusatz = '';
			}

			if ($open == $app_ident) {
				printhead(0, 0, $link, "open", $new, $icon, $titel, $zusatz, $termin->getChangeDate());
			} else {
				printhead(0, 0, $link, "close", $new, $icon, $titel, $zusatz, $termin->getChangeDate());
			}

			echo "</tr></table>	";

			if ($open == $app_ident) {
				echo "<a name=\"a\"></a>";

				$content = "";
				if($termin->getDescription()) {
					$content .= sprintf("%s<br /><br />", formatReady($termin->getDescription(), TRUE, TRUE));
				} else {
					$content .= _("Keine Beschreibung vorhanden") . "<br /><br />";
				}

				if (strtolower(get_class($termin)) == 'seminarcalendarevent') {
					$content .= '<b>' . _("Projekt:") . '</b> ' . htmlReady($termin->getSemName()) . '<br>';
				}

				$have_category = FALSE;
				if (strtolower(get_class($termin)) != 'seminarevent') {
					$content .= "<b>" . _("Kategorie:") . "</b> " . htmlReady($termin->toStringCategories());
					$have_category = TRUE;
				} else {
					$content .= "<b>" . _("Art des Termins:") . "</b> " . htmlReady($termin->toStringCategories());
					$have_category = TRUE;
				}

				if ($termin->getLocation()) {
					if ($have_category) {
						$content .= "&nbsp; &nbsp; &nbsp; &nbsp; ";
					}
					if (strtolower(get_class($termin)) == 'seminartermin') {
						$content .= "<b>" . _("Raum:") . " </b>";
					} else {
						$content .= "<b>" . _("Ort:") . " </b>";
					}
					$content .= htmlReady(mila($termin->getLocation(), 25));
				}

				if (strtolower(get_class($termin)) != 'seminarevent') {
					$content .= '<br><b>' . _("Priorit&auml;t:") . ' </b>'
							. htmlReady($termin->toStringPriority());
					$content .= '&nbsp; &nbsp; &nbsp; &nbsp; ';
					$content .= '<b>' . _("Sichtbarkeit:") . ' </b>'
							. htmlReady($termin->toStringAccessibility());
					$content .= '<br>' . htmlReady($termin->toStringRecurrence());
				}

				$edit = FALSE;
				if ($have_write_permission) {
					// Seminar appointment
					if (strtolower(get_class($termin)) == 'seminarevent') {
						$edit = sprintf('<a href="./admin_dates.php?range_id=%s&show_id=%s#anchor">'
									. makeButton("terminaendern", "img")
									. '</a>', $termin->getSeminarId(), $termin->getId());
					} elseif (strtolower(get_class($termin)) == 'seminarcalendarevent') {
						$edit = sprintf('<a href="./seminar_main.php?auswahl=%s&redirect_to=calendar.php&termin_id=%s&cmd=edit&atime=%s">'
									. makeButton('terminaendern', 'img') . '</a>', $termin->getSeminarId(), $termin->getId(), $termin->getStart());
					} else {
						// Personal appointment
						$edit = sprintf("<a href=\"./calendar.php?cmd=edit&termin_id=%s"
									. "&atime=%s&source_page=%s\">"
									. makeButton("terminaendern", "img") . "</a>"
									, $termin->getId(), $termin->getStart(), rawurlencode($PHP_SELF));
					}
				} else {
					$content .= "<br />";
				}

				if (!($termin->havePermission(CALENDAR_EVENT_PERM_READABLE))) {
					$content = _("Keine Berechtigung");
					$edit = FALSE;
				}

				echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr>";
				printcontent(0, FALSE, $content, $edit);
				echo "</tr></table>	";
			}
		}
		echo "\n</td></tr>\n</table>";
		echo "\n</td></tr>\n</table><br>";
		return TRUE;
	}

} // class DbCalendarEventList
