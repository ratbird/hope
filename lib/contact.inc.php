<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* helper functions for handling contacts
*
* helper functions for handling contacts
*
* @author               Ralf Stockmann <rstockm@gwdg.de>
* @access               public
* @package          studip_core
* @modulegroup  library
* @module               contact.inc.php
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// contact.inc.php
// Copyright (c) 2002 Ralf Stockmann <rstockm@gwdg.de>
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

require_once('lib/classes/Avatar.class.php');
if (Config::get()->getValue('CALENDAR_GROUP_ENABLE')) {
    require_once('lib/calendar/lib/Calendar.class.php');
}

/**
* built a not existing ID
*
* @access private
* @return   string
*/
function MakeUniqueContactID ()
{   // baut eine ID die es noch nicht gibt

    $hash_secret = "kertoiisdfgz";
    $statement = DBManager::get()->prepare("SELECT 1 FROM contact WHERE contact_id = ?");

    do {
        $tmp_id = md5(uniqid($hash_secret, true));

        $statement->execute(array($tmp_id));
        $present = $statement->fetchColumn();
        $statement->closeCursor();
    } while ($present);

    return $tmp_id;
}

function MakeUniqueUserinfoID ()
{   // baut eine ID die es noch nicht gibt

    $hash_secret = "kertoiisdfgz";
    $statement = DBManager::get()->prepare("SELECT 1 FROM contact_userinfo WHERE userinfo_id = ?");

    do {
        $tmp_id = md5(uniqid($hash_secret, true));

        $statement->execute(array($tmp_id));
        $present = $statement->fetchColumn();
        $statement->closeCursor();
    } while ($present);

    return $tmp_id;
}

/**
 * @addtogroup notifications
 *
 * Adding a buddy triggers a BuddyDidAdd notification. The contact_id
 * of the new buddy is transmitted as subject of the notification.
 */

/**
 * Toggles the buddy-flag for the passed contact.
 *
 * When adding a buddy this way, triggers a BuddyDidAdd notification
 * using the contact_id as subject.
 *
 * @param  string  $contact_id the md5-hash of the contact (not the user_id!)
 * @return bool
 */
function ChangeBuddy($contact_id)
{
    $db = DBManager::get();
    $stmt = $db->prepare("SELECT buddy FROM contact WHERE contact_id = ?");
    $stmt->execute(array($contact_id));
    $buddy = $stmt->fetchColumn();

    $stmt = $db->prepare('UPDATE contact SET buddy = ? WHERE contact_id = ?');
    $result = $stmt->execute(array($buddy ? 0 : 1, $contact_id));

    if (!$buddy) {
        NotificationCenter::postNotification('BuddyDidAdd', $contact_id);
    }

    return $result;
}

function RemoveBuddy($username)
{
    global $user;

    $owner_id = $user->id;
    $user_id = get_userid($username);

    $query = "SELECT contact_id FROM contact WHERE owner_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($owner_id, $user_id));
    $contact_id = $statement->fetchColumn();

    if ($contact_id) {
        $query = "UPDATE contact SET buddy = 0 WHERE contact_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($contact_id));
    }
}

function RemoveUserFromBuddys($user_id)
{
    // erst mal die selbstzugefügten weg
    $query = "SELECT contact_id FROM contact WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));
    $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    if (count($ids)) {
        $query = "DELETE FROM contact_userinfo WHERE contact_id IN (?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($ids));
    }

    // jetzt alle Zuordnungen
    $query = "DELETE FROM contact WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));

    $buddykills = $statement->rowCount();
    return $buddykills;
}

function CheckBuddy($username, $owner_id=FALSE)
{
    global $user;
    if (!$owner_id) {
        $owner_id = $user->id;
    }
    $user_id = get_userid($username);

    $query = "SELECT 1 FROM contact WHERE owner_id = ? AND user_id = ? AND buddy = 1";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($owner_id, $user_id));
    $buddy = (bool)$statement->fetchColumn();

    return $buddy;
}

function GetNumberOfBuddies()
{
    global $user;

    $query = "SELECT COUNT(*) FROM contact WHERE owner_id = ? AND buddy = 1";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    $buddies = $statement->fetchColumn();

    return $buddies;
}

