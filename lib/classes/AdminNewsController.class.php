<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* AdminNewsController.class.php
*
*
*
*
* @author   André Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
* @access   public
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2005 André Noack <noack@data-quest>,
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

require_once 'lib/classes/StudipNews.class.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/visual.inc.php';
require_once 'lib/functions.php';

class AdminNewsController {
    var $modus;
    var $msg;          //Nachricht für msg.inc.php
    var $sms=array();          //private Nachricht wegen Admin zugriff
    var $news_query=array();
    var $range_detail=array();
    var $search_result=array();
    var $user_id;
    var $news_range;
    var $range_name;
    var $range_type;
    var $full_username;
    var $news_perm=array();
    var $max_col;
    var $xres;

    function AdminNewsController() {
        global $perm,$auth,$news_range_id,$news_range_name;
        if ($auth->auth["jscript"]) {
            $this->max_col = floor($auth->auth["xres"] / 10 );
            $this->xres=$auth->auth["xres"];
        } else {
            $this->max_col =  64 ; //default für 640x480
            $this->xres=640;
        }
        $this->user_id=$auth->auth["uid"];
        $this->full_username = get_fullname(false, 'full', false);
        $this->get_news_perm();
        if ($this->news_perm[$news_range_id]["perm"]>=2 OR $perm->have_perm("root")) {
            $this->modus = "admin";
            if ($this->news_perm[$news_range_id]["name"]){
                $news_range_name = $this->news_perm[$news_range_id]["name"];
                $news_range_type = get_object_type($news_range_id);
            }
            elseif ($news_range_id=="studip"){
                $news_range_name="Stud.IP System Ankündigungen";
                $news_range_type='studip';
            }
            elseif ($news_range_id!=""){
                $object_type = get_object_type($news_range_id);
                switch ($object_type){
                    case "sem":
                    case "inst":
                    case "fak":
                        $object_name = get_object_name($news_range_id, $object_type);
                        $news_range_name = $object_name['name'];
                        $news_range_type = $object_type;
                    break;

                    default:
                    $news_range_name = get_fullname($news_range_id, 'full', false);
                    $news_range_type = 'user';
                }
            } else {
                $this->news_range=$news_range_id=$this->user_id;
                $this->range_name=$news_range_name=$this->full_username;
                $this->range_type=$news_range_type='user';
            }
        } else {
            $this->modus = "";
            $this->news_range=$news_range_id=$this->user_id;
            $this->range_name=$news_range_name=$this->full_username;
            $this->range_type=$news_range_type='user';
        }
        $this->news_range=$news_range_id;
        $this->range_name=$news_range_name;
        $this->range_type=$news_range_type;
    }

    function get_news_by_range($range) {
        $this->news_query = null;
        if ($range == $this->user_id){
            $this->news_query =& StudipNews::GetNewsByAuthor($this->user_id);
        } else {
            $this->news_query =& StudipNews::GetNewsByRange($range);
        }
    }

    function get_one_news($news_id) {
        global $perm,$_fullname_sql;

        $this->news_query = null;
        $news_obj = new StudipNews($news_id);
        if (!$news_obj->isNew()) {
            $this->news_query = $news_obj->toArray();

            $query = "SELECT a.range_id, b.user_id, {$_fullname_sql['full']} AS author,"
                   . " c.Seminar_id, c.Name AS seminar_name, c.start_time ,d.Institut_id,d.Name AS institut_name,"
                   . " IF (d.Institut_id=d.fakultaets_id,'fak','inst') AS inst_type, sd.name AS startsem, "
                   . " IF (c.duration_time = -1, '"._("unbegrenzt")."', sd2.name) AS endsem "
                   . "FROM news_range AS a "
                   . "LEFT JOIN auth_user_md5 AS b ON (b.user_id=a.range_id) "
                   . "LEFT JOIN user_info USING (user_id) "
                   . "LEFT JOIN seminare AS c ON (c.Seminar_id=a.range_id) "
                   . "LEFT JOIN semester_data sd ON (c.start_time = sd.beginn) "
                   . "LEFT JOIN semester_data sd2 ON (c.start_time + c.duration_time BETWEEN sd2.beginn AND sd2.ende) "
                   . "LEFT JOIN Institute AS d ON (d.Institut_id=a.range_id) "
                   . "WHERE news_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($news_id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                if ($row['user_id']) {
                    $this->range_detail[$row['range_id']] = array(
                        'type' => 'pers',
                        'name' => $row['author']
                    );
                }
                if ($row['Seminar_id']) {
                    $name = sprintf('%s (%s%s)', $row['seminar_name'], $row['startsem'],
                                    $row['startsem'] != $row['endsem'] ? ' - '.$row['endsem'] : '');

                    $this->range_detail[$row['range_id']] = array(
                        'type'      => 'sem',
                        'name'      => $name,
                        'starttime' => $row['start_time'],
                        'startsem'  => $row['startsem']
                    );
                }
                if ($row['Institut_id']) {
                    $this->range_detail[$row['range_id']] = array(
                        'type' => $row['inst_type'],
                        'name' => $row['institut_name']
                    );
                }
            }

