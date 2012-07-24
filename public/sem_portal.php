<?
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
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

unregister_globals();
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



//Einstellungen fuer Reitersystem

//Standard herstellen

if (Request::option('view'))
    $_SESSION['sem_portal']['bereich'] = Request::option('view');

if (!$_SESSION['sem_portal']['bereich'])
    $_SESSION['sem_portal']['bereich'] = "all";

Request::set('view', $_SESSION['sem_portal']['bereich']);
Navigation::activateItem('/search/courses/'.$_SESSION['sem_portal']['bereich']);

if (Request::option('choose_toplist'))
    $_SESSION['sem_portal']['toplist'] = Request::option('choose_toplist');

if (!$_SESSION['sem_portal']['toplist'])
    $_SESSION['sem_portal']['toplist'] = 4;

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

//function to display toplists
function getToplist($rubrik, $query, $type = 'count', $parameters = array()) {
    $cache = StudipCacheFactory::getCache();
    $hash  = '/sem_portal/' . md5($query);
    
    $top_list = unserialize($cache->read($hash));
    if (!$top_list) {
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $top_list = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $cache->write($hash, serialize($top_list), 5 * 60);
    }
    
    if (empty($top_list)) {
        return '';
    }

    $result .= "<table cellpadding=\"0\" cellspacing=\"2\" border=\"0\">";
    $result .= "<tr><td colspan=\"2\"><font size=\"-1\"><b>$rubrik</b></font></td></tr>";
    $i=1;
    foreach ($top_list as $item) {
        $result .= "<tr><td width=\"1%\" valign=\"top\"><font size=\"-1\">$i.</font></td>";
        $result .= "<td width=\"99%\"><font size=\"-1\"><a href=\"details.php?sem_id=".$item['seminar_id']."&send_from_search=true&send_from_search_page=".$_SERVER['PHP_SELF']."\">";
        $result .= htmlReady(substr($item['name'], 0, 45));
        if (strlen ($item['name']) > 45) {
            $result .= "... ";
        }
        $result .= "</a>";
        if ($type == 'date' && $item['count'] > 0) {
            $count = date('d.m.Y', $item['count']);
        } else {
            $count = $item['count'];
        }
        if ($count > 0) {
            $result .= "&nbsp; (" . $count . ")";
        }
        $result .= "</font></td></tr>";
        $i++;
    }
    $result .= "</tr>";
    $result .= "</table>";

    return $result;
}

if ($_SESSION['sem_portal']['bereich'] != "all" && $_SESSION['sem_portal']['bereich'] != "mod") {
    $_sem_status = array();
    foreach ($SEM_CLASS as $key => $value){
        if ($key == $_SESSION['sem_portal']['bereich']){
            foreach($SEM_TYPE as $type_key => $type_value){
                if($type_value['class'] == $key)
                $_sem_status[] = $type_key;
            }
        }
    }

    $query = "SELECT COUNT(*) FROM seminare WHERE status IN (?)";
    if (!$GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'))) {
        $query .= " AND visible = 1";
    }
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($_sem_status));
    $anzahl_seminare_class = $statement->fetchColumn();
} else {
    $_sem_status = false;
}

if ($_SESSION['sem_portal']['bereich'] == "mod") {
    $query = "SELECT COUNT(*) FROM stm_instances WHERE complete = 1";
    $anzahl_seminare_class = DBManager::get()->query($query)->fetchColumn();
}

$init_data = array( "level" => "f",
                    "cmd"=>"qs",
                    "show_class"=>$_SESSION['sem_portal']['bereich'],
                    "group_by"=>0,
                    "default_sem"=> ( ($default_sem = SemesterData::GetSemesterIndexById($_SESSION['_default_sem'])) !== false ? $default_sem : "all"),
                    "sem_status"=>$_sem_status);

