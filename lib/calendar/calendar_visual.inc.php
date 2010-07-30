<?
/**
* calendar_visual.inc.php
*
*
*
* @author		Peter Thienel <pthienel@arcor.de>
* @version		$Id: calendar_visual.inc.php,v 1.5 2009/10/07 20:10:42 thienel Exp $
* @access		public
* @modulegroup	calendar
* @module		calendar
* @package	calendar
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// calendar_visual.inc.php
//
// Copyright (c) 2003 Peter Tienel <pthienel@arcor.de>
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

require_once('lib/visual.inc.php');
require_once('lib/calendar_functions.inc.php');
require_once('lib/functions.php');
if (get_config('CALENDAR_GROUP_ENABLE')) {
	require_once($RELATIVE_PATH_CALENDAR . '/calendar_visual_group.inc.php');
}

// Tabellenansicht der Termine eines Tages erzeugen
function createDayTable ($day_obj, $start = 6, $end = 19, $step = 900, $params = NULL) {

	global $atime, $PHP_SELF, $CANONICAL_RELATIVE_PATH_STUDIP;
	$precol = TRUE;
	$compact = TRUE;
	$link_edit = FALSE;
	$title_length = 70;
	$height = 20;
	$padding = 6;
	$spacing = 1;
	$bg_image = 'big';
	$link_precol = TRUE;
	if (is_null($params))
		$params = array();
	extract($params);
	$term = array();    // Array mit eingeordneten Terminen und Platzhaltern (mixed[])
	$colsp = array();   // Breite der Spalten in den einzelnen Zeilen (int[])
	$tab = array();     // html-Ausgabe der Tabelle zeilenweise (String[])
	$max_spalte = 0;    // maximale Spaltenzahl der Tabelle
	$height_event = $height;
	$width_precol_1 = 5;
	$width_precol_2 = 4;
	$day_event_row = '';
	// emphesize the current day if $compact is FALSE (this means week-view)
	if (date('Ymd', $day_obj->getStart()) == date('Ymd') && !$compact)
		$style_cell = 'celltoday';
	else
		$style_cell = 'steel1';
	// one extra column for link
	if ($link_edit)
		$link_edit_column = 1;
	else
		$link_edit_column = 0;

	if ($precol) {
		if ($step >= 3600) {
			$height_precol_1 = ' height="' . ($step / 3600) * $height . '"';
			$height_precol_2 = '';
			$rowspan_precol = '';
			$width_precol_1_txt = '';
			$width_precol_2_txt = '';
		}
		else {
			$height_precol_1 = "";
			$height_precol_2 = ' height="' . $height . '"';
			$rowspan_precol = ' rowspan="' . 3600 / $step . '"';
			$width_precol_1_txt = " width=\"$width_precol_1%\" nowrap ";
			$width_precol_2_txt = " width=\"$width_precol_2%\" nowrap ";
		}
	}

	$start *= 3600;
	$end *= 3600;

	$adapted = adapt_events($day_obj, $start, $end, $step);
	$tmp_event = $adapted['events'];
	$map_events = $adapted['map'];
	$tmp_day_event = $adapted['day_events'];
	$map_day_events = $adapted['day_map'];
	unset($adapted);

	// calculate maximum number of columns
	$w = 0;
	for ($i = $start / $step;$i < $end / $step + 3600 / $step;$i++) {
		$spalte = 0;
		$zeile = $i - $start / $step;
		while ($w < sizeof($tmp_event) && $tmp_event[$w]->getStart() >= $day_obj->getStart() + $i * $step
				&& $tmp_event[$w]->getStart() < $day_obj->getStart() + ($i + 1) * $step) {
			$rows = ceil($tmp_event[$w]->getDuration() / $step);
			if ($rows < 1)
				$rows = 1;

			while ($term[$zeile][$spalte] != "" && $term[$zeile][$spalte] != "#")
				$spalte++;

			$term[$zeile][$spalte] = $tmp_event[$w];
			$mapping[$zeile][$spalte] = $map_events[$w];

			$count = $rows - 1;
			for ($x = $zeile + 1; $x < $zeile + $rows; $x++) {
				for ($y = 0; $y <= $spalte; $y++) {
					if ($y == $spalte)
						$term[$x][$y] = $count--;
					elseif ($term[$x][$y] == "")
						$term[$x][$y] = '#';
				}
			}
			if ($max_spalte < sizeof($term[$zeile]))
				$max_spalte = sizeof($term[$zeile]);
			$w++;

		}
	}

	$zeile_min = 0;

	for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) {
		$zeile = $i - $start / $step;
		$zeile_min = $zeile;

		while (maxValue($term[$zeile], $step) > 1)
			$zeile += maxValue($term[$zeile], $step) - 1;

		$size = 0;
		for ($j = $zeile_min; $j <= $zeile; $j++)
			if (sizeof($term[$j]) > $size)
					$size = sizeof($term[$j]);

		for ($j = $zeile_min; $j <= $zeile; $j++)
			$colsp[$j] = $size;

		$i = $zeile + $start / $step;
	}

	// Zeile fuer Tagestermine
	if ($precol) {
		if ($step >= 3600) {
			$day_event_row[0] = "<td class=\"steel1\" width=\"$width_precol_1%\">&nbsp;</td>";
			$day_event_row[0] .= "<td class=\"$style_cell\" width=\"".(100 - $width_precol_1)."%\"";
		}
		else {
			$day_event_row[0] = "<td class=\"precol1w\" width=\"".($width_precol_1 + $width_precol_2)."\" colspan=\"2\">";
			$day_event_row[0] .= _("Tag") . '</td>';
			$day_event_row[0] .= "<td height=\"40\" class=\"$style_cell\" width=\"".(100 - $width_precol_1 - $width_precol_2)."%\"";
	  }
	}
	else
		$day_event_row[0] = "<td class=\"$style_cell\"";

	if ($tmp_day_event) {

		if ($max_spalte > 0)
			$day_event_row[0] .= " colspan=\"" . ($max_spalte + $link_edit_column) . "\"";

		$day_event_row[0] .= ' valign="bottom"><table width="100%" border="0" cellpadding="';
		//$day_event_row[0] .= ($padding / 2) . "\" cellspacing=\"1\">\n";
		$day_event_row[0] .= '0" cellspacing="0">';
		$i = 0;
		foreach ($tmp_day_event as $day_event) {
			$category_style = $day_event->getCategoryStyle($bg_image);
			$title = fit_title($day_event->getTitle(), 1, 1, $title_length);
			if ($day_event->getPermission == CALENDAR_EVENT_PERM_CONFIDENTIAL) {
				$title_str = $title;
			} else {
				if (strtolower(get_class($day_event)) == 'seminarevent') {
					$event_type = '&evtype=sem';
				} elseif (strtolower(get_class($day_event)) == 'seminarcalendarevent') {
					$event_type = '&evtype=semcal';
				} else {
					$event_type = '';
				}
				$title_str = sprintf("<a style=\"color: #FFFFFF; font-size:10px;\" href=\"$PHP_SELF?cmd=edit&termin_id=%s&atime=%s%s\" %s>"
					, $day_event->getId(), $day_event->getStart()
					, $event_type
					, js_hover($day_obj->events[$map_day_events[$i]]));
				$title_str .= $title . '</a>';
			}
			$day_event_row[0] .= "<tr><td height=\"20\" valign=\"top\" style=\"border-style:solid; border-width:1px; border-color:";
			$day_event_row[0] .= $category_style['color'] . "; background-image:url(";
			$day_event_row[0] .= $category_style['image'] . ");\">";
			$day_event_row[0] .= $title_str;
			$day_event_row[0] .= info_icons($day_event);
			$day_event_row[0] .= '</td>';
			$i++;
		}
		if ($link_edit) {
			$tooltip = tooltip(_("neuer Tagestermin"));
			$day_event_row[0] .= "<td class=\"$style_cell\" align=\"right\" valign=\"middle\" rowspan=\"";
			$day_event_row[0] .= sizeof($tmp_day_event) . "\"><a href=\"$PHP_SELF?cmd=edit&atime=";
			$day_event_row[0] .= $day_obj->getTs() . "&devent=1\">";
			$day_event_row[0] .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/calplus.gif\" ";
			$day_event_row[0] .= "border=\"0\" $tooltip></a></td>\n";
		}

		$day_event_row[0] .= '</table></td>';
	} else {
		if ($max_spalte > 0)
			$day_event_row[0] .= " colspan=\"" . ($max_spalte + $link_edit_column) . "\"";

		if ($link_edit) {
			$tooltip = tooltip(_("neuer Tagestermin"));
			$day_event_row[0] .= " align=\"right\" valign=\"bottom\"><a href=\"$PHP_SELF?cmd=edit&atime=";
			$day_event_row[0] .= $day_obj->getTs() . "&devent=1\">";
			$day_event_row[0] .= "&nbsp;<img src=\"{$GLOBALS['ASSETS_URL']}images/calplus.gif\" ";
			$day_event_row[0] .= "border=\"0\" style=\"margin-bottom:2px;\" $tooltip></a></td>\n";
		}
		else
			$day_event_row[0] .= ">&nbsp;</td>\n";
	}

	if ($compact)
		$day_event_row[0] = "<tr>{$day_event_row[0]}</tr>\n";

	for ($i = $start / $step;$i < $end / $step + 3600 / $step;$i++) {
		$cspan_str = "";
		$zeile = $i - $start / $step;

		if ($link_edit){
			$link_edit_time = $zeile * $step + $start - 3600;
			$link_edit_alt = strftime(_("neuer Termin um %R Uhr"), $link_edit_time);
			$link_edit_tooltip = tooltip($link_edit_alt);
		}

		if ($compact)
			$tab[$zeile] .= "<tr>\n";

		// Vorspalte mit Uhrzeiten zusammenbauen
		if ($precol) {
			if (($i * $step) % 3600 == 0) {
				$tab[$zeile] .= "<td class=\"precol1\"$width_precol_1_txt$height_precol_1$rowspan_precol>";
				if ($link_precol) {
					$tab[$zeile] .= "<a class=\"calhead\" href=\"$PHP_SELF";
					$tab[$zeile] .= sprintf("?cmd=edit&atime=%s\">%s</a>"
												, $day_obj->getStart() + $i * $step, $i / (3600 / $step));
				}
				else
					$tab[$zeile] .= $i / (3600 / $step) . "</td>";
				$width_precol_1_txt = "";
			}
			// bei Intervallen mit vollen Stunden Minuten ausblenden
			if ($step % 3600 != 0) {
				$minute = ($zeile % (3600 / $step)) * ($step / 60);
				$tab[$zeile] .= "<td class=\"precol2\"$width_precol_2_txt$height_precol_2>";
				if ($link_precol) {
					$tab[$zeile] .= sprintf("<a class=\"calhead\" href=\"$PHP_SELF?cmd=edit&atime=%s\">"
												, ($day_obj->getStart() + $i * $step));
				}
				if($minute == 0)
					$tab[$zeile] .= '00';
				else
					$tab[$zeile] .= $minute;
				if ($link_precol)
					$tab[$zeile] .= '</a>';
				$tab[$zeile] .= '</td>';
				$width_precol_2_txt = '';
			}
		}

		$link_notset = TRUE;
		if (!$term[$zeile]) {
			if ($link_edit) {
				if ($max_spalte > 0) {
					$tab[$zeile] .= "<td class=\"$style_cell\" align=\"right\"  valign=\"middle\" colspan=\"";
					$tab[$zeile] .= ($max_spalte + 1) . "\"><a href=\"$PHP_SELF?cmd=edit&atime=";
					$tab[$zeile] .= ($day_obj->getStart() + $i * $step);
					$tab[$zeile] .= "\"><img src=\"{$GLOBALS['ASSETS_URL']}images/calplus.gif\" ";
					$tab[$zeile] .= "border=\"0\" align=\"bottom\" $link_edit_tooltip></a></td>\n";
				} else {
					$tab[$zeile] .= "<td class=\"$style_cell\" align=\"right\" valign=\"middle\">";
					$tab[$zeile] .= "<a href=\"$PHP_SELF?cmd=edit&atime=";
					$tab[$zeile] .=	($day_obj->getStart() + $i * $step);
					$tab[$zeile] .= "\"><img src=\"{$GLOBALS['ASSETS_URL']}images/calplus.gif\"";
					$tab[$zeile] .= "border=\"0\" align=\"bottom\" $link_edit_tooltip></a></td>\n";
				}
			} else {
				if ($max_spalte > 1) {
					$tab[$zeile] .= "<td class=\"$style_cell\" colspan=\"$max_spalte\">";
					$tab[$zeile] .= "<font class=\"inday\">&nbsp;</font></td>\n";
				} else {
					$tab[$zeile] .= "<td class=\"$style_cell\"><font class=\"inday\">&nbsp;</font></td>\n";
				}
			}

			$height = "";
			// Wenn bereits hier ein Link eingefuegt wurde braucht weiter unten keine
			// zusaetliche Spalte ausgegeben werden
			$link_notset = FALSE;
		} else {
			if ($colsp[$zeile] > 0)
				$cspan = (int) ($max_spalte / $colsp[$zeile]);
			else
				$cspan = 0;

			for ($j = 0;$j < $colsp[$zeile];$j++) {
				$sp = 0;
				$n = 0;
				if ($j + 1 == $colsp[$zeile])
					$cspan += $max_spalte % $colsp[$zeile];

				if (is_object($term[$zeile][$j])) {

					// Wieviele Termine sind zum aktuellen Termin zeitgleich?
					$p = 0;
					$count = 0;
					while ($aterm = $tmp_event[$p]) {
						if ($aterm->getStart() >= $term[$zeile][$j]->getStart()
								&& $aterm->getStart() <= $term[$zeile][$j]->getEnd()) {
							$count++;
						}
						$p++;
					}

					if ($count == 0) {
						for ($n = $j + 1;$n < $colsp[$zeile];$n++) {
							if (!is_int($term[$zeile][$n])) {
								$sp++;
							} else {
								break;
							}
						}
						$cspan += $sp;
					}

					$rows = ceil($term[$zeile][$j]->getDuration() / $step);
					$tab[$zeile] .= '<td';

					if ($cspan > 1) {
						$tab[$zeile] .= ' colspan="'.$cspan.'"';
					}
					if ($rows > 1) {
						$tab[$zeile] .= ' rowspan="'.$rows.'"';
					} else {
						$rows = 1;
					}

					$category_style = $term[$zeile][$j]->getCategoryStyle($bg_image);
					$tab[$zeile] .= ' style="vertical-align:top; font-size:10px; color:#FFFFFF;';
					$tab[$zeile] .= ' background-image:url(';
					$tab[$zeile] .= $category_style['image'];
					$tab[$zeile] .= "); border-style:solid; border-width:1px; border-color:";
					$tab[$zeile] .= $category_style['color'] . ";\">";

					if (strtolower(get_class($term[$zeile][$j])) == 'seminarevent'
							&& $term[$zeile][$j]->getTitle() == 'Kein Titel') {
						$title_out = $term[$zeile][$j]->getSemName();
					} else {
						$title_out = $term[$zeile][$j]->getTitle();
					}
					if (strtolower(get_class($term[$zeile][$j])) == 'seminarevent') {
						$event_type = '&evtype=sem';
					} elseif (strtolower(get_class($term[$zeile][$j])) == 'seminarcalendarevent') {
						$event_type = '&evtype=semcal';
					} else {
						$event_type = '';
					}
					if ($rows == 1) {
						$title = fit_title($title_out, $colsp[$zeile], $rows, $title_length - 6);
						$tab[$zeile] .= sprintf("<a style=\"color: #FFFFFF;\" href=\"$PHP_SELF?cmd=edit&termin_id=%s&atime=%d%s\" %s>"
								, $term[$zeile][$j]->getId()
								, ($day_obj->getStart() + $term[$zeile][$j]->getStart() % 86400)
								, $event_type
								, js_hover($day_obj->events[$mapping[$zeile][$j]]));
						$tab[$zeile] .= $title . "</a>";
					} else {
						$title = fit_title($title_out, $colsp[$zeile], $rows - 1, $title_length);
						$tab[$zeile] .= "<div style=\"font-size:10px; height:15px; background-color:";
						$tab[$zeile] .= $category_style['color'];
						$tab[$zeile] .= ";\">" . date('H.i-', $day_obj->events[$mapping[$zeile][$j]]->getStart());
						$tab[$zeile] .= date('H.i', $day_obj->events[$mapping[$zeile][$j]]->getEnd()) . "</div>\n";

						if ($term[$zeile][$j]->getPermission() == CALENDAR_EVENT_PERM_CONFIDENTIAL) {
							$tab[$zeile] .= $title;
						} else {
							$tab[$zeile] .= sprintf("<a style=\"color: #FFFFFF;\" href=\"$PHP_SELF?cmd=edit&termin_id=%s&atime=%d%s\" %s>"
									, $term[$zeile][$j]->getId()
									, ($day_obj->getStart() + $term[$zeile][$j]->getStart() % 86400)
									, $event_type
									, js_hover($day_obj->events[$mapping[$zeile][$j]]));
							$tab[$zeile] .= $title . "</a>";
						}
					}
					$tab[$zeile] .= info_icons($term[$zeile][$j]);
					$tab[$zeile] .= "</td>\n";

					if ($sp > 0) {
						for ($m = $zeile;$m < $rows + $zeile;$m++) {
							$colsp[$m] = $colsp[$m] - $sp + 1;
							$v = $j;
							while ($term[$m][$v] == '#') {
								$term[$m][$v] = 1;
							}
						}
						$j = $n;
					}
				}

				elseif ($term[$zeile][$j] == '#') {
					$csp = $link_edit_column;
					if ($link_edit)
						$csp--;
					while ($term[$zeile][$j] == '#') {
						$csp += $cspan;
						$j++;
					}
					if ($csp > 1)
						$colspan_attr = " colspan=\"$csp\"";
					else
						$colspan_attr = '';

						$tab[$zeile] .= "<td class=\"$style_cell\"$colspan_attr>";
						$tab[$zeile] .= "<font class=\"inday\">&nbsp;</font></td>\n";

					$height = '';
				}

				elseif ($term[$zeile][$j] == '') {
					$csp = $max_spalte - $j + $link_edit_column;
					if ($csp > 1)
						$colspan_attr = " colspan=\"$csp\"";
					else
						$colspan_attr = '';

					if ($link_edit) {
						$tab[$zeile] .= "<td class=\"$style_cell\"$colspan_attr align=\"right\" valign=\"middle\">";
						$tab[$zeile] .= sprintf("<a href=\"$PHP_SELF?cmd=edit&atime=%s\">"
															, $day_obj->getStart() + $i * $step);
						$tab[$zeile] .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/calplus.gif\" ";
						$tab[$zeile] .= "border=\"0\" $link_edit_tooltip>";
						$tab[$zeile] .= "</a></td>\n";
					}
					else {
						$tab[$zeile] .= "<td class=\"$style_cell\"$colspan_attr>";
						$tab[$zeile] .= "<font class=\"inday\">&nbsp;</font></td>\n";
					}

					$link_notset = FALSE;
					$height = '';
					break;
				}

			}

		}

		if ($link_edit && $link_notset) {
			$tab[$zeile] .= "<td class=\"$style_cell\" align=\"right\" valign=\"middle\">";
			$tab[$zeile] .= "<a href=\"$PHP_SELF?cmd=edit&atime=" . ($day_obj->getStart() + $i * $step);
			$tab[$zeile] .= "\"><img src=\"{$GLOBALS['ASSETS_URL']}images/calplus.gif\" ";
			$tab[$zeile] .= "border=\"0\" $link_edit_tooltip>";
			$tab[$zeile] .= "</a></td>\n";
		}

		if ($compact)
			$tab[$zeile] .= "</tr>\n";

		// sonst zerlegt array_merge (siehe unten) die Tabelle
		if (!isset($tab[$zeile]))
			$tab[$zeile] = '';

	}

	if ($max_spalte == 0)
		$max_spalte = 1;

	if ($link_edit && sizeof($tmp_event))
		$max_spalte++;

	if ($precol) {
		if ($step >= 3600)
			$max_spalte++;
		else
			$max_spalte += 2;
	}

	$tab = array_merge($day_event_row, $tab);

	if ($compact)
		$tab = implode('', $tab);

	return array('table' => $tab, 'max_columns' => $max_spalte);

}

function maxValue ($term, $st) {
	$max_value = 0;
	for ($i = 0; $i < sizeof($term); $i++) {
		if (is_object($term[$i]))
			$max = ceil($term[$i]->getDuration() / $st);
		elseif ($term[$i] == '#')
			continue;
		elseif ($term[$i] > $max_value)
			$max = $term[$i];
		if ($max > $max_value)
			$max_value = $max;
	}

	return $max_value;
}

function adapt_events ($day_obj, $start, $end, $step = 900) {
	// Die Generierung der Tabellenansicht erfolgt mit Hilfe geklonter Termine,
	// da die Anfangs- und Endzeiten zur korrekten Darstellung evtl. angepasst
	// werden muessen
	for ($i = 0; $i < sizeof($day_obj->events); $i++) {
		if (($day_obj->events[$i]->getEnd() >= $day_obj->getStart() + $start)
				&& ($day_obj->events[$i]->getStart() < $day_obj->getStart() + $end + 3600)) {

			if ($day_obj->events[$i]->isDayEvent()
					|| ($day_obj->events[$i]->getStart() <= $day_obj->getStart()
					&& $day_obj->events[$i]->getEnd() >= $day_obj->getEnd())) {
				$cloned_day_event = clone $day_obj->events[$i];
				$cloned_day_event->setStart($day_obj->getStart());
				$cloned_day_event->setEnd($day_obj->getEnd());
				$tmp_day_event[] = $cloned_day_event;
				$map_day_events[] = $i;
			}
			else {
				$cloned_event = clone $day_obj->events[$i];
				$end_corr = $cloned_event->getEnd() % $step;
				if ($end_corr > 0) {
					$end_corr = $cloned_event->getEnd() + ($step - $end_corr);
					$cloned_event->setEnd($end_corr);
				}
				if ($cloned_event->getStart() < ($day_obj->getStart() + $start))
					$cloned_event->setStart($day_obj->getStart() + $start);
				if ($cloned_event->getEnd() > ($day_obj->getStart() + $end + 3600))
					$cloned_event->setEnd($day_obj->getStart() + $end + 3600);

				$tmp_event[] = $cloned_event;
				$map_events[] = $i;
			}
		}
	}

	return array('events' => $tmp_event, 'map' => $map_events, 'day_events' => $tmp_day_event,
			'day_map' => $map_day_events);
}

// Tabellenansicht der Termine fuer eine Woche
function create_week_view ($week_obj, $start = 6, $end = 21, $step = 3600,
													$compact = TRUE, $link_edit = FALSE) {
	global $PHP_SELF;

	$tab_arr = '';
	$tab = '';
	$max_columns = 0;
	$rows = ($end - $start + 1) * 3600 / $step;
	// calculating the maximum title length
	$length = ceil(125 / $week_obj->getType());

	for ($i = 0; $i < $week_obj->getType(); $i++) {
		$tab_arr[$i] = createDayTable($week_obj->wdays[$i], $start, $end, $step,
				array(
					'precol'       => FALSE,
					'compact'      => FALSE,
					'link_edit'    => $link_edit,
					'title_length' => $length,
					'height'       => 20,
					'padding'      => 4,
					'spacing'      => 1,
					'bg_image'     => 'small'));
	}

	// weekday and date as title for each column
	for ($i = 0; $i < $week_obj->getType(); $i++) {
		// add up all colums of each day
		$max_columns += $tab_arr[$i]['max_columns'];
		$dtime = $week_obj->wdays[$i]->getTs();
		if ($week_obj->getType() == 5) {
			$tab[0] .= '<td class="steelgroup0" align="center" width="19%"';
		} else {
			$tab[0] .= '<td class="steelgroup0" align="center" width="13%"';
		}

		if ($tab_arr[$i]['max_columns'] > 1) {
			$tab[0] .= " colspan=\"{$tab_arr[$i]['max_columns']}\"";
		}
		$tab[0] .= "><a class=\"calhead\" href=\"$PHP_SELF?cmd=showday&atime=$dtime\"><b>";
		$tab[0] .= wday($dtime, 'SHORT') . " " . date('d', $dtime) . "</b></a>";
		$tab[0] .= "<div style=\"text-align: center; font-size: 9pt; color: #BBBBBB; height: auto; overflow: visible; font-weight: bold;\">";
		$holiday = $week_obj->wdays[$i]->isHoliday();
		$tab[0] .= $holiday['name'] . "</div></td>\n";
	}
	if ($compact) {
		$tab[0] = "<tr>{$tab[0]}</tr>\n";
	}

	// put the table together
	for ($i = 1; $i < $rows + 2; $i++){
		if ($compact) {
			$tab[$i] .= '<tr>';
		}
		for ($j = 0; $j < $week_obj->getType(); $j++){
			$tab[$i] .= $tab_arr[$j]['table'][$i - 1];
		}
		if ($compact) {
			$tab[$i] .= "</tr>\n";
		}
	}

	if ($compact) {
		$tab = implode('', $tab);
	}

	$tab = array('table' => $tab, 'max_columns' => $max_columns);

	$rowspan = ceil(3600 / $step);
	$height = ' height="20"';

	if($rowspan > 1){
		$colspan_1 = ' colspan="2"';
		$colspan_2 = $tab['max_columns'] + 4;
	} else {
		$colspan_1 = '';
		$colspan_2 = $tab['max_columns'] + 2;
	}

	if ($week_obj->getType() == 7) {
		$width = '1%';
	} else {
		$width = '3%';
	}

	$out = "<table border=\"0\" width=\"100%\" cellspacing=\"1\" cellpadding=\"0\" class=\"steelgroup0\">\n";
	$out .= "<tr><td colspan=\"$colspan_2\">\n";
	$out .= "<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\" class=\"steelgroup0\">\n";
	$out .= "<tr>\n";
	$out .= "<td align=\"center\" width=\"15%\"><a href=\"$PHP_SELF?cmd=showweek&atime=";
	$out .= mktime(12, 0, 0, date('n', $week_obj->getStart()),
			date('j', $week_obj->getStart()) - 7, date('Y', $week_obj->getStart()));
	$out .= '">&nbsp;';
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous.gif\"";
	$out .= tooltip(_("eine Woche zurück")) . ">&nbsp;</a></td>\n";
	$out .= "<td width=\"70%\" class=\"calhead\">";
	$out .= sprintf(_("%s. Woche vom %s bis %s"), strftime("%V", $week_obj->getStart()),
			strftime("%x", $week_obj->getStart()), strftime("%x", $week_obj->getEnd()));
	$out .= "</td>\n";
	$out .= "<td align=\"center\" width=\"15%\"><a href=\"$PHP_SELF?cmd=showweek&atime=";
	$out .= mktime(12, 0, 0, date('n', $week_obj->getStart()),
			date('j', $week_obj->getStart()) + 7, date('Y', $week_obj->getStart()));
	$out .= '">&nbsp;';
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_next.gif\"";
	$out .= tooltip(_("eine Woche vor")) . ">&nbsp;</a></td>\n";
	$out .= "</tr></table>\n</td></tr>\n";

	$out .= "<tr><td nowrap=\"nowrap\" align=\"center\" width=\"$width\"$colspan_1>";
	if ($start > 0) {
		$out .= "<a href=\"calendar.php?cmd=showweek&atime=";
		$out .= mktime($start - 1, 0, 0, date('n', $week_obj->getStart()),
			date('j', $week_obj->getStart()), date('Y', $week_obj->getStart()));
		$out .= "\"><img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_up.gif\"";
		$out .= tooltip(_("zeig davor")) . "></a>";
	} else {
		$out .= '&nbsp;&nbsp;&nbsp;';
	}
	// row with weekdays
	$out .= '</td>' . $tab['table'][0];

	$out .= "<td nowrap=\"nowrap\" align=\"center\" width=\"$width\"$colspan_1>";
	if ($start > 0) {
		$out .= "<a href=\"$PHP_SELF?cmd=showweek&atime=";
		$out .= mktime($start - 1, 0, 0, date('n', $week_obj->getStart()),
			date('j', $week_obj->getStart()), date('Y', $week_obj->getStart()));
		$out .= "\"><img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_up.gif\"";
		$out .= tooltip(_("zeig davor")) . "></a>";
	} else {
		$out .= '&nbsp;&nbsp;&nbsp;';
	}
	$out .= "</td></tr>\n";

	// Zeile mit Tagesterminen ausgeben
	$out .= "<tr><td class=\"precol1w\"$colspan_1 height=\"25\">" . _("Tag");
	$out .= "</td>{$tab['table'][1]}<td class=\"precol1w\"$colspan_1>";
	$out .= _("Tag") . "</td></tr>\n";
	$out .= "<tr height=\"2\"><td colspan=\"" . (2 * $colspan_1 + $colspan_2) . "\"></tr>\n";

	$j = $start;
	for ($i = 2; $i < sizeof($tab['table']); $i++) {
		$out .= "<tr>";

		if ($i % $rowspan == 0) {
			if ($rowspan == 1) {
				$out .= "<td class=\"precol1w\"$height>$j</td>";
			} else {
				$out .= "<td class=\"precol1w\" rowspan=\"$rowspan\">$j</td>";
			}
		}
		if ($rowspan > 1) {
			$minutes = (60 / $rowspan) * ($i % $rowspan);
			if ($minutes == 0) {
				$minutes = '00';
			}
			$out .= "<td class=\"precol2w\"$height>$minutes</td>\n";
		}

		$out .= $tab['table'][$i];

		if ($rowspan > 1) {
			$out .= "<td class=\"precol2w\">$minutes</td>\n";
		}
		if ($i % $rowspan == 0) {
			if ($rowspan == 1) {
				$out .= "<td class=\"precol1w\">$j</td>";
			} else {
				$out .= "<td class=\"precol1w\" rowspan=\"$rowspan\">$j</td>";
			}
			$j = $j + ceil($step / 3600);
		}

		$out .= "</tr>\n";
	}

	$out .= "<tr><td$colspan_1 align=\"center\">";
	if ($end < 23) {
		$out .= "<a href=\"$PHP_SELF?cmd=showweek&atime=";
		$out .= mktime($end + 1, 0, 0, date('n', $week_obj->getStart()),
			date('j', $week_obj->getStart()), date('Y', $week_obj->getStart()));
		$out .= "\"><img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_down.gif\"";
		$out .= tooltip(_("zeig danach")) . "></a>";
	} else {
		$out .= '&nbsp';
	}
	$out .= "</td><td colspan=\"{$tab['max_columns']}\">&nbsp;</td>";
	$out .= "<td$colspan_1 align=\"center\">";
	if ($end < 23) {
		$out .= "<a href=\"$PHP_SELF?cmd=showweek&atime=";
		$out .= mktime($end + 1, 0, 0, date('n', $week_obj->getStart()),
			date('j', $week_obj->getStart()), date('Y', $week_obj->getStart()));
		$out .= "\"><img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_down.gif\"";
		$out .= tooltip(_("zeig danach")) . "></a>";
	} else {
		$out .= '&nbsp;';
	}
	$out .= "</td></tr>\n</table>\n";

	return $out;
}

function create_month_view (&$calendar, $atime, $step = NULL) {

	$month =& $calendar->view;

	$out = "<table width=\"98%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
	$out .= "<tr><td>\n";
	$out .= "<table width=\"100%\" class=\"steelgroup0\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\">\n";
	$out .= "<tr><td>\n";

	$out .= "<table width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";
	$out .= "<tr>\n<td align=\"center\">";
	$out .= sprintf("&nbsp;<a href=\"%s?cmd=showmonth&atime=%s\">",
		$PHP_SELF, mktime(12, 0, 0, $month->getMonth(),
				date('j', $month->getStart()), date('Y', $month->getStart()) - 1));
	$tooltip = tooltip(_("ein Jahr zurück"));
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous_double.gif\"$tooltip></a>";
	$out .= sprintf("&nbsp; &nbsp; &nbsp; &nbsp;<a href=\"%s?cmd=showmonth&atime=%s\">",
		$PHP_SELF, $month->getStart() - 1);
	$tooltip = tooltip(_("einen Monat zurück"));
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous.gif\"$tooltip></a>&nbsp;</td>\n";
	$out .= sprintf("<td colspan=\"%s\" class=\"calhead\">\n", $mod == "nokw" ? "5" : "6");
	$out .= '<font size="+2">';
	$out .= htmlentities(strftime("%B ", $month->getStart()), ENT_QUOTES) . $month->getYear();
	$out .= "</font></td>\n";
	$out .= sprintf("<td align=\"center\">&nbsp;<a href=\"%s?cmd=showmonth&atime=%s\">",
		$PHP_SELF, $month->getEnd() + 1);
	$tooltip = tooltip(_("einen Monat vor"));
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_next.gif\"$tooltip></a>";
	$out .= sprintf("&nbsp; &nbsp; &nbsp; &nbsp;<a href=\"%s?cmd=showmonth&atime=%s\">",
		$PHP_SELF, mktime(12, 0, 0, $month->getMonth(),
				date('j', $month->getStart()), date('Y', $month->getEnd()) + 1));
	$tooltip = tooltip(_("ein Jahr vor"));
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_next_double.gif\"$tooltip></a></td>\n";
	$out .= "</tr>\n<tr>\n";

	$weekdays_german = array('MO', 'DI', 'MI', 'DO', 'FR', 'SA', 'SO');
	foreach ($weekdays_german as $weekday_german) {
		$out .= '<td class="precol1w" width="90">' . wday('', 'SHORT', $weekday_german) . '</td>';
	}

	if($mod != 'nokw') {
		$out .= "<td align=\"center\" class=\"precol1w\" width=\"90\">" . _("Woche") . "</td>\n";
	}
	$out .= "</tr></table>\n</td></tr>\n";
	$out .= "<tr><td class=\"blank\">\n";
	$out .= "<table width=\"100%\" class=\"steelgroup0\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";

	// Im Kalenderblatt ist links oben immer Montag. Das muss natuerlich nicht der
	// Monatserste sein. Es muessen evtl. noch Tage des vorangegangenen Monats
	// am Anfang und des folgenden Monats am Ende angefuegt werden.

	$adow = strftime("%u", $month->getStart()) - 1;

	$first_day = $month->getStart() - $adow * 86400 + 43200;
	// Ist erforderlich, um den Maerz richtig darzustellen
	// Ursache ist die Sommer-/Winterzeit-Umstellung
	$cor = 0;
	if ($month->getMonth() == 3) {
		$cor = 1;
	}

	$last_day = ((42 - ($adow + date("t", $month->getStart()))) % 7 + $cor) * 86400
	 	        + $month->getEnd() - 43199;

	for ($i = $first_day, $j = 0; $i <= $last_day; $i += 86400, $j++) {
		$aday = date('j', $i);
		// Tage des vorangehenden und des nachfolgenden Monats erhalten andere
		// style-sheets
		$class_day = '';
		if (($aday - $j - 1 > 0) || ($j - $aday  > 6)) {
			$class_cell = 'lightmonth';
			$class_day = 'light';
		} elseif (date('Ymd', $i) == date('Ymd')) { // emphesize current day
			$class_cell = 'celltoday';
		} else {
			$class_cell = 'month';
		}

		// Feiertagsueberpruefung
		if ($mod != 'compact' && $mod != 'nokw') {
			$hday = holiday($i);
		}

		/*
		// wenn Feiertag dann nur 4 Termine pro Tag ausgeben, sonst wirds zu eng
		if ($hday['col'] > 0)
			$max_apps = 4;
		else
			$max_apps = 5;
		*/

		// week column
		if ($j % 7 == 0) {
			$out .= "<tr>\n";
		}
		$out .= "<td class=\"$class_cell\" valign=\"top\" width=\"90\" height=\"80\">&nbsp;";

		// sunday column
		if (($j + 1) % 7 == 0) {
			$out .= "<a class=\"{$class_day}sday\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
		//	$out .= to_string_month_up_down($month, $i, $step, $max_apps, $atime);

			if ($hday["name"] != "")
				$out .= "<br><span style=\"color: #AAAAAA;\" class=\"inday\">{$hday['name']}</span>";

			$out .= to_string_month_events($calendar, $i);

			$out .= "</td>\n";

			if ($mod != 'nokw') {
				$out .= "<td class=\"lightmonth\" align=\"center\" width=\"90\" height=\"80\">";
				$out .= sprintf("<a class=\"calhead\" href=\"%s?cmd=showweek&atime=%s\"><b>%s</b></a></td>\n",
					$PHP_SELF, $i, strftime("%V", $i));
			}
			$out .= "</tr>\n";
		} else{
			// other days columns
			// unterschiedliche Darstellung je nach Art des Tages (Rang des Feiertages)
			$up_down_nav = '';
			switch ($hday['col']) {
				case 1:
					$out .= "<a class=\"{$class_day}day\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
					$out .= $up_down_nav;
					$out .= "<br><span style=\"color: #AAAAAA;\" class=\"inday\">{$hday['name']}</span>";
					break;
				case 2:
					$out .= "<a class=\{$class_day}shday\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
					$out .= $up_down_nav;
					$out .= "<br><span style=\"color: #AAAAAA;\" class=\"inday\">{$hday['name']}</span>";
					break;
				case 3:
					$out .= "<a class=\"{$class_day}hday\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
					$out .= $up_down_nav;
					$out .= "<br><span style=\"color: #AAAAAA;\" class=\"inday\">{$hday['name']}</span>";
					break;
				default:
					$out .= "<a class=\"{$class_day}day\" href=\"$PHP_SELF?cmd=showday&atime=$i\">$aday</a>";
					$out .= $up_down_nav;
			}

			$out .= to_string_month_events($calendar, $i);
			$out .= "</td>\n";

		}
	}

	$out .= "</td></tr></table>\n";
	$out .= "</td></tr>\n";
	$out .= "<tr><td>&nbsp;</td></tr>\n";
	$out .= "</table></td></tr></table>\n";

	return $out;
}

