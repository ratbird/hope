<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/**
 * show_dates.inc.php - Funktionen zum Anzeigen von Terminen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Andr� Noack <anoack@mcis.de>
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Peter Thienel <thienel@data-quest.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     dates
 */

use Studip\Button, Studip\LinkButton;

require_once 'lib/visual.inc.php';
require_once 'lib/dates.inc.php';
require_once 'config.inc.php';
require_once 'lib/msg.inc.php';

if ($GLOBALS["CALENDAR_ENABLE"]) {
    require_once($RELATIVE_PATH_CALENDAR . "/lib/SingleCalendar.class.php");
    require_once($RELATIVE_PATH_CALENDAR . "/lib/DbCalendarEventList.class.php");
}
/**
 * TODO: Bedarf eine kompletten �berarbeitung!!!!
 *
 * Es wird kein Seminar-Objekt instanziert
 * -> es werden hier noch keine neuen Termine angelegt, wenn ein neues Semester eingetragen wurden
 *
 * @param $date_start
 * @param $date_end
 * @param $open
 * @param $range_id
 * @param $show_not
 * @param $show_docs
 * @param $show_admin
 * @param $full_width
 * @param $show_as_window
 */
function show_dates($date_start, $date_end, $open, $range_id = "", $show_not = 0,
                    $show_docs = TRUE, $show_admin = FALSE, $full_width = TRUE,
                    $show_as_window = TRUE)
{
    global $TERMIN_TYP, $SessSemName, $user, $username, $rechte;

    // wenn man keinen Start und Endtag angibt, soll wohl alles angezeigt werden
    // "0" bedeutet jeweils "open end"

    $parameters = array();
    if (($date_start == 0) && ($date_end == 0)) {
        $show_whole_time = TRUE;
        $tmp_query = "";
    } else if ($date_start == 0) {
        $show_whole_time = TRUE;
        $tmp_query = " AND t.date <= :date_end ";
        $parameters[':date_end'] = $date_end;
    } else if ($date_end == 0) {
        $show_whole_time=TRUE;
        $tmp_query = " AND t.date >= :date_start ";
        $parameters[':date_start'] = $date_start;
    } else {
        $tmp_query = " AND (t.date >= $date_start AND t.date <= $date_end) ";
        $parameters[':date_start'] = $date_start;
        $parameters[':date_end']   = $date_end;
    }

    if ($show_admin) {
        if ($range_id == $user->id) {
            // F�r pers�nliche Termine Einsprung in Terminkalender
            $admin_link = '<a href="' . URLHelper::getLink('calendar.php', array('cmd' => 'edit')) . '">';
        } else {
            $admin_link = '<a href="' . URLHelper::getLink('raumzeit.php', array('cid' => $range_id)) . '">';
        }
    }

    $range_typ = ($range_id != $user->id) ? "sem" : "user";

    if ($show_not) {
        // wenn Seminartermine angezeigt werden und show_not =sem
        // zeigen wir nur als Sitzungen definierte Termine
        if ($show_not == "sem") {
            $date_types = array_filter($TERMIN_TYP, function ($type) { return $type['sitzung']; });
            if (count($date_types) > 0) {
                $show_query = " AND t.date_typ IN (:date_types) ";
                $parameters[':date_types'] = array_keys($date_types);
            }
        }

        //wenn Seminartermine angezeigt werden und show_not =other zeigen wir alles andere an
        if ($show_not == "other") {
            $date_types = array_filter($TERMIN_TYP, function ($type) { return !$type['sitzung']; });
            if (count($date_types) > 0) {
                $show_query = " AND t.date_typ IN (:date_types) ";
                $parameters[':date_types'] = array_keys($date_types);
            }
        }
    }

    if (is_array($range_id)) {
        $query = "SELECT t.*, th.issue_id, th.title AS Titel,
                         th.description AS Info, s.Name
                  FROM (SELECT termin_id,range_id,date,end_time,chdate,date_typ,content, 0 as ex_termin
                        FROM termine WHERE range_id IN(:range_id)
                        UNION SELECT termin_id,range_id,date,end_time,chdate,date_typ,content, 1 as ex_termin
                        FROM ex_termine WHERE content <> '' AND range_id IN(:range_id)) AS t
                  LEFT JOIN themen_termine USING (termin_id)
                  LEFT JOIN themen AS th USING (issue_id)
                  INNER JOIN seminare AS s ON (range_id = Seminar_id)
                  WHERE 1 {$show_query} {$tmp_query}
                  ORDER BY date";
        $parameters[':range_id'] = $range_id;
    } else if (strlen($range_id)) {
        $query = "SELECT t.*, th.issue_id, th.title AS Titel,
                         th.description as Info
                  FROM (SELECT termin_id,range_id,date,end_time,chdate,date_typ,content, 0 as ex_termin
                        FROM termine WHERE range_id = :range_id
                        UNION SELECT termin_id,range_id,date,end_time,chdate,date_typ,content, 1 as ex_termin
                        FROM ex_termine WHERE content <> '' AND range_id = :range_id) AS t
                  LEFT JOIN themen_termine USING (termin_id)
                  LEFT JOIN themen AS th USING (issue_id)
                  WHERE 1 {$show_query} {$tmp_query}
                  ORDER BY date";
        $parameters[':range_id'] = $range_id;
    } else {
        $query = "SELECT t.*, th.issue_id, th.title as Titel,
                         th.description as Info, s.Name, su.*
                  FROM (SELECT termin_id,range_id,date,end_time,chdate,date_typ,content, 0 as ex_termin
                        FROM termine
                        UNION SELECT termin_id,range_id,date,end_time,chdate,date_typ,content, 1 as ex_termin
                        FROM ex_termine WHERE content <> '') AS t
                  LEFT JOIN themen_termine USING (termin_id)
                  LEFT JOIN themen AS th USING (issue_id)
                  LEFT JOIN seminare AS s ON (range_id = s.Seminar_id)
                  LEFT JOIN seminar_user su ON (s.Seminar_id = su.Seminar_id)
                  WHERE user_id = :user_id {$show_query} {$tmp_query}
                  ORDER BY date";
        $parameters[':user_id'] = $user->id;
    }

    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    $dates = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($dates) > 0) {
        // set skip link
        SkipLinks::addIndex(_("Termine"), 'appointments_box');

        // Ausgabe der Kopfzeile
        $colspan = 1;
        echo "\n<table id=\"appointments_box\" role=\"article\" class=\"index_box\"".($full_width ? " style=\"width: 100%;\"" : '').">";
        if ($show_as_window) {
            if ($show_admin) {
                $colspan++;
                if (!$show_whole_time) {
                    printf("\n<tr><td class=\"table_header_bold\"><img src=\"".Assets::image_path('icons/16/white/schedule.png')."\" %s><b>", tooltip(_("Termine. Klicken Sie rechts auf die Zahnr�der, um Termine in diesen Bereich zu bearbeiten. Klicken Sie auf den einfachen Pfeil, um die Terminbeschreibung zu lesen.")));
                    printf(_("Termine f�r die Zeit vom %s bis zum %s"), strftime("%d. %B %Y", $date_start), strftime("%d. %B %Y", $date_end));
                    printf( "</b></td>\n<td align = \"right\" class=\"table_header_bold\">%s<img src=\"".Assets::image_path('icons/16/white/admin.png')."\" %s></a></td></tr>", $admin_link, tooltip(_("Neuen Termin anlegen")));
                    }
                else {
                    printf("\n<tr><td class=\"table_header_bold\"><img src=\"".Assets::image_path('icons/16/white/schedule.png')."\" %s><b>", tooltip(_("Termine. Klicken Sie rechts auf die Zahnr�der, um Termine in diesen Bereich zu bearbeiten. Klicken Sie auf den einfachen Pfeil, um die Terminbeschreibung zu lesen.")));
                    printf(_("Termine"));
                    printf("</b></td>\n<td align = \"right\" class=\"table_header_bold\">%s<img src=\"".Assets::image_path('icons/16/white/admin.png')."\" %s ></a></td></tr>", $admin_link, tooltip(_("Neuen Termin anlegen")));
                    }
                }
            else
                if (!$show_whole_time) {
                    printf("\n<tr valign=\"baseline\"><td class=\"table_header_bold\"><img src=\"".Assets::image_path('icons/16/white/schedule.png')."\" %s><b>", tooltip(_("Termine. Klicken Sie auf den Pfeil, um eine Beschreibung des Termins anzuzeigen.")));
                    printf(_("Termine f�r die Zeit vom %s bis zum %s"), strftime("%d. %B %Y", $date_start), strftime("%d. %B %Y", $date_end));
                    print("</b></td></tr>");
                } else {
                    printf("\n<tr valign=\"baseline\"><td class=\"table_header_bold\"><img src=\"".Assets::image_path('icons/16/white/schedule.png')."\" %s><b>", tooltip(_("Termine. Klicken Sie auf den Pfeil, um eine Beschreibung des Termins anzuzeigen.")));
                    printf(_("Termine"));
                    print("</b></td></tr>");
                }
            echo "\n";
        }

        // Ausgabe der Daten
        echo "\n<tr><td class=\"blank\" colspan=\"$colspan\">";


        //open/close all (show header to switch)
        if (!$show_as_window) {
            echo "\n<table id=\"appointments_box\" role=\"article\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" align=\"center\">"; //WTF?
            print "\n<tr>";
            print "\n<td width=\"5%\" class=\"table_row_odd\" align=\"left\"> ";
            if ($rechte)
                print '<a href="' . URLHelper::getLink('raumzeit.php?cmd=createNewSingleDate#newSingleDate') . '"><img class="middle" src="' . Assets::image_path('icons/16/blue/plus.png') . '" ' . tooltip(_("Einen neuen Termin anlegen")) . '></a></td>';
            print "\n<td class=\"table_row_odd\" align=\"center\">";
            if ($open == "all")
                print '<a href="' . URLHelper::getLink('?dclose=1') . '"><img style="vertical-align:middle;" src="' . Assets::image_path('close_all.png') . '" ' . tooltip(_("Alle schlie�en")) . '></a>';
            else
                print '<a href="' . URLHelper::getLink('?dopen=all') . '"><img style="vertical-align:middle;" src="' . Assets::image_path('open_all.png') . '" ' . tooltip(_("Alle �ffnen")) . 'border="0"></a>';
            print "\n</td></tr>\n<tr><td class=\"blank\" colspan=\"2\">";
        }

        if ($username)
            $add_to_link = "&username=$username";
        if ($show_not)
            $add_to_link .= "&show_not=$show_not";

        foreach ($dates as $date ) {
            if($open == $date['termin_id'])
                $date['open'] = true;
            echo '<div role="article">';
            $zusatz = '';
            if (!$range_id || is_array($range_id)) {
                die('wirklich tot?');
                 $titel = "<a href=\"$link\" class=\"tree\" onclick=\"STUDIP.Termine.opencloseSem('"
                .$date['termin_id']."','".$show_admin."','".$date['date_typ']."','"
                .$date['info']."'); return false;\" >".$titel."</a>";
                 $zusatz .= "<a href=\"".URLHelper::getLink("seminar_main.php?auswahl=" . $date['range_id'])
                                . "onclick=\"STUDIP.Termine.opencloseSem('"
                 .$date['termin_id']."','".$show_admin."','".$date['date_typ']."','"
                 .$date['info']."','"
                .False."','".FALSE."'); return false;\" ><font size=\"-1\">" . htmlReady(mila($date['Name'], 22))
                                . "</font></a>";
                $current_seminar_id = $date['range_id'];
            }
            else {
                $termin = new SingleDate($date['termin_id']);
                if( $termin->hasRoom() ){
                    $zusatz .= _("Ort:") . " " . $termin->getRoom() . " ";
                }elseif( $freeroomtext = $termin->getFreeRoomText() ){
                    $zusatz .= " (" . htmlReady($freeroomtext) . ") ";
                }else{
                    $zusatz .= _("Ort:").' '._("k.A.") . " ";
                }
                if ($termin->isExTermin()) {
                    $zusatz = _("f�llt aus");
                }
                $current_seminar_id = $range_id;
            }

            //Dokumente zaehlen
            $num_docs = 0;
            $folder_id = '';
            if ($show_docs) {
                $query = "SELECT folder_id, issue_id
                          FROM themen_termine
                          INNER JOIN folder ON (issue_id = range_id)
                          WHERE termin_id = ?
                          LIMIT 1";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($date['termin_id']));
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                if ($row['folder_id']) {
                    $num_docs = doc_count($row['issue_id'], $current_seminar_id);
                    $folder_id = $row['folder_id'];
                }
            }

            $titel = '';

            if ($open == $date['termin_id']) {
                $titel.= "<a name=\"a\"> </a>";
            }

            $titel .= $termin->toString();

            if ($date['Titel']) {
                //Beschneiden des Titels
                $tmp_titel = htmlReady(mila($date['Titel'], 60 / (($full_width ? 100 : 70) / 100)));
                $titel .= ", " . $tmp_titel;
            }
            if ($date['ex_termin']) {
                $titel .= '&nbsp;<i>' . _("f�llt aus").'</i>';
                $titel .= tooltipIcon($date['content'], true);
            }

            if ($date['chdate'] > max(object_get_visit($current_seminar_id, "schedule"), object_get_visit($current_seminar_id, "sem"))) {
                $new = false;
            } else {
                $new = FALSE;
            }

            if ($num_docs) {
                $zusatz .= '<a href="' . URLHelper::getLink('folder.php', array('cmd' => 'tree' , 'open' =>  $folder_id, 'cid' => $current_seminar_id));
                $zusatz .= '#anker"><img src="' . Assets::image_path('icons/16/blue/files.png') . '" ';
                $zusatz .= tooltip(sprintf(_("%s Dokument(e) vorhanden"), $num_docs));
                $zusatz .= '></a>';
            }

            //calendar jump
            $zusatz .= ' <a href="' . URLHelper::getLink('calendar.php', array('cmd' =>'showweek', 'atime' => $date['date'], 'caluser' => 'self'));
            $zusatz .= '"><img style="vertical-align:bottom" src="' . Assets::image_path('popupcalendar.png') . '" ';
            $zusatz .= tooltip(sprintf(_("Zum %s in den pers�nlichen Terminkalender springen"), date("d.m.Y", $date['date'])));
            $zusatz .= '></a>';


            if ($open != $date['termin_id']) {
                $link=URLHelper::getLink("?dopen=".$date['termin_id'].$add_to_link."#a");
            } else {
                $link=URLHelper::getLink("?dclose=true".$add_to_link);
            }
            $date['seminar_date'] = $termin;

            if ($link) {
                $titel = "<a href=\"$link\" class=\"tree\" onclick=\"STUDIP.Termine.openclose('".$date['termin_id']."','"
                .$show_admin."','".$date['date_typ']."','".$date['info']."','"
                .False."','".FALSE."','".False."','".False."','".False."','".False."','".False."','".False."','".$date['autor_id']."'); return false;\" >".$titel."</a>";
            }
            echo show_termin_item($date, $open, $new, $link, $zusatz, $titel, $range_id, $show_not, $show_admin);
        }
        echo "</td></tr></table>";
        return TRUE;
    }

    elseif (($show_admin) && ($show_as_window)) {   //no dates, but the possibility to create one (only, if show_dates is used in window-style)
        // set skip link
        SkipLinks::addIndex(_("Termine"), 'appointments_box');

        print("\n<table id=\"appointments_box\" role=\"article\" class=\"index_box\"".($full_width ? " style=\"width: 100%;\"" : '').">");
        printf("\n<tr><td class=\"table_header_bold\"><img src=\"".Assets::image_path('icons/16/white/schedule.png')."\"><b>  %s</b></td>",_("Termine"));
        printf("\n<td align =\"right\" class=\"table_header_bold\"> %s<img src=\"".Assets::image_path('icons/16/white/admin.png')."\" %s></a> </td></tr>", $admin_link, tooltip(_("Termine einstellen")));
        ?>
        <tr>
            <td class="table_row_even" colspan="2">
                <p class="info">
                    <?= _("Es sind keine aktuellen Termine vorhanden. Um neue Termine zu erstellen, klicken Sie rechts auf die Zahnr�der.") ?>
                </p>
            </td>
        </tr>
        </table>
        <?
        return TRUE;
    }

    elseif (!$show_as_window) {
        // set skip link
        SkipLinks::addIndex(_("Termine"), 'appointments_box');

        print("\n<table id=\"appointments_box\" role=\"article\" class=\"index_box\"".($full_width ? " style=\"width: 100%;\"" : '').">");
        print("\n<tr><td class=\"blank\" colspan=\"2\">");
        parse_msg ("info�"._("Es sind keine aktuellen Termine vorhanden."));
        print("\n</td></tr></table>\n");
        return TRUE;
    }

    else {
        return FALSE;
    }
}
function show_termin_item($termin_item, $open, $new, $link, $zusatz, $titel, $range_id = "", $show_not = 0, $show_admin = FALSE )
{

            $template = $GLOBALS['template_factory']->open('dates/seminar_date');
            $template->termin_item = $termin_item;
            $template->range_id = $range_id;
            $template->show_not = $show_not;
            $template->show_admin = $show_admin;
            $template->new = $new;
            $template->link = $link;
            $template->zusatz = $zusatz;
            $template->titel = $titel;
            $template->icon = Assets::img('icons/16/grey/date.png', array('class' => 'text-bottom'));

            return $template->render();
}