function GetBuddyIDs($user_id)
{
    $stmt = DBManager::get()->prepare('SELECT user_id FROM contact '.
                                      'WHERE owner_id = ? AND buddy = 1');
    $stmt->execute(array($user_id));
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

function GetSizeofBook()
{
    global $user;
    $owner_id = $user->id;

    $query = "SELECT COUNT(*) FROM contact WHERE owner_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($owner_id));
    $size = $statement->fetchColumn();

    return $size;
}

function GetSizeOfBookByGroup()
{
    global $user;

    $owner_id = $user->id;

    $query = "SELECT statusgruppe_id, COUNT(*)
              FROM statusgruppen
              LEFT JOIN statusgruppe_user USING (statusgruppe_id)
              WHERE range_id = ?
              GROUP BY statusgruppe_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($owner_id));
    $sizes = $statement->fetchGrouped(PDO::FETCH_COLUMN);

    return $sizes;
}

function GetSizeOfBookByLetter()
{
    global $user;

    $query = "SELECT LCASE(LEFT(TRIM(Nachname), 1)) AS first_letter, COUNT(*)
              FROM contact
              LEFT JOIN auth_user_md5 USING (user_id)
              WHERE owner_id = ? AND NOT ISNULL(Nachname)
              GROUP BY first_letter";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    $sizes = $statement->fetchGrouped(PDO::FETCH_COLUMN);

    return $sizes;
}

/**
 * When adding a buddy this way, triggers a BuddyDidAdd notification
 * using the new contact_id as subject.
 */
function AddBuddy($username)
{
    global $user;

    $owner_id = $user->id;
    $user_id = get_userid($username);

    $query = "SELECT contact_id FROM contact WHERE owner_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($owner_id, $user_id));
    $contact_id = $statement->fetchColumn();

    if ($contact_id) {
        $query = "UPDATE contact SET buddy = 1 WHERE contact_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($contact_id));

        if ($statement->rowCount()) {
            NotificationCenter::postNotification('BuddyDidAdd', $contact_id);
        }
    }
}

function AddNewContact ($user_id)
{   // Inserting an new contact
    global $user;

    $contact_id = MakeUniqueContactID();
    $owner_id = $user->id;

    $query = "SELECT 1 FROM contact WHERE owner_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($owner_id, $user_id));
    $present = $statement->fetchColumn();

    if (!$present) {
        $query = "INSERT INTO contact (contact_id, owner_id, user_id, buddy)
                  VALUES (?, ?, ?, 0)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($contact_id, $owner_id, $user_id));

        // get default permission if group calendar is enabled
        if (get_config('CALENDAR_GROUP_ENABLE')) {
            $query = "UPDATE contact SET calpermission = ? WHERE contact_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(Calendar::PERMISSION_FORBIDDEN, $contact_id));
        }
    }

    return $contact_id;
}

function AddNewUserinfo ($contact_id, $name, $content)
{   // Inserting an new contact
    global $user;

    $userinfo_id = MakeUniqueUserinfoID();

    $query = "SELECT MAX(priority) FROM contact_userinfo WHERE contact_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($contact_id));
    $priority = $statement->fetchColumn() ?: 0;

    if ($priority) {
        $priority += 1;
    }

    $query = "INSERT INTO contact_userinfo (userinfo_id, contact_id, name, content, priority)
              VALUES (?, ?, ?, ?, ?)";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($userinfo_id, $contact_id, $name, $content, $priority));

    return $userinfo_id;
}

function GetExtraUserinfo ($contact_id)
{
    // Build an array with extrauserinfos
    $query = "SELECT name, content FROM contact_userinfo WHERE contact_id = ? ORDER BY priority";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($contact_id));
    $userinfo = $statement->fetchGrouped(PDO::FETCH_COLUMN);

    return $userinfo;
}