/**
* Print a list of events for each day of month
*
* @access public
* @param object $month_obj instance of DbCalendarMonth
* @param int $max_events the number of events to print
* @param int $day_timestamp unix timestamp of the day
*/
function to_string_month_events (&$calendar, $day_timestamp, $max_events = NULL) {
	global $PHP_SELF;

	if (is_null($max_events)) {
		$max_events = 100;
	}
	$out = '';
	$count = 0;
	if (strtolower(get_class($calendar)) == 'groupcalendar') {
		for ($i = 0; $i < sizeof($calendar->calendars); $i++) {
			$events = $calendar->calendars[$i]->view->getEventsOfDay($day_timestamp);
			if (sizeof($events) && $count < $max_events) {
				$fullname = get_fullname($calendar->calendars[$i]->getUserId(), 'no_title_rev');
				$html_fullname = fit_title($fullname, 1, 1, 15);
				$js_fullname = JSReady($fullname);
				$month = $user_calendar->view;
				$js_hover = js_hover_group($events, $calendar->calendars[$i]->view->getStart(),
						$calendar->calendars[$i]->view->getEnd(), $fullname);

				$out .= "<br><a class=\"inday\" href=\"$PHP_SELF?cmd=showday&cal_user=";
				$out .= get_username($calendar->calendars[$i]->getUserId());
				$out .= "&atime=$day_timestamp\"" . $js_hover . ">";
				$out .= fit_title($fullname, 1, 1, 15) . "</a>";
				$count++;
			}
		}
	} else {
		$month =& $calendar->view;
		while (($event = $month->nextEvent($day_timestamp)) && $count < $max_events) {
			if (strtolower(get_class($event)) == 'seminarevent') {
				$html_title = fit_title($event->getSemName(), 1, 1, 15);
				$jscript_title = JSReady($event->getSemName());
				$ev_type = '&evtype=sem';
			} elseif (strtolower(get_class($event)) == 'seminarcalendarevent') {
				$html_title = fit_title($event->getTitle(), 1, 1, 15);
				$jscript_title = JSReady($event->getTitle());
				$ev_type = '&evtype=semcal';
			} else {
				$html_title = fit_title($event->getTitle(), 1, 1, 15);
				$jscript_title = JSReady($event->getTitle());
				$ev_type = '';
			}

			$out .= sprintf("<br><a class=\"inday\" href=\"%s?cmd=edit&termin_id=%s&atime=%s%s\"",
					$PHP_SELF, $event->getId(), $day_timestamp, $ev_type);

			$out .= js_hover($event) . '>';
			$category_style = $event->getCategoryStyle();
			$out .= sprintf("<span style=\"color: %s;\">%s</span></a>", $category_style['color'], $html_title);
			$count++;
		}
	}

	return $out;
}

