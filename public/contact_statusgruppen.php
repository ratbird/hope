<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
  contact_statusgruppe.php - Statusgruppen-Verwaltung von Stud.IP.
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

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");


$hash_secret = "dslkjjhetbjs";
include ('lib/seminar_open.php'); // initialise Stud.IP-Session

$range_id = Request::option('range_id');
$cmd = Request::option('cmd');
$view = Request::option('view');
$name = Request::quoted('name');
$Freesearch = Request::quotedArray('Freesearch');
$AktualMembers = Request::quotedArray('AktualMembers');
$edit_id = Request::option('edit_id');
$statusgruppe_id = Request::option('statusgruppe_id');

require_once ('lib/contact.inc.php');
require_once ('config.inc.php');
require_once ('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once ('lib/statusgruppe.inc.php');
require_once ('lib/user_visible.inc.php');


if (get_config('CALENDAR_GROUP_ENABLE') && Request::get('nav') == 'calendar') {
    PageLayout::setTitle(_("Mein persönlicher Terminkalender - Kontaktgruppen"));
    Navigation::activateItem('/calendar/calendar/admin_groups');
    URLHelper::addLinkParam('nav', 'calendar');
    // add skip link
    SkipLinks::addIndex(Navigation::getItem('/calendar/calendar/admin_groups')->getTitle(), 'main_content', 100);
} else {
    PageLayout::setTitle(_("Kontaktgruppen"));
    Navigation::activateItem('/community/contacts/admin_groups');
    // add skip link
    SkipLinks::addIndex(Navigation::getItem('/community/contacts/admin_groups')->getTitle(), 'main_content', 100);
}

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

function MovePersonStatusgruppe($range_id, $AktualMembers = '', $Freesearch = '')
{
    foreach ($_POST as $key => $value) {
        if (substr($key, -2) == '_x') {
            $statusgruppe_id = substr($key, 0, -2);
        }
    }
    $_SESSION['contact_statusgruppen']['group_open'][$statusgruppe_id] = true;
    if ($AktualMembers != '') {
        for ($i = 0; $i < sizeof($AktualMembers); $i++) {
            InsertPersonStatusgruppe(get_userid($AktualMembers[$i]), $statusgruppe_id);
        }
    }
    if ($Freesearch != '') {
        for ($i = 0; $i < sizeof($Freesearch); $i++) {
            if (InsertPersonStatusgruppe(get_userid($Freesearch[$i]), $statusgruppe_id)) {
                AddNewContact(get_userid($Freesearch[$i]), $range_id);
            }
        }
    }
}

// Funktionen zur reinen Augabe von Statusgruppendaten

function PrintAktualStatusgruppen($range_id, $view, $edit_id = '')
{
    global $range_id_statusgruppe, $_fullname_sql, $user;

    $db = new DB_Seminar();
    $db2 = new DB_Seminar();
    $db3 = new DB_Seminar();
    $db->query("SELECT name, statusgruppe_id, calendar_group FROM statusgruppen WHERE range_id = '$range_id_statusgruppe' ORDER BY position ASC");
    $AnzahlStatusgruppen = $db->num_rows();
    $lid = rand(1, 1000);
    $i = 0;
    while ($db->next_record()) {
        $statusgruppe_id = $db->f("statusgruppe_id");
        $size = $db->f("size");
        echo "\n<table id=\"$statusgruppe_id\" width=\"95%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
        echo "\n<tr>";
        echo "\n<td width=\"5%\">";
        echo "<input type=\"IMAGE\" name=\"$statusgruppe_id\" ";
        echo 'src="' . Assets::image_path('icons/16/yellow/arr_2right.png') . '" ';
        echo tooltip(_("Markierte Personen dieser Gruppe zuordnen"));
        echo ">&nbsp; </td>";
        if ($edit_id == $statusgruppe_id) {
            $s_name = htmlReady($db->f("name"));
        } else {
            $s_name = "<a class=\"tree\" href=\"" . URLHelper::getLink("?toggle_statusgruppe=$statusgruppe_id&range_id=$range_id&view=$view&foo=" . md5(uniqid('foo', 1)) . "#$statusgruppe_id") . '">'
                    . '<img style="vertical-align:top;" src="' . Assets::image_path($_SESSION['contact_statusgruppen']['group_open'][$statusgruppe_id] ? 'icons/16/blue/arr_1down.png' : 'icons/16/blue/arr_1right.png')
                    . '">&nbsp;' . htmlReady($db->f("name")) . '</a>';
        }
        $cal_group = get_config('CALENDAR_GROUP_ENABLE') && $db->f('calendar_group');
        echo '<td width="' . ($cal_group ? '80%' : '85%') . '" class="';
        echo ($edit_id == $statusgruppe_id ? 'topicwrite' : 'topic') . '">';
        echo $s_name . '</td>';
        echo '<td class="' . ($edit_id == $statusgruppe_id ? 'topicwrite' : 'topic') . '" width="5%">';
        if ($cal_group) {
            echo '<img src="' . Assets::image_path('icons/16/white/schedule.png') . '" ' . tooltip(_('Kalendergruppe')) . '>';
            echo '</td><td class="' . ($edit_id == $statusgruppe_id ? 'topicwrite' : 'topic') . '" style="whitespace: width="5%">';
        }
        echo '<a href="' . URLHelper::getLink('', array('edit_id' => $statusgruppe_id, 'range_id' => $range_id, 'view' => $view, 'cmd' => 'edit_statusgruppe')) . '">';
        echo '<img src="' . Assets::image_path('icons/16/white/edit.png') . '" ';
        echo tooltip(_("Gruppenname oder -größe anpassen")) . '></a></td>';

        printf("<td align=\"right\" width=\"5%%\" class=\"%s\"><a href=\"%s\"><img src=\"" . Assets::image_path('icons/16/white/trash.png') . "\" %s></a></td>", ($edit_id == $statusgruppe_id ? "topicwrite" : "topic"), URLHelper::getLink("?cmd=verify_remove_statusgruppe&statusgruppe_id=" . $statusgruppe_id . "&range_id=" . $range_id . "&view=" . $view . "&name=" . $db->f("name")), tooltip(_("Gruppe mit Personenzuordnung entfernen")));
        echo "\n</tr>";
        if ($_SESSION['contact_statusgruppen']['group_open'][$statusgruppe_id]) {
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

                    // query whether the current user has the permission to access the calendar
                    // of this user
                    if (get_config('CALENDAR_GROUP_ENABLE')) {
                        $query = "SELECT calpermission FROM contact WHERE owner_id = '" . $db2->f('user_id');
                        $query .= "' AND user_id = '{$user->id}' AND calpermission > 1";
                        $db3->query($query);
                        if ($db3->num_rows())
                            $color = '#FF0000';

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
                    if (get_config('CALENDAR_GROUP_ENABLE')) {
                        if ($cal_group) {
                            echo '<td class="' . $class . '"> </td>';
                        }
                        echo '<td style="text-align: center;" class="' . $class . '">';
                        echo '<a href="';
                        switch ($db3->f('calpermission')) {
                            case Calendar::PERMISSION_READABLE:
                                echo URLHelper::getLink('#' . $statusgruppe_id, array('user_id' => $db2->f('user_id'), 'view' => $view, 'lid' => $lid, 'calperm' => Calendar::PERMISSION_WRITABLE)) . '">';
                                echo Assets::img('icons/16/blue/visibility/calendar-visible.png', tooltip(_("Mein Kalender ist für diese Person lesbar")));
                                break;
                            case Calendar::PERMISSION_WRITABLE:
                                echo URLHelper::getLink('#' . $statusgruppe_id, array('user_id' => $db2->f('user_id'), 'view' => $view, 'lid' => $lid, 'calperm' => Calendar::PERMISSION_FORBIDDEN)) . '">';
                                echo Assets::img('icons/16/red/schedule.png', tooltip(_("Mein Kalender ist für diese Person schreibbar")));
                                break;
                            default:
                                echo URLHelper::getLink('#' . $statusgruppe_id, array('user_id' => $db2->f('user_id'), 'view' => $view, 'lid' => $lid, 'calperm' => Calendar::PERMISSION_READABLE)) . '">';
                                echo Assets::img('icons/16/blue/visibility/calendar-invisible.png', tooltip(_("Mein Kalender ist für diese Person unsichtbar")));
                                break;
                        }
                        echo '</a></td>';

                    } else {
                        echo "<td class=\"$class\">&nbsp;</td>\n";
                    }
                    echo '<td style="text-align: center;" class="' . $class . '">';
                    echo '<a href="' . URLHelper::getLink('#' . $statusgruppe_id, array('cmd' => 'remove_person', 'statusgruppe_id' => $statusgruppe_id, 'entry_id' => $identifier, 'range_id' => $range_id, 'view' => $view)) . '">';
                    echo Assets::img('icons/16/blue/trash.png', tooltip(_("Person aus der Gruppe entfernen"))) . '</a></td>';
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
            printf("<p align=\"center\"><a href=\"%s\"><img src=\"" . Assets::image_path('icons/16/yellow/arr_2up.png') . "\" %s><img src=\"" . Assets::image_path('icons/16/yellow/arr_2down.png') . "\" %s></a><br>&nbsp;", URLHelper::getLink('?cmd=swap&statusgruppe_id=' . $statusgruppe_id . '&range_id=' . $range_id . '&view=' . $view), tooltip(_("Gruppenreihenfolge tauschen")), tooltip(_("Gruppenreihenfolge tauschen")));
        }
    }
}

function PrintSearchResults($search_exp, $range_id)
{
    global $_fullname_sql, $user;
    $search_exp = '%' . $search_exp . '%';
    $query = 'SELECT DISTINCT auth_user_md5.user_id, ' . $_fullname_sql['full_rev']
            . ' AS fullname, username, perms '
            . 'FROM auth_user_md5 LEFT JOIN user_info USING (user_id) '
            . 'WHERE perms != ? AND '
            . '(Vorname LIKE ? OR Nachname LIKE ? OR username LIKE ?) '
            . 'ORDER BY Nachname';
    $stmt = DBManager::get()->prepare($query);
    $stmt->execute(array('user', $search_exp, $search_exp, $search_exp));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$result) {
        echo "&nbsp; " . _("keine Treffer") . "&nbsp; ";
    } else {
        $stmt2 = DBManager::get()->prepare('SELECT COUNT(*) FROM contact '
                . 'WHERE owner_id = ? '
                . 'AND user_id = ? AND calpermission > 1');
        echo "&nbsp; <select name=\"Freesearch[]\" size=\"4\" >";
        foreach ($result as $row) {
            if (get_visibility_by_id($row['user_id'])) {
                $have_perm = false;
                if (get_config('CALENDAR_GROUP_ENABLE')) {
                    $stmt2->execute(array($row['user_id'], $GLOBALS['user']->id));
                    $have_perm = $stmt2->fetchColumn() > 0;
                }
                if ($have_perm) {
                    printf("<option style=\"color:#ff0000;\" value=\"%s\">%s - %s\n", $row['username'], htmlReady(my_substr($row['fullname'], 0, 35) . " (" . $row['username'] . ")"), $row['perms']);
                } else {
                    printf("<option value=\"%s\">%s - %s\n", $row['username'], htmlReady(my_substr($row['fullname'], 0, 35) . " (" . $row['username'] . ")"), $row['perms']);
                }
            }
        }
        echo "</select>";
    }
}

function PrintAktualContacts($range_id)
{
    global $_fullname_sql, $user;
    $selected = GetAllSelected($range_id);
    $query = "SELECT contact.user_id, username, " . $_fullname_sql['full_rev'];
    $query .= " AS fullname, perms FROM contact LEFT JOIN auth_user_md5 USING(user_id) ";
    $query .= "LEFT JOIN user_info USING (user_id)  WHERE owner_id = '$range_id' ";
    $query .= " ORDER BY Nachname ASC";
    $db = new DB_Seminar();
    $db2 = new DB_Seminar();
    $db->query($query);
    if ($db->num_rows() > 10) {
        $size = 25;
    } else {
        $size = 10;
    }

    echo "<font size=\"-1\">&nbsp; " . _("Personen im Adressbuch") . "</font><br>";
    echo "&nbsp; <select size=\"$size\" name=\"AktualMembers[]\" multiple>";

    while ($db->next_record()) {
        if (get_visibility_by_id($db->f('user_id'))) {
            $have_perm = false;
            if ($GLOBALS['CALENDAR_GROUP_ENABLE']) {
                $query = "SELECT calpermission FROM contact WHERE owner_id = '" . $db->f('user_id');
                $query .= "' AND user_id = '{$user->id}' AND calpermission > 1";
                $db2->query($query);
                $have_perm = $db2->num_rows();
            }
            if ($have_perm) {
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
            echo '">';
            echo htmlReady(my_substr($db->f('fullname'), 0, 35) . " (" . $db->f('username') . ")");
            echo " - " . $db->f('perms') . "</option>\n";
        }
    }


    echo "</select>";
}

// Ende Funktionen
// fehlende Werte holen
// alles ist userbezogen:
// Abfrage der Formulare und Aktionen
// neue Statusgruppe hinzufuegen
$new_statusgruppe_name = Request::quoted('new_statusgruppe_name');
if (($cmd == "add_new_statusgruppe") && ($new_statusgruppe_name != "")) {
    if (Statusgruppe::countByName($new_statusgruppe_name, $range_id) > 0) {
        $msgs[] = 'info§' . sprintf(_("Die Gruppe %s wurde hinzugefügt, es gibt jedoch bereits ein Gruppe mit demselben Namen!"), '<b>' . htmlReady($new_statusgruppe_name) . '</b>');
    } else {
        $msgs[] = 'msg§' . sprintf(_("Die Gruppe %s wurde hinzugefügt!"), '<b>' . htmlReady($new_statusgruppe_name) . '</b>');
    }

    AddNewStatusgruppe($new_statusgruppe_name, $range_id_statusgruppe, $new_statusgruppe_size);
}

// bestehende Statusgruppe editieren

if (($cmd == "edit_existing_statusgruppe") && ($new_statusgruppe_name != "")) {
    EditStatusgruppe($new_statusgruppe_name, $new_statusgruppe_size, Request::option('update_id'));
}

// zuordnen von Personen zu einer Statusgruppe
if ($cmd == "move_person" && ($AktualMembers != "" || $Freesearch != "")) {
    MovePersonStatusgruppe($range_id, $AktualMembers, $Freesearch);
}

// Entfernen von Personen aus einer Statusgruppe

if ($cmd == "remove_person") {
    RemovePersonStatusgruppe(Request::option('entry_id'), $statusgruppe_id, $range_id);
}

// Entfernen von Statusgruppen

if ($cmd == "verify_remove_statusgruppe") {
    $msg = sprintf(_('Möchten Sie wirklich die Kategorie **%s** löschen?'), $name);
    echo createQuestion($msg, array('cmd' => 'remove_statusgruppe', "statusgruppe_id" => $statusgruppe_id, 'range_id' => $range_id));
}

if ($cmd == "remove_statusgruppe") {
    DeleteStatusgruppe($statusgruppe_id);
}

// Aendern der Position

if ($cmd == 'swap') {
    SwapStatusgruppe($statusgruppe_id);
}

// Switch Group calendar access
if (get_config('CALENDAR_GROUP_ENABLE')) {
    // Switch calendar access for group members
    if (Request::get('calperm')) {
        switch_member_cal(Request::get('user_id'), Request::get('calperm', Calendar::PERMISSION_FORBIDDEN));
    }
}

if (!is_null(Request::get('toggle_statusgruppe'))) {
    if ($_SESSION['contact_statusgruppen']['group_open'][Request::get('toggle_statusgruppe')]) {
        unset($_SESSION['contact_statusgruppen']['group_open'][Request::get('toggle_statusgruppe')]);
    } else {
        $_SESSION['contact_statusgruppen']['group_open'][Request::get('toggle_statusgruppe')] = true;
    }
}
// Ende Abfrage Formulare
// Beginn Darstellungsteil
// Anfang Edit-Bereich
?>
<table class="blank" width="100%" border="0" cellspacing="0" id="main_content">
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
    if ($cmd != "edit_statusgruppe") { // normale Anzeige
        ?>
                <form action="<? echo URLHelper::getLink('?cmd=add_new_statusgruppe') ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <?
        echo '<input type="hidden" name="range_id" value="' . $range_id . '">';
        echo"<input type=\"hidden\" name=\"view\" value=\"$view\">";
        ?>
                    <font size="2"><?= _("Adressbuchgruppe anlegen:") ?></font>

                    <input type="text" name="new_statusgruppe_name" style="vertical-align:middle" value="<?= _("Gruppenname") ?>">
                    <? if (get_config('CALENDAR_GROUP_ENABLE')) : ?>
                        <label><?= _('im Kalender auswählbar:') ?>
                        <input type="checkbox" name="is_cal_group" value="1" class="text-top"></label>
                    <? endif ?>
                    &nbsp; &nbsp; &nbsp; <b><?= _("Einf&uuml;gen") ?></b>&nbsp;
                <?
                printf("<input type=\"IMAGE\" name=\"add_new_statusgruppe\" src=\"" . Assets::image_path('icons/16/yellow/arr_2down.png') . "\" value=\" %s \" %s>&nbsp;  &nbsp; &nbsp; ", _("neue Statusgruppe"), tooltip(_("neue Gruppe anlegen")));
                ?>
                </form>
                    <?
                } else { // editieren einer bestehenden Statusgruppe
                    ?>
                <form action="<? echo URLHelper::getLink(sprintf('?cmd=edit_existing_statusgruppe#%s', $edit_id)) ?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
                    <?
                    $db = new DB_Seminar("SELECT name, size, calendar_group FROM statusgruppen WHERE statusgruppe_id = '$edit_id'");
                    if ($db->next_record()) {
                        $gruppe_name = $db->f("name");
                    }
                    echo"<input type=\"HIDDEN\" name=\"range_id\" value=\"$range_id\">";
                    echo"<input type=\"HIDDEN\" name=\"update_id\" value=\"$edit_id\">";
                    echo"<input type=\"HIDDEN\" name=\"view\" value=\"$view\">";
                    ?>
                    <font size="2"><?= _("neuer Gruppenname:") ?> </font>
                    <input type="text" name="new_statusgruppe_name" style="vertical-align:middle" value="<? echo htmlReady($gruppe_name); ?>">
                    <? if (get_config('CALENDAR_GROUP_ENABLE')) : ?>
                        <label><?= _('im Kalender auswählbar:') ?>
                        <input type="checkbox" name="is_cal_group" value="1" class="text-top"<?= ($db->f('calendar_group') ? ' checked="checked"' : '') ?>></label>
                    <? endif ?>
                    &nbsp; &nbsp; &nbsp; <b><?= _("&Auml;ndern") ?></b>&nbsp;
                    <?
                    printf("<input type=\"IMAGE\" name=\"add_new_statusgruppe\" src=\"" . Assets::image_path('icons/16/green/accept.png') . "\" value=\" %s \" %s>&nbsp;  &nbsp; &nbsp; ", _("Gruppe anpassen"), tooltip(_("Gruppe anpassen")));
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
                $db->query("SELECT name, statusgruppe_id, size FROM statusgruppen WHERE range_id = '$range_id_statusgruppe' ORDER BY position ASC");
                if ($db->num_rows() > 0) {   // haben wir schon Gruppen? dann Anzeige
                    ?>
    <form action="<? echo URLHelper::getLink('?cmd=move_person') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
        <table width="100%" border="0" cellspacing="0">
            <tr>
                <td class="steel1" valign="top" width="50%">
                    <br>
    <?
    echo"<input type=\"HIDDEN\" name=\"range_id\" value=\"$range_id\">\n";
    echo"<input type=\"HIDDEN\" name=\"view\" value=\"$view\">\n";
    if ($db->num_rows() > 0) {
        $nogroups = 1;
        PrintAktualContacts($range_id);
        ?>
                        <br><br>
        <?
        $search_exp = Request::quoted('search_exp');
        if ($search_exp) {

            $search_exp = str_replace("%", "\%", $search_exp);
            $search_exp = str_replace("_", "\_", $search_exp);
            if (strlen(trim($search_exp)) < 3) {
                echo "&nbsp; <font size=\"-1\">" . _("Ihr Suchbegriff muss mindestens 3 Zeichen umfassen!");
                echo "<br><br><font size=\"-1\">&nbsp; " . _("freie Personensuche (wird in Adressbuch übernommen)") . "</font><br>";
                echo "&nbsp; <input type=\"text\" name=\"search_exp\" value=\"\">";
                printf(" <input class=\"middle\" type=\"IMAGE\" name=\"search\" src=\"" . Assets::image_path('icons/16/blue/search.png') . "\"  value=\" %s \" %s>&nbsp;  ", _("Person suchen"), tooltip(_("Person suchen")));
            } else {
                PrintSearchResults($search_exp, $range_id);
                printf("<input type=\"IMAGE\" name=\"search\" src=\"" . Assets::image_path('icons/16/blue/refresh.png') . "\"  value=\" %s \" %s>&nbsp;  ", _("neue Suche"), tooltip(_("neue Suche")));
            }
        } else {
            echo _("freie Personensuche (wird in Adressbuch &uuml;bernommen)") . "<br>";
            echo "&nbsp; <input type=\"text\" name=\"search_exp\" value=\"\">";
            printf(" <input class=\"middle\" type=\"IMAGE\" name=\"search\" src=\"" . Assets::image_path('icons/16/blue/search.png') . "\"  value=\" %s \" %s>&nbsp;  ", _("Person suchen"), tooltip(_("Person suchen")));
        }
        echo "<br><br>\n";
        if ($GLOBALS['CALENDAR_GROUP_ENABLE']) {
            printf(_("%sRot%s dargestellte Nutzernamen kennzeichnen Personen, auf deren Kalender Sie Zugriff haben."), '<span style="color:#FF0000;">', '</span>');
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
        <tr><td>
                    <?
                    $help_url = format_help_url("Basis.VeranstaltungenVerwaltenGruppen");
                    $zusatz = array(
                        _("Um f&uuml;r diesen Bereich Gruppen oder Funktionen anzulegen, nutzen Sie bitte die obere Zeile!"),
                        _("Wenn Sie Gruppen angelegt haben, k&ouml;nnen Sie diesen Personen zuordnen. Jeder Gruppe k&ouml;nnen beliebig viele Personen zugeordnet werden. Jede Person kann beliebig vielen Gruppen zugeordnet werden."),
                        sprintf(_("Lesen Sie weitere Bedienungshinweise in der %sHilfe%s nach!"), "<a href=\"" . $help_url . "\">", "</a>")
                    );

                    echo Messagebox::info(_("Es sind noch keine Gruppen oder Funktionen angelegt worden."), $zusatz);
                    ?>
            </td></tr>
    </table>
    <?
}
include ('lib/include/html_end.inc.php');
page_close();