function GetUserInfo($user_id)
{
    global $user;

    $query = "SELECT Home, privatnr, privatcell, privadr FROM user_info WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$temp) {
        return array();
    }

    $userinfo = array();

    if ($temp['Home']) {
        $userinfo[_('Homepage')] = formatLinks($temp['Home']);
    }
    if ($temp['privatnr']) {
        $userinfo[_('Tel. (privat)')] = htmlReady($temp['privatnr']);
    }
    if ($temp['privatcell']) {
        $userinfo[_('Mobiltelefon')] = htmlReady($temp['privatcell']);
    }
    if ($temp['privadr']) {
        $userinfo[_('Adresse')] = htmlReady($temp['privadr'], 1);
    }

    if (get_config('ENABLE_SKYPE_INFO') && UserConfig::get($user_id)->SKYPE_NAME) {
        if(UserConfig::get($user_id)->SKYPE_ONLINE_STATUS){
            $img = sprintf('<img src="http://mystatus.skype.com/smallicon/%s" style="border: none;vertical-align:middle" width="16" height="16" alt="My status">', htmlReady(UserConfig::get($user_id)->SKYPE_NAME));
        } else {
            $img = '<img src="' . $GLOBALS['ASSETS_URL'] . 'images/icon_small_skype.gif" style="border: none;vertical-align:middle">';
        }
        $userinfo[_('Skype')] = sprintf('<a href="skype:%1$s?call">%2$s&nbsp;%1$s</a><br>',
                                htmlReady(UserConfig::get($user_id)->SKYPE_NAME), $img);
    }

    return $userinfo;
}

function GetinstInfo ($user_id)
{
    $query = "SELECT sprechzeiten, raum, user_inst.telefon, user_inst.fax, Name,
                     Institute.Institut_id
              FROM user_inst
              LEFT JOIN Institute USING (Institut_id)
              WHERE user_id = ? AND inst_perms != 'user' AND visible = 1
              ORDER BY priority ASC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));

    $userinfo = array();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $item = array();

        $item[_('Einrichtung')] = sprintf('<a href="institut_main.php?auswahl=%s">%s</a>',
                                          $row['Institut_id'], htmlReady($row['Name']));
        if ($row['raum']) {
            $item[_('Raum')] = formatReady($row['raum']);
        }
        if ($row['sprechzeiten']) {
            $item[_('Sprechzeiten')] = formatReady($row['sprechzeiten']);
        }
        if ($row['telefon']) {
            $item[_('Tel. (dienstl.)')] = formatReady($row['telefon']);
        }
        if ($row['fax']) {
            $item[_('Fax (dienstl.)')] = formatReady($row['fax']);
        }
        if ($gruppen = GetRoleNames(GetAllStatusgruppen($row['Institut_id'], $user_id))) {
            $item[_('Funktion')] = htmlReady(join(', ', array_values($gruppen)));
        }

        $userinfo[] = $item;
    }

    return $userinfo;
}

function ShowUserInfo ($contact_id)
{   // Show the standard userinfo
    global $user, $open, $edit_id;

    $output = "";
    $basicinfo = array();

    $query = "SELECT user_id FROM contact WHERE contact_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($contact_id));
    $user_id = $statement->fetchColumn();

    $query = "SELECT Email, username FROM auth_user_md5 WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if ($temp) {
        $basicinfo[_('E-Mail')] = sprintf('<a href="mailto:%1$s">%1$s</a>', $temp['Email']);
        $basicinfo['Stud.IP']   = sprintf('<a href="about.php?username=%1$s">%1$s (%2$s)</a>',
                                          $temp['username'], get_global_perm($user_id));
    }

    // diese Infos hat jeder
    while(list($key,$value) = each($basicinfo)) {
        $output .= "<tr><td class=\"table_row_odd\" width=\"100\"><font size=\"2\">".$key.":</font></td><td class=\"table_row_odd\" width=\"250\"><font size=\"2\">".$value."</font></td></tr>";
    }

    // hier Zusatzinfos

    if (($open == $contact_id || $open == "all") && !$edit_id) {

        $userinfo = GetUserInfo($user_id);
        if (is_array($userinfo)) {
            while(list($key,$value) = each($userinfo)) {
                $output .= "<tr><td class=\"table_row_even\" width=\"100\"><font size=\"2\">".$key.":</font></td><td class=\"table_row_even\" width=\"250\"><font size=\"2\">".$value."</font></td></tr>";
            }
        }

        $userinstinfo = GetInstInfo($user_id);
        for ($i=0; $i <sizeof($userinstinfo); $i++) {
            while(list($key,$value) = each($userinstinfo[$i])) {
                $output .= "<tr><td class=\"table_row_even\" width=\"100\"><font size=\"2\">".$key.":</font></td><td class=\"table_row_even\" width=\"250\"><font size=\"2\">".$value."</font></td></tr>";
            }
        }

        $extra = GetExtraUserinfo ($contact_id);
        if (is_array($extra)) {
            while(list($key,$value) = each($extra)) {
                $output .= "<tr><td class=\"table_row_even\" width=\"100\"><font size=\"2\">".htmlReady($key).":</font></td><td class=\"table_row_even\" width=\"250\"><font size=\"2\">".formatReady($value)."</font></td></tr>";
            }
        }

        $output .= '<tr><td align="center" class="table_row_even" colspan="2" width="350"><br>'.Avatar::getAvatar($user_id)->getImageTag(Avatar::NORMAL).'</td>';
        $owner_id = $user->id;

        $query = "SELECT DISTINCT name, statusgruppe_id
                  FROM statusgruppen
                  LEFT JOIN statusgruppe_user USING (statusgruppe_id)
                  WHERE user_id = ? AND range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id, $owner_id));
        $temp = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($temp as $row) {
            $output .= "<tr><td class=\"table_row_even\" width=\"100\"><font size=\"2\">"._("Gruppe").":</font></td><td class=\"table_row_even\" width=\"250\"><a href=\"".URLHelper::getLink('?view=gruppen&filter='.$row['statusgruppe_id'])."\"><font size=\"2\">".htmlready($row['name'])."</font></a></td></tr>";
        }
    }
    return $output;
}

