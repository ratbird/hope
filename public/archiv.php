<?
# Lifter001: TEST
# Lifter003: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter010: TODO
/*
archiv.php - Suchmaske fuer das Archiv
Copyright (C) 2001 Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de>

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
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

include('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
$druck = Request::option('druck');
$wiki_dump_id = Request::option('wiki_dump_id');
$forum_dump_id = Request::option('forum_dump_id');
$dump_id = Request::option('dump_id');
if ($druck) {
    PageLayout::removeStylesheet('style.css');
    PageLayout::addStylesheet('print.css');
}

PageLayout::setHelpKeyword("Basis.Archiv");
PageLayout::setTitle(_("Archiv"));
Navigation::activateItem('/search/archive');

// Start of Output
include('lib/include/html_head.inc.php'); // Output of html head

require_once('lib/msg.inc.php');
require_once('config.inc.php');
require_once('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once('lib/datei.inc.php');
require_once('lib/log_events.inc.php');

$cssSw=new cssClassSwitcher;
$delete_id = Request::option('delete_id');
$open = Request::option('open');
$delete_user = Request::option('delete_user');
$add_user = Request::option('add_user');
//Daten des Suchformulars uebernehmen oder loeschen
if (Request::option('suche')) {
    $_SESSION['archiv_data'] = array();
    $_SESSION['archiv_data']["all"]= Request::get('all');
    $_SESSION['archiv_data']["name"]=Request::get('name');
    $_SESSION['archiv_data']["sem"]=Request::get('sem');
    $_SESSION['archiv_data']["inst"]=Request::option('inst');
    $_SESSION['archiv_data']["fak"]=Request::get('fak');
    $_SESSION['archiv_data']["desc"]=Request::get('desc');
    $_SESSION['archiv_data']["doz"]=Request::get('doz');
    $_SESSION['archiv_data']["pers"]=Request::option('pers');
    $_SESSION['archiv_data']["perform_search"]=TRUE;
} elseif (!$open && !$delete_id && !Request::option('show_grants') && !Request::option('hide_grants') && !$delete_user && !Request::submitted('add_user') && !Request::submitted('new_search') && !Request::option('close') && !$dump_id && !Request::option('sortby') && !Request::option('back')) {
    $_SESSION['archiv_data']["perform_search"]=FALSE;
}


//Anzeige der Zugriffsberechtigten Personen ein/ausschalten
if (Request::option('show_grants')) {
    $_SESSION['archiv_data']["edit_grants"]=TRUE;
}
if (Request::option('hide_grants')) {
    $_SESSION['archiv_data']["edit_grants"]=FALSE;
}

if ($open) {
    $_SESSION['archiv_data']["open"]=$open;
}
if ((Request::option('close')) || (Request::option('suche'))){
    $_SESSION['archiv_data']["open"]=FALSE;
}

$_SESSION['archiv_data']['sortby'] = Request::option('sortby', 'Name');

$u_id = $user->id;
unset($message, $details);

//Loeschen aus dem Archiv
if (($delete_id) && Request::submitted('delete_really')){
    if (archiv_check_perm($delete_id) == "admin") {
        // Load relevant data from archive
        $query = "SELECT name, archiv_file_id, semester FROM archiv WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($delete_id));
        $seminar = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Delete from archive
        $query = "DELETE FROM archiv WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($delete_id));        
        if ($statement->rowCount()) {
            $message = sprintf(_('Die Veranstaltung "%s" wurde aus dem Archiv gelöscht'), htmlReady($seminar['name']));
            log_event("SEM_DELETE_FROM_ARCHIVE",$delete_id,NULL,$seminar['name']." (".$seminar['semester'].")"); // ...logging...
        }
        
        if ($seminar['archiv_file_id']) {
            if (unlink ($ARCHIV_PATH."/".$seminar['archiv_file_id'])){
                $details[] = _("Das Zip-Archiv der Veranstaltung wurde aus dem Archiv gelöscht.");
            } else {
                $details[] = _("Das Zip-Archiv der Veranstaltung konnte nicht aus dem Archiv gelöscht werden.");
            }
        }
        
        // Delete from archiv_user
        $query = "DELETE FROM archiv_user WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($delete_id));
        if ($statement->rowCount()) {
            $details[] = sprintf(_("Es wurden %s Zugriffsberechtigungen entfernt."), $statement->rowCount());
        }
    } else {
        $msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
    }
    unset($delete_id);
}

//Sicherheitsabfrage
if ($delete_id) {
    $query = "SELECT name FROM archiv WHERE seminar_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($delete_id));
    $name = $statement->fetchColumn();
    echo createQuestion(sprintf(_('Wollen Sie die Veranstaltung "%s" wirklich löschen? Sämtliche Daten und die mit der Veranstaltung archivierte Dateisammlung werden unwiderruflich gelöscht!'), $name),
            array('delete_really' => 'true', 'delete_id' => $delete_id), array('back' => 'true'));
}

//Loeschen von Archiv-Usern
if ($delete_user) {
    if (archiv_check_perm($d_sem_id) == "admin" || archiv_check_perm($d_sem_id) == "dozent") {
        $query = "DELETE FROM archiv_user WHERE seminar_id  = ? AND user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($d_sem_id, $delete_user));
        if ($statement->rowCount()) {
            $msg="msg§" . _("Zugriffsberechtigung entfernt") . "§";
        }
    } else {
        $msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
    }
}

//Eintragen von Archiv_Usern
if (Request::submitted('do_add_user')) {
    if (archiv_check_perm($a_sem_id) == "admin" || archiv_check_perm($a_sem_id) == "dozent") {
        $query = "INSERT IGNORE INTO archiv_user (seminar_id, user_id, status) VALUES (?, ?, 'autor')";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($a_sem_id, $add_user));
        if ($statement->rowCount()) {
            $msg="msg§" . _("Zugriffsberechtigung erteilt") . "§";
        }
    } else {
        $msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
    }
    $add_user=FALSE;
}


// wollen wir den dump?

if (!empty($dump_id)) {
    if (archiv_check_perm($dump_id)){
        $query = "SELECT dump FROM archiv WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($dump_id));
        $dump = $statement->fetchColumn();
        if ($dump) {
            if (!isset($druck)) {
                echo '<div align=center> <a href="'. URLHelper::getLink("?dump_id=".$dump_id."&druck=1") .'" target="_self"><b>' . _("Druckversion") . "</b></a><br><br></div>";
            }
            echo $dump;
        }
    } else {
        $msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
    }
}

// oder vielleicht den Forendump?

elseif (!empty($forum_dump_id)) {
    if (archiv_check_perm($forum_dump_id)){
        $query = "SELECT forumdump FROM archiv WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($forum_dump_id));
        $dump = $statement->fetchColumn();
        if ($dump) {
            if (!isset($druck)) {
                echo '<div align=center> <a href="'. URLHelper::getLink("?forum_dump_id=".$forum_dump_id."&druck=1") .'" target=_self><b>' . _("Druckversion") . "</b></a><br><br></div>";
            }
            echo $dump;
        }
    } else {
        $msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
    }
}

// oder vielleicht den Wikidump?

elseif (!empty($wiki_dump_id)) {
    if (archiv_check_perm($wiki_dump_id)){
        $query = "SELECT wikidump FROM archiv WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($wiki_dump_id));
        $dump = $statement->fetchColumn();
        if ($dump) {
            if (!isset($druck)) {
                echo '<div align=center> <a href="'. URLHelper::getLink("?wiki_dump_id=".$wiki_dump_id."&druck=1") .'" target=_self><b>' . _("Druckversion") . "</b></a><br><br></div>";
            }
            echo "<table class=blank width=95% align=center><tr><td>";
            echo stripslashes($dump);
            echo "</td></tr></table>";
        }
    } else {
        $msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
    }
}

else {

PageLayout::setHelpKeyword("Basis.SuchenArchiv");

// dann eben den Rest...

include('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
    <? if ($msg) { parse_msg($msg); } ?>
    <tr>
        <td class="blank" >
                <? if (isset($message)) : ?>
                    <?= MessageBox::success($message, $details) ?>
                <? endif ?>
                <form  name="search" method="post" action="<?= URLHelper::getLink() ?>" >
                    <?= CSRFProtection::tokenTag() ?>
                    <table border=0 cellspacing=0 cellpadding=2>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" colspan=2>
                            <b><font size=-1><?=_("Bitte geben Sie hier Ihre Suchkriterien ein:")?></font></b><br>
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%">
                                <font size=-1><?=_("Name der Veranstaltung:")?></font>
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>" width="90%">
                                <input  type="text"  size=30 maxlength=255 name="name" value="<? echo htmlReady(stripslashes($_SESSION['archiv_data']["name"])) ?>">
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%">
                                <font size=-1><?=_("DozentIn der Veranstaltung:")?></font>
                            </td>
                            <td  class="<? echo $cssSw->getClass() ?>" width="90%">
                                <input  type="text"  size=30 maxlength=255 name="doz" value="<? echo htmlReady(stripslashes($_SESSION['archiv_data']["doz"])) ?>">
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>"  width="10%">
                                <font size=-1><?=_("Semester")?> </font>
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>"  width="90%">
                                <font size=-1>
                                <select name="sem">
                                <option selected value=0><?=_("alle")?></option>
                            <?
                                $query = "SELECT DISTINCT semester FROM archiv WHERE semester != '' ORDER BY start_time";
                                $statement = DBManager::get()->query($query);
                                while ($semester = $statement->fetchColumn()) {
                                    printf('<option%s>%s</option>',
                                           $semester == $_SESSION['archiv_data']['sem'] ? ' selected' : '',
                                           htmlReady($semester));
                                }
                            ?>
                                </select>
                                </font>
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%">
                                <font size=-1><?=_("Heimat-Einrichtung")?> </font>
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>"  width="90%">
                                <font size=-1>
                                <select name="inst">
                                <option selected value=0><?=_("alle")?></option>
                            <?
                                $query = "SELECT DISTINCT heimat_inst_id, Institute.Name "
                                       . "FROM archiv "
                                       . "LEFT JOIN Institute ON (Institut_id = heimat_inst_id) "
                                       . "WHERE Institute.Name NOT IN ('', '- - -') "
                                       . "ORDER BY Name";
                                $statement = DBManager::get()->query($query);
                                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                                    printf('<option value="%s"%s>%s</option>',
                                           htmlReady($row['heimat_inst_id']),
                                           $row['heimat_inst_id'] == $_SESSION['archiv_data']['inst'] ? ' selected' : '',
                                           htmlReady(my_substr($row['Name'], 0, 40)));
                                }
                            ?>
                                </select>
                                </font>
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%">
                                <font size=-1><?=_("Fakultät")?> </font>
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>"  width="90%">
                                <font size=-1>
                                <select name="fak">
                                <option selected value=0><?=_("alle")?></option>
                            <?
                                $query = "SELECT DISTINCT fakultaet FROM archiv WHERE fakultaet != '' ORDER BY fakultaet";
                                $statement = DBManager::get()->query($query);
                                while ($fakultaet = $statement->fetchColumn()) {
                                    printf('<option value="%s"%s>%s</option>',
                                           htmlReady($fakultaet),
                                           $fakultaet == stripslashes($_SESSION['archiv_data']['fak']) ? ' selected' : '',
                                           htmlReady(my_substr($fakultaet, 0, 40)));
                                }
                            ?>
                                </select>
                                </font>
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%">
                                <font size=-1><?=_("Beschreibung:")?></font>
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>" width="90%">
                                <input  type="text"  size=30 maxlength=255 name="desc" value="<?echo htmlReady(stripslashes($_SESSION['archiv_data']["desc"])) ?>">
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%">
                                <font size=-1><?=_("Suche &uuml;ber <b>alle</b> Felder:")?></font>
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>" width="90%">
                                <input  type="text"  size=30 maxlength=255 name="all" value="<? echo htmlReady(stripslashes($_SESSION['archiv_data']["all"])) ?>">
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%">
                                &nbsp;
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>" width="90%">
                                <input  type="checkbox" name="pers" <? if ($_SESSION['archiv_data']["pers"]) echo "checked" ?>>
                                <font size=-1><?=_("Nur Veranstaltungen anzeigen, an denen ich teilgenommen habe")?></font>
                            </td>
                        </tr>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%">
                                &nbsp;
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>" width="90%">
                                <center>
                                <?= Button::create(_("Suchen")) ?>
                                </center>
                            </td>
                        </tr>
                    </table>
                    <br>
                    <input type="hidden" name="suche" value="yes">
                </form>
        </td>
        <td class="blank" align="right" valign="top" width="270">
            <?= print_infobox(array(), 'infobox/archiv.jpg') ?>
        </td>
    </tr>

<?

// wollen wir was Suchen?

if ($_SESSION['archiv_data']["perform_search"]) {
    
    //searchstring to short?
    if ((((strlen($_SESSION['archiv_data']["all"]) < 4) && ($_SESSION['archiv_data']["all"]))
        || ((strlen($_SESSION['archiv_data']["name"]) < 4) && ($_SESSION['archiv_data']["name"]))
        || ((strlen($_SESSION['archiv_data']["desc"]) < 4) && ($_SESSION['archiv_data']["desc"]))
        || ((strlen($_SESSION['archiv_data']["doz"]) < 4) && ($_SESSION['archiv_data']["doz"])))
        && (!$_SESSION['archiv_data']["pers"]) && (!$_SESSION['archiv_data']["inst"]) && (!$_SESSION['archiv_data']["fak"]))
        $string_too_short = TRUE;
    if ((!$_SESSION['archiv_data']["all"]) && (!$_SESSION['archiv_data']["name"]) && (!$_SESSION['archiv_data']["desc"]) && (!$_SESSION['archiv_data']["doz"]) && (!$_SESSION['archiv_data']["pers"]) && (!$_SESSION['archiv_data']["inst"]) && (!$_SESSION['archiv_data']["fak"]))
        $string_too_short = TRUE;

    $parameters = array();
    if ($_SESSION['archiv_data']['pers']) {
        $query = "SELECT seminar_id, name, untertitel, beschreibung, 
                         start_time, semester, studienbereiche, heimat_inst_id,
                         institute, dozenten, fakultaet, archiv_file_id,
                         forumdump, wikidump
                  FROM archiv
                  LEFT JOIN archiv_user USING (seminar_id)
                  WHERE user_id = :user_id";
        $parameters['user_id'] = $user->id;
    } else {
        $query = "SELECT seminar_id, name, untertitel, beschreibung,
                         start_time, semester, studienbereiche, heimat_inst_id,
                         institute, dozenten, fakultaet, archiv_file_id,
                         forumdump, wikidump
                  FROM archiv
                  WHERE 1";
    }
    if ($_SESSION['archiv_data']['all']) {
        $query .= " AND (";
        $query .= "name LIKE CONCAT('%', :needle, '%')";
        $query .= " OR untertitel LIKE CONCAT('%', :needle, '%')";
        $query .= " OR beschreibung LIKE CONCAT('%', :needle, '%')";
        $query .= " OR start_time LIKE CONCAT('%', :needle, '%')";
        $query .= " OR semester LIKE CONCAT('%', :needle, '%')";
        $query .= " OR studienbereiche LIKE CONCAT('%', :needle, '%')";
        $query .= " OR institute LIKE CONCAT('%', :needle, '%')";
        $query .= " OR dozenten LIKE CONCAT('%', :needle, '%')";
        $query .= " OR fakultaet LIKE CONCAT('%', :needle, '%')";
        $query .= ")";
        
        $parameters['needle'] = trim($_SESSION['archiv_data']['all']);
    } else {
        if ($_SESSION['archiv_data']['name']) {
            $query .= " AND name LIKE CONCAT('%', :needle_name, '%')";
            $parameters['needle_name'] = trim($_SESSION['archiv_data']['name']);
        }
        if ($_SESSION['archiv_data']['desc']) {
            $query .= " AND beschreibung LIKE CONCAT('%', :needle_desc, '%')";
            $parameters['needle_desc'] = trim($_SESSION['archiv_data']['desc']);
        }
        if ($_SESSION['archiv_data']['sem']) {
            $query .= " AND semester LIKE CONCAT('%', :needle_sem, '%')";
            $parameters['needle_sem'] = trim($_SESSION['archiv_data']['sem']);
        }
        if ($_SESSION['archiv_data']['inst']) {
            $query .= " AND heimat_inst_id LIKE CONCAT('%', :needle_inst, '%')";
            $parameters['needle_inst'] = trim($_SESSION['archiv_data']['inst']);
        }
        if ($_SESSION['archiv_data']['doz']) {
            $query .= " AND dozenten LIKE CONCAT('%', :needle_doz, '%')";
            $parameters['needle_doz'] = trim($_SESSION['archiv_data']['doz']);
        }
        if ($_SESSION['archiv_data']['fak']) {
            $query .= " AND fakultaet LIKE CONCAT('%', :needle_fak, '%')";
            $parameters['needle_fak'] = trim($_SESSION['archiv_data']['fak']);
        }
    }
    $query .= " ORDER BY " . $_SESSION['archiv_data']['sortby'];


    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    $hits = count($results);
    if ($hits > 0 && (!$string_too_short)) { ?>
    <tr>
        <td class="blank" colspan="2">
        <?

        echo "<p class=\"info\"><b>";
        printf(_("Es wurden %s Veranstaltungen gefunden."), $hits);
        echo "</b></p>";


        echo "<br><br><table class=\"blank\"  width=99% align=center cellspacing=0 border=0>\n";
    echo "<tr height=28><td  width=\"1%\" class=\"steel\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=1 height=20>&nbsp; </td>\n";
        echo "<td  width=\"29%\" class=\"steel\" align=center valign=bottom><b><a href=\"". URLHelper::getLink("?sortby=Name") ."\">" . _("Name") . "</a></b></td>\n";
        echo "<td  width=\"20%\" class=\"steel\" align=center valign=bottom><b><a href=\"". URLHelper::getLink("?sortby=dozenten") ."\">" . _("DozentIn") . "</a></b></td>\n";
        echo "<td  width=\"20%\" class=\"steel\" align=center valign=bottom><b><a href=\"". URLHelper::getLink("?sortby=institute") ."\">" . _("Einrichtungen") . "</a></b></td>\n";
        echo "<td  width=\"20%\" class=\"steel\" align=center valign=bottom><b><a href=\"". URLHelper::getLink("?sortby=semester") ."\">" . _("Semester") . "</a></b></td>\n";
        echo "<td  width=\"10%\" class=\"steel\" colspan=3 align=center valign=bottom><b>" . _("Aktion") . "</b></td></tr>\n";

        $c=0;
        foreach ($results as $result) {
            $file_name=_("Dateisammlung") . " ".substr($result['name'], 0, 200).".zip";
            $view = 0;
            if ($_SESSION['archiv_data']['open']) {
                if ($_SESSION['archiv_data']['open'] == $result['seminar_id']) {
                    $class = 'steelgraulight';
                } else {
                    $class = 'steel1';
                }
            } else {
                $class = ($c % 2) ? 'steelgraulight' : 'steel1';
                $c++;
            }

            echo "<tr><td class=\"$class\" width=\"1%\" nowrap>&nbsp;";

            // schon aufgeklappt?
            if ($_SESSION['archiv_data']["open"]==$result['seminar_id']) {
                echo "<a name=\"anker\"></a><a href=\"". URLHelper::getLink("?close=yes") ."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1down.png\" " . tooltip(_("Zuklappen")) . " border=\"0\" valign=\"top\"></a></td>";
                echo "<td class=\"$class\" width=\"29%\"><font size=\"-1\"><b><a href=\"". URLHelper::getLink("?close=yes") ."\">".htmlReady($result['name'])."</a></b></font></td>";
            } else {
          echo "<a href=\"". URLHelper::getLink("?open=" . $result['seminar_id']) . "#anker\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/arr_1right.png\" " . tooltip(_("Aufklappen")) . " border=\"0\" valign=\"top\"></a></td>";
                echo "<td class=\"$class\" width=\"29%\"><font size=\"-1\"><a href=\"". URLHelper::getLink("?open=" . $result['seminar_id']) . "#anker\">".htmlReady($result['name'])."</a></font></td>";
            }
        echo "<td align=center class=\"$class\">&nbsp;<font size=-1>".htmlReady($result['dozenten'])."</font></td>";
            echo "<td align=center class=\"$class\">&nbsp;<font size=-1>".htmlReady($result['institute'])."</font></td>";
            echo "<td align=center class=\"$class\">&nbsp;<font size=-1>".htmlReady($result['semester'])."</font></td>";

            if (archiv_check_perm($result['seminar_id']))
                $view = 1;
            if ($view == 1) {
                echo "<td class=\"$class\" width=\"3%\">&nbsp;<a href=\"". URLHelper::getLink("?dump_id=".$result['seminar_id']) ."\" target=_blank>" .  Assets::img('icons/16/blue/info.png', array('class' => 'text-top', 'title' =>_('Komplettansicht'))) . "</a></td>";
                echo "<td class=\"$class\" width=\"3%\">&nbsp;";
                if (!$result['archiv_file_id']=='') {
                    echo '<a href="' . URLHelper::getLink(GetDownloadLink($result['archiv_file_id'], $file_name, 1)) .'"> ' .  Assets::img('icons/16/blue/download.png', array('class' => 'text-top', 'title' =>_('Dateisammlung'))) . '</a>';
                }
                echo "</td><td class=\"$class\" width=\"3%\">&nbsp;";
                if (archiv_check_perm($result['seminar_id']) == "admin")
                    echo "<a href=\"". URLHelper::getLink("?delete_id=".$result['seminar_id']) ."\">&nbsp;<img border=0 src=\"". Assets::image_path('icons/16/blue/trash.png') ."\" " . tooltip(_("Diese Veranstaltung aus dem Archiv entfernen")) . "></a>";
                echo "</td>";
            } else
                echo "<td class=\"$class\" width=\"9%\" colspan=\"3\">&nbsp;</td>";

            if ($_SESSION['archiv_data']["open"] == $result['seminar_id']) {
                echo "</tr><tr><td class=\"steelgraulight\" colspan=8><blockquote>";
                if (!$result['untertitel']=='')
                    echo "<li><font size=\"-1\"><b>" . _("Untertitel:") . " </b>".htmlReady($result['untertitel'])."</font></li>";
                if (!$result['beschreibung']=='')
                    echo "<li><font size=\"-1\"><b>" . _("Beschreibung:") . " </b>".htmlReady($result['beschreibung'])."</font></li>";
                if (!$result['fakultaet']=='')
                    echo "<li><font size=\"-1\"><b>" . _("Fakultät:") . " </b>".htmlReady($result['fakultaet'])."</font></li>";
                if (!$result['studienbereiche']=='')
                    echo "<li><font size=-1><b>" . _("Bereich:") . " </b>".htmlReady($result['studienbereiche'])."</font></li>";

            // doppelt haelt besser: noch mal die Extras

                if ($view == 1) {
                    echo "<br><br><li><a href=\"". URLHelper::getLink("?dump_id=".$result['seminar_id']) ."\" target=_blank><font size=\"-1\">" . _("&Uuml;bersicht der Veranstaltungsinhalte") . "</font></a></li>";
                    if (!$result['forumdump']=='')
                        echo "<li><font size=\"-1\"><a href=\"". URLHelper::getLink("?forum_dump_id=".$result['seminar_id']) ."\" target=_blank>" . _("Beitr&auml;ge des Forums") . "</a></font></li>";
                    if (!$result['wikidump']=='')
                        echo "<li><font size=\"-1\"><a href=\"". URLHelper::getLink("?wiki_dump_id=".$result['seminar_id']) ."\" target=_blank>" . _("Wikiseiten") . "</a></font></li>";
                    if (!$result['archiv_file_id']=='') {
                        echo '<li><font size="-1"><a href="' . URLHelper::getLink(GetDownloadLink($result['archiv_file_id'], $file_name, 1)) .'">' . _("Download der Dateisammlung") . '</a></font></li>';
                    }
                    if (archiv_check_perm($result['seminar_id']) == "admin")
                        echo "<li><a href=\"". URLHelper::getLink("?delete_id=".$result['seminar_id']) ."\"><font size=\"-1\">" . _("Diese Veranstaltung unwiderruflich aus dem Archiv entfernen") . "</font></a></li>";
                    if (archiv_check_perm($result['seminar_id']) == "admin") {
                        if (!$_SESSION['archiv_data']["edit_grants"])
                            echo "<li><font size=\"-1\"><a href=\"". URLHelper::getLink("?show_grants=yes") ."#anker\">" . _("Zugriffsberechtigungen einblenden") . "</a></font></li>";
                        else
                            echo "<li><font size=\"-1\"><a href=\"". URLHelper::getLink("?hide_grants=yes") ."#anker\">" . _("Zugriffsberechtigungen ausblenden") . "</a></font></li>";
                    }
                } else
                    echo "<br><br><li><font size=\"-1\">" . _("Die Veranstaltungsinhalte, Beitr&auml;ge im Forum und das Dateiarchiv sind nicht zug&auml;ngig, da Sie an dieser Veranstaltung nicht teilgenommen haben.") . "</font></li>";

                if ($_SESSION['archiv_data']["edit_grants"]) {
                    echo "<br><br><hr><b><font size=\"-1\">" . _("Folgende Personen haben Zugriff auf die Daten der Veranstaltung (&Uuml;bersicht, Beitr&auml;ge und Dateiarchiv):") . "</font></b><br><br>";
                    $query = "SELECT {$_fullname_sql['full']} AS fullname, archiv_user.status, username, user_id
                              FROM archiv_user
                              LEFT JOIN auth_user_md5 USING (user_id)
                              LEFT JOIN user_info USING (user_id)
                              WHERE seminar_id = ?
                              ORDER BY Nachname";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($result['seminar_id']));

                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        echo "<font size=\"-1\">".htmlReady($row['fullname']). " (" . _("Status:") . " ". $row['status']. ")</font>";
                        if ($row['status'] != "dozent")
                            echo "<a href=\"". URLHelper::getLink("?delete_user=".$row['user_id']."&d_sem_id=".$result['seminar_id']) ,"#anker\"><font size=\"-1\">&nbsp;" . _("Zugriffsberechtigung entfernen") . "</font> <img border=0 src=\"". Assets::image_path('icons/16/blue/trash.png') ."\" " . tooltip(_("Dieser Person die Zugriffsberechtigung entziehen")) . "></a>";
                        echo "<br>";
                    }
                    if ((Request::submitted('add_user')) && (!Request::submitted('new_search'))) {
                        $query = "SELECT {$_fullname_sql['full']} AS fullname, username, user_id
                                  FROM auth_user_md5
                                  LEFT JOIN user_info USING (user_id)
                                  WHERE Vorname LIKE CONCAT('%', :needle, '%')
                                     OR Nachname LIKE CONCAT('%', :needle, '%')
                                     OR username LIKE CONCAT('%', :needle, '%')
                                  ORDER BY Nachname";
                        $statement = DBManager::get()->prepare($query);
                        $statement->bindValue(':needle', trim($search_exp));
                        $statement->execute();
                        $temp = $statement->fetchAll(PDO::FETCH_ASSOC);

                        if (count($temp)) {
                            echo "<form action=\"". URLHelper::getLink() ."#anker\">";
                            echo "<hr><b><font size=\"-1\">" . _("Person Berechtigung erteilen:") . " </font></b><br><br>";
                            echo "<b><font size=\"-1\">" . sprintf(_("Es wurden %s Personen gefunden"), count($temp)) . " </font></b><br>";
                            echo "<font size=\"-1\">" . _("Bitte w&auml;hlen Sie die Person aus der Liste aus:") . "</font>&nbsp;<br><font size=\"-1\"><select name=\"add_user\">";
                            foreach ($temp as $row) {
                                echo "<option value=\"".$row['user_id']."\">".htmlReady($row['fullname']). " (".$row['username'].") </option>";
                            }
                            echo "</select></font>";
                            echo Button::create(_('Diese Person Hinzufügen'), 'do_add_user');
                            echo Button::create(_('Neue Suche'), 'new_search');
                            echo "<input type=\"HIDDEN\"  name=\"a_sem_id\" value=\"",$result['seminar_id'], "\">";
                            echo "</form>";
                        }
                    }
                    if ((Request::submitted('add_user') && !count($temp)) || !Request::submitted('add_user') || Request::submitted('new_search')) {
                        echo "<form action=\"". URLHelper::getLink() ."#anker\">";
                        echo "<hr><b><font size=\"-1\">" . _("Person Berechtigung erteilen:") . " </font></b><br>";
                        if (Request::submitted('add_user') && !count($temp)  && !Request::submitted('new_search'))
                            echo "<br><b><font size=\"-1\">" . _("Es wurde keine Person zu dem eingegebenem Suchbegriff gefunden!") . "</font></b><br>";
                        echo "<font size=\"-1\">" . _("Bitte Namen, Vornamen oder Benutzernamen eingeben:") . "</font>&nbsp; ";
                        echo "<br><input type=\"TEXT\" size=20 maxlength=255 name=\"search_exp\">";
                        echo Button::create(_('Suche Starten'), 'add_user');
                        echo "</form>";
                    }
                }
                echo "</blockquote></td>";
            }
            echo "</tr>";
        }
        echo "</table><br><br>";
    } else {
        echo "<tr><td class=\"blank\" colspan=2>" . (($string_too_short) ? MessageBox::error(_("Der Suchbegriff ist zu kurz.")) : MessageBox::info(_("Es wurde keine Veranstaltung gefunden.")));
  }
}

?>
        </td>
    </tr>
</table>
<?
}
    include ('lib/include/html_end.inc.php');
    page_close();
 ?>
