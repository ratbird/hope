<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/*
admin_banner_ads.php - Werbebanner-Verwaltung von Stud.IP.
Copyright (C) 2003 Tobias Thelen <tthelen@uos.de>

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

use Studip\Button, Studip\LinkButton; 

require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$perm->check("root");

// keep data copies for search etc.
$sess->register("save_banner_data");
$sess->register("banner_data");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once ('lib/msg.inc.php'); //Funktionen fuer Nachrichtenmeldungen
require_once ('lib/visual.inc.php');
require_once ('config.inc.php');
require_once('lib/classes/Table.class.php');
require_once('lib/classes/ZebraTable.class.php');

// Get a database connection

function imaging($img, $img_size, $img_name)
{
    global $banner_data;
    $msg = '';
    if (!$img_name) { //keine Datei ausgewählt!
        return "error§" . _("Sie haben keine Datei zum Hochladen ausgewählt!");
    }

    //Dateiendung bestimmen
    $dot = strrpos($img_name,".");
    if ($dot) {
        $l = strlen($img_name) - $dot;
        $ext = strtolower(substr($img_name,$dot+1,$l));
    }
    //passende Endung ?
    if ($ext != 'jpg' && $ext != 'gif' && $ext != 'png') {
        $msg = "error§" . sprintf(_("Der Dateityp der Bilddatei ist falsch (%s).<br>Es sind nur die Dateiendungen .gif, .png und .jpg erlaubt!"), $ext);
        return $msg;
    }

    //na dann kopieren wir mal...
    $uploaddir = $GLOBALS['DYNAMIC_CONTENT_PATH'] . '/banner';
    $md5hash = md5($img_name+time());
    $newfile = $uploaddir . '/' . $md5hash . '.' . $ext;
    $_SESSION['banner_data']["banner_path"] = $md5hash . '.' . $ext;
    if(!@move_uploaded_file($img,$newfile)) {
        $msg = "error§" . _("Es ist ein Fehler beim Kopieren der Datei aufgetreten. Das Bild wurde nicht hochgeladen!");
        return $msg;
    } else {
        $msg = "msg§" . _("Die Bilddatei wurde erfolgreich hochgeladen.");
        chmod($newfile, 0666 & ~umask());       // set permissions for uploaded file
    }
    return $msg;
}

//Anzeige der Bannerdaten
function view_probability($prio) {
    static $computed=0, $sum=0;

    if ($prio==0) return "--";

    if (!$computed) {
        $sum = DBManager::get()
            ->query("SELECT SUM(POW(2, priority)) FROM banner_ads WHERE priority > 0")
            ->fetchColumn();
        $computed=1;
    }
    return "1/" . (1/(pow(2,$prio)/$sum));
}

function show_banner_list($table) {
    $query = "SELECT ad_id, banner_path, alttext, description, target_type, "
           . "  target, startdate, enddate, views, priority "
           . "FROM banner_ads ORDER BY priority DESC";
    $banners = DBManager::get()
        ->query($query)
        ->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($banners as $banner) {
        print $table->row(array(_("Banner"),"<img src=\"".$GLOBALS['DYNAMIC_CONTENT_URL']."/banner/".$banner['banner_path']."\" alt=\"".$banner['alttext']."\">"),"",1);
        print $table->row(array(_("Beschreibung"),$banner['description']),"",0);
        print $table->row(array(_("Ziel"),"(".$banner['target_type'].") " . $banner['target']),"",0);
        print $table->row(array(_("Anzeigezeitraum"), ($banner['startdate'] ? date("d.m.Y, H:i",$banner['startdate']) : _("sofort")) . " " . _("bis") . " " . ($banner['enddate'] ? date("d.m.Y, H:i", $banner['enddate']) : _("unbegrenzt"))),"",0);
        print $table->row(array(_("Views"), $banner['views']),"",0);
        print $table->row(array(_("Priorität (Wahrscheinlichkeit)"), $banner['priority'] . " (" . view_probability($banner['priority']) . ")"),"",0);
        print $table->row(array("", LinkButton::create(_('Bearbeiten'), $PHP_SELF.'?cmd=editdb&ad_id='.$banner['ad_id']).' '.LinkButton::create(_('Löschen'), $PHP_SELF.'?cmd=delete&ad_id='.$banner['ad_id'])),"",0);
        print $table->row(array("&nbsp;","&nbsp"),array("class"=>"blank", "bgcolor"=>"white"),0);
    }
    if (empty($banners)) {
        print $table->row(array("<h4>" . _("Keine Banner vorhanden.") . "</h4>"), array("colspan"=>2, "class"=>"blank"));
    }
}

function check_data(&$banner_data) {
    $msg = '';

    function valid_date($h,$m,$d,$mo,$y) {
        if (($h==_("hh") && $m==_("mm") && $d==_("tt") && $mo==_("mm") && $y==_("jjjj"))|| ($h+$m+$d+$mo+$y == 0)) {
            return 0; // 0= forever
        }
        // mktime return -1 if date is invalid (and does some strange
        // conversion which might be considered as a bug..)
        $x=mktime($h,$m,0,$mo,$d,$y);
        return $x;
    }

    if (!$_SESSION['banner_data']['banner_path'])
        $msg .= 'error§' . _("Es wurde kein Bild ausgewählt.") . '§';

    if (!$_SESSION['banner_data']['target'] && $_SESSION['banner_data']['target_type'] != 'none')
        $msg .= 'error§' . _("Es wurde kein Verweisziel angegeben.") . '§';

    if (($x=valid_date($_SESSION['banner_data']['start_hour'], $_SESSION['banner_data']['start_minute'], $_SESSION['banner_data']['start_day'], $_SESSION['banner_data']['start_month'], $_SESSION['banner_data']['start_year']))==-1)
        $msg .= 'error§' . _("Bitte geben Sie einen gültiges Startdatum ein.") . '§';
    else
        $_SESSION['banner_data']['startdate']=$x;

    if (($x=valid_date($_SESSION['banner_data']["end_hour"], $_SESSION['banner_data']["end_minute"], $_SESSION['banner_data']["end_day"], $_SESSION['banner_data']["end_month"], $_SESSION['banner_data']["end_year"]))==-1)
        $msg .= 'error§' . _("Bitte geben Sie einen gültiges Enddatum ein.") . '§';
    else
        $_SESSION['banner_data']['enddate']=$x;

    switch ($_SESSION['banner_data']['target_type']) {
        case 'url':
             if (!preg_match('/^(https?)|(ftp):\\/\\//i', $_SESSION['banner_data']['target'])) $msg .= "error§" . _("Das Verweisziel muss eine gültige URL sein (incl. http://).") . "§";
            break;
        case 'inst':
            $query = "SELECT 1 FROM Institute WHERE Institut_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($_SESSION['banner_data']['target']));
            if (!$statement->fetchColumn()) {
                $msg .= "error§" . _("Die angegebene Einrichtung existiert nicht. Bitte geben Sie eine gültige Einrichtungs-ID ein.") .'§';
            }
            break;
        case 'user':
            $query = "SELECT 1 FROM auth_user_md5 WHERE username = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($_SESSION['banner_data']['target']));
            if (!$statement->fetchColumn()) {
                $msg .= "error§" . _("Der angegebene Benutzername existiert nicht.") ."§";
            }
            break;
        case 'seminar':
            $query = "SELECT 1 FROM seminare WHERE Seminar_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($_SESSION['banner_data']['target']));
            if (!$statement->fetchColumn()) {
                $msg .= "error§" . _("Die angegebene Veranstaltung existiert nicht. Bitte geben Sie eine gültige Veranstaltungs-ID ein.") . "§";
            }
            break;
        case "special":
            $msg .= 'error§' . _("Der Verweistyp \"speziell\" wird in dieser Installation nicht unterstützt.") . '§';
            break;
        case "none":
            $_SESSION['banner_data']['target'] = '';
            break;
    }
    return $msg;
}

function write_data_to_db($banner_data) {
    if (!$banner_data['ad_id']) {
        $banner_data['ad_id'] = md5($banner_data['banner_path'] + time());
    }

    $query = "INSERT INTO banner_ads "
           . "(ad_id, clicks, views, mkdate, banner_path, description, alttext,"
           . " target_type, target, startdate, enddate, priority, chdate) "
           . "VALUES (?, 0, 0, UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP()) "
           . "ON DUPLICATE KEY UPDATE banner_path = VALUES(banner_path), "
           . "   description = VALUES(description), alttext = VALUES(alttext), "
           . "   target_type = VALUES(target_type), target = VALUES(target), "
           . "   startdate = VALUES(startdate), enddate = VALUES(enddate), "
           . "   priority = VALUES(priority), chdate = VALUES(chdate)";
    DBManager::get()
        ->prepare($query)
        ->execute(array(
            $banner_data['ad_id'],
            $banner_data['banner_path'],
            $banner_data['description'],
            $banner_data['alttext'],
            $banner_data['target_type'],
            $banner_data['target'],
            $banner_data['startdate'],
            $banner_data['enddate'],
            $banner_data['priority'],
        ));
}

function edit_banner_pic($banner_data) {
    global $save_banner_data;

    $table=new ZebraTable(array("bgcolor"=>"#eeeeee", "align"=>"center", "width"=>"75%", "padding"=>"2"));
    echo $table->open();
    echo $table->openRow();
    echo $table->openCell();

    // save data for lower form
    $_SESSION['save_banner_data']=$banner_data;

    print _("Aktuelles Banner:");
    if ($banner_data["banner_path"]) {
        print "<p><img src=\"".$GLOBALS['DYNAMIC_CONTENT_URL']."/banner/" . $banner_data["banner_path"] . "\"></p>";
    } else {
        print "<p>" . _("noch kein Bild hochgeladen") . "</p>";
    }
    print "</td></tr>";
    echo $table->closeRow();

    print "<form enctype=\"multipart/form-data\" action=\"$PHP_SELF?cmd=upload&view=edit\" method=\"POST\">";
    echo CSRFProtection::tokenTag();
    print $table->row(array(_("1. Bilddatei auswählen:")." <input name=\"imgfile\" type=\"file\" cols=45>"),"",0);
    print $table->row(array(_("2. Bilddatei hochladen:").Button::createAccept(_('Absenden'))),"",0);
    print "</form>";
    echo $table->close();

}

function edit_banner_data($banner_data) {

    function select_option($name, $printname, $checkval) {
        $x = "<option value=\"$name\"";
        if ($checkval==$name) {
            $x .= " selected";
        }
        $x .= ">" . $printname . "</option>";
        return $x;
    }
    $table=new ZebraTable(array("bgcolor"=>"#eeeeee", "align"=>"center", "width"=>"75%", "padding"=>"2"));
    echo $table->open();

    print "<form action=\"$PHP_SELF?cmd=edit&i_view=edit\" method=\"post\">";
    echo CSRFProtection::tokenTag();
    if ($banner_data["ad_id"]) {
        print "<input type=hidden name=\"ad_id\" value=\"" . $banner_data["ad_id"] . "\">";
    }
    if ($banner_data["banner_path"]) {
        $path_info = "<input type=hidden name=banner_path value=\"" . $banner_data["banner_path"] . "\">" . $banner_data["banner_path"];
    } else {
        $path_info = _("Noch kein Bild ausgewählt");
    }
    print $table->row(array(_("Pfad:"),$path_info),0);
    print $table->row(array(_("Beschreibung"),"<input type=text name=\"description\" size=\"40\" maxlen=\"254\" value=\"" . $banner_data["description"] . "\">"),0);
    print $table->row(array(_("Alternativtext"),"<input type=text name=\"alttext\" size=\"40\" maxlen=\"254\" value=\"". $banner_data["alttext"] . "\">"),0);
    $type_selector = "<select name=\"target_type\">";
    $type_selector .= select_option("url",_("URL"), $banner_data["target_type"]);
    $type_selector .= select_option("seminar",_("Veranstaltung"), $banner_data["target_type"]);
    $type_selector .= select_option("inst",_("Einrichtung"), $banner_data["target_type"]);
    $type_selector .= select_option("user",_("Benutzer"), $banner_data["target_type"]);
    $type_selector .= select_option("none",_("Kein Verweis"), $banner_data["target_type"]);
    //$type_selector .= select_option("special",_("speziell"), $_SESSION['banner_data']["target_type"]);
    $type_selector .= "</select>";
    print $table->row(array(_("Verweis-Typ"),$type_selector),0);

    print $table->row(array(_("Verweis-Ziel"),"<input type=text name=\"target\" size=40 maxlength=254 value=\"". $banner_data["target"] . "\">"),0);

    $startdate_fields = "<input name=\"start_day\" value=\"".$banner_data[start_day]."\" size=2 maxlength=2>. ";
    $startdate_fields .= "<input name=\"start_month\" value=\"".$banner_data[start_month]."\" size=2 maxlength=2>. ";
    $startdate_fields .= "<input name=\"start_year\" value=\"".$banner_data[start_year]."\" size=4 maxlength=4> &nbsp; &nbsp;";
    $startdate_fields .= "<input name=\"start_hour\" value=\"".$banner_data[start_hour]."\" size=2 maxlength=2>:";
    $startdate_fields .= "<input name=\"start_minute\" value=\"".$banner_data[start_minute]."\" size=2 maxlength=2> ";
    print $table->row(array(_("Anzeigen ab:"), $startdate_fields),0);

    $enddate_fields = "<input name=\"end_day\" value=\"".$banner_data[end_day]."\" size=2 maxlength=2>. ";
    $enddate_fields .= "<input name=\"end_month\" value=\"".$banner_data[end_month]."\" size=2 maxlength=2>. ";
    $enddate_fields .= "<input name=\"end_year\" value=\"".$banner_data[end_year]."\" size=4 maxlength=4> &nbsp; &nbsp;";
    $enddate_fields .= "<input name=\"end_hour\" value=\"".$banner_data[end_hour]."\" size=2 maxlength=2>:";
    $enddate_fields .= "<input name=\"end_minute\" value=\"".$banner_data[end_minute]."\" size=2 maxlength=2> ";
    print $table->row(array(_("Anzeigen bis:"), $enddate_fields),0);

    $prio_selector = "<select name=\"priority\">";
    $prio_selector .= select_option("0", _("0 (nicht anzeigen)"), $banner_data[priority]);
    $prio_selector .= select_option("1", _("1 (sehr niedrig)"), $banner_data[priority]);
    $prio_selector .= select_option("2", _("2"), $banner_data[priority]);
    $prio_selector .= select_option("3", _("3"), $banner_data[priority]);
    $prio_selector .= select_option("4", _("4"), $banner_data[priority]);
    $prio_selector .= select_option("5", _("5"), $banner_data[priority]);
    $prio_selector .= select_option("6", _("6"), $banner_data[priority]);
    $prio_selector .= select_option("7", _("7"), $banner_data[priority]);
    $prio_selector .= select_option("8", _("8"), $banner_data[priority]);
    $prio_selector .= select_option("9", _("9"), $banner_data[priority]);
    $prio_selector .= select_option("10", _("10 (sehr hoch)"), $banner_data[priority]);
    $prio_selector .= "</select>";
    print $table->row(array("Priorität:", $prio_selector),0);

    print $table->row(array("", Button::createAccept(_('Absenden')).' '.LinkButton::createCancel(_('Abbrechen'))),0);

    print "</form>";
    $table->close();
}

PageLayout::setTitle(_("Verwaltung der Werbebanner"));
Navigation::activateItem('/admin/config/banner_ads');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if (!$BANNER_ADS_ENABLE) {
    echo '<p>', _("Banner-Modul abgeschaltet."), "</p>\n";
    include ('lib/include/html_end.inc.php');
    page_close();
    die;
}

$container=new ContainerTable();
echo $container->headerRow("<b>&nbsp;"._("Verwaltung der Werbebanner")."</b>");
echo $container->openCell();

$content=new ContentTable();
echo $content->open();
echo $content->openRow();
echo $content->cell("<b><a href=\"$PHP_SELF?i_view=new\">&nbsp;"._("Neues Banner anlegen")."</a><b><br><br>", array("colspan"=>"2"));
echo $content->openRow();
echo $content->openCell(array("colspan"=>"2"));

$_SESSION['banner_data']=array();
$cmd = Request::option('cmd');
$i_view = Request::option('i_view');
if ($cmd=="upload") {
    $msg=imaging(Request::quoted('imgfile'),$imgfile_size,$imgfile_name);
    parse_msg($msg);
    parse_msg("info§" . _("Die Daten wurden noch nicht in die Datenbank geschrieben."));
    $banner_path = $_SESSION['banner_data']["banner_path"];
    $_SESSION['banner_data'] = $_SESSION['save_banner_data'];
    if ($banner_path != '' ) $_SESSION['banner_data']["banner_path"] = $banner_path;
    $i_view="edit";
} elseif ($cmd=="delete") {
    DBManager::get()
        ->prepare("DELETE FROM banner_ads WHERE ad_id = ?")
        ->execute(array($ad_id));
    parse_msg("msg§". _("Banner gelöscht"));
    $i_view="list";
} elseif ($cmd=="editdb") {
    $query = "SELECT ad_id, target, target_type, description, alttext, banner_path, priority, startdate, enddate "
           . "FROM banner_ads WHERE ad_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($ad_id));
    $_SESSION['banner_data'] = $statement->fetch(PDO::FETCH_ASSOC);
    if ($_SESSION['banner_data']) {
        $starttime = $_SESSION['banner_data']['startdate'];
        $_SESSION['banner_data']['start_minute'] = ($starttime == 0) ? _('mm') : date('i', $starttime);
        $_SESSION['banner_data']['start_hour']   = ($starttime == 0) ? _('hh') : date('H', $starttime);
        $_SESSION['banner_data']['start_day']    = ($starttime == 0) ? _('tt') : date('d', $starttime);
        $_SESSION['banner_data']['start_month']  = ($starttime == 0) ? _('mm') : date('m', $starttime);
        $_SESSION['banner_data']['start_year']   = ($starttime == 0) ? _('jjjj') : date('Y', $starttime);
        unset($_SESSION['banner_data']['startdate']);
        
        $endtime = $_SESSION['banner_data']['enddate'];
        $_SESSION['banner_data']['end_minute'] = ($endtime == 0) ? _('mm') : date('i', $endtime);
        $_SESSION['banner_data']['end_hour']   = ($endtime == 0) ? _('hh') : date('H', $endtime);
        $_SESSION['banner_data']['end_day']    = ($endtime == 0) ? _('tt') : date('d', $endtime);
        $_SESSION['banner_data']['end_month']  = ($endtime == 0) ? _('mm') : date('m', $endtime);
        $_SESSION['banner_data']['end_year']   = ($endtime == 0) ? _('jjjj') : date('Y', $endtime);
        unset($_SESSION['banner_data']['enddate']);

        $i_view = "edit";
    } else {
        parse_msg("error§" . _("Ungültige Banner-ID"));
    }
} elseif ($cmd == 'edit') {
    $_SESSION['banner_data']['ad_id']        = Request::option('ad_id', null);
    $_SESSION['banner_data']['target']       = Request::get('target');
    $_SESSION['banner_data']['target_type']  = Request::option('target_type');
    $_SESSION['banner_data']['description']  = Request::get('description');
    $_SESSION['banner_data']['alttext']      = Request::get('alttext');
    $_SESSION['banner_data']['banner_path']  = Request::get('banner_path');
    $_SESSION['banner_data']['start_minute'] = Request::option('start_minute');
    $_SESSION['banner_data']['start_hour']   = Request::option('start_hour');
    $_SESSION['banner_data']['start_day']    = Request::option('start_day');
    $_SESSION['banner_data']['start_month']  = Request::option('start_month');
    $_SESSION['banner_data']['start_year']   = Request::option('start_year');
    $_SESSION['banner_data']['end_minute']   = Request::option('end_minute');
    $_SESSION['banner_data']['end_hour']     = Request::option('end_hour');
    $_SESSION['banner_data']['end_day']      = Request::option('end_day');
    $_SESSION['banner_data']['end_month']    = Request::option('end_month');
    $_SESSION['banner_data']['end_year']     = Request::option('end_year');
    $_SESSION['banner_data']['priority']     = Request::int('priority');
    $msg=check_data($_SESSION['banner_data']);
    if ($msg) {
        parse_msg($msg);
        $i_view="edit";
    } else {
        write_data_to_db($_SESSION['banner_data']);
        parse_msg("msg§" . _("Die Daten wurden erfolgreich in die Datenbank geschrieben."));
        $i_view="list";
    }
}

if ($i_view=="new") {
    $_SESSION['banner_data']["target"]="";
    $_SESSION['banner_data']["target_type"]="url";
    $_SESSION['banner_data']["description"]="";
    $_SESSION['banner_data']["alttext"]="";
    if (!$_SESSION['banner_data']["banner_path"]) {
        $_SESSION['banner_data']["banner_path"]="";
    }
    $_SESSION['banner_data']["start_minute"]=_("mm");
    $_SESSION['banner_data']["start_hour"]=_("hh");
    $_SESSION['banner_data']["start_day"]=_("tt");
    $_SESSION['banner_data']["start_month"]=_("mm");
    $_SESSION['banner_data']["start_year"]=_("jjjj");
    $_SESSION['banner_data']["end_minute"]=_("mm");
    $_SESSION['banner_data']["end_hour"]=_("hh");
    $_SESSION['banner_data']["end_day"]=_("tt");
    $_SESSION['banner_data']["end_month"]=_("mm");
    $_SESSION['banner_data']["end_year"]=_("jjjj");
    $_SESSION['banner_data']["priority"]="1";
    edit_banner_pic($_SESSION['banner_data']);
    print "<p>&nbsp;</p>";
    edit_banner_data($_SESSION['banner_data']);
} else if ($i_view=="edit") {
    edit_banner_pic($_SESSION['banner_data']);
    print "<p>&nbsp;</p>";
    edit_banner_data($_SESSION['banner_data']);
} else {
    $table=new ZebraTable(array("bgcolor"=>"#eeeeee", "align"=>"center", "width"=>"75%", "padding"=>"2"));
    echo $table->open();
    show_banner_list($table);
    echo $table->close();
}

echo $content->close();
echo $container->blankRow();
echo $container->close();

    include ('lib/include/html_end.inc.php');
    page_close();
?>