function ShowContact ($contact_id)
{   // Ausgabe eines Kontaktes
    global $open, $filter, $view;

    $query = "SELECT contact_id, user_id, buddy, calpermission
              FROM contact
              WHERE contact_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($contact_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if ($temp) {
        if ($open == $contact_id || $open == "all") {
            $rnd = rand(0,10000);

            // switch icon to display the users permission
            if (get_config('CALENDAR_GROUP_ENABLE') && $GLOBALS['perm']->get_perm($temp['user_id']) != 'root') {
                switch ($temp['calpermission']) {
                    case 2:
                        $calstatus .= Assets::img('icons/16/blue/visibility/calendar-visible.png', tooltip(_("Mein Kalender ist für diese Person sichtbar")) . ' class="text-top"');
                    break;
                    case 4:
                        $calstatus .= Assets::img('icons/16/red/schedule.png', tooltip(_("Mein Kalender ist für diese Person schreibbar")) . ' class="text-top"');
                    break;
                    default:
                        $calstatus .= Assets::img('icons/16/blue/visibility/calendar-invisible.png', tooltip(_("Mein Kalender ist für diese Person unsichtbar")) . ' class="text-top"');
                    break;
                }
                $calstatus .= '&nbsp;';
            } else {
                $calstatus = '';
            }

            if ($temp['buddy'] == '1') {
                $buddy = '<a href=' . URLHelper::getLink('#anker', array('view' => $view, 'cmd' => 'changebuddy', 'contact_id' => $contact_id, 'open' => $open, 'rnd' => $rnd)) . '">' . Assets::img('icons/16/red/person.png', array('class' => 'text-top', 'title' =>_('Als Buddy entfernen'))) . '</a>&nbsp; ';
            } else {
                $buddy = '<a href="' . URLHelper::getLink('#anker', array('view' => $view, 'cmd' => 'changebuddy', 'contact_id' => $contact_id, 'open' => $open, 'rnd' => $rnd)) . '">' . Assets::img('icons/16/blue/person.png', array('class' => 'text-top', 'title' =>_('Zu Buddies hinzufügen'))) . '</a>&nbsp; ';
            }
            $lastrow = "<tr><td colspan=\"2\" class=\"table_row_even\" align=\"right\">"
                        . $calstatus . $buddy
                        . '<a href="' . URLHelper::getLink('', array('edit_id' => $contact_id)) . '">' . Assets::img('icons/16/blue/edit.png', array('class' => 'text-top', 'title' => _('Editieren'))) . '</a> '
                        . '<a href="' . URLHelper::getLink('contact_export.php', array('contactid' => $contact_id)) . '">'
                        .  Assets::img('icons/16/blue/vcard.png', array('class' => 'text-top', 'title' => _("Als vCard exportieren")))
                        . ' <a href="' . URLHelper::getLink('', array('view' => $view, 'cmd' => 'delete', 'contact_id' => $contact_id, 'open' => $open)) . '">'
                        .  Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _("Kontakt löschen")))
                        . ' </a></td></tr>'
                        . '<tr><td colspan="2" class="table_row_odd" align="center"><a href="' . URLHelper::getLink('', array('view' => $view, 'filter' => $filter)) . '">'
                        . Assets::img('icons/16/blue/arr_1up.png', array('class' => 'text-top', 'title' =>_('Kontakte schließen')))
                        . '</a></td></tr>';
        } else {
            $link = '<a href="' . URLHelper::getLink('#anker', array('view' => $view, 'filter' => $filter, 'open' => $contact_id)) . '">'. Assets::img('icons/16/blue/arr_1down.png') . '</a>';
            $lastrow = '<tr><td colspan="3" class="table_row_odd" align="center">' . $link . '</td></tr>';
        }
        if ($open == $contact_id) {     //es ist ein einzelner Beitrag aufgeklappt, also Anker setzen
            $output = '<a name="anker"></a>';
        } else {
            $output = '';
        }
        $output .= "<table border=\"0\" cellspacing=\"0\" width=\"280\" class=\"blank\">
                    <tr>
                        <td class=\"table_header_bold\" width=\"99%\" style=\"font-weight:bold;\">"
                            . get_fullname($temp['user_id'], $format = "full_rev",true ) . '</td>'
                            . "<td class=\"table_header_bold\">"
                            // export to vcf
                            . '<a href="' . URLHelper::getLink('sms_send.php', array('sms_source_page' => 'contact.php', 'rec_uname' => get_username($temp['user_id']))) . '">' . Assets::img('icons/16/white/mail.png', array('class' => 'text-top', 'title' =>_('Nachricht schreiben'))) . '</a>'
                            . '</td>'
                            . "
                        </td>
                    </tr>
                    </table>
                    <table border=\"0\" cellspacing=\"0\" width=\"280\" class=\"blank\">"
                        . ShowUserInfo ($contact_id)
                        . $lastrow
                . '</table>';
    } else {
        $output = _("Fehler!");
    }
    return $output;
}