function show_termin_item_content($termin_item, $new = FALSE, $range_id = "", $show_admin = FALSE )
{
            global $TERMIN_TYP;
            $template = $GLOBALS['template_factory']->open('dates/seminar_date-content');

            $template->termin_item = $termin_item;
            $template->range_id = $range_id;

            $template->show_admin = $show_admin;
            $template->new = $new;
            return $template->render();
}
/**
 *
 * @param unknown_type $range_id
 * @param unknown_type $date_start
 * @param unknown_type $date_end
 * @param unknown_type $show_docs
 * @param unknown_type $show_admin
 * @param unknown_type $open
 */
function show_personal_dates ($range_id, $date_start, $date_end, $show_docs = FALSE, $show_admin = FALSE, $open)
{
    global $SessSemName, $user, $TERMIN_TYP;
    global $PERS_TERMIN_KAT, $username;

    if ($show_admin && $range_id == $user->id) {
        $admin_link = '<a href="'.URLHelper::getLink('calendar.php', array('cmd' => 'edit', 'source_page' => URLHelper::getURL())).'">';
    }

    if ($date_end <= $date_start) {
        // show seven days
        $date_end = $date_start + 7*24*60*60;
    }

    $list = new DbCalendarEventList(new SingleCalendar($range_id, Calendar::PERMISSION_READABLE), $date_start, $date_end, TRUE, null, ($GLOBALS['user']->id == $range_id ? array() : array('CLASS' => 'PUBLIC')));

    if ($list->existEvent()) {
        // set skip link
        SkipLinks::addIndex(_("Termine"), 'appointments_box');

        // Ausgabe der Kopfzeile
        $colspan = 1;
        echo "\n<table id=\"appointments_box\" role=\"article\" class=\"index_box\" style=\"width: 100%;\">";
        if ($show_admin) {
            $colspan++;
            echo "\n<tr><td class=\"table_header_bold\"> <img src=\"" . Assets::image_path('icons/16/white/schedule.png') . '" ' . tooltip(_("Termine. Klicken Sie rechts auf die Zahnr�der, um Termine in diesen Bereich zu bearbeiten. Klicken Sie auf den einfachen Pfeil, um die Terminbeschreibung zu lesen.")) . '> <b>';
            printf(_("Termine f�r die Zeit vom %s bis zum %s"), strftime("%d. %B %Y", $list->getStart()), strftime("%d. %B %Y", $list->getEnd()));
            echo "</b></td>";
            echo "\n<td align=\"right\" class=\"table_header_bold\"> $admin_link<img src=\"" . Assets::image_path('icons/16/white/admin.png') . '" ' . tooltip(_("Neuen Termin anlegen")) . '></a></td></tr>';
        }
        else {
            echo "\n<tr><td class=\"table_header_bold\"> <img src=\"" . Assets::image_path('icons/16/white/schedule.png') . '" ' . tooltip(_("Termine. Klicken Sie auf den Pfeil, um eine Beschreibung des Termins anzuzeigen.")) . '><b>  ';
            printf(_("Termine f�r die Zeit vom %s bis zum %s"), strftime("%d. %B %Y", $list->getStart()), strftime("%d. %B %Y", $list->getEnd()));
            echo "</b></td></tr>";
        }
        echo "\n";

        // Ausgabe der Daten
        echo "\n<tr><td class=\"blank\" colspan=$colspan>";
        echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" align=\"center\"><tr><td class=\"blank\">";

        while ($termin = $list->nextEvent()) {
            echo '<div role="article">';
            $icon = Assets::img('icons/16/grey/date.png', array('class' => 'text-bottom'));

            $zusatz = '';
            if ($termin->getLocation()) {
                $zusatz .= '<font size="-1">' . _("Raum:") . ' ';
                $zusatz .= htmlReady($termin->getLocation()) . ' </font>';
            }

            $titel = "";
            if (date("Ymd", $termin->getStart()) == date("Ymd", time()))
                $titel .= _("Heute") . date(", H:i", $termin->getStart());
            else {
                $titel = substr(strftime("%a", $termin->getStart()),0,2);
                $titel .= date(". d.m.Y, H:i", $termin->getStart());
            }

            if ($termin->getStart() < $termin->getEnd()) {
                if (date("Ymd", $termin->getStart()) < date("Ymd", $termin->getEnd())) {
                    $titel .= " - ".substr(strftime("%a", $termin->getEnd()),0,2);
                    $titel .= date(". d.m.Y, H:i", $termin->getEnd());
                } else {
                    $titel .= " - ".date("H:i", $termin->getEnd());
                }
            }

            if ($termin->getTitle()) {
                $tmp_titel = htmlReady(mila($termin->getTitle())); //Beschneiden des Titels
                $titel .= ", ".$tmp_titel;
            }
            $new = ($termin->getChangeDate() > UserConfig::get($user->id)->LAST_LOGIN_TIMESTAMP);

            // Zur Identifikation von auf- bzw. zugeklappten Terminen muss zusaetzlich
            // die Startzeit ueberprueft werden, da die Wiederholung eines Termins die
            // gleiche ID besitzt.
            $app_ident = $termin->getId() . $termin->getStart();
            if ($open != $app_ident) {
                $link = URLHelper::getLink('', ($username ? array('dopen' => $app_ident, 'username' => $username) : array('dopen' => $app_ident))) . '#a';
            } else {
                $link = URLHelper::getLink('', ($username ? array('dclose' => 'true', 'username' => $username) : array('dclose' => 'true')));
            }

            echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr>";

            if ($link) {
                $titel = "<a href=\"$link\"  onclick=\"STUDIP.Termine.openclose('".$termin->getId()."'); return false;\" class=\"tree\" >$titel</a>";
            }
            if (strtolower(get_class($termin)) == 'seminarevent') {
                    $description = $issue_descriptions;
            } else {
                    $description = $termin->getDescription();
            }
            $kat = false;
            if (sizeof($PERS_TERMIN_KAT) > 1 && strtolower(get_class($termin)) != 'seminarevent') {
                    $kat = htmlReady($termin->toStringCategories());
            }

            if (strtolower(get_class($termin)) == 'seminarcalendarevent') {
                    $sem =  htmlReady($termin->getSemName());
            }

            if (strtolower(get_class($termin)) == 'seminarevent') {
                    if ($termin->getRoom()) {

                        $raum= htmlReady(mila($singledate->getRoom(), 25));
                    } else if ($termin->getFreeRoomText()) {

                        $ort=htmlReady(mila($singledate->getFreeRoomText(), 25));
                    }
             } else {
                    $pri =  htmlReady($termin->toStringPriority());

                    $sicht = htmlReady($termin->toStringAccessibility());
                    $res = htmlReady($termin->toStringRecurrence());
             }
             $edit = FALSE;
             if ($have_write_permission && strtolower(get_class($termin)) != 'seminarevent')  {
                        // Personal appointment
                        $edit = true;
             }





           $termin_item = array('termin_id'=>$termin->getId(),"chdate"=>$termin->getChangeDate(),
            'info'=>$description,
            'kat'=>$kat,'cont'=>$cont,'edit'=>$edit,
            'sem'=>$sem,'raum'=>$raum,'ort'=>$ort,'pri'=>$pri,'sicht'=>$sicht,'res'=>$res,
            'autor'=>$autor);

           if ($open == $app_ident) {
                $termin_item['open'] = "open";
            }
            echo show_termin_item($termin_item, $open, $new, $link, $zusatz, $titel);


        }
        echo "</td></tr></table></td></tr></table>";
        return TRUE;
    }
    // keine Termine da, aber die Moeglichkeit welche einzustellen
    else if ($show_admin) {
        // set skip link
        SkipLinks::addIndex(_("Termine"), 'appointments_box');

        echo "\n<table id=\"appointments_box\" role=\"article\" class=\"index_box\" style=\"width: 100%;\">";
        echo "\n<tr><td class=\"table_header_bold\"><img src=\"".Assets::image_path('icons/16/white/schedule.png')."\"> <b>" . _("Termine") . "</b></td>";
        echo "\n<td align =\"right\" class=\"table_header_bold\"> $admin_link<img src=\"".Assets::image_path('icons/16/white/admin.png')."\" " . tooltip(_("Termine einstellen")) . "></a> </td></tr>";
        ?>

        <tr>
            <td class="table_row_even" colspan="2">
                <p class="info">
                    <?= _("Es sind keine aktuellen Termine vorhanden. Um neue Termine zu erstellen, klicken Sie rechts auf die Zahnr�der.") ?>
                </p>
            </td>
        </tr>
        </table>
        <?
        return TRUE;
    }

    else {
        return FALSE;
    }
}

