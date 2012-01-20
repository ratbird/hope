<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* sem_portal.php
*
* the body for the serach engine
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  views
* @module       sem_portal.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// sem_portal.php
// Rahmenseite der Suchfunktion
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

ob_start();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

PageLayout::setHelpKeyword("Basis.VeranstaltungenAbonnieren");
PageLayout::setTitle(_("Veranstaltungssuche"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once 'lib/functions.php';   //hier wird der "Kopf" nachgeladen
require_once 'config.inc.php';      //wir brauchen die Seminar-Typen
require_once 'lib/visual.inc.php';  //wir brauchen die Seminar-Typen
require_once 'lib/classes/SemBrowse.class.php';
require_once 'lib/classes/StmBrowse.class.php';

$db=new DB_Seminar;



//Einstellungen fuer Reitersystem
$sess->register("sem_portal");


//Standard herstellen

if (Request::get("search_sem_quick_search_parameter")) {
    $_REQUEST['search_sem_quick_search'] = Request::get("search_sem_quick_search_parameter");
}
if ($_REQUEST['view'])
    $sem_portal["bereich"] = $_REQUEST['view'];

if (!$sem_portal["bereich"])
    $sem_portal["bereich"] = "all";

$_REQUEST['view'] = $sem_portal['bereich'];
Navigation::activateItem('/search/courses/'.$sem_portal['bereich']);

if ($choose_toplist)
    $sem_portal["toplist"] = $choose_toplist;

if (!$sem_portal["toplist"])
    $sem_portal["toplist"] = 4;

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head
PageLayout::addBodyElements(cssClassSwitcher::GetHoverJSFunction());

//function to display toplists
function getToplist($rubrik, $query, $type="count") {
    global $PHP_SELF;
    $result .= "<table cellpadding=\"0\" cellspacing=\"2\" border=\"0\">";
    $db=new DB_Seminar;
    $db->cache_query($query);
    if  ($db->num_rows() > 0) {
        $result .= "<tr><td colspan=\"2\"><font size=\"-1\"><b>$rubrik</b></font></td></tr>";
        $i=1;
        while ($db->next_record() ){
            $result .= "<tr><td width=\"1%\" valign=\"top\"><font size=\"-1\">$i.</font></td>";
            $result .= "<td width=\"99%\"><font size=\"-1\"><a href=\"details.php?sem_id=".$db->f("seminar_id")."&send_from_search=true&send_from_search_page=$PHP_SELF\">";
            $result .= htmlReady(substr($db->f("name"),0,45));
            if (strlen ($db->f("name")) > 45)
                $result .= "... ";
            $result .= "</a>";
            if ($type == "date" AND $db->f("count") >0) {
                $last =  date("YmdHis",$db->f("count"));
                $count = substr($last,6,2).".".substr($last,4,2).".". substr($last,0,4);
            }
            else
                $count = $db->f("count");
            if ($count>0)
                $result .= "&nbsp; (".$count.")";
            $result .= "</font></td></tr>";
            $i++;
        }
        $result .= "</tr>";
    }
    $result .= "</table>";
    return $result;
}

if ($sem_portal["bereich"] != "all" && $sem_portal["bereich"] != "mod") {
    $_sem_status = array();
    foreach ($SEM_CLASS as $key => $value){
        if ($key == $sem_portal["bereich"]){
            foreach($SEM_TYPE as $type_key => $type_value){
                if($type_value['class'] == $key)
                $_sem_status[] = $type_key;
            }
        }
    }

    $query = "SELECT count(*) AS count FROM seminare WHERE "
        . (!$GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM')) ? "seminare.visible=1 AND" : "" )
        . " seminare.status IN ('" . join("','", $_sem_status) . "')";
    $db->query($query);
    if ($db->next_record())
        $anzahl_seminare_class = $db->f("count");
} else {
    $_sem_status = false;
}

if ($sem_portal["bereich"] == "mod") {
    $query = "SELECT count(*) AS count FROM stm_instances WHERE complete=1";
    $db->query($query);
    if ($db->next_record())
        $anzahl_seminare_class = $db->f("count");
}

$init_data = array( "level" => "f",
                    "cmd"=>"qs",
                    "show_class"=>$sem_portal['bereich'],
                    "group_by"=>0,
                    "default_sem"=> ( ($default_sem = SemesterData::GetSemesterIndexById($_SESSION['_default_sem'])) !== false ? $default_sem : "all"),
                    "sem_status"=>$_sem_status);

if ($reset_all) $sem_browse_data = null;
if (get_config('STM_ENABLE') &&  $sem_portal["bereich"] == "mod"){
    $sem_browse_obj = new StmBrowse($init_data);
} else {
    $sem_browse_obj = new SemBrowse($init_data);
    $sem_browse_data['show_class'] = $sem_portal["bereich"];
}
if (!$perm->have_perm("root")){
    $sem_browse_obj->target_url="details.php";
    $sem_browse_obj->target_id="sem_id";
} else {
    $sem_browse_obj->target_url="seminar_main.php";
    $sem_browse_obj->target_id="auswahl";
}
if (isset($_REQUEST['send_excel'])){
    $tmpfile = basename($sem_browse_obj->create_result_xls());
    if($tmpfile){
        header('Location: ' . getDownloadLink( $tmpfile, _("ErgebnisVeranstaltungssuche.xls"), 4));
        page_close();
        die;
    }
}
ob_end_flush();
?>
<table width="100%" border=0 cellpadding=0 cellspacing=0>
<tr>
    <td class="blank" valign="top">
    <table cellpadding="5" border="0" width="100%" id="main_content"><tr><td colspan="2">
        <?
        //
        if ($sem_portal["bereich"] == "mod") {
            print "<br>"._("Hier finden Sie alle verfügbaren Studienmodule.");
        } elseif ($anzahl_seminare_class > 0) {
            print $SEM_CLASS[$sem_portal["bereich"]]["description"]."<br>" ;
        } elseif ($sem_portal["bereich"] != "all") {
            print "<br>"._("In dieser Kategorie sind keine Veranstaltungen angelegt.<br>Bitte w&auml;hlen Sie einen andere Kategorie!");
        }

        echo "</td></tr><tr><td class=\"blank\" align=\"left\">";
        if ($sem_portal["bereich"] != "mod"){
                if ($sem_browse_data['cmd'] == "xts"){
                    echo LinkButton::create(_('Schnellsuche'), $PHP_SELF.'?cmd=qs&level=f', array('title' => _("Zur Schnellsuche zurückgehen")));
                } else {
                    echo LinkButton::create(_('erweiterte Suche'), $PHP_SELF.'?cmd=xts&level=f', array('title' => _("Erweitertes Suchformular aufrufen")));
                }
        }
        echo "</td>\n";
        echo "<td class=\"blank\" align=\"right\">";
        echo LinkButton::create(_('zurücksetzen'), $PHP_SELF.'?reset_all=1', array('title' => _("zurücksetzen")));
        echo "</td></tr>\n";


?>

    </table>
<?

$sem_browse_obj->do_output();

print "</td><td class=\"blank\" width=\"270\" align=\"right\" valign=\"top\">";

if ($sem_browse_obj->show_result && count($sem_browse_data['search_result'])){
    $group_by_links = "";
    for ($i = 0; $i < count($sem_browse_obj->group_by_fields); ++$i){
        if($sem_browse_data['group_by'] != $i){
            $group_by_links .= "<a href=\"$PHP_SELF?group_by=$i&keep_result_set=1\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"16\" height=\"20\" border=\"0\">";
        } else {
            $group_by_links .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\" align=\"bottom\">";
        }
        $group_by_links .= "&nbsp;" . $sem_browse_obj->group_by_fields[$i]['name'];
        if($sem_browse_data['group_by'] != $i){
            $group_by_links .= "</a>";
        }
        $group_by_links .= "<br>";
    }
    $infobox[] =    array(  "kategorie" => _("Suchergebnis gruppieren:"),
                            "eintrag" => array(array(   'icon' => "blank.gif",
                                                        "text" => $group_by_links))
                    );
    if ($sem_portal['bereich'] != 'mod') {
            $infobox[] =    array(  "kategorie" => _("Aktionen:"),
                            "eintrag" => array(array(   'icon' => "icons/16/blue/download.png",
                                                        "text" => '<a href="'.$PHP_SELF.'?send_excel=1">' . _("Download des Ergebnisses") . '</a>'))
                    );
    }
} elseif ($sem_portal['bereich'] != 'mod') {
    $toplist = $toplist_links = '';
    $sql_where_query_seminare = " WHERE 1 ";
    if (!$GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'))) $sql_where_query_seminare .= " AND seminare.visible=1  ";

    if ($sem_portal['bereich'] !="all")
        $sql_where_query_seminare .= " AND seminare.status IN ('" . join("','", $_sem_status) . "')";


    switch ($sem_portal["toplist"]) {
        case 4:
        default:
            $toplist =  getToplist(_("neueste Veranstaltungen"),"SELECT seminare.seminar_id, seminare.name, mkdate as count FROM seminare ".$sql_where_query_seminare." ORDER BY mkdate DESC LIMIT 5", "date");
        break;
        case 1:
            $toplist = getToplist(_("Teilnehmeranzahl"), "SELECT seminare.seminar_id, seminare.name, count(seminare.seminar_id) as count FROM seminare LEFT JOIN seminar_user USING(seminar_id) ".$sql_where_query_seminare." GROUP BY seminare.seminar_id ORDER BY count DESC LIMIT 5");
        break;
        case 2:
            $toplist =  getToplist(_("die meisten Materialien"),"SELECT dokumente.seminar_id, seminare.name, count(dokumente.seminar_id) as count FROM seminare INNER JOIN  dokumente USING(seminar_id) ".$sql_where_query_seminare." GROUP BY dokumente.seminar_id  ORDER BY count DESC LIMIT 5");
        break;
        case 3:
            $toplist =  getToplist(_("aktivste Veranstaltungen"),"SELECT px_topics.seminar_id, seminare.name, count(px_topics.seminar_id) as count FROM px_topics INNER JOIN seminare USING(seminar_id) ".$sql_where_query_seminare." AND px_topics.mkdate > ".(time()-1209600) . " GROUP BY px_topics.seminar_id  ORDER BY count DESC LIMIT 5");
        break;
    }

    //toplist link switcher
    if ($sem_portal["toplist"] != 4)
        $toplist_links .= "<a href=\"$PHP_SELF?choose_toplist=4\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\"> "._("neueste Veranstaltungen")."</a><br>";
    if ($sem_portal["toplist"] != 1)
        $toplist_links .= "<a href=\"$PHP_SELF?choose_toplist=1\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\"> "._("Teilnehmeranzahl")."</a><br>";
    if ($sem_portal["toplist"] != 2)
        $toplist_links .= "<a href=\"$PHP_SELF?choose_toplist=2\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\"> "._("die meisten Materialien")."</a><br>";
    if ($sem_portal["toplist"] != 3)
        $toplist_links .= "<a href=\"$PHP_SELF?choose_toplist=3\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\"> "._("aktivste Veranstaltungen")."</a><br>";
    // if ($sem_portal["bereich"] == "all")
    $infotxt = _("Sie können hier nach allen Veranstaltungen suchen, sich Informationen anzeigen lassen und Veranstaltungen abonnieren.");
    $infobox = array();
    $infobox[] =
        array  ("kategorie" => _("Information:"),
            "eintrag" => array        (
                array         (        'icon' => 'icons/16/black/search.png',
                    "text"  =>        $infotxt
                )
        )
    );

    $infobox[] = ($sem_portal['bereich'] !="all") ?
                array  ("kategorie"  => _("Information:"),
                        "eintrag" => array  (
                                    array ( 'icon' => 'icons/16/black/info.png',
                                            "text"  => sprintf (_("Gew&auml;hlte Kategorie: <b>%s</b>")."<br>"._("%s Veranstaltungen vorhanden"), $SEM_CLASS[$sem_portal["bereich"]]["name"], $anzahl_seminare_class)
                                                        . (($anzahl_seminare_class && $anzahl_seminare_class < 30) ? "<br>" . sprintf(_("Alle Veranstaltungen %sanzeigen%s"),"<a href=\"$PHP_SELF?do_show_class=1\">","</a>") : ""))
                                        )
                        ) : FALSE;
    $infobox[] = $information_entry;
    $infobox[] =
        array  ("kategorie" => _("Topliste:"),
            "eintrag" => array  (
                array    (  'icon' => "blank.gif",
                                    "text"  =>  $toplist
                )
            )
        );
    $infobox[] =
        array  ("kategorie" => _("weitere Toplisten:"),
            "eintrag" => array  (
                array    (  'icon' => "blank.gif",
                                    "text"  =>  $toplist_links
                )
            )
        );
} else {
    $infotxt = _("Sie können hier nach allen Modulen suchen, sich Informationen anzeigen lassen und Module abonnieren.");

    $infobox[] =
        array  ("kategorie" => _("Aktionen:"),
            "eintrag" => array        (
                array         (        "icon" => "icons/16/black/search.png",
                    "text"  =>        $infotxt
                )
        )
    );

    $infobox[] = ($sem_portal['bereich'] !="all") ?
                array  ("kategorie"  => _("Information:"),
                        "eintrag" => array  (
                                    array ( "icon" => "icons/16/black/info.png",
                                            "text"  => sprintf (_("Gew&auml;hlte Kategorie: <b>%s</b>")."<br>"._("%s Studienmodule vorhanden"), _("Studienmodule"), $anzahl_seminare_class)
                                                        . (($anzahl_seminare_class && $anzahl_seminare_class < 30) ? "<br>" . sprintf(_("Alle Studienmodule %sanzeigen%s"),"<a href=\"$PHP_SELF?do_show_class=mod\">","</a>") : ""))
                                        )
                        ) : FALSE;
}

print_infobox ($infobox, "infobox/board1.jpg");

?>

    </td>
</tr>
<tr>
    <td class="blank" colspan="2">&nbsp;
    </td>
</tr>
</table>
<?php
 include ('lib/include/html_end.inc.php');
 page_close();
 ?>