/**
 * Search for an user containing the passed string in his first name, last name
 * or username, excluding the searching user itself an all invisible users
 * Returns a HTML select-box
 *
 * @param  string  $search_exp  the search string to search for
 * @return string  a HTML select-box containing all results
 */
function SearchResults ($search_exp)
{

    $stmt = DBManager::get()->prepare('SELECT DISTINCT auth_user_md5.user_id, '
          . $GLOBALS['_fullname_sql']['full_rev'] .' AS fullname, username, perms '
          . 'FROM auth_user_md5 '
          . 'LEFT JOIN user_info USING (user_id) '
          . 'WHERE user_id != :user_id AND (Vorname LIKE :search_exp OR Nachname LIKE :search_exp '
          . 'OR username LIKE :search_exp) AND ' . get_vis_query() . ' '
          . 'ORDER BY Nachname');

    $search_for = '%'. $search_exp .'%';
    $stmt->bindParam(':search_exp', $search_for);
    $stmt->bindParam(':user_id', $GLOBALS['user']->id);
    $stmt->execute();

    $ret = false;

    while ($data = $stmt->fetch()) {
        $ret .= sprintf ('<option value="%s">%s - %s'. "\n",
            $data['username'],
            htmlReady(my_substr($data['fullname'], 0, 35) .' ('. $data['username'] . ')'),
            $data['perms']);
    }

    if (strlen($ret)) {
        $ret = '<select name="Freesearch">' . $ret . '</select>';
    }

    return $ret;
}

