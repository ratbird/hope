<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* calendar_visual.inc.php
*
*
*
* @author       Peter Thienel <pthienel@web.de>
* @access       public
* @modulegroup  calendar
* @module       calendar
* @package  calendar
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// calendar_visual.inc.php
//
// Copyright (c) 2003 Peter Tienel <pthienel@web.de>
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

// Tabellenansicht der Termine eines Tages erzeugen
function createDayTable ($day_obj, $start = 6, $end = 19, $step = 900, $precol = TRUE,
            $compact = TRUE, $link_edit = FALSE, $title_length = 70,
            $height = 20, $padding = 6, $spacing = 1, $bg_image = 'big') {

    global $atime, $PHP_SELF, $CANONICAL_RELATIVE_PATH_STUDIP;
    $term = array();    // Array mit eingeordneten Terminen und Platzhaltern (mixed[])
    $colsp = array();   // Breite der Spalten in den einzelnen Zeilen (int[])
    $tab = array();     // html-Ausgabe der Tabelle zeilenweise (String[])
    $max_spalte = 0;    // maximale Spaltenzahl der Tabelle
    $height_event = $height;
    $width_precol_1 = 5;
    $width_precol_2 = 4;
    $day_event_row = "";
    // emphesize the current day if $compact is FALSE (this means week-view)
    if (date("Ymd", $day_obj->getStart()) == date("Ymd") && !$compact)
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
            $height_precol_2 = "";
            $rowspan_precol = "";
            $width_precol_1_txt = "";
            $width_precol_2_txt = "";
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

    // Die Generierung der Tabellenansicht erfolgt mit Hilfe geklonter Termine,
    // da die Anfangs- und Endzeiten zur korrekten Darstellung evtl. angepasst
    // werden muessen
    for ($i = 0; $i < sizeof($day_obj->events); $i++) {
        if (($day_obj->events[$i]->getEnd() >= $day_obj->getStart() + $start)
                && ($day_obj->events[$i]->getStart() < $day_obj->getStart() + $end + 3600)) {

            if ($day_obj->events[$i]->isDayEvent()
                    || ($day_obj->events[$i]->getStart() <= $day_obj->getStart()
                    && $day_obj->events[$i]->getEnd() >= $day_obj->getEnd())) {
                $cloned_day_event = $day_obj->events[$i]->getClone();
                $cloned_day_event->setStart($day_obj->getStart());
                $cloned_day_event->setEnd($day_obj->getEnd());
                $tmp_day_event[] = $cloned_day_event;
                $map_day_events[] = $i;
            }
            else {
                $cloned_event = $day_obj->events[$i]->getClone();
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
                        $term[$x][$y] = "#";
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
            $day_event_row[0] = "<td class=\"precol1w\" width=\"$width_precol_1%\">";
            $day_event_row[0] .= "<a class=\"calhead\" href=\"$PHP_SELF?cmd=edit&atime=";
            $day_event_row[0] .= $day_obj->getTs() . '&devent=1">' . _("Tag") . '</a></td>';
            $day_event_row[0] .= "<td class=\"$style_cell\" width=\"".(100 - $width_precol_1)."%\"";
        }
        else {
            $day_event_row[0] = "<td class=\"precol1w\" width=\"".($width_precol_1 + $width_precol_2)."\" colspan=\"2\">";
            $day_event_row[0] .= "<a class=\"calhead\" href=\"$PHP_SELF?cmd=edit&atime=";
            $day_event_row[0] .= $day_obj->getTs() . '&devent=1">' . _("Tag") . "</a></td>";
            $day_event_row[0] .= "<td height=\"40\" class=\"$style_cell\" width=\"".(100 - $width_precol_1 - $width_precol_2)."%\"";
      }
    }
    else
        $day_event_row[0] = "<td class=\"$style_cell\"";

    if ($tmp_day_event) {

        if ($max_spalte > 0)
            $day_event_row[0] .= " colspan=\"" . ($max_spalte + $link_edit_column) . "\"";

        $day_event_row[0] .= " valign=\"bottom\"><table width=\"100%\" border=\"0\" cellpadding=\"";
        //$day_event_row[0] .= ($padding / 2) . "\" cellspacing=\"1\">\n";
        $day_event_row[0] .= "0\" cellspacing=\"0\">";
        $i = 0;
        foreach ($tmp_day_event as $day_event) {
            $category_style = $day_event->getCategoryStyle($bg_image);
            $title = fit_title($day_event->getTitle(), 1, 1, $title_length);
            $title_str = sprintf("<a style=\"color: #FFFFFF; font-size:10px;\" href=\"$PHP_SELF?cmd=edit&termin_id=%s&atime=%s%s\" %s>"
                                                    , $day_event->getId(), $day_event->getStart()
                                                    , strtolower(get_class($day_event)) == 'seminarevent' ? '&evtype=sem' : ''
                                                    , js_hover($day_obj->events[$map_day_events[$i]]));
            $title_str .= $title . '</a>';
            $day_event_row[0] .= "<tr><td height=\"20\" valign=\"top\" style=\"border-style:solid; border-width:1px; border-color:";
            $day_event_row[0] .= $category_style['color'] . "; background-image:url(";
            $day_event_row[0] .= $category_style['image'] . ");\">";
            $day_event_row[0] .= $title_str;
            $day_event_row[0] .= info_icons($day_event);
            $day_event_row[0] .= "</td>";
            $i++;
        }
        if ($link_edit) {
            $tooltip = tooltip(_("neuer Tagestermin"));
            $day_event_row[0] .= "<td class=\"$style_cell\" align=\"right\" valign=\"bottom\" rowspan=\"";
            $day_event_row[0] .= sizeof($tmp_day_event) . "\"><a href=\"$PHP_SELF?cmd=edit&atime=";
            $day_event_row[0] .= $day_obj->getTs() . "&devent=1\">";
            $day_event_row[0] .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/calplus.gif\" ";
            $day_event_row[0] .= "border=\"0\" $tooltip></a></td>\n";
        }

        $day_event_row[0] .= "</table></td>";
    }
    else {
        if ($max_spalte > 0)
            $day_event_row[0] .= " colspan=\"" . ($max_spalte + $link_edit_column) . "\"";

        if ($link_edit) {
            $tooltip = tooltip(_("neuer Tagestermin"));
            $day_event_row[0] .= " align=\"right\" valign=\"bottom\"><a href=\"$PHP_SELF?cmd=edit&atime=";
            $day_event_row[0] .= $day_obj->getTs() . "&devent=1\">";
            $day_event_row[0] .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/calplus.gif\" ";
            $day_event_row[0] .= "border=\"0\" $tooltip></a></td>\n";
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
                $tab[$zeile] .= "<a class=\"calhead\" href=\"$PHP_SELF";
                $tab[$zeile] .= sprintf("?cmd=edit&atime=%s\">%s</a></td>"
                                                , $day_obj->getStart() + $i * $step, $i / (3600 / $step));
                $width_precol_1_txt = "";
            }
            // bei Intervallen mit vollen Stunden Minuten ausblenden
            if ($step % 3600 != 0) {
                $tab[$zeile] .= "<td class=\"precol2\"$width_precol_2_txt$height_precol_2>";
                $tab[$zeile] .= sprintf("<a class=\"calhead\" href=\"$PHP_SELF?cmd=edit&atime=%s\">"
                                                , ($day_obj->getStart() + $i * $step));
                $minute = ($zeile % (3600 / $step)) * ($step / 60);
                if($minute == 0)
                    $tab[$zeile] .= "00</a></td>";
                else
                    $tab[$zeile] .= $minute."</a></td>";
                $width_precol_2_txt = "";
            }
        }

        $link_notset = TRUE;
        if (!$term[$zeile]) {
            if ($link_edit) {
                if ($max_spalte > 0) {
                    $tab[$zeile] .= "<td class=\"$style_cell\" align=\"right\"  valign=\"bottom\" colspan=\"";
                    $tab[$zeile] .= ($max_spalte + 1) . "\"><a href=\"$PHP_SELF?cmd=edit&atime=";
                    $tab[$zeile] .= ($day_obj->getStart() + $i * $step);
                    $tab[$zeile] .= "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/calplus.gif\" ";
                    $tab[$zeile] .= "border=\"0\" $link_edit_tooltip></a></td>\n";
                }
                else {
                    $tab[$zeile] .= "<td class=\"$style_cell\" align=\"right\" valign=\"bottom\">";
                    $tab[$zeile] .= "<a href=\"$PHP_SELF?cmd=edit&atime=";
                    $tab[$zeile] .= ($day_obj->getStart() + $i * $step);
                    $tab[$zeile] .= "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/calplus.gif\"";
                    $tab[$zeile] .= "border=\"0\" $link_edit_tooltip></a></td>\n";
                }
            }
            else {
                if ($max_spalte > 1) {
                    $tab[$zeile] .= "<td class=\"$style_cell\" colspan=\"$max_spalte\">";
                    $tab[$zeile] .= "<font class=\"inday\">&nbsp;</font></td>\n";
                }
                else
                    $tab[$zeile] .= "<td class=\"$style_cell\"><font class=\"inday\">&nbsp;</font></td>\n";
            }

            $height = "";
            // Wenn bereits hier ein Link eingefuegt wurde braucht weiter unten keine
            // zusaetliche Spalte ausgegeben werden
            $link_notset = FALSE;
        }
        else {
            if($colsp[$zeile] > 0)
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
                            }
                            else
                                break;
                        }
                        $cspan += $sp;
                    }

                    $rows = ceil($term[$zeile][$j]->getDuration() / $step);
                    $tab[$zeile] .= '<td';

                    if ($cspan > 1)
                        $tab[$zeile] .= ' colspan="'.$cspan.'"';
                    if ($rows > 1)
                        $tab[$zeile] .= ' rowspan="'.$rows.'"';
                    else
                        $rows = 1;

                    $category_style = $term[$zeile][$j]->getCategoryStyle($bg_image);
                    $tab[$zeile] .= " style=\"vertical-align:top; font-size:10px; color:#FFFFFF;";
                    $tab[$zeile] .= " background-image:url(";
                    $tab[$zeile] .= $category_style['image'];
                    $tab[$zeile] .= "); border-style:solid; border-width:1px; border-color:";
                    $tab[$zeile] .= $category_style['color'] . ";\">";

                    if (strtolower(get_class($term[$zeile][$j])) == 'seminarevent'
                            && ($term[$zeile][$j]->getTitle() == _("Kein Titel") || !$term[$zeile][$j]->getTitle()) ) {
                        $title_out = $term[$zeile][$j]->getSemName();
                    }
                    else
                        $title_out = $term[$zeile][$j]->getTitle();

                    if ($rows == 1) {
                        $title = fit_title($title_out, $colsp[$zeile], $rows, $title_length - 6);

                        $tab[$zeile] .= sprintf("<a style=\"color: #FFFFFF;\" href=\"$PHP_SELF?cmd=edit&termin_id=%s&atime=%d%s\" %s>"
                                                    , $term[$zeile][$j]->getId()
                                                    , ($day_obj->getStart() + $term[$zeile][$j]->getStart() % 86400)
                                                    , strtolower(get_class($term[$zeile][$j])) == "seminarevent" ? "&evtype=sem" : ""
                                                    , js_hover($day_obj->events[$mapping[$zeile][$j]]));
                        $tab[$zeile] .= $title . "</a>";
                    }
                    else {
                        $title = fit_title($title_out, $colsp[$zeile], $rows - 1, $title_length);
                        $tab[$zeile] .= "<div style=\"font-size:10px; height:15px; background-color:";
                        $tab[$zeile] .= $category_style['color'];
                        $tab[$zeile] .= ";\">" . date('H.i-', $day_obj->events[$mapping[$zeile][$j]]->getStart());
                        $tab[$zeile] .= date('H.i', $day_obj->events[$mapping[$zeile][$j]]->getEnd()) . "</div>\n";
                        $tab[$zeile] .= sprintf("<a style=\"color: #FFFFFF;\" href=\"$PHP_SELF?cmd=edit&termin_id=%s&atime=%d%s\" %s>"
                                                    , $term[$zeile][$j]->getId()
                                                    , ($day_obj->getStart() + $term[$zeile][$j]->getStart() % 86400)
                                                    , strtolower(get_class($term[$zeile][$j])) == "seminarevent" ? "&evtype=sem" : ""
                                                    , js_hover($day_obj->events[$mapping[$zeile][$j]]));
                        $tab[$zeile] .= $title . "</a>";
                    }
                    $tab[$zeile] .= info_icons($term[$zeile][$j]);
                    $tab[$zeile] .= "</td>\n";

                    if ($sp > 0) {
                        for ($m = $zeile;$m < $rows + $zeile;$m++) {
                            $colsp[$m] = $colsp[$m] - $sp + 1;
                            $v = $j;
                            while ($term[$m][$v] == "#")
                                $term[$m][$v] = 1;
                        }
                        $j = $n;
                    }
                }

                elseif ($term[$zeile][$j] == "#") {
                    $csp = $link_edit_column;
                    if ($link_edit)
                        $csp--;
                    while ($term[$zeile][$j] == "#") {
                        $csp += $cspan;
                        $j++;
                    }
                    if ($csp > 1)
                        $colspan_attr = " colspan=\"$csp\"";
                    else
                        $colspan_attr = "";

                        $tab[$zeile] .= "<td class=\"$style_cell\"$colspan_attr>";
                        $tab[$zeile] .= "<font class=\"inday\">&nbsp;</font></td>\n";

                    $height = "";
                }

                elseif ($term[$zeile][$j] == "") {
                    $csp = $max_spalte - $j + $link_edit_column;
                    if ($csp > 1)
                        $colspan_attr = " colspan=\"$csp\"";
                    else
                        $colspan_attr = "";

                    if ($link_edit) {
                        $tab[$zeile] .= "<td class=\"$style_cell\"$colspan_attr align=\"right\" valign=\"bottom\">";
                        $tab[$zeile] .= sprintf("<a href=\"$PHP_SELF?cmd=edit&atime=%s\">"
                                                            , $day_obj->getStart() + $i * $step);
                        $tab[$zeile] .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/calplus.gif\" ";
                        $tab[$zeile] .= "border=\"0\" $link_edit_tooltip>";
                        $tab[$zeile] .= "</a></td>\n";
                    }
                    else {
                        $tab[$zeile] .= "<td class=\"$style_cell\"$colspan_attr>";
                        $tab[$zeile] .= "<font class=\"inday\">&nbsp;</font></td>\n";
                    }

                    $link_notset = FALSE;
                    $height = "";
                    break;
                }

            }

        }

        if ($link_edit && $link_notset) {
            $tab[$zeile] .= "<td class=\"$style_cell\" align=\"right\" valign=\"bottom\">";
            $tab[$zeile] .= "<a href=\"$PHP_SELF?cmd=edit&atime=" . ($day_obj->getStart() + $i * $step);
            $tab[$zeile] .= "\"><img src=\"".$GLOBALS['ASSETS_URL']."images/calplus.gif\" ";
            $tab[$zeile] .= "border=\"0\" $link_edit_tooltip>";
            $tab[$zeile] .= "</a></td>\n";
        }

        if ($compact)
            $tab[$zeile] .= "</tr>\n";

        // sonst zerlegt array_merge (siehe unten) die Tabelle
        if (!isset($tab[$zeile]))
            $tab[$zeile] = "";

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

    $tab = array_merge((array)$day_event_row, (array)$tab);

    if ($compact)
        $tab = implode("", $tab);

    return array("table" => $tab, "max_columns" => $max_spalte);

}

