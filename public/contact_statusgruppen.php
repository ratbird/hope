<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", 'user' => "Seminar_User"));
$auth->login_if($auth->auth["uid"] == "nobody");


$hash_secret = "dslkjjhetbjs";
include ('lib/seminar_open.php'); // initialise Stud.IP-Session
PageLayout::addStylesheet('multi-select.css');
PageLayout::addScript('jquery/jquery.multi-select.js');
PageLayout::addScript('multi_person_search.js');
$range_id = Request::option('range_id');
$cmd = Request::option('cmd');
$view = Request::option('view');
$name = Request::get('name');
$Freesearch = Request::getArray('Freesearch');
$AktualMembers = Request::getArray('AktualMembers');
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

ob_start();

if (get_config('CALENDAR_GROUP_ENABLE')) {
    require_once('lib/calendar/lib/Calendar.class.php');
}

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
            InsertPersonStatusgruppe(get_userid($AktualMembers[$i]), $statusgruppe_id, false);
        }
    }
    if ($Freesearch != '') {
        for ($i = 0; $i < sizeof($Freesearch); $i++) {
            if (InsertPersonStatusgruppe(get_userid($Freesearch[$i]), $statusgruppe_id, false)) {
                AddNewContact(get_userid($Freesearch[$i]), $range_id);
            }
        }
    }
}
/**
 * Add members to a statusgroup.
 */
function addToStatusgroup($range_id, $statusgruppe_id) {
    $mp = MultiPersonSearch::load("contacts_statusgroup_" . $statusgruppe_id);
    if (count($mp->getAddedUsers()) !== 0) {
        $quickfilters = $mp->getQuickfilterIds();
        foreach ($mp->getAddedUsers() as $m) {
            if (!in_array($m, $quickfilters[_("Adressbuch")])) {
                if (InsertPersonStatusgruppe($m, $statusgruppe_id, false)) {
                    AddNewContact($m, $range_id);
                }
            } else {
                InsertPersonStatusgruppe($m, $statusgruppe_id, false);
            }
        }
    }
    $mp->clearSession();
}

// Funktionen zur reinen Augabe von Statusgruppendaten