function ShowEditContact ($contact_id)
{   // Ausgabe eines zu editierenden Kontaktes
    global $open, $filter, $edit_id;
    $db = DBManager::get()->prepare('SELECT user_id FROM contact WHERE contact_id = ?');
    $db->execute(array($contact_id));
    $result = $db->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $css_switcher = new cssClassSwitcher();
        $output = "<table cellspacing=\"0\" cellpadding=\"3\" width=\"700\" class=\"blank\">
                    <tr>
                        <td class=\"table_header_bold_red\" colspan=\"3\">"
                            .get_fullname($result['user_id'], $format = "full_rev",true )."</td>"
                            ."
                        </td>
                    </tr>"
                        .ShowUserInfo ($contact_id)."</table><table cellspacing=\"0\" width=\"700\" class=\"blank\">"
                        .'<form action="' . URLHelper::getLink('', array('edit_id' => $contact_id)) . '" method="post">'
                        . CSRFProtection::tokenTag();
        if (Config::get()->getValue('CALENDAR_GROUP_ENABLE')) {
            $stmt = DBManager::get()->prepare('SELECT calpermission FROM contact WHERE owner_id = ? AND user_id = ?');
            $stmt->execute(array($GLOBALS['user']->id, $result['user_id']));
            $cal_perm = $stmt->fetch(PDO::FETCH_ASSOC);
            $css_switcher->switchClass();
            $output .= '<input type="hidden" name="user_id" value="' . $result['user_id'] . '">';
            $output .= '<tr><td class="' . $css_switcher->getClass() . '">&nbsp;' . _("Mein Kalender ist für diese Person:") . '</td><td colspan="2" class="' . $css_switcher->getClass() . '" width="250">';
            $output .= '<label><input type="radio" name="calperm" value="' . Calendar::PERMISSION_FORBIDDEN . '"' . (!$cal_perm['calpermission'] || $cal_perm['calpermission'] == Calendar::PERMISSION_FORBIDDEN ? ' checked="checked"' : '') . '>' . _("unsichtbar") . '</label>';
            $output .= '<label><input type="radio" name="calperm" value="' . Calendar::PERMISSION_READABLE . '"' . ($cal_perm['calpermission'] == Calendar::PERMISSION_READABLE ? ' checked="checked"' : '') . '>' . _("sichtbar") . '</label>';
            $output .= '<label><input type="radio" name="calperm" value="' . Calendar::PERMISSION_WRITABLE . '"' . ($cal_perm['calpermission'] == Calendar::PERMISSION_WRITABLE ? ' checked="checked"' : '') . '>' . _("schreibbar") . '</label>';
        }
        $db2 = DBManager::get()->prepare('SELECT * FROM contact_userinfo WHERE contact_id = ? ORDER BY priority');
        $db2->execute(array($contact_id));
        $i = 0;
        foreach ($db2->fetchAll(PDO::FETCH_ASSOC) as $result) {
            $css_switcher->switchClass();
            if ($i == 0) {
                $output .= '<tr><td class="' . $css_switcher->getClass() . '" width="100" nowrap="nowrap">&nbsp; <input type="hidden" name="userinfo_id[]" value="' . $result['userinfo_id'] . '"><input type="text" name="existingowninfolabel[]" value="' . htmlReady($result['name']) . '"></td><td class="' . $css_switcher->getClass() . '" width="250"><textarea name="existingowninfocontent[]" value="' . htmlReady($result['content']) . '" style="width: 90%" cols="20" rows="3" wrap="virtual">' . htmlReady($result['content']) . '</textarea></td><td class="' . $css_switcher->getClass() . '" width="50"><a href="' . URLHelper::getLink('', array('edit_id' => $contact_id, 'deluserinfo' => $result['userinfo_id'])) . '">' . Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _("Diesen Eintrag löschen"))) . "</a></td></tr>";
            } else {
                $output .= '<tr><td class="' . $css_switcher->getClass() . '" width="100" nowrap="nowrap">&nbsp; <input type="hidden" name="userinfo_id[]" value="' . $result['userinfo_id'] . '"><input type="text" name="existingowninfolabel[]" value="' . htmlReady($result['name']) . '"></td><td nowrap="nowrap" class="' . $css_switcher->getClass() . '" width="250"><textarea name="existingowninfocontent[]" value="' . htmlReady($result['content']) . '" style="width: 90%" cols="20" rows="3" wrap="virtual">' . htmlReady($result['content']) . '</textarea></td><td class="' . $css_switcher->getClass() . '" width="50" nowrap><a href="' . URLHelper::getLink('', array('edit_id' => $contact_id, 'deluserinfo' => $result['userinfo_id'])) . '">' . Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _("Diesen Eintrag löschen"))) . '</a>&nbsp; <a href="' . URLHelper::getLink('', array('edit_id' => $contact_id, 'move' => $result['userinfo_id'])) . '">' . Assets::img('icons/16/yellow/arr_2up.png', array('class' => 'text-top', 'title' =>_('Diesen Eintrag nach oben schieben')))."</a></td></tr>";
            }
            $i++;
        }
        if ($i == 0) { // noch nichts angelegt
            $output .= "<tr><td class=\"table_row_even\" colspan=\"3\">&nbsp;<font size=\"2\">"._("Sie können hier eigene Rubriken für diesen Kontakt anlegen:")."</font></td></tr>";
        }
        $css_switcher->switchClass();
        $output .= '<tr><td class="' . $css_switcher->getClass() . '">&nbsp; '
                    . '<input type="text" name="owninfolabel[]" value="' . _("Neue Rubrik") . '"></td>'
                    . '<td colspan="2" class="' . $css_switcher->getClass() . '"><textarea style="width: 90%" cols="20" rows="3" wrap="virtual" name="owninfocontent[]" value="Inhalt">' . _("Inhalt") . '</textarea>'
                    . "\n"
                    . '</td></tr>';
        $css_switcher->switchClass();
        $output .= '<tr><td valign="middle" colspan="3" class="' . $css_switcher->getClass()
                . '" align="center">' . LinkButton::create('<< ' . _('Zurück'),  URLHelper::getURL('#anker', array('open' => $contact_id)), array('title' => _('zurück zur Übersicht')))
                . '&nbsp; ' . Button::create(_('Übernehmen')) . '</form></td></tr>';
        $output .= '</table>';
    } else {
        $output = _("Fehler!");
    }
    return $output;
}