function maxValue ($term, $st) {
    $max_value = 0;
    for ($i = 0; $i < sizeof($term); $i++) {
        if (is_object($term[$i]))
            $max = ceil($term[$i]->getDuration() / $st);
        elseif ($term[$i] == "#")
            continue;
        elseif ($term[$i] > $max_value)
            $max = $term[$i];
        if ($max > $max_value)
            $max_value = $max;
    }

    return $max_value;
}

// Tabellenansicht der Termine fuer eine Woche
function createWeekTable ($week_obj, $start = 6, $end = 21, $step = 3600,
                                                    $compact = TRUE, $link_edit = FALSE) {
    global $PHP_SELF;
    $tab_arr = "";
    $tab = "";
    $max_columns = 0;
    $rows = ($end - $start + 1) * 3600 / $step;
    // calculating the maximum title length
    $length = ceil(125 / $week_obj->getType());

    for ($i = 0; $i < $week_obj->getType(); $i++)
        $tab_arr[$i] = createDayTable($week_obj->wdays[$i], $start, $end, $step, FALSE,
                                                FALSE, $link_edit, $length, 20, 4, 1, 'small');

    // weekday and date as title for each column
    for ($i = 0; $i < $week_obj->getType(); $i++) {
        // add up all colums of each day
        $max_columns += $tab_arr[$i]["max_columns"];
        $dtime = $week_obj->wdays[$i]->getTs();
        if ($week_obj->getType() == 5)
            $tab[0] .= "<td class=\"steelgroup0\" align=\"center\" width=\"19%\"";
        else
            $tab[0] .= "<td class=\"steelgroup0\" align=\"center\" width=\"13%\"";

        if ($tab_arr[$i]["max_columns"] > 1)
            $tab[0] .= " colspan=\"{$tab_arr[$i]["max_columns"]}\"";
        $tab[0] .= "><a class=\"calhead\" href=\"$PHP_SELF?cmd=showday&atime=$dtime\"><b>";
        $tab[0] .= wday($dtime, "SHORT") . " " . date("d", $dtime) . "</b></a></td>\n";
    }
    if ($compact)
        $tab[0] = "<tr>{$tab[0]}</tr>\n";

    // put the table together
    for ($i = 1;$i < $rows + 2;$i++){
        if ($compact)
            $tab[$i] .= "<tr>";
        for ($j = 0; $j < $week_obj->getType(); $j++){
            $tab[$i] .= $tab_arr[$j]["table"][$i - 1];
        }
        if ($compact)
            $tab[$i] .= "</tr>\n";
    }

    if ($compact)
        $tab = implode("", $tab);

    return array("table" => $tab, "max_columns" => $max_columns);

}