            if ($perm->have_perm('root')) {
                $query = "SELECT range_id FROM news_range WHERE news_id = ? AND range_id = 'studip'";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($news_id));
                if ($range_id = $statement->fetchColumn()) {
                    $this->range_detail[$range_id]= array(
                        'type' => 'sys',
                        'name' => 'Stud.IP System Ankündigungen'
                    );
                }
            }
        }
    }

    function show_news($id){
        global $auth, $view_mode;
        $cssSw= new cssClassSwitcher();
        $cssSw->enableHover();
        $this->get_news_by_range($id);
        if (!is_array($this->news_query) || !count($this->news_query) ) {
            $this->msg .= "info§" . _("Keine Ankündigungen vorhanden!") . "§";
            return FALSE;
        }
        if ($this->news_perm[$id]["perm"]<2 AND $auth->auth["perm"]!="root") {
            $this->msg .= "error§" . _("Sie d&uuml;rfen diesen Ankündigungs-Bereich nicht administrieren!") . "§";
            return FALSE;
        }
        echo "\n<tr><td width=\"100%\" class=\"blank\"><p class=\"info\">";
        echo "\n<form action=\"".URLHelper::getLink("?cmd=kill&view_mode=$view_mode")."\" method=\"POST\">";
        echo CSRFProtection::tokenTag();
        echo "<table class=\"blank\" align=\"left\" width=\"".round(0.88*$this->xres)."\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">";
        echo "\n<tr><td class=\"blank\" colspan=\"4\" align=\"left\" style=\"vertical-align:middle;\"><font size=-1 >" . _("Vorhandene Ankündigungen im gew&auml;hlten Bereich:") . "<br>";
        echo "</td><td class=\"blank\" colspan=\"4\" align=\"right\" style=\"vertical-align:middle;\"><font size=-1 >" . _("Markierte Ankündigungen l&ouml;schen");
        echo "\n<input type=\"IMAGE\" style=\"vertical-align:middle;\" name=\"kill\" " . makeButton("loeschen","src") . tooltip(_("Markierte Ankündigungen löschen")) . " border=\"0\" >&nbsp;&nbsp;</td></tr>";
        echo "\n<tr><th width=\"15%\">" . _("&Uuml;berschrift") . "</th><th width=\"20%\">" . _("Inhalt") . "</th><th width=\"20%\">"
            . _("Autor") . "</th><th width=\"10%\">" . _("Einstelldatum") . "</th><th width=\"10%\">" . _("Ablaufdatum") . "</th><th width=\"15%\">"
            . _("Bearbeiten") . "</th><th width=\"10%\">" . _("L&ouml;schen") . "</th></tr>";
        while (list ($news_id,$details) = each ($this->news_query)) {
            $cssSw->switchClass();
            echo "\n<tr ".$cssSw->getHover()."><td class=\"".$cssSw->getClass()."\" width=\"15%\" align=\"center\"><font size=\"-1\"><b>".htmlReady($details["topic"])."</b></font></td>";
            list ($body,$admin_msg)=explode("<admin_msg>",$details["body"]);
            echo "\n<td class=\"".$cssSw->getClass()."\" width=\"25%\" align=\"center\"><font size=\"-1\">".htmlready(mila($body))."</font></td>";
            echo "\n<td class=\"".$cssSw->getClass()."\" width=\"15%\" align=\"center\"><font size=\"-1\">".htmlReady($details["author"])."</font></td>";
            echo "\n<td class=\"".$cssSw->getClass()."\" width=\"10%\" align=\"center\">".strftime("%d.%m.%y", $details["date"])."</td>";
            echo "\n<td class=\"".$cssSw->getClass()."\" width=\"10%\" align=\"center\">".strftime("%d.%m.%y", ($details["date"]+$details["expire"]))."</td>";
            echo "\n<td class=\"".$cssSw->getClass()."\" width=\"15%\" align=\"center\"><a href=\"".URLHelper::getLink("?cmd=edit&edit_news=$news_id&view_mode=$view_mode")."\"><img "
                . makeButton("bearbeiten","src") . tooltip(_("Diese Ankündigung bearbeiten")) . " border=\"0\"></a></td>";
            echo "\n<td class=\"".$cssSw->getClass()."\" width=\"10%\" align=\"center\">";
            if ($this->news_perm[$id]["perm"]==3 OR $auth->auth["perm"]=="root" OR $details["user_id"]==$this->user_id)
                echo "<input type=\"CHECKBOX\" name=\"kill_news[]\" value=\"$news_id\" " . tooltip(_("Diese Ankündigung zum Löschen vormerken"),false) . ">";
            else
                echo "<font color=\"red\">" . _("Nein") . "</font>";
            echo "</td></tr>";
        }
        echo "\n<tr><td class=\"blank\" colspan=8>&nbsp; </td></tr>";
        echo "\n</table></form><br><br></p></td></tr>";
        return TRUE;
    }

    function restore_edited_fields() {
        $this->news_query['topic'] = Request::get('topic', $this->news_query['topic'] );
        $this->news_query['body'] = Request::get('body', $this->news_query['body']);
        $this->news_query['date'] = strtotime(Request::get('startdate')) ? strtotime(Request::get('startdate')) : $this->news_query['date'];
        $this->news_query['enddate'] = strtotime(Request::get('enddate')) ? strtotime(Request::get('enddate')) : $this->news_query['date'] + $this->news_query['expire'];
        $this->news_query['expire'] = $this->news_query['enddate'] - $this->news_query['date'];
        $this->news_query['allow_comments'] = Request::int('allow_comments', $this->news_query['allow_comments']);
    }

    function edit_news($news_id=0) {
        global $perm;
        $aktuell=mktime(0,0,0,strftime("%m",time()),strftime("%d",time()),strftime("%y",time()));
        if ($news_id && $news_id != "new_entry") {
            $this->get_one_news($news_id);
        } else {
            $this->news_query = array("news_id"=> "new_entry",
                                        "topic" => "",
                                        "body" => "",
                                        "date" => $aktuell,
                                        "user_id" => $this->user_id,
                                        "author" => $this->full_username,
                                        "expire" => 604800,
                                        "allow_comments" => 0);
            if ($perm->have_perm("admin")){
                $this->search_result[$this->news_range] = array('type' => $this->range_type, 'name' => $this->range_name);
            }
        }
        $this->restore_edited_fields();
        // merge current range_detail into search result
        $this->search_result += $this->range_detail;
        uasort($this->search_result, array('AdminNewsController', 'compare_range'));

        if ($this->news_query["user_id"]==$this->user_id)
            $this->modus="";
        echo "\n<tr> <td class=\"blank\" align=\"center\"><br>";
        echo "\n<form action=\"".URLHelper::getLink("?cmd=news_edit")."\" method=\"POST\">";
        echo CSRFProtection::tokenTag();
        echo "\n<input type=\"HIDDEN\" name=\"view_mode\" value=\"".$GLOBALS['view_mode']."\">";
        echo "\n<input type=\"HIDDEN\" name=\"news_id\" value=\"".$this->news_query["news_id"]."\">";
        echo "\n<input type=\"HIDDEN\" name=\"user_id\" value=\"".$this->news_query["user_id"]."\">";
        echo "\n<input type=\"HIDDEN\" name=\"author\" value=\"".$this->news_query["author"]."\">";
        echo "\n</td></tr>";
        echo "\n<tr> <td class=\"blank\" align=\"center\"><br>";
        echo "\n<table width=\"99%\" cellspacing=\"0\" cellpadding=\"6\" border=\"0\">";
        echo "\n<tr><td class=\"steel1\" width=\"70%\"><b>" . _("Autor:") . "</b>&nbsp;". htmlReady($this->news_query["author"]) ."<br><br><b>" . _("&Uuml;berschrift")
            . "</b><br><input type=\"TEXT\" style=\"width: 100%\" size=\"".floor($this->max_col*.5*.8)."\" maxlength=\"255\" name=\"topic\" value=\""
            .htmlReady($this->news_query["topic"])."\"><br>";
        list ($body,$admin_msg)=explode("<admin_msg>",$this->news_query["body"]);
        echo "\n<br><b>" . _("Inhalt") . "</b><br><textarea name=\"body\" style=\"width: 100%\" cols=\"".floor($this->max_col*.8*.8)."\" rows=\"10\"      wrap=\"virtual\">"
            .htmlReady($body)."</textarea><br></td>";
        echo "\n<td class=\"steelgraulight\" width=\"30%\">" . _("Geben Sie hier die &Uuml;berschrift und den Inhalt Ihrer Ankündigung ein.")
            . "<br><br>" . _("Im unteren Bereich k&ouml;nnen Sie ausw&auml;hlen, in welchen Bereichen Ihre Ankündigung angezeigt wird.");
        echo "\n<br><br>" . _("Klicken Sie danach hier, um die &Auml;nderungen zu &uuml;bernehmen.") . "<br><br><center>"
            . "<input type=\"IMAGE\" name=\"news_submit\" " . makeButton("uebernehmen","src") . tooltip(_("Änderungen übernehmen")) ."  border=\"0\" ></center></td></tr>";

        echo "\n<tr><td class=\"blank\" colspan=\"2\">" . _("Einstelldatum:");
        ?>
        <input type="text" required ="required" id="startdate" name="startdate" maxlength="10" size="10" value="<?= date('d.m.Y', $this->news_query['date'])?>">
        <?= _("Ablaufdatum:");?>
        <input type="text" required ="required" id="enddate" name="enddate" maxlength="10" size="10" value="<?= date('d.m.Y', $this->news_query['enddate'])?>">
        <script>
            jQuery('#startdate').datepicker();
            jQuery('#enddate').datepicker();
        </script>
        <?
        echo "\n</td></tr>";

        echo "<tr><td class=\"blank\">"._("Kommentare zulassen")."&nbsp;<input name=\"allow_comments\" value=\"1\" type=\"checkbox\" style=\"vertical-align:middle\"";
        if ($this->news_query["allow_comments"]) print " checked";
        echo "></td></tr>";
        echo "\n</table></td></tr>";
        echo "\n<tr><td class=\"blank\"><hr width=\"99%\"></td></tr>";
        echo "\n<tr><td class=\"blank\">&nbsp; <b>" . _("In diesen Bereichen wird die Ankündigung angezeigt:") . "</b><br><br></td></tr>";
        echo "\n<tr><td class=\"blank\"><table class=\"blank\" width=\"99%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\" align=\"center\">";
        $cssSw=new cssClassSwitcher;
        $cssSw->enableHover();
        $cssSw->switchClass();
        if ($perm->have_perm("root")) {
            echo "\n<tr><th width=\"90%\" align=\"left\">" . _("Systembereich") . "</th><th align=\"center\" width=\"10%\">" . _("Anzeigen ?") . "</th></tr>";
            echo "\n<tr ".$cssSw->getHover()."><td  ".$cssSw->getFullClass()." width=\"90%\">" . _("Systemweite Ankündigungen") . "</td>";
            echo "\n<td ".$cssSw->getFullClass()." width=\"10%\" align=\"center\"><input type=\"CHECKBOX\" name=\"add_range[]\" value=\"studip\"";
            if ($this->range_detail["studip"]["type"] OR ($this->news_range=="studip" AND $news_id=="new_entry"))
                echo "checked";
            echo "></td></tr>";
        }
        echo "\n<tr><th width=\"90%\" align=\"left\">" . _("Pers&ouml;nlicher Bereich") . "</th><th align=\"center\" width=\"10%\">" . _("Anzeigen ?") . "</th></tr>";
        echo "\n<tr ".$cssSw->getHover()."><td ".$cssSw->getFullClass()." width=\"90%\">".htmlReady($this->news_query["author"])."</td>";
        echo "\n<td  ".$cssSw->getFullClass()." width=\"10%\" align=\"center\">";
        if ($this->news_perm[$this->news_query["user_id"]]["perm"] OR $this->news_query["user_id"]==$this->user_id) {
            echo"<input type=\"CHECKBOX\" name=\"add_range[]\" value=\"".$this->news_query["user_id"]."\"";
            if ($this->range_detail[$this->news_query["user_id"]]["type"] OR ($this->news_range==$this->user_id AND $news_id=="new_entry"))
                echo "checked";
            echo "></td></tr>";
        } else {
            if ($this->range_detail[$this->news_query["user_id"]]["type"])
                echo _("Ja") . "<input type=\"HIDDEN\" name=\"add_range[]\" value=\"".$this->news_query["user_id"]."\">";
            else
                echo _("Nein");
            echo"</td></tr>";
        }
        if (isDeputyEditAboutActivated()) {
            $this->list_range_details("user");
        }
        $this->list_range_details("sem");
        $this->list_range_details("inst");
        $this->list_range_details("fak");
        echo "\n<tr><td class=\"blank\"> &nbsp; </td>";
        echo "\n</td></tr>";
        if ($perm->have_perm("admin")) {
            echo "<tr><td class=\"blank\" colspan=2>";
            echo "<table class=\"blank\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\" align=\"center\">";
            echo "\n<tr><td class=\"blank\"><b>" . _("Einen weiteren Bereich hinzuf&uuml;gen:") . "<br></td></tr>";
            echo "\n<tr><td class=\"steel1\"><font size=-1>" . _("Hier k&ouml;nnen Sie weitere Bereiche, auf die Sie Zugriff haben, der Auswahl hinzuf&uuml;gen") . "</font><br>";
            echo "<br><input style=\"vertical-align:middle;\" type=\"TEXT\"  name=\"search\" size=\"20\">&nbsp; <input type=\"IMAGE\" name=\"news_range_search\""
                . makeButton("suchestarten","src") . tooltip(_("Suche starten")) . " border=\"0\" style=\"vertical-align:middle;\"></div></td></tr></form></table><br>";
        }
        echo "</form></table>";
    }


    function update_news($news_id,$author,$topic,$body,$user_id,$date,$expire,$add_range, $allow_comments) {
        global $auth;

        // null value is not allowed for this field
        if ($allow_comments == null) $allow_comments = 0;

        if ($news_id) {
            if($this->check_news_perm($news_id)) {
                if ($news_id == "new_entry") {
                    $news_obj = new StudipNews();
                    $flag = TRUE;
                    $news_obj->setValue('user_id', $this->user_id);
                    $news_obj->setValue('author', $this->full_username);
                    $news_obj->setValue('date', ($date ? $date : time()));
                    $news_obj->setValue('topic', stripslashes($topic));
                    $news_obj->setValue('body', stripslashes($body));
                    $news_obj->setValue('expire', $expire);
                    $news_obj->setValue('allow_comments', $allow_comments);
                    if ($news_obj->store()){
                        $this->msg .= "msg§" . _("Ihre neue Ankündigung wurde gespeichert!") . "§";
                    }
                } else {
                    if ($this->news_query["topic"]!=stripslashes($topic)
                    OR $this->news_query["body"]!=stripslashes($body)
                    OR $this->news_query["date"]!=$date
                    OR $this->news_query["allow_comments"]!=$allow_comments
                    OR $this->news_query["expire"]!=$expire) {
                        $news_obj = new StudipNews($news_id);
                        if ($this->news_query['date'] != $date && $this->news_query["expire"] == $expire){
                            $expire = ($this->news_query['date'] + $this->news_query["expire"]) - $date;
                        }
                        $news_obj->setValue('date', $date);
                        $news_obj->setValue('topic', stripslashes($topic));
                        $news_obj->setValue('body', stripslashes($body));
                        $news_obj->setValue('expire', $expire);
                        $news_obj->setValue('allow_comments', $allow_comments);
                        if ($this->modus == "admin" && $user_id != $this->user_id) {
                            $news_obj->setValue('chdate_uid', $this->user_id);
                        } else {
                            $news_obj->setValue('chdate_uid', '');
                        }
                        if ($news_obj->store()) {
                            $this->msg .= "msg§ " . _("Die Ankündigung wurde ver&auml;ndert!") . "§";
                            if ($this->modus=="admin" AND $user_id!=$this->user_id) {
                                setTempLanguage($user_id);
                                $this->sms[$user_id] = sprintf(_("Ihre Ankündigung \"%s\" wurde von einem Administrator verändert!"),$this->news_query["topic"])
                                                    ."\n" . get_fullname() . ' ('.get_username().')'. "\n";
                                restoreLanguage();
                            }
                        }
                    }
                    if ($add_range) {
                        if (!is_object($news_obj)){
                            $news_obj = new StudipNews($news_id);
                        }
                        reset($this->range_detail);
                        while (list ($range,$details)=each($this->range_detail)) {
                            if(!in_array($range,$add_range)) {
                                if($this->news_perm[$range]["perm"] OR $auth->auth["perm"]=="root") {
                                    if ($news_obj->deleteRange($range)) {
                                        if ($this->modus=="admin" AND $user_id!=$this->user_id) {
                                            setTempLanguage($user_id);
                                            $msg .="\n" .sprintf(_("Der Bereich: %s wurde gelöscht."),$details["name"]);
                                            restoreLanguage();
                                        } else {
                                            $msg .="\n" .sprintf(_("Der Bereich: %s wurde gelöscht."),$details["name"]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if (!$add_range) {
                    $this->msg="info§" . _("Sie haben keinen Bereich für Ihre Ankündigung ausgew&auml;hlt. Ihre Ankündigung wird damit nirgends angezeigt!")."§";
                    return $news_id;
                } else {
                    for ($i=0;$i<count($add_range);$i++) {
                        if (!$this->range_detail[$add_range[$i]]["name"]) {
                            if($this->news_perm[$add_range[$i]]["perm"] OR $auth->auth["perm"]=="root") {
                                if ($news_obj->addRange($add_range[$i])) {
                                    if ( !($range_name = $this->news_perm[$add_range[$i]]["name"]) ){
                                        list($range_name,) = array_values(get_object_name($add_range[$i], get_object_type($add_range[$i])));
                                    }
                                    if ($this->modus=="admin" AND $user_id!=$this->user_id) {
                                            setTempLanguage($user_id);
                                            $msg .="\n" .sprintf(_("Der Bereich: %s wurde hinzugefügt."),$range_name);
                                            restoreLanguage();
                                        } else {
                                            $msg .="\n" .sprintf(_("Der Bereich: %s wurde hinzugefügt."),$range_name);
                                        }
                                }
                            }
                        }
                    }
                    if ($msg) {
                        $this->msg.="msg§".htmlReady($msg,true,true)."§";
                        if ($this->modus=="admin" AND $user_id!=$this->user_id) {
                            if ($this->sms[$user_id])
                                $this->sms[$user_id] .= $msg;
                            else
                                $this->sms[$user_id] = sprintf(_("Ihre Ankündigung \"%s\" wurde von einem Administrator verändert!"),$this->news_query["topic"])
                                                    ."\n" . get_fullname() . ' ('.get_username().')'. "\n" . $msg;
                        }
                    }
                $news_obj->storeRanges();
                }
            }
        } else {
            $this->msg="error§" . _("Fehler: Keine news_id &uuml;bergeben!") . "§";
        }
        return FALSE;
    }

    function kill_news($kill_news) {
        if ($kill_news) {
            if (!is_array($kill_news))
                $kill_news=array($kill_news);
            $kill_count=0;
            for ($i=0;$i<count($kill_news);$i++) {
                if ($this->check_news_perm($kill_news[$i],3)) {
                    $news = new StudipNews($kill_news[$i]);
                    if ($this->modus=="admin" AND $this->news_query["user_id"]!=$this->user_id) {
                        setTempLanguage($this->news_query["user_id"]);
                        $this->sms[$this->news_query["user_id"]] .= sprintf(_("Ihre Ankündigung \"%s\" wurde von einer Administratorin oder einem Administrator gelöscht!")
                                                                    ,$news->getValue('topic')) ."\n" . get_fullname() . ' ('.get_username().')';
                        restoreLanguage();
                    }
                    $kill_count += $news->delete();
                }

            }
            $this->msg.="msg§" . sprintf(_("Es wurden %s Ankündigungen gel&ouml;scht!"),$kill_count) . "§";
        }
        else $this->msg.="error§" . _("Sie haben keine Ankündigungen zum l&ouml;schen ausgew&auml;hlt!") . "§";
    }

    function search_range($search_str = false) {
        global $_fullname_sql;
        $this->search_result = (array)$this->search_result + (array)search_range($search_str, true);
        if (is_array($this->search_result) && count($this->search_result)){
            $query = "SELECT range_id, COUNT(range_id) AS anzahl FROM news_range WHERE range_id IN (?) GROUP BY range_id";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(array_keys($this->search_result)));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->search_result[$row['range_id']]['anzahl'] = $row['anzahl'];
            }
        }
        if (get_config('DEPUTIES_ENABLE')) {
            $query = "SELECT DISTINCT d.range_id, s.Name, s.status, sd.name AS sem_name "
                   . "FROM deputies d "
                   . "JOIN seminare s ON (d.range_id = s.Seminar_id) "
                   . "JOIN semester_data sd ON (s.start_time = sd.beginn) "
                   . "WHERE d.user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($GLOBALS['user']->id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $name = sprintf('%s: %s (%s) [%s]',
                                $GLOBALS['SEM_TYPE'][$row['status']]['name'],
                                $row['Name'], $row['sem_name'], _('Vertretung'));

                $this->search_result[$row['range_id']] = array(
                    'type' => 'sem',
                    'name' => $name
                );
            }
            if (isDeputyEditAboutActivated()) {
                $query = "SELECT DISTINCT d.range_id, {$_fullname_sql['full']} AS name, a.username "
                       . "FROM deputies d "
                       . "JOIN auth_user_md5 a ON (d.range_id = a.user_id) "
                       . "JOIN user_info u ON (a.user_id=u.user_id) "
                       . "WHERE d.user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($GLOBALS['user']->id));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->search_result[$row['range_id']] = array(
                        'type'     => 'user',
                        'name'     => $row['name'],
                        'perm'     => 2,
                        "username" => $row['username']
                    );
                }
            }
        }
    }

    //Hilfsfunktionen
    function compare_range($range1, $range2) {
        if ($range1['starttime'] < $range2['starttime']) {
            return 1;
        } else if ($range1['starttime'] > $range2['starttime']) {
            return -1;
        } else {
            return strnatcasecmp($range1['name'], $range2['name']);
        }
    }

    function list_range_details($type) {
        global $perm, $_fullname_sql;
        $ranges = array();

        switch ($type) {
            case "sem" :
                $group = _("Veranstaltungen");
                break;

            case "inst" :
                $group = _("Einrichtungen");
                $query = "SELECT Institute.Institut_id AS id,Name AS name FROM user_inst LEFT JOIN Institute ON(user_inst.Institut_id=Institute.Institut_id AND Institute.Institut_id!=fakultaets_id) WHERE NOT ISNULL(Institute.Institut_id) AND user_inst.user_id=? AND user_inst.inst_perms='autor' ORDER BY Name";
                break;

            case "fak" :
                $group = _("Fakultäten");
                $query = "SELECT Institute.Institut_id AS id,Name AS name FROM user_inst LEFT JOIN Institute ON(user_inst.Institut_id=Institute.Institut_id AND Institute.Institut_id=fakultaets_id) WHERE NOT ISNULL(Institute.Institut_id) AND user_inst.user_id = ? AND user_inst.inst_perms='autor' ORDER BY Name";
                break;

            case "user" :
                $group = _("Andere Nutzerinnen und Nutzer, deren Vertretung ich bin");
                $query = "SELECT DISTINCT d.range_id AS id, ".$_fullname_sql['full']." AS name FROM deputies d JOIN auth_user_md5 a ON (d.range_id = a.user_id) JOIN user_info u ON (a.user_id=u.user_id) WHERE d.user_id = ?";
                break;
        }

        if ($perm->have_perm('autor')) {
            foreach ($this->search_result as $range => $details) {
                if ($details['type'] == $type) {
                    $ranges[$range] = array(
                        'name' => $details['name'],
                        'group' => isset($details['startsem']) ? $group.': '.$details['startsem'] : $group
                    );
                }
            }
        } else if (isset($query)) {
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $ranges[$row['id']] = array(
                    'name'  => $row['name'],
                    'group' => $group
                );
            }
        }
        $this->list_range_groups($ranges);
    }

    function list_range_groups($ranges) {
        $cssSw=new cssClassSwitcher();
        $cssSw->enableHover();

        foreach ($ranges as $range => $details) {
            if ($details['group'] != $lastgroup) {
                echo "<tr><th width=\"90%\" align=\"left\">".$details['group'].'</th><th align="center" width="10%">' . _("Anzeigen ?") . '</th></tr>';
                $lastgroup = $details['group'];
                $cssSw->resetClass();
            }
            $cssSw->switchClass();
            echo "\n<tr ".$cssSw->getHover().'><td  '.$cssSw->getFullClass(). '  width="90%">' .htmlReady($details['name']).'</td>';
            echo "\n<td  ".$cssSw->getFullClass(). ' width="10%" align="center">';
            if ($this->news_perm[$range]["perm"] || $GLOBALS['perm']->have_perm("root")) {
                echo '<input type="CHECKBOX" name="add_range[]" value="' . $range. '"';
                if ($range == $this->news_range && $this->news_query['news_id'] == 'new_entry' || isset($this->range_detail[$range]))
                    echo ' checked ';
                echo '>';
            } elseif (isset($this->range_detail[$range])) {
                echo _("Ja") . '<input type="hidden" name="add_range[]" value="' . $range . '">';
            }
            echo '</td></tr>';
        }
    }


    function get_news_perm() {
        global $auth,$perm,$_fullname_sql;

        $this->news_perm[$this->user_id] = array(
            'name' => $this->full_username,
            'perm' => 3
        );

        if ($auth->auth['perm'] == 'root'){
            $this->news_perm['studip'] = array(
                'name' => 'Stud.IP Ankündigungen',
                'perm' => 3
            );
        } else {
            if (in_array($auth->auth['perm'], array('dozent', 'tutor', 'autor'))) {
                $query = "SELECT seminare.Seminar_id AS id, seminar_user.status, Name "
                       . "FROM seminar_user "
                       . "LEFT JOIN seminare USING (Seminar_id) "
                       . "WHERE seminar_user.user_id = ? AND seminar_user.status IN ('dozent','tutor')";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($this->user_id));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->news_perm[$row['id']] = array(
                        'name' => $row['Name'],
                        'perm' => 2
                    );
                }
            }

            if ($auth->auth['perm'] == 'admin') {
                $query = "SELECT b.Seminar_id AS id, b.Name "
                       . "FROM user_inst AS a "
                       . "LEFT JOIN seminare AS b USING (Institut_id) "
                       . "WHERE a.user_id = ? AND a.inst_perms = 'admin'";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($this->user_id));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->news_perm[$row['id']] = array(
                        'name' => $row['Name'],
                        'perm' => 3
                    );
                }
            }

            $query = "SELECT Institute.Institut_id AS id, Name, user_inst.inst_perms AS status "
                   . "FROM user_inst "
                   . "LEFT JOIN Institute USING (Institut_id) "
                   . "WHERE user_inst.user_id = ? "
                   . " AND user_inst.inst_perms IN ('admin','dozent','tutor','autor')";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->news_perm[$row['id']] = array(
                    'name' => $row['Name'],
                    'perm' => $row['status'] == 'admin' ? 3 : 2
                );
            }

            $query = "SELECT b.Institut_id AS id, b.Name, a.inst_perms AS status "
                   . "FROM user_inst a "
                   . "LEFT JOIN Institute b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id) "
                   . "WHERE a.user_id = ? "
                   . " AND a.inst_perms IN ('admin','autor') "
                   . " AND NOT ISNULL(b.Institut_id)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user_id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->news_perm[$row['id']] = array(
                    'name' => $row['Name'],
                    'perm' => $row['status'] == 'admin' ? 3 : 1
                );
            }

            if ($perm->is_fak_admin()){
                $query = "SELECT d.Seminar_id AS id, d.Name "
                       . "FROM user_inst a "
                       . "LEFT JOIN Institute b ON(a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id) "
                       . "LEFT JOIN Institute c ON(c.fakultaets_id = b.institut_id AND c.fakultaets_id != c.institut_id) "
                       . "LEFT JOIN seminare d ON(d.institut_id=c.institut_id) "
                       . "WHERE a.user_id = ? "
                       . " AND a.inst_perms='admin' "
                       . " AND NOT ISNULL(b.Institut_id)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($this->user_id));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->news_perm[$row['id']] = array(
                        'name' => $row['Name'],
                        'perm' => 3
                    );
                }

                $query = "SELECT c.Institut_id AS id, c.Name "
                       . "FROM user_inst a "
                       . "LEFT JOIN Institute b ON (a.Institut_id = b.Institut_id AND b.Institut_id = b.fakultaets_id) "
                       . "LEFT JOIN Institute c ON (c.fakultaets_id = b.institut_id AND c.fakultaets_id != c.institut_id) "
                       . "WHERE a.user_id = ? "
                       . " AND a.inst_perms='admin' "
                       . " AND NOT ISNULL(b.Institut_id)";
                   $statement = DBManager::get()->prepare($query);
                   $statement->execute(array($this->user_id));
                   while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                       $this->news_perm[$row['id']] = array(
                           'name' => $row['Name'],
                           'perm' => 3
                       );
                   }
            }

            if (get_config('DEPUTIES_ENABLE')) {
                $query = "SELECT DISTINCT d.range_id AS id, s.Name "
                       . "FROM deputies d "
                       . "JOIN seminare s ON (d.range_id = s.Seminar_id) "
                       . "WHERE d.user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($GLOBALS['user']->id));
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $this->news_perm[$row['id']] = array(
                        'name' => $row['Name'],
                        'perm' => 2
                    );
                }

                if (isDeputyEditAboutActivated()) {
                    $query = "SELECT DISTINCT d.range_id AS id, {$_fullname_sql['full']} AS name, a.username "
                           . "FROM deputies d "
                           . "JOIN auth_user_md5 a ON (d.range_id = a.user_id) "
                           . "JOIN user_info u ON (a.user_id=u.user_id) "
                           . "WHERE d.user_id = '".$GLOBALS["auth"]->auth["uid"]."'";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($GLOBALS['user']->id));
                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        $this->news_perm[$row['id']] = array(
                            'name' => $row['Name'],
                            'perm' => 2,
                            'username' => $row['username']
                        );
                    }
                }
            }
        }
    }

    function check_news_perm($news_id,$check=2) {
        global $auth;
        if ($news_id=="new_entry")
            return TRUE;
        $this->get_one_news($news_id);
        if ($auth->auth["perm"]=="root")
            return TRUE;
        if ($this->news_query["user_id"]==$this->user_id)
            return TRUE;
        elseif ($this->modus!="admin")
            $this->msg.="error§" . _("Sie d&uuml;rfen nur Ihre eigenen Ankündigungen ver&auml;ndern") . "§";
        if ($this->modus=="admin") {
            reset($this->range_detail);
            while (list ($range,$details) = each ($this->range_detail)) {
                if ($this->news_perm[$range]["perm"]>=$check)
                    return TRUE;
            }
            $this->msg.="error§" . _("Sie haben keine Berechtigung diese Ankündigung zu bearbeiten") . "§";
        }
        return FALSE;
    }

    function get_news_range_perm($range_id){
        return ($GLOBALS['perm']->get_perm() == 'root' ? 3 : $this->news_perm[$range_id]["perm"]);
    }

    function send_sms() {
        $msg_object = new messaging();
        while (list($user_id,$msg) = each($this->sms)) {
            $msg_object->insert_message(mysql_escape_string($msg), get_username($user_id) , "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Ankündigung geändert"));
        }
    }

}   //Ende Klassendefintion