function PrintAktualStatusgruppen($range_id, $view, $edit_id = '')
{
    global $range_id_statusgruppe, $_fullname_sql, $user;

    // Prepare statement for members of a contact group
    $query = "SELECT statusgruppe_user.user_id, {$_fullname_sql['full']} AS fullname, username, nachname
              FROM statusgruppe_user
              LEFT JOIN auth_user_md5 USING (user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE statusgruppe_id = ?
              ORDER BY nachname";
    $members_statement = DBManager::get()->prepare($query);

    // Prepare statement for calendar permissions
    $query = "SELECT calpermission
              FROM contact
              WHERE owner_id = ? AND user_id = ?";
    $permission_statement = DBManager::get()->prepare($query);

    // Prepare and execute main query that reads all contact groups
    $query = "SELECT statusgruppe_id, name, calendar_group, COUNT(statusgruppe_user.user_id) AS count
              FROM statusgruppen
              LEFT JOIN statusgruppe_user USING (statusgruppe_id)
              WHERE range_id = ?
              GROUP BY statusgruppe_id
              ORDER BY statusgruppen.position ASC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($range_id_statusgruppe));

    // generate MultiPersonSearch
    // load addressbook
    $contacts_query = "SELECT user_id, username, {$_fullname_sql['full_rev']} AS fullname, perms
                  FROM contact
                  LEFT JOIN auth_user_md5 USING (user_id)
                  LEFT JOIN user_info USING (user_id)
                  WHERE owner_id = ?
                  ORDER BY Nachname ASC";
    $contacts_statement = DBManager::get()->prepare($contacts_query);
    $contacts_statement->execute(array($range_id));
    $contacts = $contacts_statement->fetchAll();
    foreach ($contacts as $c) {
        $quickfilter[] = $c['user_id'];
    }
    $search_obj = new SQLSearch("SELECT auth_user_md5.user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms "
                            . "FROM auth_user_md5 "
                            . "LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) "
                            . "WHERE "
                            . "username LIKE :input OR Vorname LIKE :input "
                            . "OR CONCAT(Vorname,' ',Nachname) LIKE :input "
                            . "OR CONCAT(Nachname,' ',Vorname) LIKE :input "
                            . "OR Nachname LIKE :input OR {$GLOBALS['_fullname_sql']['full_rev']} LIKE :input "
                            . " ORDER BY fullname ASC",
                            _("Nutzer suchen"), "user_id");

    $lid = rand(1, 1000);
    $i = 0;
    ?>
    <div class="sortable">
    <?
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $statusgruppe_id = $row['statusgruppe_id'];

    addToStatusgroup($range_id, $statusgruppe_id);


    $members_statement->execute(array($statusgruppe_id));
    $member = $members_statement->fetchAll();

    $defaultSelectedUser = array();
    foreach ($member as $m) {
        $defaultSelectedUser[] = $m['user_id'];
    }
    URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
    $mp = MultiPersonSearch::get("contacts_statusgroup_" . $statusgruppe_id)
        ->setLinkText("")
        ->setDefaultSelectedUser($defaultSelectedUser)
        ->setTitle(_('Personen eintragen'))
        ->setExecuteURL(URLHelper::getLink("contact_statusgruppen.php"))
        ->setSearchObject($search_obj)
        ->addQuickfilter(_("Adressbuch"), $quickfilter)
        ->addQuickfilter(_("Buddies"), GetBuddyIDs($GLOBALS['user']->id))
        ->render();
        ?>
        <table id="<?= $statusgruppe_id ?>" width="95%" border="0" cellpadding="2" cellspacing="0" class="sortable">
            <tr class="handle">
        <?
        echo "\n<td width=\"5%\">&nbsp; </td>";

        $cal_group = get_config('CALENDAR_GROUP_ENABLE') && $row['calendar_group'];
        echo '<td width="' . ($cal_group ? '80%' : '85%') . '" class="table_header';
        echo ($edit_id == $statusgruppe_id ? ' table_header_bold_red' : '') . '" style="cursor: move">';
        ?>

            <?= Assets::img('') ?>
            <a class="tree" href="<?= URLHelper::getLink("?toggle_statusgruppe=$statusgruppe_id&range_id=$range_id&view=$view&foo=" . md5(uniqid('foo', 1)) . "#$statusgruppe_id") ?>">
            <? if ($_SESSION['contact_statusgruppen']['group_open'][$statusgruppe_id]) : ?>
                <?= Assets::img('icons/16/blue/arr_1down.png') ?>
            <? else : ?>
                <?= Assets::img('icons/16/blue/arr_1right.png') ?>
            <? endif ?>
                <?= htmlReady($row['name']) ?>
            </a>
        </td>

        <td class="table_header<?= $edit_id == $statusgruppe_id ? ' table_header_bold_red' : '' ?>" style="width: 1%; white-space: nowrap">
            <?= count($member)?>
        </td>

        <td class="table_header<?= $edit_id == $statusgruppe_id ? ' table_header_bold_red' : '' ?>" style="width: 1%; white-space: nowrap">
            <?= $mp; ?>
        </td>

        <?
        echo '<td class="table_header' . ($edit_id == $statusgruppe_id ? ' table_header_bold_red' : '') . '" width="1%">';
        if ($cal_group) {
            echo Assets::img('icons/16/blue/schedule.png', tooltip2(_('Kalendergruppe')));
            echo '</td><td class="table_header ' . ($edit_id == $statusgruppe_id ? ' table_header_bold_red' : '') . '" style="whitespace: width="5%">';
        }

        echo '<a href="' . URLHelper::getLink('', array('edit_id' => $statusgruppe_id, 'range_id' => $range_id, 'view' => $view, 'cmd' => 'edit_statusgruppe')) . '">';
        echo Assets::img('icons/16/blue/edit.png', tooltip2(_('Gruppenname oder -größe anpassen')));
        echo '</a></td>';

        printf("<td align=\"right\" width=\"1%%\" class=\"table_header%s\"><a href=\"%s\">%s</a></td>", ($edit_id == $statusgruppe_id ? " table_header_bold_red" : ''), URLHelper::getLink("?cmd=verify_remove_statusgruppe&statusgruppe_id=" . $statusgruppe_id . "&range_id=" . $range_id . "&view=" . $view . "&name=" . $row['name']), Assets::img('icons/16/blue/trash.png', tooltip2(_('Gruppe mit Personenzuordnung entfernen'))));
        echo "\n</tr>";

        // if the current statusgroup is opened, display associated users
        if ($_SESSION['contact_statusgruppen']['group_open'][$statusgruppe_id]) {
            $members_statement->execute(array($statusgruppe_id));

            $k = 1;
            while ($member = $members_statement->fetch(PDO::FETCH_ASSOC)) {
                if (!get_visibility_by_id($member['user_id'])) {
                    continue;
                }

                $color = false;

                // user entries
                $fullname = $member['fullname'];
                $identifier = $member['username'];

                // query whether the current user has the permission to access the calendar
                // of this user
                if (get_config('CALENDAR_GROUP_ENABLE')) {
                    $permission_statement->execute(array($member['user_id'], $user->id));
                    $cal_permission = $permission_statement->fetchColumn();
                    $permission_statement->closeCursor();

                    if ($cal_permission > 1)
                        $color = '#FF0000';
                }

                if ($k % 2) {
                    $class = 'table_row_even';
                } else {
                    $class = 'table_row_odd';
                }
                echo "\n<tr>\n\t\t<td><font color=\"#AAAAAA\">$k</font></td>";
                ?>

                <td class="<?= $class ?>" colspan="2">
                    <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $identifier) ?>"
                       <? if ($color) echo 'style="color:' . $color . '"'; ?>>
                        <?= htmlReady($fullname) ?>
                    </a>
                </td>

                <?
                if (get_config('CALENDAR_GROUP_ENABLE')) {
                    $permission_statement->execute(array($user->id, $member['user_id']));
                    $cal_permission = $permission_statement->fetchColumn();
                    $permission_statement->closeCursor();

                    if ($cal_group) {
                        echo '<td class="' . $class . '"> </td>';
                    }
                    echo '<td style="text-align: center;" class="' . $class . '">';
                    echo '<a href="';
                    switch ($cal_permission) {
                        case Calendar::PERMISSION_READABLE:
                            echo URLHelper::getLink('#' . $statusgruppe_id, array('user_id' => $member['user_id'], 'view' => $view, 'lid' => $lid, 'calperm' => Calendar::PERMISSION_WRITABLE)) . '">';
                            echo Assets::img('icons/16/blue/visibility/calendar-visible.png', tooltip(_("Mein Kalender ist für diese Person lesbar")));
                            break;
                        case Calendar::PERMISSION_WRITABLE:
                            echo URLHelper::getLink('#' . $statusgruppe_id, array('user_id' => $member['user_id'], 'view' => $view, 'lid' => $lid, 'calperm' => Calendar::PERMISSION_FORBIDDEN)) . '">';
                            echo Assets::img('icons/16/red/schedule.png', tooltip(_("Mein Kalender ist für diese Person schreibbar")));
                            break;
                        default:
                            echo URLHelper::getLink('#' . $statusgruppe_id, array('user_id' => $member['user_id'], 'view' => $view, 'lid' => $lid, 'calperm' => Calendar::PERMISSION_READABLE)) . '">';
                            echo Assets::img('icons/16/blue/visibility/calendar-invisible.png', tooltip(_("Mein Kalender ist für diese Person unsichtbar")));
                            break;
                    }
                    echo '</a></td>';

                } else {
                    echo "<td class=\"$class\">&nbsp;</td><td class=\"$class\">&nbsp;</td>\n";
                }

                echo '<td style="text-align: center;" class="' . $class . '">';
                echo '<a href="' . URLHelper::getLink('#' . $statusgruppe_id, array('cmd' => 'remove_person', 'statusgruppe_id' => $statusgruppe_id, 'entry_id' => $identifier, 'range_id' => $range_id, 'view' => $view)) . '">';
                echo Assets::img('icons/16/blue/trash.png', tooltip(_("Person aus der Gruppe entfernen"))) . '</a></td>';
                echo "\n\t</tr>";
                $k++;
            }

            $members_statement->closeCursor();
        }

        $i++;
        echo "</table>";
    }
    ?>
    </div>
    <script>
        STUDIP.StatusGroup.init();
    </script>
    <?
}