function jumpTo ($month, $day, $year, $colsp = 1) {
    global $atime, $cmd, $PHP_SELF;

    echo "<tr><td";
    if ($colsp > 1)
        echo " colspan=\"$colsp\"";
    echo ">&nbsp;</td></tr>\n";
    echo "<tr><td width=\"100%\" align=\"center\"";
    if ($colsp > 1)
        echo " colspan=\"$colsp\"";
    echo ">\n\n";
    echo "<form action=\"$PHP_SELF?cmd=$cmd\" method=\"post\">\n";
    echo CSRFProtection::tokenTag();
    $currentDate = ($month < 10 ? "0".$month : $month)."/".($day < 10 ? "0".$day : $day)."/".$year;
    echo "<input type=\"text\" style=\"visibility:hidden; width: 0px\" name=\"realdate\" id=\"realdate\" value=\"".$currentDate."\">";
    echo "<b>" . _("Gehe zu:") . "</b>&nbsp;&nbsp;";
    echo "<input type=\"text\" name=\"jmp_d\" size=\"2\" maxlength=\"2\" value=\"$day\">";
    echo "&nbsp;.&nbsp;<input type=\"text\" name=\"jmp_m\" size=\"2\" maxlength=\"2\" value=\"$month\">";
    echo "&nbsp;.&nbsp;<input type=\"text\" name=\"jmp_y\" size=\"4\" maxlength=\"4\" value=\"$year\">";
    echo "&nbsp;" . makeButton("absenden", "input") . "\n";
    echo "<input type=\"hidden\" name=\"atime\" value=\"$atime\">\n";
    echo "</form>\n\n</td></tr>\n";
    echo "<tr><td";
    if ($colsp > 1)
        echo " colspan=\"$colsp\"";
    echo ">&nbsp;</td></tr>\n";
}