/**
 *
 * @param unknown_type $date_start
 * @param unknown_type $date_end
 * @param unknown_type $show_docs
 * @param unknown_type $show_admin
 * @param unknown_type $open
 */
function show_all_dates($date_start, $date_end, $show_docs=FALSE, $show_admin=TRUE, $open)
{
    global $RELATIVE_PATH_CALENDAR, $SessSemName, $user, $TERMIN_TYP;
    global $PERS_TERMIN_KAT, $username, $CALENDAR_DRIVER;

    $admin_link = '<a href="'.URLHelper::getLink('calendar.php', array('cmd' => 'edit', 'source_page' => URLHelper::getURL())).'">';

    $list = new DbCalendarEventList(new SingleCalendar($user->id, Calendar::PERMISSION_OWN), $date_start, $date_end, TRUE, Calendar::getBindSeminare());

    if ($list->existEvent()) {
        // set skip link
        SkipLinks::addIndex(_("Termine"), 'appointments_box');

        // Ausgabe der Kopfzeile
        echo "<table id=\"appointments_box\" role=\"article\" class=\"index_box\">";
        echo "\n<tr><td class=\"table_header_bold\" align=\"left\">\n";
        echo '<img src="' . Assets::image_path('icons/16/white/schedule.png') . '" ';
        echo tooltip(_("Termine. Klicken Sie rechts auf die Zahnr�der, um Termine in diesen Bereich zu bearbeiten. Klicken Sie auf den einfachen Pfeil, um die Terminbeschreibung zu lesen."));
        echo "> <b>";
        echo _("Meine aktuellen Termine");
        echo "</b></td>";
        echo "\n<td align=\"right\" class=\"table_header_bold\"> $admin_link<img src=\"" . Assets::image_path('icons/16/white/admin.png') . '" ' . tooltip(_("Neuen Termin anlegen")) . "></a> </td></tr>\n";

        // Ausgabe der Daten
        echo "<tr><td class=\"blank\" colspan=\"2\">";

        while ($termin = $list->nextEvent()) {
            echo '<div role="article">';

            $icon = Assets::img('icons/16/grey/date.png', array('class' => 'text-bottom'));
            $have_write_permission = true;

            $zusatz = '';
            $singledate = null;

            if(strtolower(get_class($termin)) == 'seminarevent') {
                $have_write_permission = $GLOBALS['perm']->have_studip_perm('tutor', $termin->getSeminarId());
                $singledate = new SingleDate($termin->id);
                $issues = array_map(array('IssueDB', 'restoreIssue'), (array)$singledate->getIssueIDs());
                $issue_titles = join('; ', array_map(create_function('$a', 'return $a["title"];'), $issues));
                if (!$issue_titles) {
                    $issue_titles = _("Ohne Titel");
                }
                $issue_descriptions = join("\n\n", array_map(create_function('$a', 'return $a["description"];'), $issues));
                $zusatz .= '<a href="'.URLHelper::getLink("seminar_main.php?auswahl=" . $termin->getSeminarId())
                                . "\"><font size=\"-1\">".htmlReady(mila($termin->getSemName(), 22))
                                . ' </font></a>';
            }

            $titel = '';
            $length = 70;
            if (date('Ymd', $termin->getStart()) == date('Ymd', time())) {
                $titel .= _("Heute") . date(", H:i", $termin->getStart());
            } else {
                $titel .= substr(strftime('%a,', $termin->getStart()),0,2);
                $titel .= date('. d.m.Y, H:i', $termin->getStart());
                $length = 55;
            }

            if (date('Ymd', $termin->getStart()) != date('Ymd', $termin->getEnd())) {
                $titel .= ' - ' . substr(strftime('%a,', $termin->getEnd()), 0, 2);
                $titel .= date('. d.m.Y, H:i', $termin->getEnd());
                $length = 55;
            } else {
                $titel .= ' - '.date('H:i', $termin->getEnd());
            }

            if (strtolower(get_class($termin)) == 'seminarevent') {
                //Beschneiden des Titels
                if ($singledate->isExTermin()) {
                    $titel .= ', <i>' . _("f�llt aus") . '</i>';
                    $titel .= tooltipIcon($singledate->getComment(), true);
                } else {
                    $titel .= ', ' . htmlReady(mila($issue_titles, $length));
                }
            } else {
                //Beschneiden des Titels
                $titel .= ', ' . htmlReady(mila($termin->getTitle(), $length));
            }

            //Dokumente zaehlen
            $num_docs = 0;
            if ($show_docs && strtolower(get_class($termin)) == 'seminarevent') {
                $query = "SELECT folder_id, issue_id
                          FROM themen_termine
                          INNER JOIN folder ON (issue_id = range_id)
                          WHERE termin_id = ?
                          LIMIT 1";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $termin->getId()
                ));
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                if ($row['folder_id']) {
                    $num_docs = doc_count($row['issue_id'],  $termin->getSeminarId());
                    if ($num_docs) {
                        $zusatz .= '<a href="' .URLHelper::getLink('seminar_main.php', array('auswahl' => $termin->getSeminarId(), 'redirect_to' => 'folder.php', 'cmd' => 'tree', 'open' => $row['folder_id']))
                        . '#anker"><img src="' . Assets::image_path('icons/16/blue/files.png') . '" ';
                        $zusatz .= tooltip(sprintf(_("%s Dokument(e) vorhanden"), $num_docs));
                        $zusatz .= ">";
                    }
                }
            }
            $new = ($termin->getChangeDate() > UserConfig::get($user->id)->LAST_LOGIN_TIMESTAMP);

            // Zur Identifikation von auf- bzw. zugeklappten Terminen muss zus�tzlich
            // die Startzeit �berpr�ft werden, da die Wiederholung eines Termins die
            // gleiche ID besitzt.
            $app_ident = $termin->getId() . $termin->getStart();
            if ($open != $app_ident) {
                $link = URLHelper::getLink("?dopen=".$app_ident."#a");
            } else {
                $link = URLHelper::getLink("?dclose=true");
            }

           if (strtolower(get_class($termin)) == 'seminarevent') {
                    $description = $issue_descriptions;
            } else {
                    $description = $termin->getDescription();
            }
            $kat = false;
            if (sizeof($PERS_TERMIN_KAT) > 1 && strtolower(get_class($termin)) != 'seminarevent') {
                    $kat = htmlReady($termin->toStringCategories());
            }

            if (strtolower(get_class($termin)) == 'seminarcalendarevent') {
                    $sem =  htmlReady($termin->getSemName());
            }

            if (strtolower(get_class($termin)) == 'seminarevent') {
                    $type=$singledate->getTypeName();
                    if ($singledate->getRoom()) {

                        $raum= htmlReady(mila($singledate->getRoom(), 25));
                    } else if ($singledate->getFreeRoomText()) {

                        $ort=htmlReady(mila($singledate->getFreeRoomText(), 25));
                    }
             } else {
                    $pri =  htmlReady($termin->toStringPriority());

                    $sicht = htmlReady($termin->toStringAccessibility());
                    $res = htmlReady($termin->toStringRecurrence());
             }
             $edit = FALSE;
             if ($have_write_permission && strtolower(get_class($termin)) != 'seminarevent')  {
                        // Personal appointment
                        $edit = true;
             }

             if ($link) {

                $titel = "<a href=\"$link\" class=\"tree\"  "
                 ."onclick=\"STUDIP.Termine.openclose('".$termin->getId()."'); return false;\">".$titel."</a>";
             }




           $termin_item = array('termin_id'=>$termin->getId(),"chdate"=>$termin->getChangeDate(),
            'type'=>$type,'info'=>$description,
            'kat'=>$kat,'cont'=>$cont,'edit'=>$edit,
            'sem'=>$sem,'raum'=>$raum,'ort'=>$ort,'pri'=>$pri,'sicht'=>$sicht,'res'=>$res,
            'autor'=>$autor, 'seminar_date' => $singledate);
           if ($open == $app_ident) {
                $termin_item['open'] = "open";
            }
            echo show_termin_item($termin_item, $open, $new, $link, $zusatz, $titel);

           //contend
        }
        echo "\n</td></tr>\n</table>";
        return TRUE;
    }
    // keine Termine da, aber die Moeglichkeit welche einzustellen
    else if($show_admin) {
        // set skip link
        SkipLinks::addIndex(_("Termine"), 'appointments_box');

        echo "\n<table id=\"appointments_box\" role=\"article\" class=\"index_box\">";
        echo "\n<tr><td class=\"table_header_bold\">" . Assets::img('icons/16/white/schedule.png', array('class' => 'text-top', 'title' =>_('Termine'))) . '<b>  ' . _("Termine") . '</b></td>';
        echo "\n<td align=\"right\" class=\"table_header_bold\"> $admin_link<img src=\"" . Assets::image_path('icons/16/white/admin.png') . '" ' . tooltip(_("Termine einstellen")) . '></a> </td></tr>';
        ?>
        <tr>
            <td class="table_row_even" colspan="2">
                <p class="info">
                    <?= _("Es sind keine aktuellen Termine vorhanden. Um neue Termine zu erstellen, klicken Sie rechts auf die Zahnr�der.") ?>
                </p>
            </td>
        </tr>
        </table>
        <?
        return TRUE;
    }

    else {
        return FALSE;
    }
}