if (Request::option('reset_all')) $_SESSION['sem_browse_data'] = null;
if (get_config('STM_ENABLE') &&  $_SESSION['sem_portal']['bereich'] == "mod"){
    $sem_browse_obj = new StmBrowse($init_data);
} else {
    $sem_browse_obj = new SemBrowse($init_data);
    $sem_browse_data['show_class'] = $_SESSION['sem_portal']['bereich'];
}
if (!$perm->have_perm("root")){
    $sem_browse_obj->target_url="details.php";
    $sem_browse_obj->target_id="sem_id";
} else {
    $sem_browse_obj->target_url="seminar_main.php";
    $sem_browse_obj->target_id="auswahl";
}
if (Request::int('send_excel')){
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
        if ($_SESSION['sem_portal']["bereich"] == "mod") {
            print "<br>"._("Hier finden Sie alle verfügbaren Studienmodule.");
        } elseif ($anzahl_seminare_class > 0) {
            print $SEM_CLASS[$_SESSION['sem_portal']["bereich"]]["description"]."<br>" ;
        } elseif ($_SESSION['sem_portal']["bereich"] != "all") {
            print "<br>"._("In dieser Kategorie sind keine Veranstaltungen angelegt.<br>Bitte w&auml;hlen Sie einen andere Kategorie!");
        }

        echo "</td></tr><tr><td class=\"blank\" align=\"left\">";
        if ($_SESSION['sem_portal']["bereich"] != "mod"){
                if ($_SESSION['sem_browse_data']['cmd'] == "xts"){
                    echo LinkButton::create(_('Schnellsuche'), URLHelper::getLink('?cmd=qs&level=f'), array('title' => _("Zur Schnellsuche zurückgehen")));
                } else {
                    echo LinkButton::create(_('Erweiterte Suche'), URLHelper::getLink('?cmd=xts&level=f'), array('title' => _("Erweitertes Suchformular aufrufen")));
                }
        }
        echo "</td>\n";
        echo "<td class=\"blank\" align=\"right\">";
        echo LinkButton::create(_('Zurücksetzen'), URLHelper::getLink('?reset_all=1'), array('title' => _("zurücksetzen")));
        echo "</td></tr>\n";


?>

    </table>
<?

$sem_browse_obj->do_output();

print "</td><td class=\"blank\" width=\"270\" align=\"right\" valign=\"top\">";

if ($sem_browse_obj->show_result && count($_SESSION['sem_browse_data']['search_result'])){
    $group_by_links = "";
    for ($i = 0; $i < count($sem_browse_obj->group_by_fields); ++$i){
        if($_SESSION['sem_browse_data']['group_by'] != $i){
            $group_by_links .= "<a href=\"".URLHelper::getLink('?group_by=$i&keep_result_set=1')."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"16\" height=\"20\" border=\"0\">";
        } else {
            $group_by_links .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\" align=\"bottom\">";
        }
        $group_by_links .= "&nbsp;" . $sem_browse_obj->group_by_fields[$i]['name'];
        if($_SESSION['sem_browse_data']['group_by'] != $i){
            $group_by_links .= "</a>";
        }
        $group_by_links .= "<br>";
    }
    $infobox[] =    array(  "kategorie" => _("Suchergebnis gruppieren:"),
                            "eintrag" => array(array(   'icon' => "blank.gif",
                                                        "text" => $group_by_links))
                    );
    if ($_SESSION['sem_portal']['bereich'] != 'mod') {
            $infobox[] =    array(  "kategorie" => _("Aktionen:"),
                            "eintrag" => array(array(   'icon' => "icons/16/blue/download.png",
                                                        "text" => '<a href="'.URLHelper::getLink('?send_excel=1').'">' . _("Download des Ergebnisses") . '</a>'))
                    );
    }
} elseif ($_SESSION['sem_portal']['bereich'] != 'mod') {
    $toplist = $toplist_links = '';

    $sql_where_query_seminare = " WHERE 1 ";
    $parameters = array();
    
    if (!$GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'))) {
        $sql_where_query_seminare .= " AND seminare.visible = 1 ";
    }

    if ($_SESSION['sem_portal']['bereich'] != 'all') {
        $sql_where_query_seminare .= " AND seminare.status IN (?) ";
        $parameters[] = $_sem_status;
    }


    switch ($_SESSION['sem_portal']["toplist"]) {
        case 4:
        default:
            $query = "SELECT seminar_id, name, mkdate AS count
                      FROM seminare 
                      {$sql_where_query_seminare}
                      ORDER BY mkdate DESC
                      LIMIT 5";
            $toplist =  getToplist(_('neueste Veranstaltungen'), $query, 'date', $parameters);
        break;
        case 1:
            $query = "SELECT seminare.seminar_id, seminare.name, COUNT(seminare.seminar_id) AS count
                      FROM seminare
                      LEFT JOIN seminar_user USING (seminar_id)
                      {$sql_where_query_seminare}
                      GROUP BY seminare.seminar_id
                      ORDER BY count DESC
                      LIMIT 5";
            $toplist = getToplist(_('Teilnehmeranzahl'), $query, 'count', $parameters);
        break;
        case 2:
            $query = "SELECT dokumente.seminar_id, seminare.name, COUNT(dokumente.seminar_id) AS count
                      FROM seminare
                      INNER JOIN dokumente USING (seminar_id) 
                      {$sql_where_query_seminare}
                      GROUP BY dokumente.seminar_id
                      ORDER BY count DESC
                      LIMIT 5";
            $toplist =  getToplist(_('die meisten Materialien'), $query, 'count', $parameters);
        break;
        case 3:
            $query = "SELECT px_topics.seminar_id, seminare.name, COUNT(px_topics.seminar_id) AS count
                      FROM px_topics
                      INNER JOIN seminare USING (seminar_id)
                      {$sql_where_query_seminare} AND px_topics.mkdate > UNIX_TIMESTAMP(NOW() - INTERVAL 14 DAY)
                      GROUP BY px_topics.seminar_id
                      ORDER BY count DESC
                      LIMIT 5";
            $toplist =  getToplist(_("aktivste Veranstaltungen"), $query, 'count', $parameters);
        break;
    }

    //toplist link switcher
    if ($_SESSION['sem_portal']["toplist"] != 4)
        $toplist_links .= "<a href=\"".URLHelper::getLink('?choose_toplist=4')."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\"> "._("neueste Veranstaltungen")."</a><br>";
    if ($_SESSION['sem_portal']["toplist"] != 1)
        $toplist_links .= "<a href=\"".URLHelper::getLink('?choose_toplist=1')."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\"> "._("Teilnehmeranzahl")."</a><br>";
    if ($_SESSION['sem_portal']["toplist"] != 2)
        $toplist_links .= "<a href=\"".URLHelper::getLink('?choose_toplist=2')."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\"> "._("die meisten Materialien")."</a><br>";
    if ($_SESSION['sem_portal']["toplist"] != 3)
        $toplist_links .= "<a href=\"".URLHelper::getLink('?choose_toplist=3')."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/red/arr_1right.png\" border=\"0\"> "._("aktivste Veranstaltungen")."</a><br>";
    // if ($_SESSION['sem_portal']["bereich"] == "all")
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

    $infobox[] = ($_SESSION['sem_portal']['bereich'] !="all") ?
                array  ("kategorie"  => _("Information:"),
                        "eintrag" => array  (
                                    array ( 'icon' => 'icons/16/black/info.png',
                                            "text"  => sprintf (_("Gew&auml;hlte Kategorie: <b>%s</b>")."<br>"._("%s Veranstaltungen vorhanden"), $SEM_CLASS[$_SESSION['sem_portal']["bereich"]]["name"], $anzahl_seminare_class)
                                                        . (($anzahl_seminare_class && $anzahl_seminare_class < 30) ? "<br>" . sprintf(_("Alle Veranstaltungen %sanzeigen%s"),"<a href=\"".URLHelper::getLink('?do_show_class=1')."\">","</a>") : ""))
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

    $infobox[] = ($_SESSION['sem_portal']['bereich'] !="all") ?
                array  ("kategorie"  => _("Information:"),
                        "eintrag" => array  (
                                    array ( "icon" => "icons/16/black/info.png",
                                            "text"  => sprintf (_("Gew&auml;hlte Kategorie: <b>%s</b>")."<br>"._("%s Studienmodule vorhanden"), _("Studienmodule"), $anzahl_seminare_class)
                                                        . (($anzahl_seminare_class && $anzahl_seminare_class < 30) ? "<br>" . sprintf(_("Alle Studienmodule %sanzeigen%s"),"<a href=\"".URLHelper::getLink('?do_show_class=mod')."\">","</a>") : ""))
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