// Ende Funktionen
// fehlende Werte holen
// alles ist userbezogen:
// Abfrage der Formulare und Aktionen
// neue Statusgruppe hinzufuegen
$new_statusgruppe_name = htmLReady(Request::get('new_statusgruppe_name'));
if (($cmd == "add_new_statusgruppe") && ($new_statusgruppe_name != "")) {
    if (Statusgruppe::countByName($new_statusgruppe_name, $range_id) > 0) {
        $msgs[] = 'info§' . sprintf(_("Die Gruppe %s wurde hinzugefügt, es gibt jedoch bereits eine Gruppe mit demselben Namen!"), '<b>' . htmlReady($new_statusgruppe_name) . '</b>');
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

// store the position of the statusgroups
if ($cmd == 'storeSortOrder') {
    $i = 0;
    foreach (Request::optionArray('statusgroup_ids') as $statusgroup_id) {
        $statusgroup = new Statusgruppe($statusgroup_id);
        $statusgroup->setPosition($i);
        $statusgroup->store();
        $i++;
    }

    // if we have an ajax call, no further execution is required
    if (Request::isXhr()) {
        die;
    }
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
                    <label>
                        <?= _('Adressbuchgruppe anlegen:') ?>
                        <input type="text" name="new_statusgruppe_name" style="vertical-align:middle" placeholder="<?= _("Gruppenname") ?>">
                    </label>
                    <? if (get_config('CALENDAR_GROUP_ENABLE')) : ?>
                        <label><?= _('im Kalender auswählbar:') ?>
                        <input type="checkbox" name="is_cal_group" value="1" class="text-top"></label>
                    <? endif ?>
                    &nbsp; &nbsp; &nbsp; <b><?= _("Einfügen") ?></b>&nbsp;
                    <?= Assets::input('icons/16/yellow/arr_2down.png', tooltip2(_('neue Gruppe anlegen')) + array(
                            'name' => 'add_new_statusgruppe',
                            'value' => _('neue Statusgruppe'),
                    )) ?>
                </form>
                    <?
                } else { // editieren einer bestehenden Statusgruppe
                    ?>
                <form action="<? echo URLHelper::getLink(sprintf('?cmd=edit_existing_statusgruppe#%s', $edit_id)) ?>" method="POST">
    <?= CSRFProtection::tokenTag() ?>
                    <?
                    $query = "SELECT name, size, calendar_group FROM statusgruppen WHERE statusgruppe_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($edit_id));
                    $temp = $statement->fetch(PDO::FETCH_ASSOC);

                    if ($temp) {
                        $gruppe_name = $temp['name'];
                    }
                    echo"<input type=\"HIDDEN\" name=\"range_id\" value=\"$range_id\">";
                    echo"<input type=\"HIDDEN\" name=\"update_id\" value=\"$edit_id\">";
                    echo"<input type=\"HIDDEN\" name=\"view\" value=\"$view\">";
                    ?>
                    <font size="2"><?= _("neuer Gruppenname:") ?> </font>
                    <input type="text" name="new_statusgruppe_name" style="vertical-align:middle" value="<? echo htmlReady($gruppe_name); ?>">
                    <? if (get_config('CALENDAR_GROUP_ENABLE')) : ?>
                        <label><?= _('im Kalender auswählbar:') ?>
                        <input type="checkbox" name="is_cal_group" value="1" class="text-top"<?= ($temp['calendar_group'] ? ' checked' : '') ?>></label>
                    <? endif ?>
                    &nbsp; &nbsp; &nbsp; <b><?= _("&Auml;ndern") ?></b>&nbsp;
                    <?= Assets::input('icons/16/green/accept.png', tooltip2(_('Gruppe anpassen')) + array(
                            'name' => 'add_new_statusgruppe',
                            'value' => _('Gruppe anpassen'),
                    )) ?>
                </form>
                    <?
                }
                ?>

            <br></td>
    </tr>
</table><?
// Ende Edit-Bereich
// Anfang Personenbereich
                $query = "SELECT 1 FROM statusgruppen WHERE range_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($range_id_statusgruppe));
                $present = $statement->fetchColumn();

                if ($present) {   // haben wir schon Gruppen? dann Anzeige

    // Anfang Gruppenuebersicht
    PrintAktualStatusgruppen($range_id, $view, $edit_id);
    ?>

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

                    echo MessageBox::info(_("Es sind noch keine Gruppen oder Funktionen angelegt worden."), $zusatz);
                    ?>
            </td></tr>
    </table>
    <?
}

Sidebar::get()->setImage('sidebar/group-sidebar.png');
echo $GLOBALS['template_factory']->render('layouts/base.php', array(
    'content_for_layout' => ob_get_clean(),
));

page_close();

