<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * termine.php - Termine controller
 *
*/
global $RELATIVE_PATH_CALENDAR, $template_factory;


class TerminWidget extends StudIPPlugin implements PortalPlugin {

    public function getPortalTemplate() {

        $script_attributes = array(
            'src'   => $this->getPluginURL() . '/js/Termine.js');
        PageLayout::addHeadElement('script', $script_attributes, '');

        global $perm;
        if (!$perm->have_perm('admin')) { // only dozent, tutor, autor, user
            $date_start = time();
            $date_end = $date_start + 60 * 60 * 24 * 7;
            $terminItemArray = array();
            if (get_config('CALENDAR_ENABLE')) {
                $list = new DbCalendarEventList(new SingleCalendar($user->user_id, Calendar::PERMISSION_OWN), $date_start, $date_end, TRUE, Calendar::getBindSeminare());
                if ($list->existEvent()) {
                    while ($termin = $list->nextEvent()) {
                        $icon = Assets::img('icons/16/grey/date.png', array('class' => 'text-bottom'));
                        $have_write_permission = true;
                        $zusatz = '';
                        $singledate = null;
                        if(strtolower(get_class($termin)) == 'seminarevent') {
                            $have_write_permission = $perm->have_studip_perm('tutor', $termin->getSeminarId());
                            $singledate = new SingleDate($termin->id);

                            $terminItemArray[$termin->id]=array();
                            $terminItemArray[$termin->id]['singledate'] = $singledate;
                            $terminItemArray[$termin->id]['id'] = $termin->id;
                            $terminItemArray[$termin->id]['icon'] = $icon;
                            $terminItemArray[$termin->id]['have_write_permission'] = $have_write_permission;

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

                            if ($singledate->isExTermin()) {
                                $titel .= ', <i>' . _("fällt aus") . '</i>';
                                $titel .= tooltipIcon($singledate->getComment(), true);
                            } else {
                                $titel .= ', ' . htmlReady(mila($issue_titles, $length));
                            }
                        } else {
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
                            $statement->execute(array($termin->getId()));
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
                        $terminItemArray[$termin->id]['zusatz'] = $zusatz;
                        $new = ($termin->getChangeDate() > UserConfig::get($user->id)->LAST_LOGIN_TIMESTAMP);
                        if (strtolower(get_class($termin)) == 'seminarevent') {
                            $description = $issue_descriptions;
                        } else {
                            $description = $termin->getDescription();
                        }
                        $terminItemArray[$termin->id]['description'] = $description;
                        $terminItemArray[$termin->id]['issue_titles'] = $issue_titles;
                        $kat = false;
                        if (sizeof($PERS_TERMIN_KAT) > 1 && strtolower(get_class($termin)) != 'seminarevent') {
                            $kat = htmlReady($termin->toStringCategories());
                        }

                        if (strtolower(get_class($termin)) == 'seminarcalendarevent') {
                            $sem =  htmlReady($termin->getSemName());
                        }
                        $terminItemArray[$termin->id]['sem'] = $sem;
                        $terminItemArray[$termin->id]['kat'] = $kat;
                        if (strtolower(get_class($termin)) == 'seminarevent') {
                            $type=$singledate->getTypeName();
                            if ($singledate->getRoom()) {
                                $raum= htmlReady(mila($singledate->getRoom(), 25));
                            } else if ($singledate->getFreeRoomText()) {
                                $ort=htmlReady(mila($singledate->getFreeRoomText(), 25));
                            }
                            $terminItemArray[$termin->id]['ort'] = $ort;
                            $terminItemArray[$termin->id]['raum'] = $raum;
                            $terminItemArray[$termin->id]['type'] = $type;
                        } else {
                            $pri =  htmlReady($termin->toStringPriority());

                            $sicht = htmlReady($termin->toStringAccessibility());
                            $res = htmlReady($termin->toStringRecurrence());
                        }
                        $edit = FALSE;
                        if ($have_write_permission && strtolower(get_class($termin)) != 'seminarevent') {
                            // Personal appointment
                            $edit = true;
                        }

                        $titel = "<a href=\"#\" class=\"tree\"  "
                                ."onclick=\"TERMIN_WIDGET.openclose('".$termin->getId()."'); return false;\">".$titel."</a>";

                        $terminItemArray[$termin->id]['titel'] = $titel;

                        $terminItemArray[$termin->id]['edit'] = $edit;
                        $terminItemArray[$termin->id]['res'] = $res;
                        $terminItemArray[$termin->id]['seminar_date'] = $singledate;
                        if ($chdate == 0)
                            $timecolor = "#BBBBBB";
                        else {
                            if ($new == TRUE)
                                $timecolor = "#FF0000";
                            else {
                                $timediff = (int) log((time() - $timestmp) / 86400 + 1) * 15;
                                if ($timediff >= 68)
                                    $timediff = 68;
                                $red = dechex(255 - $timediff);
                                $other = dechex(119 + $timediff);
                                $timecolor= "#" . $red . $other . $other;
                            }
                        }
                        $terminItemArray[$termin->id]['timecolor'] = $timecolor;
                    }
                }
                $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
                $template = $factory->open('seminar_alldate-list');
                $template->new = $new;
                $template->terminItemArray = $terminItemArray;
            } else {
                $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
                $template = $factory->open('seminar_alldate-list');
                $template->new = $new;
                $template->terminItemArray = $this->show_dates($date_start, $date_end);
            }
            $template->title = _('Termine');
            $template->icon_url = 'icons/16/white/schedule.png';
            $template->admin_link = 'icons/16/white/schedule.png';
            $template->admin_title = 'Terminwidget einstellen...';

            return $template;
        } else {
            return null;
        }
    }

    function getContent() {
        global $perm;
        if (!$perm->have_perm('admin')) { // only dozent, tutor, autor, user
            $start = time();
            $end = $start + 60 * 60 * 24 * 7;
            $terminItemArray = array();
            if (get_config('CALENDAR_ENABLE')) {
                $list = new DbCalendarEventList(new SingleCalendar($user->id, Calendar::PERMISSION_OWN), $date_start, $date_end, TRUE, Calendar::getBindSeminare());
                if ($list->existEvent()) {
                    while ($termin = $list->nextEvent()) {
                        $icon = Assets::img('icons/16/grey/date.png', array('class' => 'text-bottom'));
                        $have_write_permission = true;
                        $zusatz = '';
                        $singledate = null;
                        if(strtolower(get_class($termin)) == 'seminarevent') {
                            $have_write_permission = $perm->have_studip_perm('tutor', $termin->getSeminarId());
                            $singledate = new SingleDate($termin->id);

                            $terminItemArray[$termin->id]=array();
                            $terminItemArray[$termin->id]['singledate'] = $singledate;
                            $terminItemArray[$termin->id]['id'] = $termin->id;
                            $terminItemArray[$termin->id]['icon'] = $icon;
                            $terminItemArray[$termin->id]['have_write_permission'] = $have_write_permission;

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

                            if ($singledate->isExTermin()) {
                                $titel .= ', <i>' . _("fällt aus") . '</i>';
                                $titel .= tooltipIcon($singledate->getComment(), true);
                            } else {
                                $titel .= ', ' . htmlReady(mila($issue_titles, $length));
                            }
                        } else {
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
                            $statement->execute(array($termin->getId()));
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
                        $terminItemArray[$termin->id]['zusatz'] = $zusatz;
                        $new = ($termin->getChangeDate() > UserConfig::get($user->id)->LAST_LOGIN_TIMESTAMP);
                        if (strtolower(get_class($termin)) == 'seminarevent') {
                            $description = $issue_descriptions;
                        } else {
                            $description = $termin->getDescription();
                        }
                        $terminItemArray[$termin->id]['description'] = $description;
                        $terminItemArray[$termin->id]['issue_titles'] = $issue_titles;
                        $kat = false;
                        if (sizeof($PERS_TERMIN_KAT) > 1 && strtolower(get_class($termin)) != 'seminarevent') {
                            $kat = htmlReady($termin->toStringCategories());
                        }

                        if (strtolower(get_class($termin)) == 'seminarcalendarevent') {
                            $sem =  htmlReady($termin->getSemName());
                        }
                        $terminItemArray[$termin->id]['sem'] = $sem;
                        $terminItemArray[$termin->id]['kat'] = $kat;
                        if (strtolower(get_class($termin)) == 'seminarevent') {
                            $type=$singledate->getTypeName();
                            if ($singledate->getRoom()) {
                                $raum= htmlReady(mila($singledate->getRoom(), 25));
                            } else if ($singledate->getFreeRoomText()) {
                                $ort=htmlReady(mila($singledate->getFreeRoomText(), 25));
                            }
                            $terminItemArray[$termin->id]['ort'] = $ort;
                            $terminItemArray[$termin->id]['raum'] = $raum;
                            $terminItemArray[$termin->id]['type'] = $type;
                        } else {
                            $pri =  htmlReady($termin->toStringPriority());

                            $sicht = htmlReady($termin->toStringAccessibility());
                            $res = htmlReady($termin->toStringRecurrence());
                        }
                        $edit = FALSE;
                        if ($have_write_permission && strtolower(get_class($termin)) != 'seminarevent') {
                            // Personal appointment
                            $edit = true;
                        }

                        $titel = "<a href=\"#\" class=\"tree\"  "
                                ."onclick=\"STUDIP.Termine.openclose('".$termin->getId()."'); return false;\">".$titel."</a>";

                        $terminItemArray[$termin->id]['titel'] = $titel;

                        $terminItemArray[$termin->id]['edit'] = $edit;
                        $terminItemArray[$termin->id]['res'] = $res;
                        $terminItemArray[$termin->id]['seminar_date'] = $singledate;
                        if ($chdate == 0)
                            $timecolor = "#BBBBBB";
                        else {
                            if ($new == TRUE)
                                $timecolor = "#FF0000";
                            else {
                                $timediff = (int) log((time() - $timestmp) / 86400 + 1) * 15;
                                if ($timediff >= 68)
                                    $timediff = 68;
                                $red = dechex(255 - $timediff);
                                $other = dechex(119 + $timediff);
                                $timecolor= "#" . $red . $other . $other;
                            }
                        }
                        $terminItemArray[$termin->id]['timecolor'] = $timecolor;
                    }
                }

                $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
                $template = $factory->open('seminar_alldate-list');
                $template->new = $new;
                $template->terminItemArray = $terminItemArray;
                return $template;
            } else {
                $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
                $template = $factory->open('seminar_alldate-list');
                $template->new = $new;
                $template->terminItemArray = $this->show_dates($start, $end);
                return $template;
            }
        }
    }

    function getHeaderOptions() {

        global $perm;
        $options = array();
        $options[] = array('url' => URLHelper::getLink('calendar.php', array('cmd' => 'edit', 'source_page' => URLHelper::getURL())),
                'img' => 'icons/16/blue/admin.png',
                'tooltip' =>_('Neuen Termin anlegen'));
        return $options;
    }
    function getURL() {

    }
    function getRange() {
        global $user;
        return $user->id;
    }

    function show_all_dates() {

    }
    function show_dates($date_start, $date_end) {
        global $TERMIN_TYP, $SessSemName, $user, $username, $rechte;
        $terminItemArray = array();
        $parameters = array();
        $show_docs = TRUE;
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
        $range_typ =  "sem" ;

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
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $dates = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (count($dates) > 0) {
            foreach ($dates as $date ) {
                $zusatz = '';
                $termin = new SingleDate($date['termin_id']);
                if( $termin->hasRoom() ) {
                    $terminItemArray[$termin->id]['ort'] = $termin->getRoom();
                }elseif( $freeroomtext = $termin->getFreeRoomText() ) {
                    $terminItemArray[$termin->id]['ort'] = $termin->getFreeRoomText();
                }else {
                    $terminItemArray[$termin->id]['ort'] = "k.A.";
                }
                if ($termin->isExTermin()) {
                    $zusatz = "fällt aus";
                }
                $current_seminar_id = $range_id;
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
                    $titel .= '&nbsp;<i>' . _("fällt aus").'</i>';
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
                $zusatz .= tooltip(sprintf(_("Zum %s in den persönlichen Terminkalender springen"), date("d.m.Y", $date['date'])));
                $zusatz .= '></a>';
                $date['seminar_date'] = $termin;
                $titel = "<a href=\"#\" class=\"tree\" onclick=\"TERMIN_WIDGET.openclose('".$date['termin_id']."','"
                        .$show_admin."','".$date['date_typ']."','".$date['info']."','"
                        .False."','".FALSE."','".False."','".False."','".False."','".False."','".False."','".False."','".$date['autor_id']."'); return false;\" >".$titel."</a>";
                $terminItemArray[$termin->id]['titel'] = $titel;
                $terminItemArray[$termin->id]['autor_id'] = $date['autor_id'];
                $terminItemArray[$termin->id]['zusatz'] = $zusatz;
                $terminItemArray[$termin->id]['date'] = $date;
                $terminItemArray[$termin->id]['seminar_date'] = $termin;
            }
        }
        return $terminItemArray;
    }

    function show_termin_item_content($termin_item, $new = FALSE, $range_id = "", $show_admin = FALSE ) {
        global $TERMIN_TYP;
        $factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates');
        $template = $factory->open('seminar_date-content');

        $template->termin_item = $termin_item;
        $template->range_id = $range_id;

        $template->show_admin = $show_admin;
        $template->new = $new;
        return $template->render();
    }

    function getPluginName(){
        return _("Meine aktuellen Termine");
    }

}