/**
* Up-/down-navigation if there are more events per day than the given number
*
* deprecated
*
* @access private
* @param object &$month_obj instance of DbCalendarMonth
* @param int $day_timestamp unix timestamp of this day
* @param int $step the current step
* @param int $max_events the number of events per step
* @param int $atime timestamp
*/
function to_string_month_up_down (&$month, $day_timestamp, $step, $max_events, $atime) {
	global $PHP_SELF, $CANONICAL_RELATIVE_PATH_STUDIP;

	if($atime == $day_timestamp){
		$spacer = TRUE;
		$up = FALSE;
		$a = $month->numberOfEvents($day_timestamp) - $step - $max_events;
		$up = ($month->numberOfEvents($day_timestamp) > $max_events && $step >= $max_events);
		if($a + $max_events > $max_events){
			if($up)
				$out = '&nbsp; &nbsp; &nbsp;';
			else
				$out = '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;';
			$tooltip = sprintf(_("noch %s Termine danach"), $a);
			$tooltip = tooltip($tooltip);
			$out .= "<a href=\"$PHP_SELF?cmd=showmonth&atime=$day_timestamp&step=";
			$out .= ($step + $max_events) . "\">";
			$out .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/calendar_down_small.gif\" ";
			$out .= $tooltip . " border=\"0\"></a>\n";
			$spacer = FALSE;
		}
		if ($up) {
			if($spacer)
				$out .= '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;';
			$tooltip = sprintf(_("noch %s Termine davor"), $step);
			$tooltip = tooltip($tooltip);
			$out .= "<a href=\"$PHP_SELF?cmd=showmonth&atime=$day_timestamp&step=";
			$out .= ($step - $max_events) . "\">";
			$out .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/calendar_up_small.gif\" ";
			$out .= $tooltip . " border=\"0\"></a>\n";
			$month->setPointer($atime, $step);
		}
	} elseif ($month->numberOfEvents($day_timestamp) > $max_events) {
		$out .= '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;';
		$tooltip = sprintf(_("noch %s Termine danach"),
				$month->numberOfEvents($day_timestamp) - $max_events);
		$tooltip = tooltip($tooltip);
		$out .= "<a href=\"$PHP_SELF?cmd=showmonth&atime=$day_timestamp&step=";
		$out .= ($max_events) . "\"><img src=\"{$GLOBALS['ASSETS_URL']}images/calendar_down_small.gif\" ";
		$out .= $tooltip . " border=\"0\"></a>\n";
	}

	return $out;
}