function MoveUserinfo($userinfo_id)
{
    $query = "SELECT contact_id, priority FROM contact_userinfo WHERE userinfo_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($userinfo_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    if ($temp) {
        $priority        = $temp['priority'];
        $priority_before = $priority - 1;
        $contact_id      = $temp['contact_id'];
    }

    $query = "SELECT userinfo_id FROM contact_userinfo WHERE contact_id = ? AND priority = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($contact_id, $priority_before));
    $userinfo_id_before = $statement->fetchColumn();

    $query = "UPDATE contact_userinfo SET priority = ? WHERE userinfo_id = ?";
    $statement = DBManager::get()->prepare($query);

    $statement->execute(array($priority_before, $userinfo_id));
    $statement->execute(array($priority, $userinfo_id_before));
}

function UpdateUserinfo($name, $content, $userinfo_id)
{
    $query = "UPDATE contact_userinfo SET name = ?, content = ? WHERE userinfo_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($name, $content, $userinfo_id));
}

function ResortUserinfo($contact_id)
{
    // resort the userinfos after deleting an item etc.
    $query = "SELECT userinfo_id FROM contact_userinfo WHERE contact_id = ? ORDER BY priority";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($contact_id));
    $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    $query = "UPDATE contact_userinfo SET priority = ? WHERE userinfo_id = ?";
    $update = DBManager::get()->prepare($query);

    foreach ($ids as $index => $id) {
        $update->execute(array($index, $id));
    }
}

function DeleteUserinfo ($userinfo_id)
{
    // loeschen einer Userinfo
    $query = "SELECT contact_id FROM contact_userinfo WHERE userinfo_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($userinfo_id));
    $contact_id = $statement->fetchColumn();

    if ($contact_id) {
        $query = "DELETE FROM contact_userinfo WHERE userinfo_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($userinfo_id));

        ResortUserinfo($contact_id);
    }
}

function DeleteContact ($contact_id)
{
    // loeschen eines Kontaktes
    global $user;

    $query = "SELECT owner_id, user_id FROM contact WHERE contact_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($contact_id));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    $output = false;

    if ($temp) {
        if ($temp['owner_id'] != $user->id) {
            $output = _('Sie haben kein Zugriffsrecht auf diesen Kontakt!');
        } else {
            $query = "DELETE FROM contact WHERE contact_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($contact_id));

            $query = "DELETE FROM contact_userinfo WHERE contact_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($contact_id));

            $query = "SELECT DISTINCT statusgruppe_id
                      FROM statusgruppen
                      LEFT JOIN statusgruppe_user USING (statusgruppe_id)
                      WHERE range_id = ? AND user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($temp['owner_id'], $temp['user_id']));
            $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

            if (count($ids)) {
                $query = "DELETE FROM statusgruppe_user WHERE statusgruppe_id IN (?) AND user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($ids, $temp['user_id']));
            }

            $output = _('Kontakt gelöscht');
        }
    }

    return $output;
}