function includeMonth ($imt, $href, $mod = "", $js_include = "", $ptime = "") {
    global $RELATIVE_PATH_CALENDAR, $CANONICAL_RELATIVE_PATH_STUDIP;
    require_once($RELATIVE_PATH_CALENDAR . "/lib/CalendarMonth.class.php");

    $amonth = new CalendarMonth($imt);
    $now = mktime(12, 0, 0, date("n", time()), date("j", time()), date("Y", time()), 0);
    $width = "25";
    $height = "25";

    $ret = "<table class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\">\n";
    $ret .= "<tr><td class=\"steelgroup0\" align=\"center\">\n";
    $ret .= "<table border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";
    $ret .= "<tr>\n";

    // navigation arrows left
    $ret .= "<td align=\"center\" class=\"steelgroup0\" valign=\"top\">\n";
    if ($mod == 'NONAV') {
        $ret .= '&nbsp;';
    } else {
        $ret .= "<a href=\"" . URLHelper::getLink($href, array('atime' => $ptime, 'imt' => mktime(0, 0, -1, $amonth->mon, 15, $amonth->year - 1))) . "\">";
        $ret .= "<img src=\"" . Assets::image_path('icons/16/blue/arr_eol-left.png') . "\"";
        $ret .= tooltip(_("ein Jahr zurück")) . "></a>";
        $ret .= "<a href=\"" . URLHelper::getLink($href, array('atime' => $ptime, 'imt' => $amonth->getStart() - 1)) . "\">";
        $ret .= "<img src=\"" . Assets::image_path('icons/16/blue/arr_2left.png') . "\"";
        $ret .= tooltip(_("einen Monat zurück")) . "></a>\n";
    }
    $ret .= "</td>\n";

    // month and year
    $ret .= '<td class="precol1w" colspan="'. (($mod == 'NOKW')? 5:6). '" align="center">';
    $ret .= sprintf("%s %s</td>\n",
            htmlentities(strftime("%B", $amonth->getStart()), ENT_QUOTES), $amonth->getYear());

    // navigation arrows right
    $ret .= "<td class=\"steelgroup0\" align=\"center\" valign=\"top\">";
    if ($mod == 'NONAV' || $mod == 'NONAVARROWS') {
        $ret .= '&nbsp;';
    } else {
        $ret .= "<a href=\"" . URLHelper::getLink($href, array('atime' => $ptime, 'imt' => $amonth->getEnd() + 1)) . "\">";
        $ret .= "<img src=\"" . Assets::image_path('icons/16/blue/arr_2right.png') . "\"";
        $ret .= tooltip(_("einen Monat vor")) . "></a>";
        $ret .= "<a href=\"" . URLHelper::getLink($href, array('atime' => $ptime, 'imt' => mktime(0, 0, 1, $amonth->mon, 1, $amonth->year + 1))) . "\">";
        $ret .= "<img src=\"" . Assets::image_path('icons/16/blue/arr_eol-right.png') . "\"";
        $ret .= tooltip(_("ein Jahr vor")) . "></a>\n";
    }
    $ret .= "</td></tr>\n";

    // weekdays
    $ret .= "<tr>\n";
    $day_names_german = array("MO", "DI", "MI", "DO", "FR", "SA", "SO");
    foreach ($day_names_german as $day_name_german)
        $ret .= "<td align=\"center\" class=\"precol2w\" width=\"$width\">" . wday("", "SHORT", $day_name_german) . "</td>\n";
    if ($mod != "NOKW")
        $ret .= "<td class=\"precol2w\" width=\"$width\">&nbsp;</td>";
    $ret .= "</tr>\n</table></td></tr>\n<tr><td class=\"blank\">";
    $ret .= "<table class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">";

    // Im Kalenderblatt ist links oben immer Montag. Das muss natuerlich nicht der
    // Monatserste sein. Es muessen evtl. noch Tage des vorangegangenen Monats
    // am Anfang und des folgenden Monats am Ende angefuegt werden.
    $adow = date("w", $amonth->getStart());
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
        $style = "";
        if (($aday - $j - 1 > 0) || ($j - $aday  > 6))
            $style = "light";

        // Feiertagsueberpruefung
        $hday = holiday($i);

        if ($j % 7 == 0)
            $ret .= "<tr>";

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
                $ret .= "<a class=\"{$style}sdaymin\" href=\"" . URLHelper::getLink($href, array('atime' => $i)) . "\"";
                if ($hday['name'])
                    $ret .= ' ' . tooltip($hday['name']);
                $ret .= "$js_inc>$aday</a>";
            }
            $ret .= "</td>\n";

            if ($mod != "NOKW") {
                $ret .= " <td class=\"steel1\" align=\"center\" width=\"$width\" height=\"$height\">";
                if ($mod != 'NONAV') $ret .= "<a href=\"./calendar.php?cmd=showweek&atime=$i\">";
                $ret .= "<font class=\"kwmin\">" . strftime("%V", $i) . "</font>";
                if ($mod != 'NONAV') $ret .= '</a>';
                $ret .= "</td>";
            }
            $ret .= "</tr>\n";
        }
        else {
            if ($mod == 'NONAV' && $style == 'light') {
                $ret .= '&nbsp;'; // Tag gehört nicht zu diesem Monat
            } else {
                // unterschiedliche Darstellung je nach Art des Tages (Rang des Feiertages)
                switch ($hday["col"]) {
                    case 1:
                        $ret .= "<a class=\"{$style}daymin\" href=\"" . URLHelper::getLink($href, array('atime' => $i)) . "\" ";
                        $ret .= tooltip($hday['name']) . "$js_inc>$aday</a>";
                        break;
                    case 2:
                    case 3:
                        $ret .= "<a class=\"{$style}hdaymin\" href=\"" . URLHelper::getLink($href, array('atime' => $i)) . "\" ";
                        $ret .= tooltip($hday['name']) . "$js_inc>$aday</a>";
                        break;
                    default:
                        $ret .= "<a class=\"{$style}daymin\" href=\"" . URLHelper::getLink($href, array('atime' => $i)) . "\"$js_inc>$aday</a>";
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
    if ($auth->auth["jscript"])
        $max_length = $max_length * ($auth->auth["xres"] / 1024);

    $title_length = strlen($title);
    $length = ceil($max_length / $cols);
    $new_title = substr($title, 0, $length * $rows);

    if (strlen($new_title) < $title_length)
        $new_title = substr($new_title, 0, - (strlen($end_str))) . $end_str;

    $new_title = htmlentities(wordwrap($new_title, $length, "\n", TRUE), ENT_QUOTES);
    $new_title = str_replace("\n", "<br>", $new_title);

    if ($pad && $title_length < $length)
        $new_title .= str_repeat("&nbsp;", $length - $title_length);

    return $new_title;
}

function js_hover ($aterm) {

    return "";
}

function info_icons (&$event) {
    global $CANONICAL_RELATIVE_PATH_STUDIP;

    $ret = '';
    $div = FALSE;
    $tooltip = _("Öffentlicher Termin");

    if ($event->getType() == 'PUBLIC') {
        $ret .= "<div align=\"right\">";
        $div = TRUE;
        $ret .= "<img src=\"" . Assets::image_path('icons/16/blue/visibility-visible.png') . "\" ";
        $ret .= tooltip($tooltip) . ">";
    }

    if ($event->getRepeat('rtype') != 'SINGLE') {
        if (!$div)
            $ret .= "<div align=\"right\">";
        $ret .= "<img src=\"" . Assets::image_path('icons/16/blue/refresh.png') . "\" ";
        $ret .= tooltip($event->toStringRecurrence()) . ">";
    }

    $ret .= "</div>";

    return $ret;
}

function date_insert_popup () {}


?>