function create_year_view (&$calendar) {
	global $PHP_SELF, $CANONICAL_RELATIVE_PATH_STUDIP;

	$year =& $calendar->view;

	$out = "<table class=\"steelgroup0\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	$out .= "<tr><td align=\"center\" width=\"10%\">\n";
	$out .= "<a href=\"$PHP_SELF?cmd=showyear&atime=" . ($year->getStart() - 1) . '">';
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous.gif\"";
	$out .= tooltip(_("zurück")) . ">&nbsp;</a></td>\n";
	$out .= "<td class=\"calhead\" align=\"center\" width=\"80%\">\n";
	$out .= "<font size=\"+2\"><b>" . $year->getYear() . "</b></font></td>\n";
	$out .= "<td align=\"center\" width=\"10%\"><a href=\"$PHP_SELF?cmd=showyear&atime=";
	$out .= ($year->getEnd() + 1) . "\">\n";
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_next.gif\"";
	$out .= tooltip(_("vor")) . '>&nbsp;</a></td>';
	$out .= "</tr>\n";
	$out .= "<tr><td colspan=\"3\" class=\"blank\">";

	$out .= '<table class="steelgroup0" width="100%" border="0" ';
	$out .= "cellpadding=\"2\" cellspacing=\"1\">\n";

	$days_per_month = array(31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if (date('L', $year->getStart())) {
		$days_per_month[2]++;
	}

	$out .= '<tr>';
	for ($i = 1; $i < 13; $i++) {
		$ts_month += ($days_per_month[$i] - 1) * 86400;
		$out .= '<td align="center" width="8%">';
		$out .= "<a class=\"calhead\" href=\"$PHP_SELF";
		$out .= '?cmd=showmonth&atime=' . ($year->getStart() + $ts_month) . '">';
		$out .= '<font size="-1"><b>';
		$out .= htmlentities(strftime("%B", $ts_month), ENT_QUOTES);
		$out .= "</b></font></a></td>\n";
	}
	$out .= "</tr>\n";

	$now = date('Ymd');
	for ($i = 1; $i < 32; $i++) {
		$out .= '<tr>';
		for ($month = 1; $month < 13; $month++) {
			$aday = mktime(12, 0, 0, $month, $i, $year->getYear());

			if($i <= $days_per_month[$month]){
				$wday = date('w', $aday);
				// emphesize current day
				if (date('Ymd', $aday) == $now)
					$day_class = ' class="celltoday"';
				else if ($wday == 0 || $wday == 6)
					$day_class = ' class="weekend"';
				else
					$day_class = ' class="weekday"';

				if ($month == 1)
					$out .= "<td$day_class height=\"25\">";
				else
					$out .= "<td$day_class>";

				$event_count_txt = javascript_hover_year($calendar, $aday);
				if($event_count_txt != '') {
					$out .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr>";
					$out .= "<td$day_class>";
				}

				$weekday = '<font size="2">' . wday($aday, 'SHORT') . '</font>';

				// noch wird nicht nach Wichtigkeit bestimmter Feiertage unterschieden
				$hday = holiday($aday);
				switch ($hday['col']) {

					case "1":
						if (date("w", $aday) == "0") {
							$out .= "<a class=\"sday\" href=\"$PHP_SELF?cmd=showday&atime=$aday\">";
							$out .= "<b>$i</b></a> " . $weekday;
							$count++;
							}
						else {
							$out .= "<a class=\"day\" href=\"$PHP_SELF?cmd=showday&atime=$aday\">";
							$out .= "<b>$i</b></a> " . $weekday;
						}
						break;
					case "2":
					case "3":
						if (date("w", $aday) == "0") {
							$out .= "<a class=\"sday\" href=\"$PHP_SELF?cmd=showday&atime=$aday\">";
							$out .= "<b>$i</b></a> " . $weekday;
							$count++;
						}
						else {
							$out .= "<a class=\"hday\" href=\"$PHP_SELF?cmd=showday&atime=$aday\">";
							$out .= "<b>$i</b></a> " . $weekday;
						}
						break;
					default:
						if (date("w", $aday) == "0") {
							$out .= "<a class=\"sday\" href=\"$PHP_SELF?cmd=showday&atime=$aday\">";
							$out .= "<b>$i</b></a> " . $weekday;
							$count++;
							}
						else {
							$out .= "<a class=\"day\" href=\"$PHP_SELF?cmd=showday&atime=$aday\">";
							$out .= "<b>$i</b></a> " . $weekday;
						}
				}

				if	($event_count_txt != '') {
					$out .= "</td><td$day_class align=\"right\">";
					$out .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/icon-uhr.gif\" ";
					$out .= $event_count_txt . " border=\"0\">";
					$out .= "</td></tr></table>\n";
				}
				$out .= '</td>';
			}
			else
				$out .= '<td class="weekday">&nbsp;</td>';
		}
		$out .= "</tr>\n";

	}
	$out .= '<tr>';
	$ts_month = 0;
	for ($i = 1; $i < 13; $i++){
		$ts_month += ($days_per_month[$i] - 1) * 86400;
		$out .= "<td align=\"center\" width=\"8%%\">";
		$out .= "<a class=\"calhead\" href=\"" . $PHP_SELF;
		$out .= "?cmd=showmonth&atime=" . ($year->getStart() + $ts_month) . "\">";
		$out .= "<font size=\"-1\"><b>";
		$out .= htmlentities(strftime("%B", $ts_month), ENT_QUOTES);
		$out .= "</b></font></a></td>\n";
	}
	$out .= "</tr></table>\n</td></tr></table>\n";

	return $out;
}

function javascript_hover_year (&$calendar, $day_time) {
	global $forum, $auth;

	$out = '';
	$event_count_txt = array();
	if (strtolower(get_class($calendar)) == 'groupcalendar') {
		foreach ($calendar->calendars as $user_calendar) {
			if ($event_count = $user_calendar->view->numberOfEvents($day_time)) {
				if ($event_count > 1) {
					$txt = _("%s hat %s Termine");
				} else {
					$txt = _("%s hat 1 Termin");
				}

				if ($forum['jshover'] == 1 && $auth->auth['jscript']) {
					$event_count_txt[] = sprintf($txt,
							'<b>' . get_fullname($user_calendar->getUserId(), 'no_title_rev') . '</b>',
							$event_count);
				} else {
					$event_count_txt[] = sprintf($txt,
							get_fullname($user_calendar->getUserId(), 'no_title_rev'),
							$event_count);
				}
			}
		}
		if (sizeof($event_count_txt)) {
			if ($forum['jshover'] == 1 && $auth->auth['jscript']) {
				$out .= implode('<hr>', $event_count_txt);
			} else {
				$out .= implode('; ', $event_count_txt);
			}

			$out = " onmouseover=\"return overlib('" . JSReady($out, 'contact') . "',CAPTION,'"
					. ldate($day_time)
				//	. "&nbsp; &nbsp; ". $jscript_title
					. "',NOCLOSE,CSSOFF);\" onmouseout=\"return nd();\"";
		}
	} else {
		$event_count = $calendar->view->numberOfEvents($day_time);
		if ($event_count > 1) {
			$out = tooltip(sprintf(_("%s Termine"), $event_count));
		} elseif ($event_count == 1) {
			$out = tooltip(_("1 Termin"));
		}
	}

	return $out;
}

function jumpTo ($month, $day, $year, $colsp = 1) {
	global $atime, $cmd, $PHP_SELF, $CANONICAL_RELATIVE_PATH_STUDIP, $RELATIVE_PATH_CALENDAR;

	$atimetxt = ($day && $month && $year) ?
			'&imt=' . mktime(12, 0, 0, $month, $day, $year) : '';
	if (strlen($day) < 2) {
		$day = '0' . $day;
	}
	if (strlen($month) < 2) {
		$month = '0' . $month;
	}
	$ret = "<!-- CALENDAR JUMP TO -->\n";
	$ret .= "<form action=\"$PHP_SELF?cmd=$cmd&atime=$atime\" method=\"post\" name=\"jump_to\">\n";
	$ret .= "<span style=\"font-size: small; color: #555555;\">";
	$ret .= _("Gehe zu:") . "&nbsp;</span>";
	$ret .= "<input type=\"text\" name=\"jmp_day\" size=\"2\" maxlength=\"2\" value=\"$day\">";
	$ret .= "&nbsp;.&nbsp;<input type=\"text\" name=\"jmp_month\" size=\"2\" maxlength=\"2\" value=\"$month\">";
	$ret .= "&nbsp;.&nbsp;<input type=\"text\" name=\"jmp_year\" size=\"4\" maxlength=\"4\" value=\"$year\">";
	$ret .= "&nbsp;<img src=\"{$GLOBALS['ASSETS_URL']}images/popupkalender.gif\" border=\"0\" ";
	$ret .= "onClick=\"window.open('". UrlHelper::getLink("termin_eingabe_dispatch.php?element_switch=jmp&submit=1&form_name=jump_to{$atimetxt}&mcount=6&imt=$atime&atime=$atime");
	$ret .= "', 'InsertDate', 'dependent=yes, width=700, height=450, left=250, top=150')\" style=\"vertical-align:bottom;\">";
	$ret .= '&nbsp;';
	$ret .= '<input type="image" src="' . $GLOBALS['ASSETS_URL'] . 'images/GruenerHakenButton.png" border="0" style="vertical-align: bottom;">';
	$ret .= "</form>\n";
	$ret .= "<!-- END CALENDAR JUMP TO -->\n";
	return $ret;
}

/**
* Creates a small month view.
*
* @access public
* @param int $imt A unix time stamp within the time range of the month.
* @param string $href The part of the query with parameters needed for the script where this calendar is embedded.
* @param int $mod Possible modifications are: 'NOKW' hide calendar weeks; 'NONAVARROWS': hide navigation arrows;<br>
* 'NONAV': calendar weeks will not be linked to the calendars week view.
* @param string $js_include Java Script triggered by onClick event handler.
* @param int $ptime The day with this time stamp gets a red border.
*/
function includeMonth ($imt, $href, $mod = '', $js_include = '', $ptime = '') {
	global $ABSOLUTE_PATH_STUDIP, $RELATIVE_PATH_CALENDAR, $CANONICAL_RELATIVE_PATH_STUDIP;
	require_once $RELATIVE_PATH_CALENDAR . '/lib/CalendarMonth.class.php';

	$amonth = new CalendarMonth($imt);
	$now = mktime(12, 0, 0, date('n', time()), date('j', time()), date('Y', time()), 0);
	$width = '25';
	$height = '25';

	$ret = "<table valign=\"top\" class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\">\n";
	$ret .= "<tr><td class=\"steelgroup0\" align=\"center\">\n";
	$ret .= "<table border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";
	$ret .= "<tr>\n";

	// navigation arrows left
	$ret .= "<td align=\"center\" class=\"steelgroup0\" valign=\"top\" style=\"white-space:nowrap;\">\n";
	if ($mod == 'NONAV' || $mod == 'NONAVARROWS') {
		$ret .= '&nbsp;';
	} else {
		$ret .= "<a href=\"$href$ptime&imt=";
		$ret .= mktime(0, 0, -1, $amonth->mon, 15, $amonth->year - 1) . "\">";
		$ret .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous_double_small.gif\"";
		$ret .= tooltip(_("ein Jahr zurück")) . "></a>";
		$ret .= "<a href=\"$href$ptime&imt=" . ($amonth->getStart() - 1) . "\">";
		$ret .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous_small.gif\"";
		$ret .= tooltip(_("einen Monat zurück")) . "></a>\n";
	}
	$ret .= "</td>\n";

	// month and year
	$ret .= '<td class="precol1w" colspan="'. (($mod == 'NOKW')? 5:6). '" align="center">';
	$ret .= sprintf("%s %s</td>\n",
			htmlentities(strftime("%B", $amonth->getStart()), ENT_QUOTES), $amonth->getYear());

	// navigation arrows right
	$ret .= '<td class="steelgroup0" align="center" valign="top" style="white-space:nowrap;">';
	if ($mod == 'NONAV' || $mod == 'NONAVARROWS') {
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
	if ($mod != 'NOKW')
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

		if (abs($now - $i) < 43199 && !($mod == 'NONAV' && $style == 'light'))
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
		if (abs($ptime - $i) < 43199 )
			$aday = "<span style=\"border-width: 2px; border-style: solid; "
					. "border-color: #DD0000; padding: 2px;\">$aday</span>";

		if (($j + 1) % 7 == 0) {
			if ($mod == 'NONAV' && $style == 'light') {
				$ret .= '&nbsp;'; // Tag gehört nicht zu diesem Monat
			} else {
				$ret .= "<a class=\"{$style}sdaymin\" href=\"$href$i\"";
				if ($hday['name'])
					$ret .= ' ' . tooltip($hday['name']);
				$ret .= "$js_inc>$aday</a>";
			}
			$ret .= "</td>\n";

			if ($mod != 'NOKW') {
				$ret .= " <td class=\"steel1\" align=\"center\" width=\"$width\" height=\"$height\">";
				if ($mod != 'NONAV') $ret .= "<a href=\"./calendar.php?cmd=showweek&atime=$i\">";
				$ret .= "<font class=\"kwmin\">" . strftime("%V", $i) . "</font>";
				if ($mod != 'NONAV') $ret .= '</a>';
				$ret .= '</td>';
			}
			$ret .= "</tr>\n";
		}
		else {
			if ($mod == 'NONAV' && $style == 'light') {
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

function fit_title ($title, $cols, $rows, $max_length, $end_str = "...", $pad = TRUE) {
	global $auth;
	if ($auth->auth['jscript'])
		$max_length = $max_length * ($auth->auth['xres'] / 1024);

	$title_length = strlen($title);
	$length = ceil($max_length / $cols);
	$new_title = substr($title, 0, $length * $rows);

	if (strlen($new_title) < $title_length)
		$new_title = substr($new_title, 0, - (strlen($end_str))) . $end_str;

	$new_title = htmlentities(chunk_split($new_title, $length, "\n"),ENT_QUOTES);
	$new_title = substr(str_replace("\n", '<br>', $new_title), 0, -4);

	if ($pad && $title_length < $length)
		$new_title .= str_repeat('&nbsp;', $length - $title_length);

	return $new_title;
}

function js_hover ($aterm) {
	global $forum, $auth;

	if ($forum['jshover'] == 1 && $auth->auth['jscript']) { // Hovern
		$jscript_text = '<b>' . _("Zusammenfassung:") . ' </b>'
				. htmlReady($aterm->getTitle()) . '<hr>';

		if (strtolower(get_class($aterm)) == 'seminarevent' || strtolower(get_class($aterm)) == 'seminarcalendarevent') {
			$jscript_text .= '<b>' . _("Veranstaltung:") . ' </b> '
					. htmlReady($aterm->getSemName()) . '<br>';
		}
		if ($aterm->getDescription()) {
			$jscript_text .= '<b>' . _("Beschreibung:") . ' </b> '
					. htmlReady($aterm->getDescription()) . '<br>';
		}
		if ($categories = $aterm->toStringCategories()) {
			$jscript_text .= '<b>' . _("Kategorie:") . ' </b> '
					. htmlReady($categories) . '<br>';
		}
		if ($aterm->getLocation()) {
			$jscript_text .= '<b>' . _("Ort:") . ' </b> '
					. htmlReady($aterm->getLocation()) . '<br>';
		}
		if (strtolower(get_class($aterm)) != 'seminarevent') {
			if ($aterm->toStringPriority()) {
				$jscript_text .= '<b>' . _("Priorit&auml;t:") . ' </b>'
						. htmlReady($aterm->toStringPriority()) . '<br>';
			}
			$jscript_text .= '<b>' . _("Zugriff:") . ' </b>'
					. htmlReady($aterm->toStringAccessibility()) . '<br>';
			$jscript_text .= '<b>' . _("Wiederholung:") . ' </b>'
					. htmlReady($aterm->toStringRecurrence()) . '<br>';
		}

		$jscript_text = "'" . JSReady($jscript_text, 'contact')
								. "',CAPTION,'"
								. JSReady($aterm->toStringDate('SHORT_DAY'))
							//	. "&nbsp; &nbsp; ". $jscript_title
								. "',NOCLOSE,CSSOFF";

		return " onmouseover=\"return overlib($jscript_text);\" onmouseout=\"return nd();\"";
	}

	return '';
}


function info_icons (&$event) {
	global $CANONICAL_RELATIVE_PATH_STUDIP;

	$out = '';
	if ($event->havePermission(CALENDAR_EVENT_PERM_READABLE) && (strtolower(get_class($event)) == 'seminarcalendarevent' || strtolower(get_class($event)) == 'seminarevent')) {
		$out .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/projectevent-icon.gif\" ";
		$out .= "border=\"0\"" . tooltip(_("Projekttermin") . ' - ' . $event->getSemName()) . " valign>";
	}

	if ($event->getType() == 'PUBLIC') {
		$out .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/calendar_public.gif\" ";
		$out .= 'border="0"' . tooltip($event->toStringAccessibility()) . '>';
	}
	else if ($event->getType() == 'CONFIDENTIAL') {
		$out .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/calendar_confid.gif\" ";
		$out .= 'border="0"' . tooltip($event->toStringAccessibility()) . '>';
	}

	if ($event->getRepeat('rtype') != 'SINGLE') {
		$out .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/recur.gif\" ";
		$out .= 'border="0"' . tooltip($event->toStringRecurrence()) . '>';
	}

	if ($out != '') {
		$out =  "<div align=\"right\">" . $out . "</div>";
	}

	return $out;
}


function print_jscript_hover () {
	global $forum, $auth;

	// JS switched on in browser and "My Stud.IP"?
	if ($forum["jshover"] == 1 && $auth->auth["jscript"]) {
		echo "<script language=\"JavaScript\">";
		echo "var ol_textfont = \"Arial\"";
		echo "</script>";
		echo "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>";
		echo "<script language=\"JavaScript\" src=\"overlib.js\"></script>";
	}
}


function create_day_view (&$calendar, $start, $end, $step, $atime, $params) {
	global $PHP_SELF;

	$out .= "<table width=\"100%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	$out .= "<tr><td class=\"blank\" width=\"100%\">\n";
	$out .= "<table class=\"steelgroup0\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";

	$out .= "<tr><td align=\"center\" width=\"10%\" height=\"40\"><a href=\"$PHP_SELF?cmd=showday&atime=";
	$out .= $atime - 86400 . "\">\n";
	$tooltip = tooltip(_("zurück"));
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_previous.gif\"$tooltip></a></td>\n";
	$out .= "<td class=\"calhead\" width=\"80%\" class=\"cal\"><b>\n";

	$out .= $calendar->view->toString("LONG") . ", " . $calendar->view->getDate();
	// event. Feiertagsnamen ausgeben
	$out .= "<div style=\"text-align: center; font-size: 12pt; color: #BBBBBB; height: auto; overflow: visible; font-weight: bold;\">";
	$holiday = holiday($calendar->view->getTs());
	$out .= $holiday['name'] . "</div></td>\n";

	$out .= "</b></td>\n";
	$out .= "<td align=\"center\" width=\"10%\"><a href=\"$PHP_SELF?cmd=showday&atime=";
	$out .= $atime + 86400 . "\">\n";
	$tooltip = tooltip(_("vor"));
	$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_next.gif\"$tooltip></a></td>\n";
	$out .= "</tr>\n";

	$at = date('G', $atime);
	if ($start > 0) {
		$out .= "<tr><td align=\"center\" colspan=\"3\"><a href=\"$PHP_SELF?cmd=showday&atime=";
		$out .= ($atime - ($at - $start + 1) * 3600) . "\">";
		$tooltip = tooltip(_("zeig davor"));
		$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_up.gif\"$tooltip></a></td></tr>\n";
	}
	$out .= "</table>\n</td></tr>\n<tr><td class=\"blank\">\n";
	$out .= "<table class=\"steelgroup0\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">";
	$tab = createDayTable($calendar->view, $start, $end, $step, $params);
	$out .= $tab["table"];

	if ($end < 23) {
		$out .= "<tr><td align=\"center\" colspan=\"" . $tab["max_columns"] . "\">";
		$out .= "<a href=\"$PHP_SELF?cmd=showday&atime=";
		$out .= ($atime + ($end - $at + 1) * 3600) . "\">";
		$tooltip = tooltip(_("zeig danach"));
		$out .= "<img border=\"0\" src=\"{$GLOBALS['ASSETS_URL']}images/calendar_down.gif\"$tooltip></a></td></tr>\n";
	}
	else
		$out .= "<tr><td colspan=\"" . $tab["max_columns"] . "\">&nbsp;</td></tr>\n";

	$out .= "</table>\n</td></tr>\n</table>\n";

	return $out;
}


function restrict_category ($selected, $cmd, $atime) {
	global $PHP_SELF, $PERS_TERMIN_KAT;

	$out = "\n<!-- CALENDAR RESTRICT CATEGORIES -->\n";
	$out .= "<form name=\"cal_restrict_categories\" method=\"post\" action=\"$PHP_SELF?cmd=$cmd&atime=$atime\">\n";
	$out .= "<span style=\"font-size: small; color: #555555;\">";
	$out .= _("Kategorie:") . "&nbsp;</span>";
	$out .= "<select style=\"font-size: small;\" name=\"cal_restrict[studip_category]\" onChange=\"document.cal_restrict_categories.submit();\">\n";
	$out .= "<option value=\"\" style=\"font-weight: bold;\">" . _("Alle Kategorien") . "</option>\n";
	foreach ($PERS_TERMIN_KAT as $key => $category) {
		$out .= "<option value=\"$key\" ";
		if ($selected == $key)
			$out .= "selected=\"selected\" ";
		$out .= "style=\"color: {$category['color']}; font-weight: bold;\">";
		$out .= htmlReady($category['name']) . "</option>\n";
	}
	$out .= "</select>&nbsp;";
	$out .= '<input type="image" src="' . $GLOBALS['ASSETS_URL'] . 'images/GruenerHakenButton.png" border="0" style="vertical-align: bottom;"></form>';
	$out .= "<!-- END CALENDAR RESTRICT CATEGORIES -->\n";

	return $out;
}

function quick_search_form ($search_string, $cmd, $atime) {
	global $PHP_SELF;

	$out = "\n<!-- CALENDAR QUICK SEARCH -->\n";
	$out .= "<form name=\"cal_event_search\" method=\"post\" action=\"$PHP_SELF?cmd=$cmd&atime=$atime\">\n";
	$out .= "<font font size=\"2\" color=\"#555555\">";
	$out .= _("Suche: ") . " </font>";
	$out .= "<input type=\"text\" name=\"cal_quick_search\" size=\"15\" maxlength=\"50\">";
	$out .= stripslashes($search_string) . "</input>\n";
	$out .= '<input type="image" src="' . $GLOBALS['ASSETS_URL'] . 'images/GruenerHakenButton.png" border="0" style="vertical-align: bottom;"></form>';
	$out .= "<!-- END CALENDAR QUICK SEARCH -->\n";

	return $out;
}

?>