function DeleteAdressbook($owner_id)
{
    // delete a complete guestbook (only needed when deleting user)
    $query = "SELECT contact_id FROM contact WHERE owner_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($owner_id));
    $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    if (count($ids)) {
        $query = "DELETE FROM contact_userinfo WHERE contact_id IN (?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($ids));
    }

    $query = "DELETE FROM contact WHERE owner_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($owner_id));

    return $statement->rowCount()
         ? sprintf(_('Adressbuch mit %d Einträgen gelöscht.'), count($ids))
         : false;
}

function PrintEditContact($edit_id)
{
    $query = "SELECT contact_id
              FROM contact
              LEFT JOIN auth_user_md5 USING (user_id)
              WHERE owner_id = ? AND contact_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($GLOBALS['user']->id, $edit_id));

    echo '<table class="blank" width="700" align="center" cellpadding="10"><tr><td valign="top" width="700" class="blank">';
    while ($id = $statement->fetchColumn()) {
        echo ShowEditContact($id);
        echo '<br>';
    }
    echo '</td></tr></table>';
}

function PrintAllContact($filter="")
{   global $user, $open, $filter, $contact, $auth;
    $i = 1;
    $owner_id = $user->id;

    if (in_array($contact['view'], words('alpha gruppen')) && $filter == '') {
        $query = "SELECT contact_id
                  FROM contact
                  LEFT JOIN auth_user_md5 USING (user_id)
                  WHERE owner_id = ?
                  ORDER BY nachname";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($owner_id));

    } else if ($contact['view'] == 'alpha' && $filter != '') {

        $query = "SELECT contact_id
                  FROM contact
                  LEFT JOIN auth_user_md5 USING (user_id)
                  WHERE owner_id = ? AND LEFT(nachname, 1) = ?
                  ORDER BY nachname";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($owner_id, $filter));

    } else if ($contact['view']=='gruppen' && $filter != '') {

        $query = "SELECT contact_id
                  FROM contact
                  LEFT JOIN statusgruppe_user USING (user_id)
                  LEFT JOIN auth_user_md5 USING (user_id)
                  WHERE statusgruppe_id = ? AND owner_id = ?
                  ORDER BY nachname";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($filter, $owner_id));
    }

    $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

    $spalten = 0;
    if ($auth->auth['xres'] > 800) { // TODO Remove this
        $maxcolls = 2;
        $maxwidth = 900;
        $middle[0] = ceil(count($ids) / 3);
        $middle[1] = round(count($ids) / 3);
        $middle[2] = floor(count($ids) / 3);
    } else {
        $maxcolls = 1;
        $maxwidth = 600;
        $middle[0] = ceil(count($ids) / 2);
    }

    if (!count($ids)) {
        echo "<table class=\"blank\" width=\"$maxwidth\" align=center cellpadding=\"10\"><tr><td valign=\"top\" width=\"300\" class=\"white\">"._("Keine Einträge in diesem Bereich")."";
        echo "</td><td valign=\"top\" width=\"300\" class=\"blank\">";
    } else {
        echo "<table class=\"blank\" width=\"$maxwidth\" align=center cellpadding=\"10\"><tr><td valign=\"top\" width=\"280\" class=\"white\">";

        $i = 1;
        foreach ($ids as $id) {
            echo ShowContact($id);
            echo '<br>';
            if ($i == $middle[$spalten] && $spalten != $maxcolls) { //Spaltenumbruch
                echo '</td><td valign="top" width="280" class="white">';
                $i = 0;
                $spalten++;
            }
            $i += 1;
        }
    }
    echo "</td></tr></table>";
}

// set the permission for the own calendar for the contact with the given user_id
function switch_member_cal ($user_id, $permission) {
    if (in_array($permission, array(Calendar::PERMISSION_FORBIDDEN, Calendar::PERMISSION_READABLE, Calendar::PERMISSION_WRITABLE))) {
        $stmt = DBManager::get()->prepare('UPDATE contact SET calpermission = ? WHERE owner_id = ? AND user_id = ?');
        $stmt->execute(array($permission, $GLOBALS['user']->id, $user_id));
    }
}
