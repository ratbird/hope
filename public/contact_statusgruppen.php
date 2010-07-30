<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
admin_statusgruppe.php - Statusgruppen-Verwaltung von Stud.IP.
Copyright (C) 2002 Ralf Stockmann <rstockm@gwdg.de>

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


require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
    $auth->login_if($auth->auth["uid"] == "nobody");


$hash_secret = "dslkjjhetbjs";
include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once ('lib/contact.inc.php');
require_once ('config.inc.php');
require_once ('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once ('lib/statusgruppe.inc.php');
require_once ('lib/user_visible.inc.php');

PageLayout::setTitle(_("Kontaktgruppen"));
Navigation::activateItem('/community/address_book/admin_groups');

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if (get_config('CALENDAR_GROUP_ENABLE')) {
    require_once('lib/calendar/lib/Calendar.class.php');
}

$cssSw = new cssClassSwitcher;                                  // Klasse für Zebra-Design
$cssSw->enableHover();

echo "\n" . $cssSw->GetHoverJSFunction() . "\n";
$cssSw->switchClass();

$range_id_statusgruppe = $range_id = $GLOBALS['user']->id;

// Beginn Funktionsteil

// Hilfsfunktionen

function MovePersonStatusgruppe ($range_id, $AktualMembers = '', $Freesearch = '')
{
    foreach ($_POST as $key => $value) {
        if (substr($key, -2) == '_x') {
            $statusgruppe_id = substr($key, 0, -2);
        }
    }

    if ($AktualMembers != '') {
        for ($i  = 0; $i < sizeof($AktualMembers); $i++) {
            InsertPersonStatusgruppe(get_userid($AktualMembers[$i]), $statusgruppe_id);
        }
    }
    if ($Freesearch != '') {
        for ($i  = 0; $i < sizeof($Freesearch); $i++) {
            if (InsertPersonStatusgruppe(get_userid($Freesearch[$i]), $statusgruppe_id)) {
                AddNewContact($Freesearch[$i], $range_id);
            }
        }
    }
}


// Funktionen zur reinen Augabe von Statusgruppendaten

function PrintAktualStatusgruppen ($range_id, $view, $edit_id = '')
{
    global $PHP_SELF, $range_id_statusgruppe, $_fullname_sql, $user, $contact;

    $db = new DB_Seminar();
    $db2 = new DB_Seminar();
    $db3 = new DB_Seminar();
    $db->query("SELECT name, statusgruppe_id FROM statusgruppen WHERE range_id = '$range_id_statusgruppe' ORDER BY position ASC");
    $AnzahlStatusgruppen = $db->num_rows();
    $lid = rand(1, 1000);
    $i = 0;
    while ($db->next_record()) {
        $statusgruppe_id = $db->f("statusgruppe_id");
        $size = $db->f("size");
        echo "<a id=\"$statusgruppe_id\">\n";
        echo "\n<table width=\"95%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
        echo "\n<tr>";
        echo "\n<td width=\"5%\">";
        echo "                  <input type=\"IMAGE\" name=\"$statusgruppe_id\" ";
        echo 'src="'.$GLOBALS['ASSETS_URL'].'images/move.gif" border="0" ';
        echo tooltip(_("Markierte Personen dieser Gruppe zuordnen"));
        echo ">&nbsp; </td>";
        if ($edit_id == $statusgruppe_id){
            $s_name = htmlReady($db->f("name"));
        } else {
            $s_name = "<a class=\"tree\" href=\"".URLHelper::getLink($PHP_SELF."?toggle_statusgruppe=$statusgruppe_id&range_id=$range_id&view=$view&foo=".md5(uniqid('foo',1))."#$statusgruppe_id").'">'
            . '<img border="0"  align="absbottom" src="'.$GLOBALS['ASSETS_URL'].'images/'
            . ($contact['group_open'][$statusgruppe_id] ? 'forumgraurunt2.gif' : 'forumgrau2.gif')
            . '">&nbsp;' . htmlReady($db->f("name")) . '</a>';
        }
        printf("              <td width=\"85%%\" class=\"%s\">&nbsp; %s </td>",
        $edit_id == $statusgruppe_id ? "topicwrite" : "printhead", $s_name);
        printf("<td class=\"%s\" width=\"5%%\" nowrap=\"nowrap\">", $edit_id == $statusgruppe_id ? "topicwrite" : "printhead");
        echo "<a href=\"".URLHelper::getLink($PHP_SELF."?cmd=edit_statusgruppe&edit_id=$statusgruppe_id&range_id=$range_id&view=$view").'">';
        printf('<img src="'.$GLOBALS['ASSETS_URL'].'images/einst.gif" border="0" %s></a></td>',
        tooltip(_("Gruppenname anpassen")));
        echo '              <td width="5%">';
        echo "<a href=\"".URLHelper::getLink($PHP_SELF."?cmd=remove_statusgruppe&statusgruppe_id=$statusgruppe_id&range_id=$range_id&view=$view").'">';
        printf('<img src="'.$GLOBALS['ASSETS_URL'].'images/trash_att.gif" width="11" height="17" border="0" %s></a></td>',
        tooltip(_("Gruppe mit Personenzuordnung entfernen")));
        echo     "\n</tr>";
        if ($contact['group_open'][$statusgruppe_id]) {
            $db2->query("SELECT statusgruppe_user.user_id, " . $_fullname_sql['full']
            . " AS fullname , username, nachname FROM statusgruppe_user LEFT JOIN auth_user_md5 "
            . "USING(user_id) LEFT JOIN user_info USING (user_id) WHERE "
            . "statusgruppe_id = '$statusgruppe_id' ORDER BY nachname");
            $k = 1;
            while ($db2->next_record()) {
                if (get_visibility_by_id($db2->f("user_id"))) {
                    $color = '#000000';
                    // user entries

                    $fullname = $db2->f('fullname');
                    $identifier = $db2->f('username');
                    $have_calendar = true;

                    // query whether the current user has the permission to access the calendar
                    // of this user
                    if ($GLOBALS['CALENDAR_GROUP_ENABLE'] && $have_calendar){
                        $query = "SELECT calpermission FROM contact WHERE owner_id = '" . $db2->f('user_id');
                        $query .= "' AND user_id = '{$user->id}' AND calpermission > 1";
                        $db3->query($query);
                        if ($db3->num_rows()) $color = '#FF0000';

                        $query = "SELECT calpermission FROM contact WHERE owner_id = '{$user->id}'";
                        $query .= " AND user_id = '" . $db2->f('user_id') . "'";
                        $db3->query($query);
                        $db3->next_record();
                    }

                    if ($k % 2) {
                        $class = 'steel1';
                    } else {
                        $class = 'steelgraulight';
                    }
                    echo "\n<tr>\n\t\t<td><font color=\"#AAAAAA\">$k</font></td>";
                    echo "<td class=\"$class\"><font size=\"2\" color=\"$color\">";
                    echo htmlReady($fullname) . "</font></td>";
                    if ($GLOBALS['CALENDAR_GROUP_ENABLE'] && $have_calendar){
                            $perm_user = $GLOBALS['perm']->get_perm($db2->f('user_id'));
                            $perm_own = !$GLOBALS['perm']->have_perm('admin');
                            if ($perm_user != 'admin'
                                && $perm_user != 'root'
                                && $perm_own) {
                            echo "<td class=\"$class\">";
                            echo "<a href=\"$PHP_SELF?cmd=switch_member_cal&group_id=$statusgruppe_id";
                            echo "&username=" . $db2->f('username') . "&view=$view&lid=$lid#$statusgruppe_id\">";
                            switch ($db3->f('calpermission')) {
                                case 2:
                                printf('<img src="'.$GLOBALS['ASSETS_URL'].'images/group_cal_visible.gif" %s></a></td>',
                                tooltip(_("Mein Kalender ist für dieses Mitglied lesbar")));
                                break;
                                case 4:
                                printf('<img src="'.$GLOBALS['ASSETS_URL'].'images/group_cal_writable.gif" %s></a></td>',
                                tooltip(_("Mein Kalender ist für dieses Mitglied schreibbar")));
                                break;
                                default:
                                printf('<img src="'.$GLOBALS['ASSETS_URL'].'images/group_cal.gif" %s></a></td>',
                                tooltip(_("Mein Kalender ist für dieses Mitglied unsichtbar")));
                                break;
                            }
                        } else {
                            echo "<td class=\"$class\">&nbsp;</td>\n";
                        }
                    } else {
                            echo "<td class=\"$class\">&nbsp;</td>\n";
                    }
                    echo "<td class=\"$class\"><a href=\"".URLHelper::getLink($PHP_SELF."?cmd=remove_person&statusgruppe_id=$statusgruppe_id&entry_id=$identifier&range_id=$range_id&view=$view#$statusgruppe_id").'">';
                    echo '<img src="'.$GLOBALS['ASSETS_URL'].'images/trash.gif" width="11" height="17" ';
                    echo tooltip(_("Person aus der Gruppe entfernen")) . "></a></td>";
                    echo "\n\t</tr>";
                    $k++;
                }
            }
        }
        while ($k <= $db->f("size")) {
            echo "\n\t<tr>\n\t\t<td><font color=\"#FF4444\">$k</font></td>";
            echo "<td class=\"blank\" colspan=\"3\">&nbsp; </td>";
            echo "\n\t</tr>";
            $k++;
        }
        $i++;
        echo "</table>";
        if ($i < $AnzahlStatusgruppen) {
            printf ("<p align=\"center\"><a href=\"".URLHelper::getLink($PHP_SELF."?cmd=swap&statusgruppe_id=%s"
            . "&range_id=%s&view=%s#$statusgruppe_id")."\"><img src=\"{$GLOBALS['ASSETS_URL']}images/move_up.gif\"  vspace=\"1\" "
            . "width=\"13\" height=\"11\" border=\"0\"  %s><img src=\"{$GLOBALS['ASSETS_URL']}images/move_down.gif\" "
            . "vspace=\"1\" width=\"13\" height=\"11\" %s></a><br>&nbsp;",
            $statusgruppe_id, $range_id, $view, tooltip(_("Gruppenreihenfolge tauschen")),
            tooltip(_("Gruppenreihenfolge tauschen")));
        }
    }
}

function PrintSearchResults ($search_exp, $range_id)
{
    global $_fullname_sql, $user;
    $db = new DB_Seminar();
    $db2 = new DB_Seminar();
    $query = "SELECT DISTINCT auth_user_md5.user_id, " . $_fullname_sql['full_rev'] ." AS fullname, username, perms ".
    "FROM auth_user_md5 LEFT JOIN user_info USING (user_id) ".
    "WHERE perms !='root' AND perms !='admin' AND perms !='user' AND ".
    "(Vorname LIKE '%$search_exp%' OR Nachname LIKE '%$search_exp%' OR username LIKE '%$search_exp%') ORDER BY Nachname ";
    $db->query($query); // results all users which are not in the seminar
    if (!$db->num_rows()) {
        echo "&nbsp; " . _("keine Treffer") . "&nbsp; ";
    } else {
        $perm_own = !$GLOBALS['perm']->have_perm('admin');
        echo "&nbsp; <select name=\"Freesearch[]\" size=\"4\" >";
        while ($db->next_record()) {
            if (get_visibility_by_id($db->f("user_id"))) {
                $have_perm = false;
                if ($GLOBALS['CALENDAR_GROUP_ENABLE']) {
                    $query = "SELECT calpermission FROM contact WHERE owner_id = '" . $db->f('user_id');
                    $query .= "' AND user_id = '{$user->id}' AND calpermission > 1";
                    $db2->query($query);
                    $have_perm = $db2->num_rows();
                }
                if ($perm_own && $have_perm) {
                    printf("<option style=\"color:#ff0000;\" value=\"%s\">%s - %s\n", $db->f("username"), htmlReady(my_substr($db->f("fullname"),0,35)." (".$db->f("username").")"), $db->f("perms"));
                } else {
                    printf("<option value=\"%s\">%s - %s\n", $db->f("username"), htmlReady(my_substr($db->f("fullname"),0,35)." (".$db->f("username").")"), $db->f("perms"));
                }
            }
        }
        echo "</select>";
    }
}

function PrintAktualContacts ($range_id)
{
    global $_fullname_sql, $user;
    $selected = GetAllSelected($range_id);
    $query = "SELECT contact.user_id, username, " . $_fullname_sql['full_rev'];
    $query .= " AS fullname, perms FROM contact LEFT JOIN auth_user_md5 USING(user_id) ";
    $query .= "LEFT JOIN user_info USING (user_id)  WHERE owner_id = '$range_id' ";
    $query .= " ORDER BY Nachname ASC";
    $db =& new DB_Seminar();
    $db2 =& new DB_Seminar();
    $db->query ($query);

    $user_entries_result = array();
    $headline_result_user = '';
    $headline_result_user_entries = '';
    $spacer = '';

    if (($db->num_rows() + sizeof($user_entries)) > 10){
        $size = 25;
    } else {
        $size = 10;
    }

    echo "<font size=\"-1\">&nbsp; " . _("Personen im Adressbuch") . "</font><br>";
    echo "&nbsp; <select size=\"$size\" name=\"AktualMembers[]\" multiple>";

    echo $headline_result_user;
    $perm_own = !$GLOBALS['perm']->have_perm('admin');
    while ($db->next_record()) {
        if (get_visibility_by_id($db->f("user_id"))) {
            $have_perm = false;
            if ($GLOBALS['CALENDAR_GROUP_ENABLE']) {
                $query = "SELECT calpermission FROM contact WHERE owner_id = '" . $db->f('user_id');
                $query .= "' AND user_id = '{$user->id}' AND calpermission > 1";
                $db2->query($query);
                $have_perm = $db2->num_rows();
            }
            if ($perm_own && $have_perm) {
                if (in_array($db->f('user_id'), $selected)) {
                    $tmpcolor = '#ff7777';
                } else {
                    $tmpcolor = '#ff0000';
                }
            } else {
                if (in_array($db->f('user_id'), $selected)) {
                    $tmpcolor = '#777777';
                } else {
                    $tmpcolor = '#000000';
                }
            }
            echo "<option style=\"color:$tmpcolor;\" value=\"" . $db->f('username');
            echo "\">" . $spacer;
            echo htmlReady(my_substr($db->f('fullname'),0,35)." (".$db->f('username').")");
            echo " - " . $db->f('perms') . "</option>\n";
        }
    }

    echo $headline_result_user_entries;

    echo "</select>";
}

// Ende Funktionen

// fehlende Werte holen

    // alles ist userbezogen:


// Abfrage der Formulare und Aktionen

    // neue Statusgruppe hinzufuegen

    if (($cmd=="add_new_statusgruppe") && ($new_statusgruppe_name != "")) {
        if (Statusgruppe::countByName($new_statusgruppe_name, $range_id) > 0) {
            $msgs[] = 'info§' . sprintf(_("Die Gruppe %s wurde hinzugefügt, es gibt jedoch bereits ein Gruppe mit demselben Namen!"), '<b>'. htmlReady($new_statusgruppe_name) .'</b>');
        } else {
            $msgs[] = 'msg§' . sprintf(_("Die Gruppe %s wurde hinzugefügt!"), '<b>'. htmlReady($new_statusgruppe_name) .'</b>');
        }

        AddNewStatusgruppe($new_statusgruppe_name, $range_id_statusgruppe, $new_statusgruppe_size);
    }

    // bestehende Statusgruppe editieren

    if (($cmd=="edit_existing_statusgruppe") && ($new_statusgruppe_name != "")) {
        EditStatusgruppe($new_statusgruppe_name, $new_statusgruppe_size, $update_id);
    }

    // zuordnen von Personen zu einer Statusgruppe
    if ($cmd=="move_person" && ($AktualMembers !="" || $Freesearch !=""))  {
        MovePersonStatusgruppe($range_id, $AktualMembers, $Freesearch);
    }

    // Entfernen von Personen aus einer Statusgruppe

    if ($cmd=="remove_person") {
        RemovePersonStatusgruppe($entry_id, $statusgruppe_id, $range_id);
    }

    // Entfernen von Statusgruppen

    if ($cmd=="verify_remove_statusgruppe") {
        $msg = sprintf(_('Möchten Sie wirklich die Kategorie **%s** löschen?'), $name);
        echo createQuestion($msg, array('cmd' => 'remove_statusgruppe', "statusgruppe_id" => $statusgruppe_id, 'range_id' => $range_id));
    }

    if ($cmd=="remove_statusgruppe") {
        DeleteStatusgruppe($statusgruppe_id);
    }

    // Aendern der Position

    if ($cmd=="swap") {
        SwapStatusgruppe($statusgruppe_id);
    }

    // Switch Group calendar access
    if ($CALENDAR_GROUP_ENABLE) {
        if ($cmd == 'switch_grp_cal')
            switch_grp_cal($group_id);

        // Switch calendar access for group members

        if ($cmd == 'switch_member_cal') {
            switch_member_cal(get_userid($_GET['username']));
        }
    }

    if(isset($_REQUEST['toggle_statusgruppe'])){
        if ($contact['group_open'][$_REQUEST['toggle_statusgruppe']]){
            unset($contact['group_open'][$_REQUEST['toggle_statusgruppe']]);
        } else {
            $contact['group_open'][$_REQUEST['toggle_statusgruppe']] = true;
        }
    }
// Ende Abfrage Formulare



// Beginn Darstellungsteil

// Anfang Edit-Bereich
?>
<table class="blank" width="100%" border="0" cellspacing="0">
<?
if (is_array($msgs)) {
    foreach ($msgs as $msg) {
        parse_msg($msg);
    }
}
?>
  <tr>
    <td align="right" width="50%" class="blank"></td>
    <td align="right" width="50%" class="blank" nowrap="nowrap">
<?
    if ($cmd!="edit_statusgruppe") { // normale Anzeige
?>
        <form action="<? echo URLHelper::getLink('?cmd=add_new_statusgruppe') ?>" method="post">
        <?
            echo '<input type="hidden" name="range_id" value="'.$range_id.'">';
                  echo"<input type=\"hidden\" name=\"view\" value=\"$view\">";
          ?>
            <font size="2"><?=_("Adressbuchgruppe anlegen:")?></font>
            <input type="text" name="new_statusgruppe_name" style="vertical-align:middle" value="<?=_("Gruppenname")?>">
            &nbsp; &nbsp; &nbsp; <b><?=_("Einf&uuml;gen")?></b>&nbsp;
            <?
            printf ("<input type=\"IMAGE\" name=\"add_new_statusgruppe\" src=\"".$GLOBALS['ASSETS_URL']."images/move_down.gif\" border=\"0\" value=\" %s \" %s>&nbsp;  &nbsp; &nbsp; ", _("neue Statusgruppe"), tooltip(_("neue Gruppe anlegen")));
            ?>
          </form>
<?
    } else { // editieren einer bestehenden Statusgruppe
?>
        <form action="<? echo URLHelper::getLink(sprintf('?cmd=edit_existing_statusgruppe#%s', $edit_id)) ?>" method="POST">
        <?
        $db = new DB_Seminar("SELECT name, size FROM statusgruppen WHERE statusgruppe_id = '$edit_id'");
        if ($db->next_record()) {
            $gruppe_name = $db->f("name");
        }
            echo"<input type=\"HIDDEN\" name=\"range_id\" value=\"$range_id\">";
              echo"<input type=\"HIDDEN\" name=\"update_id\" value=\"$edit_id\">";
              echo"<input type=\"HIDDEN\" name=\"view\" value=\"$view\">";
          ?>
            <font size="2"><?=_("neuer Gruppenname:")?> </font>
            <input type="text" name="new_statusgruppe_name" style="vertical-align:middle" value="<? echo htmlReady($gruppe_name);?>">
            &nbsp; &nbsp; &nbsp; <b><?=_("&Auml;ndern")?></b>&nbsp;
            <?
            printf ("<input type=\"IMAGE\" name=\"add_new_statusgruppe\" src=\"".$GLOBALS['ASSETS_URL']."images/move_down.gif\" value=\" %s \" %s>&nbsp;  &nbsp; &nbsp; ", _("Gruppe anpassen"), tooltip(_("Gruppe anpassen")));
            ?>
          </form>
<?
    }
?>

      <br></td>
  </tr>
</table><?
// Ende Edit-Bereich

// Anfang Personenbereich
$db = new DB_Seminar();
$db->query ("SELECT name, statusgruppe_id, size FROM statusgruppen WHERE range_id = '$range_id_statusgruppe' ORDER BY position ASC");
if ($db->num_rows()>0) {   // haben wir schon Gruppen? dann Anzeige
    ?>
<form action="<? echo URLHelper::getLink('?cmd=move_person') ?>" method="post">
<table width="100%" border="0" cellspacing="0">
    <tr>
        <td class="steel1" valign="top" width="50%">
        <br>
<?         echo"<input type=\"HIDDEN\" name=\"range_id\" value=\"$range_id\">\n";
          echo"<input type=\"HIDDEN\" name=\"view\" value=\"$view\">\n";
    if ($db->num_rows() > 0)
    {
        $nogroups = 1;
        PrintAktualContacts ($range_id);

        ?>
              <br><br>
        <?
        if ($search_exp) {
            $search_exp = str_replace("%","\%",$search_exp);
            $search_exp = str_replace("_","\_",$search_exp);
            if (strlen(trim($search_exp)) < 3) {
                echo "&nbsp; <font size=\"-1\">"._("Ihr Suchbegriff muss mindestens 3 Zeichen umfassen!");
                echo "<br><br><font size=\"-1\">&nbsp; " . _("freie Personensuche (wird in Adressbuch übernommen)") . "</font><br>";
                echo "&nbsp; <input type=\"text\" name=\"search_exp\" value=\"\">";
                printf ("<input type=\"image\" name=\"search\" src= \"".$GLOBALS['ASSETS_URL']."images/suchen.gif\" border=\"0\" value=\" %s \" %s>&nbsp;  ", _("Person suchen"), tooltip(_("Person suchen")));
            } else {
                PrintSearchResults($search_exp, $range_id);
                printf ("<input type=\"image\" name=\"search\" src= \"".$GLOBALS['ASSETS_URL']."images/rewind.gif\" border=\"0\" value=\" %s \" %s>&nbsp;  ", _("neue Suche"), tooltip(_("neue Suche")));
            }
        } else {
            echo "<font size=\"-1\">&nbsp; " . _("freie Personensuche (wird in Adressbuch &uuml;bernommen)") . "</font><br>";
            echo "&nbsp; <input type=\"text\" name=\"search_exp\" value=\"\">";
            printf ("<input type=\"image\" name=\"search\" src= \"".$GLOBALS['ASSETS_URL']."images/suchen.gif\" border=\"0\" value=\" %s \" %s>&nbsp;  ", _("Person suchen"), tooltip(_("Person suchen")));
        }
        echo "<br><br>\n";
        // admins and roots have no calendar
        if (!$GLOBALS['perm']->have_perm('admin') && $GLOBALS['CALENDAR_GROUP_ENABLE']) {
            echo "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"blank\">\n";
            echo "<tr><td>\n<font size=\"-1\">&nbsp;&nbsp;</font></td><td><font size=\"-1\">";
            printf(_("%sRot%s dargestellte Benutzernamen kennzeichnen Benutzer, auf deren Kalender Sie Zugriff haben."),
            '<span style="color:#FF0000;">', '</span>');
            echo "</td></tr></table>";
        }
    }
    echo "\n</td>\n";
    echo '<td class="blank" width="50%" align="center" valign="top">';
 // Ende Personen-Bereich

 // Anfang Gruppenuebersicht
  PrintAktualStatusgruppen($range_id, $view, $edit_id);
?>
    <br>
  </td>
 </tr>
</table>
</form>
<?
} else { // es sind noch keine Gruppen angelegt, daher Infotext
?>
<table class="blank" width="100%" border="0" cellspacing="0">
    <?

    if (get_config("EXTERNAL_HELP")) {
        $help_url = format_help_url("Basis.VeranstaltungenVerwaltenGruppen");
    } else {
        $help_url = "help/index.php?help_page=admin_statusgruppe.php";
    }
      parse_msg("info§"
        . _("Es sind noch keine Gruppen oder Funktionen angelegt worden.") . "<br>"
        . _("Um f&uuml;r diesen Bereich Gruppen oder Funktionen anzulegen, nutzen Sie bitte die obere Zeile!")
      . "<br><br>"
        . _("Wenn Sie Gruppen angelegt haben, k&ouml;nnen Sie diesen Personen zuordnen. Jeder Gruppe k&ouml;nnen beliebig viele Personen zugeordnet werden. Jede Person kann beliebig vielen Gruppen zugeordnet werden.")
      . "<br><br>"
        . sprintf(_("Lesen Sie weitere Bedienungshinweise in der %sHilfe%s nach!"),"<a href=\"".$help_url."\">", "</a>")
      . "§");
    ?>
  </table>
<?php
}
// Ende Gruppenuebersicht

    include ('lib/include/html_end.inc.php');
page_close();
?>
