<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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
    $db=new DB_Seminar;
    $tmp_id=md5(uniqid($hash_secret));
    $db->query ("SELECT contact_id FROM contact WHERE contact_id = '$tmp_id'");
    IF ($db->next_record())
        $tmp_id = MakeUniqueContactID(); //ID gibt es schon, also noch mal
    RETURN $tmp_id;
}

function MakeUniqueUserinfoID ()
{   // baut eine ID die es noch nicht gibt

    $hash_secret = "kertoiisdfgz";
    $db=new DB_Seminar;
    $tmp_id=md5(uniqid($hash_secret));
    $db->query ("SELECT userinfo_id FROM contact_userinfo WHERE userinfo_id = '$tmp_id'");
    IF ($db->next_record())
        $tmp_id = MakeUniqueContactID(); //ID gibt es schon, also noch mal
    RETURN $tmp_id;
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
{ global $user;
    $owner_id = $user->id;
    $user_id = get_userid($username);
    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db->query ("SELECT contact_id FROM contact WHERE owner_id = '$owner_id' AND user_id = '$user_id'");
    if ($db->next_record()) {
        $contact_id = $db->f("contact_id")  ;
        $db2->query("UPDATE contact SET buddy='0' WHERE contact_id = '$contact_id'");
    }
}

function RemoveUserFromBuddys($user_id)
{
    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db->query ("SELECT contact_id FROM contact WHERE user_id = '$user_id'");
    while ($db->next_record()) {   // erst mal die selbstzugefügten weg
        $contact_id = $db->f("contact_id")  ;
        $db2->query ("DELETE FROM contact_userinfo WHERE contact_id = '$contact_id'");
    }
    $db->query ("DELETE FROM contact WHERE user_id = '$user_id'");     // jetzt alle Zuordnungen
    $buddykills = $db->affected_rows();
    return $buddykills;
}

function CheckBuddy($username, $owner_id=FALSE)
{ global $user;
    if (!$owner_id)
        $owner_id = $user->id;
    $buddy = "";
    $user_id = get_userid($username);
    $db=new DB_Seminar;
    $db->query ("SELECT buddy FROM contact WHERE owner_id = '$owner_id' AND user_id = '$user_id' AND buddy = '1'");
    if ($db->next_record()) {
        $buddy = TRUE;
    } else {
        $buddy = FALSE;
    }
    return $buddy;
}

function GetNumberOfBuddies()
{
    global $user;
    $db=new DB_Seminar;
    $db->query("SELECT count(*) FROM contact WHERE owner_id = '$user->id' AND buddy=1");
    $db->next_record();
    return $db->f(0);
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
    $db=new DB_Seminar;
    $db->query("SELECT count(*) FROM contact WHERE owner_id = '$owner_id'");
    $db->next_record();
    return $db->f(0);
}

function GetSizeOfBookByGroup()
{   global $user;
    $ret = array();
    $owner_id = $user->id;
    $db = new DB_Seminar();
    $db->query("SELECT statusgruppe_id, count(*) AS anzahl FROM statusgruppen 
        LEFT JOIN statusgruppe_user USING(statusgruppe_id) 
        WHERE range_id = '$owner_id' GROUP BY statusgruppe_id");
    while ($db->next_record()){
        $ret[$db->f('statusgruppe_id')] = $db->f('anzahl');
    }
    return $ret;
}

function GetSizeOfBookByLetter()
{   global $user;
    $ret = array();
    $db = new DB_Seminar();
    $db->query("SELECT LCASE(LEFT(TRIM(Nachname),1)) AS first_letter, count(*) AS anzahl FROM contact LEFT JOIN auth_user_md5 USING(user_id)
                WHERE owner_id='$user->id' AND NOT ISNULL(Nachname) GROUP BY first_letter");
    while ($db->next_record()){
        $ret[$db->f('first_letter')] = $db->f('anzahl');
    }
    return $ret;
}

/**
 * When adding a buddy this way, triggers a BuddyDidAdd notification
 * using the new contact_id as subject.
 */
function AddBuddy($username)
{ global $user;

    $owner_id = $user->id;
    $user_id = get_userid($username);
    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db->query ("SELECT contact_id FROM contact WHERE owner_id = '$owner_id' AND user_id = '$user_id'");
    if ($db->next_record()) {
        $contact_id = $db->f("contact_id")  ;
        $affected = $db2->query("UPDATE contact SET buddy='1' WHERE contact_id = '$contact_id'");
        if ($affected) {
            NotificationCenter::postNotification('BuddyDidAdd', $contact_id);
        }
    }
}

function AddNewContact ($user_id)
{   // Inserting an new contact
    global $user;

    // get default permission if group calendar is enabled
    if (get_config('CALENDAR_GROUP_ENABLE')) {
        $calpermission = 'calpermission = ' . Calendar::PERMISSION_FORBIDDEN . ', ';
    } else {
        $calpermission = '';
    }

    $contact_id = MakeUniqueContactID();
    $owner_id = $user->id;
    $db=new DB_Seminar;
    $db->query ("SELECT contact_id FROM contact WHERE owner_id = '$owner_id' AND user_id = '$user_id'");
    if (!$db->next_record())    // nur wenn es die Kombination owner/user noch nicht gibt
        $db->query("INSERT INTO contact SET contact_id = '$contact_id', owner_id = '$owner_id', user_id= '$user_id', {$calpermission}buddy=0");
    return $contact_id;
}

function AddNewUserinfo ($contact_id, $name, $content)
{   // Inserting an new contact
    global $user;
    $userinfo_id = MakeUniqueUserinfoID();
    $db=new DB_Seminar;
    $db->query ("SELECT MAX(priority) as maximum FROM contact_userinfo WHERE contact_id = '$contact_id'");
    if ($db->next_record()) {
        $priority = $db->f("maximum")+1;
    }
    $db->query("INSERT INTO contact_userinfo SET userinfo_id = '$userinfo_id', contact_id = '$contact_id', name = '$name', content= '$content', priority= '$priority'");
    return $userinfo_id;
}

function GetExtraUserinfo ($contact_id)
{   // Build an array with extrauserinfos
        $output = "";
        $db=new DB_Seminar;
        $db->query ("SELECT * FROM contact_userinfo WHERE contact_id = '$contact_id' ORDER BY priority");
        while ($db->next_record())  {
            $userinfo[$db->f("name")] = $db->f("content");
        }
        return $userinfo;
}

function GetUserInfo($user_id)
{
    global $user;
    $db=new DB_Seminar;
    $db->query ("SELECT * FROM user_info WHERE user_id = '$user_id'");
    if ($db->next_record()) {
        if ($db->f("Home")!="")
            $userinfo[_("Homepage")] = formatLinks($db->f("Home"));
        if ($db->f("privatnr")!="")
            $userinfo[_("Tel. (privat)")] = htmlReady($db->f("privatnr"));
        if ($db->f("privatcell")!="")
            $userinfo[_("Mobiltelefon")] = htmlReady($db->f("privatcell"));
        if (get_config("ENABLE_SKYPE_INFO") && UserConfig::get($user_id)->SKYPE_NAME) {
            if(UserConfig::get($user_id)->SKYPE_ONLINE_STATUS){
                $img = sprintf('<img src="http://mystatus.skype.com/smallicon/%s" style="border: none;vertical-align:middle" width="16" height="16" alt="My status">', htmlReady(UserConfig::get($user_id)->SKYPE_NAME));
            } else {
                $img = '<img src="' . $GLOBALS['ASSETS_URL'] . 'images/icon_small_skype.gif" style="border: none;vertical-align:middle">';
            }
            $userinfo[_("Skype")] = sprintf('<a href="skype:%1$s?call">%2$s&nbsp;%1$s</a><br>',
                                    htmlReady(UserConfig::get($user_id)->SKYPE_NAME), $img);
        }
        if ($db->f("privadr")!="")
            $userinfo[_("Adresse")] = htmlReady($db->f("privadr"),1);

    }
    return $userinfo;
}

function GetinstInfo ($user_id)
{
    $db=new DB_Seminar;
    $i = 0;
    $query = "SELECT sprechzeiten, raum, user_inst.telefon, user_inst.fax, Name, ";
    $query .= "Institute.Institut_id FROM user_inst LEFT JOIN Institute USING(Institut_id) ";
    $query .= "WHERE user_id = '$user_id' AND inst_perms != 'user' AND visible = 1 ";
    $query .= "ORDER BY priority ASC";
    $db->query($query);
    while ($db->next_record()) {
        $userinfo[$i][_("Einrichtung")] = "<a href=\"institut_main.php?auswahl=".$db->f("Institut_id")."\">".htmlReady($db->f("Name"))."</a>";
        if ($gruppen = GetRoleNames(GetAllStatusgruppen($db->f("Institut_id"), $user_id)))
            $userinfo[$i][_("Funktion")] = htmlReady(join(", ", array_values($gruppen)));
        if ($db->f("raum")!="")
            $userinfo[$i][_("Raum")] = FormatReady($db->f("raum"));
        if ($db->f("sprechzeiten")!="")
            $userinfo[$i][_("Sprechzeiten")] = FormatReady($db->f("sprechzeiten"));
        if ($db->f("telefon")!="")
            $userinfo[$i][_("Tel. (dienstl.)")] =FormatReady($db->f("telefon"));
        if ($db->f("fax")!="")
            $userinfo[$i][_("Fax (dienstl.)")] = FormatReady($db->f("fax"));
        $i++;
    }
    return $userinfo;
}




function ShowUserInfo ($contact_id)
{   // Show the standard userinfo
    global $user, $open, $edit_id;

    $output = "";
    $basicinfo = array();
    $db=new DB_Seminar;
    $db->query ("SELECT user_id FROM contact WHERE contact_id = '$contact_id'");
    if ($db->next_record()) {
        $user_id = $db->f("user_id");
    }
    $db->query ("SELECT Email, username FROM auth_user_md5 WHERE user_id = '$user_id'");
    if ($db->next_record()) {
        $basicinfo[_("E-Mail")] = "<a href=\"mailto:".$db->f("Email")."\">".$db->f("Email")."</a>";
        $basicinfo["Stud.IP"] = "<a href=\"about.php?username=".$db->f("username")."\">".$db->f("username")." (".get_global_perm($user_id).")</a>";
    }

    // diese Infos hat jeder
    while(list($key,$value) = each($basicinfo)) {
        $output .= "<tr><td class=\"steelgraulight\" width=\"100\"><font size=\"2\">".$key.":</font></td><td class=\"steelgraulight\" width=\"250\"><font size=\"2\">".$value."</font></td></tr>";
    }

    // hier Zusatzinfos

    if (($open == $contact_id || $open == "all") && !$edit_id) {

        $userinfo = GetUserInfo($user_id);
        if (is_array($userinfo)) {
            while(list($key,$value) = each($userinfo)) {
                $output .= "<tr><td class=\"steel1\" width=\"100\"><font size=\"2\">".$key.":</font></td><td class=\"steel1\" width=\"250\"><font size=\"2\">".$value."</font></td></tr>";
            }
        }

        $userinstinfo = GetInstInfo($user_id);
        for ($i=0; $i <sizeof($userinstinfo); $i++) {
            while(list($key,$value) = each($userinstinfo[$i])) {
                $output .= "<tr><td class=\"steel1\" width=\"100\"><font size=\"2\">".$key.":</font></td><td class=\"steel1\" width=\"250\"><font size=\"2\">".$value."</font></td></tr>";
            }
        }

        $extra = GetExtraUserinfo ($contact_id);
        if (is_array($extra)) {
            while(list($key,$value) = each($extra)) {
                $output .= "<tr><td class=\"steel1\" width=\"100\"><font size=\"2\">".htmlReady($key).":</font></td><td class=\"steel1\" width=\"250\"><font size=\"2\">".formatReady($value)."</font></td></tr>";
            }
        }

        $output .= '<tr><td align="center" class="steel1" colspan="2" width="350"><br>'.Avatar::getAvatar($user_id)->getImageTag(Avatar::NORMAL).'</td>';
        $owner_id = $user->id;
        $db->query ("SELECT DISTINCT name, statusgruppen.statusgruppe_id FROM statusgruppen LEFT JOIN statusgruppe_user USING(statusgruppe_id) WHERE user_id = '$user_id' AND range_id= '$owner_id'");
        if ($db->num_rows()) {
            while ($db->next_record()) {
                $output .= "<tr><td class=\"steel1\" width=\"100\"><font size=\"2\">"._("Gruppe").":</font></td><td class=\"steel1\" width=\"250\"><a href=\"$PHP_SELF?view=gruppen&filter=".$db->f("statusgruppe_id")."\"><font size=\"2\">".htmlready($db->f("name"))."</font></a></td></tr>";
            }
        }
    }
    return $output;
}

function ShowContact ($contact_id)
{   // Ausgabe eines Kontaktes
    global $PHP_SELF, $open, $filter, $view;
    $db=new DB_Seminar;
    $db->query ("SELECT contact_id, user_id, buddy, calpermission FROM contact WHERE contact_id = '$contact_id'");
    if ($db->next_record()) {
        if ($open == $contact_id || $open == "all") {
            $rnd = rand(0,10000);

            // switch icon to display the users permission
            if (get_config('CALENDAR_GROUP_ENABLE') && $GLOBALS['perm']->get_perm($db->f('user_id') != 'root')) {
                switch ($db->f('calpermission')) {
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

            if ($db->f("buddy")=="1") {
                $buddy = '<a href=' . URLHelper::getLink('#anker', array('view' => $view, 'cmd' => 'changebuddy', 'contact_id' => $contact_id, 'open' => $open, 'rnd' => $rnd)) . '">' . Assets::img('icons/16/red/person.png', array('class' => 'text-top', 'title' =>_('Als Buddy entfernen'))) . '</a>&nbsp; ';
            } else {
                $buddy = '<a href="' . URLHelper::getLink('#anker', array('view' => $view, 'cmd' => 'changebuddy', 'contact_id' => $contact_id, 'open' => $open, 'rnd' => $rnd)) . '">' . Assets::img('icons/16/blue/person.png', array('class' => 'text-top', 'title' =>_('Zu Buddies hinzufügen'))) . '</a>&nbsp; ';
            }
            $lastrow = "<tr><td colspan=\"2\" class=\"steel1\" align=\"right\">"
                        . $calstatus . $buddy
                        . '<a href="' . URLHelper::getLink('', array('edit_id' => $contact_id)) . '">' . Assets::img('icons/16/blue/edit.png', array('class' => 'text-top', 'title' => _('Editieren'))) . '</a> '
                        . '<a href="' . URLHelper::getLink('contact_export.php', array('contactid' => $contact_id)) . '">'
                        .  Assets::img('icons/16/blue/vcard.png', array('class' => 'text-top', 'title' => _("Als vCard exportieren")))
                        . ' <a href="' . URLHelper::getLink('', array('view' => $view, 'cmd' => 'delete', 'contact_id' => $contact_id, 'open' => $open)) . '">'
                        .  Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _("Kontakt löschen")))
                        . ' </a></td></tr>'
                        . '<tr><td colspan="2" class="steelgraulight" align="center"><a href="' . URLHelper::getLink('', array('view' => $view, 'filter' => $filter)) . '">'
                        . Assets::img('icons/16/blue/arr_1up.png', array('class' => 'text-top', 'title' =>_('Kontakte schließen')))
                        . '</a></td></tr>';
        } else {
            $link = '<a href="' . URLHelper::getLink('#anker', array('view' => $view, 'filter' => $filter, 'open' => $contact_id)) . '">'. Assets::img('icons/16/blue/arr_1down.png') . '</a>';
            $lastrow = '<tr><td colspan="3" class="steelgraulight" align="center">' . $link . '</td></tr>';
        }
        if ($open == $contact_id) {     //es ist ein einzelner Beitrag aufgeklappt, also Anker setzen
            $output = '<a name=\"anker\"></a>';
        } else {
            $output = '';
        }
        $output .= "<table border=\"0\" cellspacing=\"0\" width=\"280\" class=\"blank\">
                    <tr>
                        <td class=\"blue_gradient\" width=\"99%\" style=\"font-weight:bold;\">"
                            . get_fullname($db->f("user_id"), $format = "full_rev",true ) . '</td>'
                            . "<td class=\"blue_gradient\">"
                            // export to vcf
                            . '<a href="' . URLHelper::getLink('sms_send.php', array('sms_source_page' => 'contact.php', 'rec_uname' => get_username($db->f("user_id")))) . '">' . Assets::img('icons/16/blue/mail.png', array('class' => 'text-top', 'title' =>_('Nachricht schreiben'))) . '</a>'
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
                        <td class=\"topicwrite\" colspan=\"3\">"
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
            $output .= "<tr><td class=\"steel1\" colspan=\"3\">&nbsp;<font size=\"2\">"._("Sie können hier eigene Rubriken für diesen Kontakt anlegen:")."</font></td></tr>";
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
    $db=new DB_Seminar;
    $db->query ("SELECT * FROM contact_userinfo WHERE userinfo_id = '$userinfo_id'");
    if ($db->next_record()) {
        $priority = $db->f("priority");
        $prioritybevore = $db->f("priority")-1;
        $contact_id = $db->f("contact_id");
    }
    $db->query ("SELECT * FROM contact_userinfo WHERE contact_id = '$contact_id' AND priority = '$prioritybevore'");
    if ($db->next_record()) {
        $userinfobevore_id = $db->f("userinfo_id");
    }
    $db->query("UPDATE contact_userinfo SET priority = '$prioritybevore' WHERE userinfo_id = '$userinfo_id'");
    $db->query("UPDATE contact_userinfo SET priority = '$priority' WHERE userinfo_id = '$userinfobevore_id'");
}

function UpdateUserinfo($name, $content, $userinfo_id)
{
    $db=new DB_Seminar;
    $db->query("UPDATE contact_userinfo SET name =  '$name', content = '$content' WHERE userinfo_id = '$userinfo_id'");
}

function ResortUserinfo($contact_id)
{   // resort the userinfos after deleting an item etc.
    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $i = 0;
    $db->query ("SELECT * FROM contact_userinfo WHERE contact_id = '$contact_id' ORDER BY priority");
    while ($db->next_record()) {
        $userinfo_id = $db->f("userinfo_id");
        $db2->query("UPDATE contact_userinfo SET priority =  '$i' WHERE userinfo_id = '$userinfo_id'");
        $i++;
    }
}

function DeleteUserinfo ($userinfo_id)
{   // loeschen einer Userinfo
    $db=new DB_Seminar;
    $db->query ("SELECT contact_id FROM contact_userinfo WHERE userinfo_id = '$userinfo_id'");
    if ($db->next_record()) {
        $contact_id = $db->f("contact_id");
    }
    $db->query ("DELETE FROM contact_userinfo WHERE userinfo_id = '$userinfo_id'");
    ResortUserinfo($contact_id);
}

function DeleteContact ($contact_id)
{   // loeschen eines Kontaktes
    global $user;
    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db3=new DB_Seminar;
    $db->query ("SELECT owner_id, user_id FROM contact WHERE contact_id = '$contact_id'");
    if ($db->next_record()) {
        if ($db->f("owner_id")!=$user->id) {
            $output = _("Sie haben kein Zugriffsrecht auf diesen Kontakt!");
        } else {
            $user_id = $db->f("user_id");
            $owner_id = $db->f("owner_id");
            $db->query ("DELETE FROM contact WHERE contact_id = '$contact_id'");
            $db->query ("DELETE FROM contact_userinfo WHERE contact_id = '$contact_id'");
            $db2->query ("SELECT DISTINCT statusgruppe_user.statusgruppe_id FROM statusgruppen LEFT JOIN statusgruppe_user USING(statusgruppe_id) WHERE range_id = '$owner_id' AND user_id = '$user_id'");
            WHILE ($db2->next_record()) {
                $statusgruppe_id = $db2->f("statusgruppe_id");
                $db3->query ("DELETE FROM statusgruppe_user WHERE statusgruppe_id = '$statusgruppe_id' AND user_id = '$user_id'");
            }
            $output = _("Kontakt gelöscht");
        }
    }
    return $output;
}

function DeleteAdressbook($owner_id) {
    // delete a complete guestbook (only needed when deleting user)
    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $i = 0;
    $db->query ("SELECT contact_id FROM contact WHERE owner_id = '$owner_id'");
    WHILE ($db->next_record()) {    // remove all selfmade entries
        $contact_id = $db->f("contact_id");
        $db2->query ("DELETE FROM contact_userinfo WHERE contact_id = '$contact_id'");
        $i++;
    }
    $db->query ("DELETE FROM contact WHERE owner_id = '$owner_id'");
    if ($db->affected_rows()) // remove all contacts
        $return = sprintf(_("Adressbuch mit %d Einträgen gelöscht."), $i);
    else
        $return = FALSE;
    return $return;
}

function PrintEditContact($edit_id)
{   global $user;
    $owner_id = $user->id;
    $db=new DB_Seminar;
    $db->query ("SELECT contact_id, nachname FROM contact LEFT JOIN auth_user_md5 using(user_id) WHERE owner_id = '$owner_id' AND contact_id = '$edit_id'");
    echo "<table class=\"blank\" width=\"700\" align=center cellpadding=\"10\"><tr><td valign=\"top\" width=\"700\" class=\"blank\">";
    while ($db->next_record()) {
            $contact_id = $db->f("contact_id");
            echo ShowEditContact ($contact_id);
            echo "<br>";
    }
    echo "</td></tr></table>";
}

function PrintAllContact($filter="")
{   global $user, $open, $filter, $contact, $auth;
    $i = 1;
    $owner_id = $user->id;
    $db=new DB_Seminar;

    if ($contact["view"]=="alpha" && $filter!="")
        $db->query ("SELECT contact_id, nachname FROM contact LEFT JOIN auth_user_md5 using(user_id) WHERE owner_id = '$owner_id' AND LEFT(nachname,1) = '$filter' ORDER BY nachname");
    if ($contact["view"]=="alpha" && $filter=="")
        $db->query ("SELECT contact_id, nachname FROM contact LEFT JOIN auth_user_md5 using(user_id) WHERE owner_id = '$owner_id' ORDER BY nachname");
    if ($contact["view"]=="gruppen" && $filter=="")
        $db->query ("SELECT contact_id, nachname FROM contact LEFT JOIN auth_user_md5 using(user_id) WHERE owner_id = '$owner_id' ORDER BY nachname");
    if ($contact["view"]=="gruppen" && $filter!="")
        $db->query ("SELECT nachname, contact_id FROM contact LEFT JOIN statusgruppe_user USING(user_id) LEFT JOIN auth_user_md5 USING(user_id)  WHERE statusgruppe_id = '$filter' AND owner_id =  '$owner_id' ORDER BY nachname");

    $spalten = 0;
    if ($auth->auth["xres"] > 800) {
        $maxcolls = 2;
        $maxwidth = 900;
        $middle[0] = ceil($db->num_rows()/3);
        $middle[1] = round($db->num_rows()/3);
        $middle[2] = floor($db->num_rows()/3);
    } else {
        $maxcolls = 1;
        $maxwidth = 600;
        $middle[0] = ceil($db->num_rows()/2);
    }

    if ($db->num_rows() == 0) {
        echo "<table class=\"blank\" width=\"$maxwidth\" align=center cellpadding=\"10\"><tr><td valign=\"top\" width=\"300\" class=\"white\">"._("Keine Einträge in diesem Bereich")."";
        echo "</td><td valign=\"top\" width=\"300\" class=\"blank\">";
    } else {
        echo "<table class=\"blank\" width=\"$maxwidth\" align=center cellpadding=\"10\"><tr><td valign=\"top\" width=\"280\" class=\"white\">";
        while ($db->next_record()) {
            $contact_id = $db->f("contact_id");
            echo ShowContact ($contact_id);
            echo "<br>";
            if ($i==$middle[$spalten] && $spalten!=$maxcolls) { //Spaltenumbruch
                echo "</td><td valign=\"top\" width=\"280\" class=\"white\">";
                $i = 0;
                $spalten++;
            }
        $i++;
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

?>
