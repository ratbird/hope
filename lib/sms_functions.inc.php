<?
# Lifter002: TODO
# Lifter005: TEST
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* several functions used for the systeminternal messages
*
* @author               Nils K. Windisch <studip@nkwindisch.de>
* @access               public
* @modulegroup  Messaging
* @module               sms_functions.inc.php
* @package          Stud.IP Core
*/
/*
sms_functions.inc.php -
Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Nils K. Windisch <info@nkwindisch.de>

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


/**
 * returns the key from a val
 *
 * @author          Nils K. Windisch <studip@nkwindisch.de>
 * @access          private
 */

function return_key_from_val($array, $val) {
    return array_search($val, $array);
}

/**
 * returns the val from a key
 *
 *
 * @author          Nils K. Windisch <studip@nkwindisch.de>
 * @access          private
 */

function return_val_from_key($array, $key) {
    return $array[$key];
}

/**
 *
 * @param array $message_hovericon
 * @return string
 */
function MessageIcon($message_hovericon)
{
    $hovericon = "<a href=\"".$message_hovericon['link']."\">".Assets::img($message_hovericon["picture"], array('class' => 'text-bottom'))."</a>";
    return $hovericon;
}

function count_x_messages_from_user($snd_rec, $folder, $where = '')
{
    global $user;

    if ($snd_rec == 'in' || $snd_rec == 'out') {
        $tmp_snd_rec = ($snd_rec == 'in') ? 'rec' : 'snd';
    } else {
        $tmp_snd_rec = $snd_rec;
    }

    $query = "SELECT COUNT(*)
              FROM message_user
              WHERE snd_rec = ? AND user_id = ? AND deleted = 0 ";
    $parameters = array($tmp_snd_rec, $user->id);

    if ($folder != 'all') {
        $query .= " AND message_user.folder = ? ";
        $parameters[] = $folder;
    }
    $query .= $where;

    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    return $statement->fetchColumn();
}

function count_messages_from_user($snd_rec, $where = '')
{
    global  $user;

    if ($snd_rec == 'in' || $snd_rec == 'out') {
        $tmp_snd_rec = ($snd_rec == 'in') ? 'rec' : 'snd';
    } else {
        $tmp_snd_rec = $snd_rec;
    }
    $query = "SELECT COUNT(*)
              FROM message_user
              WHERE snd_rec = ? AND user_id = ? AND deleted = 0 "
           . $where;

    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $tmp_snd_rec,
        $user->id
    ));
    return $statement->fetchColumn();

}

/**
 *
 * @param unknown_type $sms_show
 * @param unknown_type $value
 */
function show_icon($sms_show, $value)
{
    if ($sms_show == $value) {
        $x = 'icons/16/red/arr_1right.png';
    } else {
        $x = "blank.gif";
    }
    return $x;
}

/**
 *
 * @param unknown_type $tmp
 * @param unknown_type $count
 */
function showfoldericon($tmp, $count)
{
    global $sms_show, $sms_data;

    if ($count == "0" && folder_openclose($sms_show['folder'][$sms_data['view']], $tmp) == "close") {
        $picture = "icons/16/blue/folder-empty.png";
    } else if ($count == "0" && folder_openclose($sms_show['folder'][$sms_data['view']], $tmp) == "open") {
        $picture = "icons/16/blue/folder-empty.png";
    } else if ($count != "0" && folder_openclose($sms_show['folder'][$sms_data['view']], $tmp) == "close") {
        $picture = "icons/16/blue/folder-full.png";
    } else if ($count != "0" && folder_openclose($sms_show['folder'][$sms_data['view']], $tmp) == "open") {
        $picture = "icons/16/blue/folder-full.png";
    }
    return $picture;
}

function folder_makelink($tmp) {
    global $sms_show, $sms_data;
    if (folder_openclose($sms_show['folder'][$sms_data['view']], $tmp) == "open") {
        $link = URLHelper::getLink('?show_folder=close');
    } else {
        $link = URLHelper::getLink('?show_folder='.$tmp);
    }
    return $link;
}

function folder_openclose($folder, $x) {
    if ($folder == $x) {
        $tmp = "open";
    } else {
        $tmp = "close";
    }
    return $tmp;
}

// print_snd_message
function print_snd_message($psm) {
    global $n, $my_messaging_settings, $msging, $sms_data, $user, $_fullname_sql, $cmd_show, $cmd;

    $db = DBManager::get();

    // open?!
    if ($sms_data["open"] == $psm['message_id']) {
        $open = "open";
        $link = URLHelper::getLink('?mclose=TRUE');
    } else if ($cmd_show == "openall" || $my_messaging_settings["openall"] == "1") {
        $open = "open";
        $link = URLHelper::getLink('?mopen='.$psm['message_id'].'#'.$psm['message_id']);
    } else {
        $open = "close";
        $link = URLHelper::getLink('?mopen='.$psm['message_id'].'#'.$psm['message_id']);
    }

    // make message_header
    $x = $psm['num_rec']; // how many receivers are there?
    if ($psm['dont_delete'] == "1") { // disable the checkbox if message is locked
        $tmp_cmd = "open_selected";
        $tmp_picture = "icons/16/blue/lock-locked.png";
        $tmp_tooltip = tooltip(_("L�schschutz deaktivieren."));
        $trash =  Assets::img('blank.gif', array('width' => '16'));
    } else {
        $tmp_cmd = "safe_selected";
        $tmp_picture = "icons/16/blue/lock-unlocked.png";
        $tmp_tooltip = tooltip(_("L�schschutz f�r diese Nachricht aktivieren."));
        $trash = "<a href=\"".URLHelper::getLink('?cmd=delete_selected&sel_sms[1]='.$psm['message_id'])."\">" .  Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _("Diese Nachricht l�schen."))) . "</a> ";
    }

    if ($x == 1) { // if only one receiver
        $zusatz .= sprintf(_("an %s, %s"), "</font><a href=\"dispatch.php/profile?username=".$psm['rec_uname']."\"><font size=-1 color=\"#333399\">".htmlReady($psm['rec_vorname'])."&nbsp;".htmlReady($psm['rec_nachname'])."</font></a><font size=-1>", date("d.m.y, H:i",$psm['mkdate']));
        $zusatz .= "&nbsp;";
        $zusatz .= "<a href=\"".URLHelper::getLink('?cmd='.$tmp_cmd.'&sel_lock='.$psm['message_id'].'#'.$psm['message_id'])."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$tmp_picture."\" ".$tmp_tooltip."></a> ".$trash." <input type=\"checkbox\" name=\"sel_sms[]\" value=\"".$psm['message_id']."\" ".CheckChecked($cmd, "select_all").">";
    } else if ($x >= "2") { // if more than one receiver
        $zusatz .= sprintf(_("an %s Empf&auml;nger, %s"), $x, date("d.m.y, H:i",$psm['mkdate']));
        $zusatz .= "&nbsp;";
        $zusatz .= "<a href=\"".URLHelper::getLink('?cmd='.$tmp_cmd.'&sel_lock='.$psm['message_id'].'#'.$psm['message_id'])."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$tmp_picture."\" ".$tmp_tooltip."></a> ".$trash." <input type=\"checkbox\" name=\"sel_sms[]\" value=\"".$psm['message_id']."\" ".CheckChecked($cmd, "select_all").">";
    }

    if (have_msgfolder($sms_data['view']) == TRUE) {
        $zusatz .= " <a href=\"".URLHelper::getLink('?move_to_folder[1]='.$psm['message_id'])."\">";
        $zusatz .= Assets::img('icons/16/blue/move_right/folder-empty.png', array('class' => 'text-top', 'title' => _("Diese Nachricht in einen frei w�hlbaren Ordner verschieben.")));
        $zusatz .= "</a> ";
    }

    if ($open == "open") {
        $content = formatReady($psm['message']);
        if ($psm['num_attachments']) {
            $content.= "<br>--<br>";
            foreach (get_message_attachments($psm["message_id"]) as $key => $attachment) {
                $content.= "\n<a href=\"" . GetDownloadLink($attachment["dokument_id"], $attachment["name"], 7) . "\">";
                $content.= "&nbsp;". GetFileIcon(getFileExtension($attachment["name"]), true);
                $content.= "&nbsp;" . htmlready($attachment["name"]);
                $content.= "&nbsp;(" . ($attachment["filesize"] / 1024 / 1024 >= 1 ? round($attachment["filesize"] / 1024 / 1024) ." Mb" : round($attachment["filesize"] / 1024)." Kb");
                $content.= ")</a><br>";
            }
        }
        if ($x >= 2) { // if more than one receiver add appendix
            $content .= "<br><br>--<br>"._("gesendet an:")."<br>";
            $query = "SELECT auth_user_md5.username, {$_fullname_sql['full']} AS fullname
                      FROM message_user
                      LEFT JOIN auth_user_md5 USING(user_id)
                      LEFT JOIN user_info USING(user_id)
                      WHERE message_user.message_id = ? AND message_user.snd_rec = 'rec'";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($psm['message_id']));

            $i = 0;
            while ($row = $statement->fetch()) {
                if ($row["user_id"] != $user->id && $row["username"] != "") {
                    if ($i > "0") {
                        $content .= ",&nbsp;";
                    }
                    $content .= "<a href=\"dispatch.php/profile?username=".$row["username"]."\"><font size=-1 color=\"#333399\">".htmlReady($row["fullname"])."</font></a>";
                    ++$i;
                } else {
                    $msg_sndnote = _("und an Sie selbst");
                }
            }
            if ($msg_sndnote) {
                $content .= "&nbsp;".$msg_sndnote;
                unset($msg_sndnote);
            }
        }

        // buttons
        $edit = '<div class="button-group">';
        $edit .= LinkButton::create(_('Weiterleiten'), URLHelper::getUrl('sms_send.php', array('cmd' => 'write', 'quote' => $psm['message_id'], 'forward' => 'snd')));
        $edit .= LinkButton::create(_('Drucken'),  URLHelper::getUrl('dispatch.php/messages/show_print/' . $psm['message_id'] . '/snd'), array('target' => '_blank', 'class' => 'print_action'));
        $edit .= LinkButton::create(_('L�schen'), URLHelper::getURL("?cmd=delete_selected", array('sel_sms[1]' => $psm['message_id'])), array('title' => _('Diese Nachricht l�schen.')));
        if (have_msgfolder($sms_data['view']) == TRUE) {
            $edit .= LinkButton::create(_('Verschieben'), URLHelper::getURL('', array('move_to_folder[1]' => $psm['message_id'])), array('title' => _('Diese Nachricht in einen frei w�hlbaren Ordner verschieben.')));
        }
        $edit .= '</div>';
    }

    if ($psm['num_attachments'])
        $attachment_icon = Assets::img('icons/16/grey/staple.png', array('class' => 'text-top', 'title' => _("Diese Nachricht enth�lt einen Dateianhang.")));
    else
        $attachment_icon = "";

    $ajax_link = URLHelper::getLink('dispatch.php/messages/get_msg_body'
               . '/'.$psm['message_id']
               . '/'.(int)($open != 'open')
               . '/'.$psm['count']);
    $custom_data = "data-behaviour=\"'ajaxContent'\" "
                 . "data-target=\"'#msg_item_{$psm['message_id']}'\" "
                 . "data-url=\"'{$ajax_link}'\"";
    $subject = $psm['message_subject'];
    if (strlen($subject) > 80) {
        $custom_data .= sprintf(' title="%s"', htmlReady($subject));
        $subject = mila($subject, 80);
    }

    $titel = "<a name=\"{$psm['message_id']}\" "
           . "href=\"{$link}\" "
           . "class=\"tree\" "
           . $custom_data . "> "
           . htmlReady($subject)
           . "{$attachment_icon}</a>";
    $message_hovericon['titel'] = $psm['message_subject'];
    // (hover) icon
    $message_hovericon['openclose'] = $open;
    $message_hovericon['content'] = $psm['message'];
    $message_hovericon['id'] = $psm['message_id'];
    $message_hovericon['picture'] = 'icons/16/blue/mail.png';
    $icon = MessageIcon($message_hovericon);
    // print message_header
    echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\" class=\"table_row_even\"><tr>";
    if ($psm['count'] == "0" || sizeof($sms_data['tmp']['move_to_folder']) == "1" || $psm['count_2'] == "0") {
        $tmp_line1 = "forumstrich2.gif";
        $tmp_line2 = "blank.gif";
    } else {
        $tmp_line1 = "forumstrich3.gif";
        $tmp_line2 = "forumstrich.gif";
    }
    echo "<td class=\"blank tree-indent\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$tmp_line1."\"></td>";
    printhead(0, 0, $link.'" class="'.$ajax_classes.'" '.$custom_data.'"', $open, FALSE, $icon, $titel, $zusatz, $psm['mkdate']);
    echo "</tr></table> ";
    // print content
    if (($open == "open") || ($psm['sms_data_open'] == $psm['message_id'])) {
        echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
        echo "<td class=\"blank\" background=\"".$GLOBALS['ASSETS_URL']."images/".$tmp_line2."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"100%\" width=\"10px\"></td>";
        printcontent("99%",0, $content, $edit);
        echo "</tr></table> ";
    }
    return $n++;
}

// print_rec_message
function print_rec_message($prm) {
    global $n, $my_messaging_settings, $msging, $sms_show, $sms_data, $user, $cmd_show, $cmd;

    // build
    if ($prm['readed'] != "1" && $my_messaging_settings["opennew"] == "1") { // open if unread
        $open = "open";
        $link = URLHelper::getLink('?mclose=TRUE');
    } else if ($sms_data["open"] == $prm['message_id']) {
        $open = "open";
        $link = URLHelper::getLink('?mclose=TRUE');
    } else if ($cmd_show == "openall" || $my_messaging_settings["openall"] == "1") {
        $open = "open";
        $link = URLHelper::getLink('?mopen='.$prm['message_id'].'#'.$prm['message_id']);
    } else {
        $open = "close";
        $link = URLHelper::getLink('?mopen='.$prm['message_id'].'#'.$prm['message_id']);
    }
    if ($prm['readed'] == "1") { // unread=new ... is message new? if new and opened=set readed
        $red = FALSE;
        if ($prm['answered'] == 1) {
            $picture = "icons/16/blue/move_right/mail.png";
        } else {
            $picture = 'icons/16/blue/mail.png';
        }
    } else {
        $red = TRUE;
        $picture = 'icons/16/red/new/mail.png';
        if ($open == "open") $msging->set_read_message($prm['message_id']);
    }
    if ($prm['dont_delete'] == "1") { // disable the checkbox if message is locked
        $tmp_cmd = "open_selected";
        $tmp_picture = "icons/16/blue/lock-locked.png";
        $tmp_tooltip = tooltip(_("L�schschutz deaktivieren."));
        $trash =  Assets::img('blank.gif', array('width' => '16'));
    } else {
        $tmp_cmd = "safe_selected";
        $tmp_picture = "icons/16/blue/lock-unlocked.png";
        $tmp_tooltip = tooltip(_("L�schschutz f�r diese Nachricht aktivieren."));
        $trash = "<a href=\"".URLHelper::getLink('?cmd=delete_selected&sel_sms[1]='.$prm['message_id'])."\">" .  Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' => _("Diese Nachricht l�schen."))) . "</a> ";
    }
    // zusatz
    if (have_msgfolder($sms_data['view']) == TRUE) {
        $move_option = "<a href=\"".URLHelper::getLink('?move_to_folder[1]='.$prm['message_id'])."\">";
        $move_option .= Assets::img('icons/16/blue/move_right/folder-empty.png', array('class' => 'text-top', 'title' => _("Diese Nachricht in einen frei w�hlbaren Ordner verschieben."))) . "</a> ";
    }
    $zusatz = "<font size=-1>";
    if ($prm['user_id_snd'] == "____%system%____") {
        $zusatz .= _("automatische Systemnachricht, ");
    } else {
        $zusatz .= sprintf(_("von %s, "), "</font><a href=\"dispatch.php/profile?username=".$prm['uname_snd']."\"><font size=-1 color=\"#333399\">".htmlReady($prm['vorname'])."&nbsp;".htmlReady($prm['nachname'])."</font></a><font size=-1>");
    }
    $zusatz .= date("d.m.y, H:i", $prm['mkdate']);
    $zusatz .= " ".$move_option."<a href=\"".URLHelper::getLink('?cmd='.$tmp_cmd.'&sel_lock='.$prm['message_id'].'#'.$prm['message_id'])."\"><img class=\"text-top\" src=\"".$GLOBALS['ASSETS_URL']."images/".$tmp_picture."\" ".$tmp_tooltip."></a> ".$trash." <input type=\"checkbox\" name=\"sel_sms[]\" value=\"".$prm['message_id']."\" ".CheckChecked($cmd, "select_all").">";
    $zusatz .= "</font>";

    if ($prm["num_attachments"])
        $attachment_icon = Assets::img('icons/16/grey/staple.png', array('class' => 'text-top', 'title' => _("Diese Nachricht enth�lt einen Dateianhang.")));
    else
        $attachment_icon = "";

    $ajax_link = URLHelper::getLink('dispatch.php/messages/get_msg_body'
               . '/'.$prm['message_id']
               . '/'.(int)($open != 'open')
               . '/'.$prm['count']);
    $custom_data = "data-behaviour=\"'ajaxContent'\" "
                 . "data-target=\"'#msg_item_{$prm['message_id']}'\" "
                 . "data-url=\"'{$ajax_link}'\"";
    $subject = $prm['message_subject'];
    if (strlen($subject) > 80) {
        $custom_data .= sprintf(' title="%s"', htmlReady($subject));
        $subject = mila($subject, 80);
    }

    $titel = "<a name=\"{$prm['message_id']}\" "
           . "href=\"{$link}\" "
           . "class=\"tree\" "
           . $custom_data . '"> '
           . htmlReady($subject)."{$attachment_icon}</a>";

    if ($open == 'open'){
        $content = formatReady($prm['message']);
        if ($prm["num_attachments"]) {
            $content.= "<br>--<br>";
            foreach (get_message_attachments($prm['message_id']) as $key => $attachment) {
                $content.= "\n<a href=\"" . GetDownloadLink($attachment["dokument_id"], $attachment["name"], 7) . "\">";
                $content.= "&nbsp;". GetFileIcon(getFileExtension($attachment["name"]), true);
                $content.= "&nbsp;" . htmlready($attachment["name"]);
                $content.= "&nbsp;(" . ($attachment["filesize"] / 1024 / 1024 >= 1 ? round($attachment["filesize"] / 1024 / 1024) ." Mb" : round($attachment["filesize"] / 1024)." Kb");
                $content.= ")</a><br>";
            }
        }
        if ($my_messaging_settings["confirm_reading"] != 1 && $prm['message_reading_confirmation'] == 1) { // yeah i'm interested in readingconfirmations and the message has a readingrequested
            if ($my_messaging_settings["confirm_reading"] == 3 && $prm['confirmed_read'] != 1) { // let me decided what to do
                $content .= "<br>--<br>"._("Der Absender / Die Absenderin hat eine Lesebest�tigung angefordert.");
                $content .= "<br><a href=\"".URLHelper::getLink('?readingconfirmation='.$prm['message_id'].'&uname_snd='.$prm['uname_snd'].'#'.$prm['message_id'])."\">"._("Klicken Sie hier um das Lesen der Nachricht zu best�tigen")."</a>";
            } else if ($my_messaging_settings["confirm_reading"] == 2 && $prm['confirmed_read'] != 1) { // automatic confirm my reading and don't nag me
                $query = "UPDATE message_user
                          SET confirmed_read = 1
                          WHERE message_id = ? AND user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $prm['message_id'],
                    $user->id
                ));
                if ($statement->rowCount() > 0) {
                    $user_fullname = get_fullname($user->id);
                    $subject = sprintf (_('Lesebest�tigung von %s'), $user_fullname);
                    $message = sprintf (_('Ihre Nachricht an %s mit dem Betreff: %s vom %s wurde gelesen.'),
                                        '%%' . $user_fullname . '%%',
                                        '%%' . $prm['message_subject'] . '%%',
                                        '%%' . date('d.m.y, H:i', $prm['mkdate']) . '%%');
                    $msging->insert_message(mysql_escape_string($message), $prm['uname_snd'], '____%system%____', FALSE, FALSE, 1, FALSE, mysql_escape_string($subject));
                }
            }
        }

        $tmp_snd_id = get_userid($prm['uname_snd']);
        if ($prm['user_id_snd'] != '____%system%____') {
            $content = "<table width=\"100%\" cellpadding=0 cellmargin=0><tr><td valign=\"top\" width=\"99%\"><font size=\"-1\">".$content."</font><td>";
            $content .= "<td align=\"right\" style=\"border-left: 1px dotted black;\">&nbsp;";
            $content .= Avatar::getAvatar($tmp_snd_id)->getImageTag(Avatar::MEDIUM);
            $content .= "&nbsp;</td></tr></table>";
        }

        // mk buttons
        $edit = '<div class="button-group">';
        if ($prm['user_id_snd'] != "____%system%____") {
            $edit .= LinkButton::create(_('Antworten'), URLHelper::getURL('sms_send.php', array('cmd'=> 'write', 'answer_to' => $prm['message_id'])));
            $edit .= LinkButton::create(_('Zitieren'), URLHelper::getURL('sms_send.php', array('cmd' => 'write', 'quote' => $prm['message_id'], 'answer_to' => $prm['message_id'])));
        }
        $edit .= LinkButton::create(_('Weiterleiten'), URLHelper::getUrl('sms_send.php', array('cmd' => 'write', 'quote' => $prm['message_id'], 'forward' => 'rec')));
        $edit .= LinkButton::create(_('Drucken'),  URLHelper::getUrl('dispatch.php/messages/show_print/' . $prm['message_id'] . '/rec'), array('target' => '_blank', 'class' => 'print_action'));
        $edit .= LinkButton::create(_('L�schen'), URLHelper::getURL('', array('cmd' => 'delete_selected', "sel_sms[1]" => $prm['message_id'])));
        if (have_msgfolder($sms_data['view']) == TRUE) {
            $edit .= LinkButton::create(_('Verschieben'), URLHelper::getURL('', array('move_to_folder[1]'=> $prm['message_id'])), array('title' => _('Diese Nachricht in einen frei w�hlbaren Ordner verschieben.')));
        }
        $edit .= '</div>';
    }
    // (hover) icon
    $message_hovericon['titel'] = $prm['message_subject'];
    $message_hovericon['openclose'] = $open;
    $message_hovericon['content'] = $prm['message'];
    $message_hovericon['id'] = $prm['message_id'];
    $message_hovericon['link'] = $link.'" class="'.$ajax_classes.'" '.$custom_data;
    $message_hovericon["picture"] = $picture;
    $icon = MessageIcon($message_hovericon);
    // print message_header
    if ($prm['count'] <= "0" || sizeof($sms_data['tmp']['move_to_folder'])== "1" || $prm['count_2'] == "0") {
        $tmp_line1 = "forumstrich2.gif";
        $tmp_line2 = "blank.gif";
    } else {
        $tmp_line1 = "forumstrich3.gif";
        $tmp_line2 = "forumstrich.gif";
    }
    echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\" class=\"table_row_even\"><tr>";
    echo "<td class=\"blank tree-indent\">".Assets::img($tmp_line1, array('class' => 'text-bottom'))."</td>";

    // if messages with priority are enabled, we pass a content_title_red css-class
    if ($GLOBALS['MESSAGE_PRIORITY'] && ($prm['priority'] == 'high')) {
        printhead(0, 0, $link.'" class="'.$ajax_classes.'" '.$custom_data, $open, $red, $icon, $titel, $zusatz, $prm['mkdate'], '', 'age', 'content_title_red');
    } else {
        printhead(0, 0, $link.'" class="'.$ajax_classes.'" '.$custom_data, $open, $red, $icon, $titel, $zusatz, $prm['mkdate'], TRUE, "");
    }
    echo "</tr></table> ";
    // print message content
    if (($open == "open") || ($sms_data["open"] == $prm['message_id'])) {
        echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
        echo "<td class=\"blank\" background=\"".$GLOBALS['ASSETS_URL']."images/".$tmp_line2."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"100%\" width=\"10px\"></td>";
        printcontent("99%", 0, $content, $edit);
        echo "</tr></table> ";
    }
    return $n++;
}

function print_messages() {
    global $user,$_fullname_sql, $my_messaging_settings, $sms_data, $sms_show, $query_showfolder, $query_time_sort, $query_movetofolder, $query_time, $srch_result, $no_message_text, $n, $count, $count_timefilter, $cmd, $cmd_show;

    if ($query_time) {
        $count = $count_timefilter;
    }
    $n = 0;
    $user_id = $user->id;

    if ($sms_data['view'] == "in") { // postbox in
        $tmp_move_to_folder = sizeof($sms_data['tmp']['move_to_folder']);

        $query = "SELECT message.*, folder, confirmed_read, answered,
                         message_user.readed, dont_delete, Vorname, Nachname, username,
                         COUNT(dokument_id) AS num_attachments
                  FROM message_user
                  LEFT JOIN message USING (message_id)
                  LEFT JOIN auth_user_md5 ON (autor_id=auth_user_md5.user_id)
                  LEFT JOIN dokumente ON range_id=message_user.message_id
                  WHERE message_user.user_id = ? AND message_user.snd_rec = 'rec'
                    AND message_user.deleted = 0 {$query_movetofolder} {$query_showfolder} {$query_time}
                  GROUP BY message_user.message_id
                  ORDER BY message_user.mkdate DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $count -= 1;
            $prm['count']           = $count;
            $prm['count_2']         = $tmp_move_to_folder - ($n + 1);
            $prm['user_id_snd']     = $row['autor_id'];
            $prm['folder']          = $my_messaging_settings['folder']['active']['in'];
            $prm['mkdate']          = $row['mkdate'];
            $prm['message_id']      = $row['message_id'];
            $prm['message_subject'] = $row['subject'];
            $prm['message_reading_confirmation'] = $row['reading_confirmation'];
            $prm['confirmed_read']  = $row['confirmed_read'];
            $prm['answered']        = $row['answered'];
            $prm['message']         = $row['message'];
            $prm['vorname']         = $row['Vorname'];
            $prm['nachname']        = $row['Nachname'];
            $prm['readed']          = $row['readed'];
            $prm['dont_delete']     = $row['dont_delete'];
            $prm['uname_snd']       = $row['username'];
            $prm['priority']        = $row['priority'];
            $prm['num_attachments'] = $row['num_attachments'];

            ob_start();
            echo '<div id="msg_item_'.$prm['message_id'].'">' ;
            print_rec_message($prm);
            echo '</div>';
            ob_end_flush();
        }
    } else if ($sms_data['view'] == "out") { // postbox out
        $tmp_move_to_folder = sizeof($sms_data['tmp']['move_to_folder']);

        $query = "SELECT message.*, message_user.folder, message_user.dont_delete ,
                         auth_user_md5.user_id AS rec_uid, auth_user_md5.vorname AS rec_vorname,
                         auth_user_md5.nachname AS rec_nachname, auth_user_md5.username AS rec_uname,
                         COUNT(DISTINCT mu.user_id) AS num_rec, COUNT(dokument_id) AS num_attachments
                  FROM message_user
                  LEFT JOIN message_user AS mu ON (message_user.message_id = mu.message_id AND mu.snd_rec = 'rec')
                  LEFT JOIN message ON (message.message_id = message_user.message_id)
                  LEFT JOIN auth_user_md5 ON (mu.user_id = auth_user_md5.user_id)
                  LEFT JOIN dokumente ON (range_id = message_user.message_id)
                  WHERE message_user.user_id = ?
                    AND message_user.snd_rec = 'snd' AND message_user.deleted = 0
                        {$query_movetofolder} {$query_showfolder} {$query_time_sort}
                  GROUP BY message_user.message_id
                  ORDER BY message_user.mkdate DESC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $count -= 1;
            $psm['count']           = $count;
            $psm['count_2']         = $tmp_move_to_folder - ($n+1);
            $psm['mkdate']          = $row['mkdate'];
            $psm['folder']          = $my_messaging_settings['folder']['active']['out'];
            $psm['message_id']      = $row['message_id'];
            $psm['message_subject'] = $row['subject'];
            $psm['message']         = $row['message'];
            $psm['dont_delete']     = $row['dont_delete'];
            $psm['rec_uid']         = $row['rec_uid'];
            $psm['rec_vorname']     = $row['rec_vorname'];
            $psm['rec_nachname']    = $row['rec_nachname'];
            $psm['rec_uname']       = $row['rec_uname'];
            $psm['num_rec']         = $row['num_rec'];
            $psm['num_attachments'] = $row['num_attachments'];

            ob_start();
            echo '<div id="msg_item_'.$psm['message_id'].'">' ;
            print_snd_message($psm);
            echo '</div>';
            ob_end_flush();
        }
    }
    if (!$n) { // wenn keine nachrichten zum anzeigen
        echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\">";
        $srch_result = "info�".$no_message_text;
        parse_msg ($srch_result, "�", "table_row_even", 2, FALSE);
        echo "</td></tr></table>";
    }
}

function ajax_show_body($mid)   {
    global  $my_messaging_settings, $_fullname_sql, $user, $n, $count, $sms_data, $query_time, $query_movetofolder,$sms_show, $query_time_sort, $srch_result, $no_message_text, $count_timefilter;
    if ($query_time) {
        $count = $count_timefilter;
    }
    $n = 0;
    $user_id = $user->id;

    if ($sms_data['view'] == 'in') {
        $query = "SELECT message.*, folder, confirmed_read, answered, message_user.readed,
                         dont_delete, Vorname, Nachname, username,
                         COUNT(dokument_id) AS num_attachments
                  FROM message_user
                  LEFT JOIN message USING (message_id)
                  LEFT JOIN auth_user_md5 ON (autor_id = auth_user_md5.user_id)
                  LEFT JOIN dokumente ON (range_id = message_user.message_id)
                  WHERE message_user.user_id = :user_id AND message_user.snd_rec = 'rec'
                    AND message_user.deleted = 0
                    AND message.message_id = :mid
                  GROUP BY message_user.message_id";
        $stmt = DBManager::get()->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':mid', $mid);
        $stmt->execute();

        $tmp_move_to_folder = sizeof($sms_data['tmp']['move_to_folder']);
        $row = $stmt->fetch();

        $prm['folder']          = $my_messaging_settings['folder']['active']['in'];
        $prm['answered']        = $row['answered'];
        $prm['vorname']         = $row['Vorname'];
        $prm['nachname']        = $row['Nachname'];
        $prm['readed']          = $row['readed'];
        $prm['dont_delete']     = $row['dont_delete'];
        $prm['priority']        = $row['priority'];
        $prm['num_attachments'] = $row['num_attachments'];
        $prm['count_2']         = $tmp_move_to_folder - ($n+1);
        $prm['count']           = (int)$count;
        $prm['message_id']      = $row['message_id'];
        $prm['message']         = $row['message'];
        $prm['message_reading_confirmation'] = $row['reading_confirmation'];
        $prm['confirmed_read']  = $row['confirmed_read'];
        $prm['uname_snd']       = $row['username'];
        $prm['message_subject'] = $row['subject'];
        $prm['mkdate']          = $row['mkdate'];
        $prm['user_id_snd']     = $row['autor_id'];

        ob_start();
        print_rec_message($prm, $f_open);
        return ob_get_clean();

    } else if ($sms_data['view'] == 'out') {
        $tmp_move_to_folder = sizeof($sms_data['tmp']['move_to_folder']);

        $query = "SELECT message.*, message_user.folder, message_user.dont_delete,
                         auth_user_md5.user_id AS rec_uid, auth_user_md5.vorname AS rec_vorname,
                         auth_user_md5.nachname AS rec_nachname, auth_user_md5.username AS rec_uname,
                         COUNT(mu.message_id) AS num_rec, COUNT(dokument_id) AS num_attachments
                  FROM message_user
                  LEFT  JOIN message_user AS mu ON (message_user.message_id = mu.message_id AND mu.snd_rec = 'rec')
                  LEFT  JOIN message ON (message.message_id = message_user.message_id)
                  LEFT  JOIN auth_user_md5 ON (mu.user_id = auth_user_md5.user_id)
                  LEFT JOIN dokumente ON (range_id = message_user.message_id)
                  WHERE message_user.user_id = ?
                    AND message_user.snd_rec = 'snd' AND message_user.deleted = 0
                    AND message.message_id = ?
                  GROUP BY message_user.message_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id, $mid));
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $psm['count']           = $count;
        $psm['count_2']         = $tmp_move_to_folder - ($n+1);
        $psm['mkdate']          = $row['mkdate'];
        $psm['folder']          = $my_messaging_settings['folder']['active']['out'];
        $psm['message_id']      = $row['message_id'];
        $psm['message_subject'] = $row['subject'];
        $psm['message']         = $row['message'];
        $psm['dont_delete']     = $row['dont_delete'];
        $psm['rec_uid']         = $row['rec_uid'];
        $psm['rec_vorname']     = $row['rec_vorname'];
        $psm['rec_nachname']    = $row['rec_nachname'];
        $psm['rec_uname']       = $row['rec_uname'];
        $psm['num_rec']         = $row['num_rec'];
        $psm['num_attachments'] = $row['num_attachments'];

        ob_start();
        print_snd_message($psm, $f_open);
        return ob_get_clean();
    }
}

function show_nachrichtencount($count, $count_timefilter) {
    if ($count == "0") {
        $zusatz = _("keine Nachrichten");
    } else {
        $zusatz = sprintf(_("%s von %s Nachrichten"), $count_timefilter, $count);
    }
    return $zusatz;
}

function have_msgfolder($view) {
    global $my_messaging_settings;
    static $have_folder = null;
    if (isset($have_folder[$view])) return $have_folder[$view];
    $dummies = array_unique($my_messaging_settings["folder"][$view]);
    if (sizeof($dummies) == 1 && $dummies[0] == 'dummy') {
        return ($have_folder[$view] = false);
    } else {
        return ($have_folder[$view] = true);
    }
}

// checkt ob alle adressbuchmitglieder in der empaengerliste stehen
function CheckAllAdded($adresses_array, $rec_array) {

    $x = sizeof($adresses_array);
    if (!empty($rec_array)) {
        foreach ($rec_array as $a) {
            if (in_array($a, $adresses_array)) {
                $x = ($x-1);
            }
        }
    }
    if ($x != "0") {
        return FALSE;
    } else {
        return TRUE;
    }

}

///////////////////////////////////////////////////////////////////////

function show_precform() {

    global $sms_data, $user, $my_messaging_settings;

    $tmp_01 = min(sizeof($sms_data["p_rec"]), 12);
    $tmp = "";

    if (sizeof($sms_data["p_rec"]) == "0") {
        $tmp .= "<font size=\"-1\">"._("Bitte w&auml;hlen Sie mindestens einen Empf&auml;nger aus.")."</font>";
    } else {
        $tmp .= "<select size=\"$tmp_01\" id=\"del_receiver\" name=\"del_receiver[]\" multiple style=\"width: 250\">";
        if ($sms_data["p_rec"]) {
            foreach ($sms_data["p_rec"] as $a) {
                $tmp .= "<option value=\"$a\">".get_fullname_from_uname($a,'full',true)."</option>";
            }
        }
        $tmp .= "</select><br>";
        $tmp .= "<input style=\"vertical-align: text-top;\" type=\"image\" name=\"del_receiver_button\" src=\"".Assets::image_path('icons/16/blue/trash.png'). "\" ".tooltip(_("l�scht alle ausgew�hlten Empf�ngerInnen"))." border=\"0\">";
        $tmp .= " <font size=\"-1\">"._("ausgew�hlte l�schen")."</font><br>";
        $tmp .= "<input style=\"vertical-align: text-top;\" type=\"image\" name=\"del_allreceiver_button\" src=\"".Assets::image_path('icons/16/blue/trash.png'). "\" ".tooltip(_("Empf�ngerliste leeren"))." border=\"0\">";
        $tmp .= " <font size=\"-1\">"._("Empf�ngerliste leeren")."</font>";
    }

    return $tmp;

}


function show_addrform()
{
    global $sms_data, $user, $adresses_array, $search_exp, $my_messaging_settings, $_fullname_sql;

    $picture = 'icons/16/yellow/arr_2up.png';

    // list of adresses
    $query = "SELECT username, user_id, {$_fullname_sql['full_rev']} AS fullname
              FROM contact
              LEFT JOIN auth_user_md5 USING(user_id)
              LEFT JOIN user_info USING (user_id)
              WHERE owner_id = ?
              ORDER BY Nachname ASC";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    $temp = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    $statement->closeCursor();

    $adresses_array = array_merge((array)$adresses_array, $temp);

    $tmp = "<b><font size=\"-1\">"._("Adressbuch-Liste:")."</font></b><br>";

    if (empty($adresses_array)) { // user with no adress-members at all

        $tmp .= sprintf("<font size=\"-1\">"._("Sie haben noch keine Personen in Ihrem Adressbuch. %s Klicken Sie %s hier %s um dorthin zu gelangen.")."</font>", "<br>", "<a href=\"contact.php\">", "</a>");

    } else if (!empty($adresses_array)) { // test if all adresses are added?

        if (CheckAllAdded($adresses_array, $sms_data["p_rec"]) == TRUE) { // all adresses already added
            $tmp .= sprintf("<font size=\"-1\">"._("Bereits alle Personen des Adressbuchs hinzugef&uuml;gt!")."</font>");
        } else { // show adresses-select
            $tmp_count = 0;

            $statement->execute(array($user->id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                if (empty($sms_data['p_rec']) || !in_array($row['username'], $sms_data['p_rec'])) {
                    $tmp_02 .= sprintf('<option value="%s">%s</option>',
                                       $row['username'],
                                       htmlReady(my_substr($row['fullname'],0,35)));
                    $tmp_count += 1;
                }
            }

            $tmp_01 = min($tmp_count, 12);
            $tmp .= "<select size=\"".$tmp_01."\" id=\"add_receiver\" name=\"add_receiver[]\" multiple style=\"width: 250\">";
            $tmp .= $tmp_02;
            $tmp .= "</select><br>";
            $tmp .= "<input type=\"image\" name=\"add_receiver_button\" src=\"" . Assets::image_path($picture) . "\" class=\"text-top\" ".tooltip(_("f�gt alle ausgew�htlen Personen der Empf�ngerInnenliste hinzu")).">";
            $tmp .= "&nbsp;<font size=\"-1\">"._("ausgew&auml;hlte hinzuf�gen")."";
            $tmp .= "&nbsp;<br><input type=\"image\" name=\"add_allreceiver_button\" src=\"" . Assets::image_path($picture) . "\" class=\"text-top\" ".tooltip(_("f�gt alle Personen der Empf�ngerInnenliste hinzu")).">";
            $tmp .= "&nbsp;<font size=\"-1\">"._("alle hinzuf&uuml;gen")."</font>";

        }

    }

    // free search
    $tmp .= "<br><br><font size=\"-1\"><b>"._("Freie Suche:")."</b></font><br>";

    ob_start();


    if ((Request::get("adressee_parameter") && Request::get("adressee_parameter") !== _("Nutzer suchen") )) {
        print "<input type=\"image\" name=\"add_freesearch\" ".
            tooltip(_("zu Empf�ngerliste hinzuf�gen")).
            " value=\""._("zu Empf&auml;ngerliste hinzuf&uuml;gen").
            "\" src=\"" . Assets::image_path($picture) . "\" class=\"text-top\"> ";
    }

    print QuickSearch::get("adressee", new StandardSearch("username"))
        ->setInputStyle("width: 211px;")
        ->withoutButton()
        ->fireJSFunctionOnSelect("STUDIP.Messaging.addToAdressees")
        ->render();


	print Assets::input(!(Request::get("adressee_parameter") && Request::get("adressee_parameter") !== _("Nutzer suchen")) 
              ? 'icons/16/blue/search.png' : 'icons/16/blue/refresh.png', 
              array('type' => "image", 'style' => "vertical-align: text-top;", 'name' => "search_person", 
	          'title' => !(Request::get("adressee_parameter") && Request::get("adressee_parameter") !== _("Nutzer suchen")) ? _("Suchen"): _("Suche zur�cksetzen")));

    $tmp .= ob_get_clean();

    return $tmp;
}

function show_msgform() {

    global $sms_data, $tmp_sms_content, $messagesubject, $message, $quote_username, $quote, $cmd;

    $temp_message = '';
    if ($quote) {
        $temp_message = quotes_encode($tmp_sms_content, get_fullname_from_uname($quote_username));
    }
    if ($message) {
        $temp_message .= $message;
    }

    $template = $GLOBALS['template_factory']->open('messaging/message_form');
    $template->cmd            = $cmd;
    $template->messagesubject = $messagesubject;
    $template->message        = $temp_message;
    $template->show_submit    = count($sms_data['p_rec']) > 0;
    // Redirect to specified page, defaults to message inbox
    $template->return_to      = Request::get('sms_source_page', 'sms_box.php');
    return $template->render();

}

function show_previewform()
{
    global $sms_data, $my_messaging_settings, $signature, $cmd, $messagesubject, $message;

    $tmp = "<input type=\"image\" name=\"refresh_message\" class=\"text-top\" src=\"" . Assets::image_path('icons/16/blue/refresh.png') . "\" ".tooltip(_("aktualisiert die Vorschau der aktuellen Nachricht."))."> "._("Vorschau erneuern.")."<br><br>";
    $tmp .= "<b>"._("Betreff:")."</b><br>".htmlready($messagesubject);
    $tmp .= "<br><br><b>"._("Nachricht:")."</b><br>";
    $tmp .= formatReady($message);
    if ($sms_data["sig"] == "1") {
        $tmp .= "<br><br>-- <br>";
        if ($signature) {
            $tmp .= formatReady($signature);
        } else {
            $tmp .= formatReady(stripslashes($my_messaging_settings["sms_sig"]));
        }
    }

    return $tmp;
}

function show_sigform()
{
    global $sms_data, $my_messaging_settings, $signature, $cmd;

    if ($sms_data["sig"] == "1") {
            $tmp =  "<font size=\"-1\">";
            $tmp .= _("Dieser Nachricht wird eine Signatur angeh�ngt");
            $tmp .= "<br><input class=\"text-top\" type=\"image\" name=\"rmv_sig_button\" src=\"".Assets::image_path('icons/16/blue/vcard.png'). "\" ".tooltip(_("entfernt die Signatur von der aktuellen Nachricht."))."> "._("Signatur entfernen.");
            $tmp .= "</font><br>";
            $tmp .= "<textarea name=\"signature\" style=\"width: 250px\" cols=20 rows=7 wrap=\"virtual\">\n";
            if (!$signature) {
                $tmp .= htmlready(stripslashes($my_messaging_settings["sms_sig"]));
            } else {
                $tmp .= htmlready($signature);
            }
            $tmp .= "</textarea>\n";
    } else {
        $tmp =  "<font size=\"-1\">";
        $tmp .=  _("Dieser Nachricht wird keine Signatur angeh�ngt");
            $tmp .= "<br><input class=\"text-top\" type=\"image\" name=\"add_sig_button\" src=\"".Assets::image_path('icons/16/blue/vcard.png'). "\" ".tooltip(_("f�gt der aktuellen Nachricht eine Signatur an."))."> "._("Signatur anh�ngen.");
        $tmp .= "</font>";
    }

    $tmp = "<font size=\"-1\">".$tmp."</font>";
    return $tmp;
}

function show_msgsaveoptionsform()
{
    global $sms_data, $my_messaging_settings;

    if($sms_data["tmpsavesnd"] == 1) {
        $tmp .= "<input class=\"text-top\" type=\"image\" name=\"rmv_tmpsavesnd_button\" src=\"".Assets::image_path('icons/16/blue/checkbox-checked.png'). "\" ".tooltip(_("Nachricht speichern"))."> "._("Nachricht speichern");
        // do we have any personal folders? if, show them here
        if (have_msgfolder("out") == TRUE) {
            // walk throw personal folders
            $tmp .= "<br><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"5\" height=\"5\">";
            $tmp .= "<br>"._("in: ");
            $tmp .= "<select name=\"tmp_save_snd_folder\" style=\"width: 180px\" class=\"middle\">";
            $tmp .=  "<option value=\"dummy\">"._("Postausgang")."</option>";
            for($x="0";$x<sizeof($my_messaging_settings["folder"]["out"]);$x++) {
                if (htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"]["out"], $x))) != "dummy") {
                    $tmp .=  "<option value=\"".$x."\" ".CheckSelected($x, $sms_data["tmp_save_snd_folder"]).">".htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"]["out"], $x)))."</option>";
                }
            }
            $tmp .= "</select>";
        }
    } else {
        $tmp .= "<input style=\"vertical-align: text-top;\" type=\"image\" name=\"add_tmpsavesnd_button\" src=\"".Assets::image_path('icons/16/blue/checkbox-unchecked.png'). "\" " . tooltip(_("Nachricht speichern")).">&nbsp;"._("Nachricht speichern");
    }

    $tmp = "<font size=\"-1\">".$tmp."</font>";
    return $tmp;
}

function show_msgemailoptionsform()
{
    global $sms_data, $my_messaging_settings;

    if($sms_data["tmpemailsnd"] == 1) {
        $tmp .= "<input style=\"vertical-align: text-top;\" type=\"image\" name=\"rmv_tmpemailsnd_button\" src=\"".Assets::image_path('icons/16/blue/checkbox-checked.png'). "\" " . tooltip(_("Nachricht als E-Mail versenden")).">&nbsp;"._("Nachricht als E-Mail versenden");
    } else {
        $tmp .= "<input style=\"vertical-align: text-top;\" type=\"image\" name=\"add_tmpemailsnd_button\" src=\"".Assets::image_path('icons/16/blue/checkbox-unchecked.png'). "\" " . tooltip(_("Nachricht als E-Mail versenden")).">&nbsp;"._("Nachricht als E-Mail versenden");
    }

    $tmp = "<font size=\"-1\">".$tmp."</font>";
    return $tmp;
}

function show_msgreadconfirmoptionsform()
{
    global $sms_data, $my_messaging_settings;

    if($sms_data["tmpreadsnd"] == 1) {
        $tmp .= "<input style=\"vertical-align: text-top;\" type=\"image\" name=\"rmv_tmpreadsnd_button\" src=\"".Assets::image_path('icons/16/blue/checkbox-checked.png'). "\" " . tooltip(_("Lesebest�tigung anzufordern")).">&nbsp;"._("Lesebest�tigung anfordern");
    } else {
        $tmp .= "<input style=\"vertical-align: text-top;\" type=\"image\" name=\"add_tmpreadsnd_button\" src=\"".Assets::image_path('icons/16/blue/checkbox-unchecked.png'). "\" " . tooltip(_("Lesebest�tigung anzufordern")).">&nbsp;"._("Lesebest�tigung anfordern");
    }

    $tmp = "<font size=\"-1\">".$tmp."</font>";
    return $tmp;
}

//Ausgabe des Formulars f�r Nachrichtenanh�nge
function show_attachmentform()
{
    //erlaubte Dateigroesse aus Regelliste der Config.inc.php auslesen
    $max_filesize = $GLOBALS['UPLOAD_TYPES']['attachments']["file_sizes"][$GLOBALS['perm']->get_perm()];
    if( !($attachment_message_id = Request::option('attachment_message_id')) ){
        $attachment_message_id = md5(uniqid('message', true));
    }
    $attachments = get_message_attachments($attachment_message_id, true);
    if (count($attachments)) {
        $print.="\n<table border=\"0\" cellpadding=\"1\" cellspacing=\"1\">";
        $print.="\n";
        $print.="\n<tr><td colspan=\"3\">";
        $print.="\n<b>" . _("Angeh�ngte Dateien:") . "</b></td></tr>";
        foreach ($attachments as $attachment) {
            $print.= "\n<tr><td>". GetFileIcon(getFileExtension($attachment["filename"]), true);
            $print.= "</td><td>" . htmlReady($attachment["filename"]) ."&nbsp;(";
            $print.= ($attachment["filesize"] / 1024 / 1024 >= 1 ? round($attachment["filesize"] / 1024 / 1024) ." Mb" : round($attachment["filesize"] / 1024)." Kb");
            $print.= ")</td><td style=\"padding-left:5px\">";
            $print.= "<input type=\"image\" name=\"remove_attachment_{$attachment['dokument_id']}\" src=\"". Assets::image_path('icons/16/blue/trash.png') . "\" ".tooltip(_("entfernt den Dateianhang")).">";
            $print.= "</td></tr>";
        }
        $print.= "</table>";
    } else {
        $print.="\n<br>" . _("An diese Nachricht ist keine Datei angeh�ngt.");
    }
    $print.="\n<div style=\"margin-top:5px;font-weight:bold;\">";
    if ($GLOBALS['UPLOAD_TYPES']['attachments']['type'] == "allow") {
        $print.= _("Unzul&auml;ssige Dateitypen:");
    } else {
        $print.= _("Zul&auml;ssige Dateitypen:");
    }
    $print .= '&nbsp;'. join(', ', array_map('strtoupper', (array)$GLOBALS['UPLOAD_TYPES']['attachments']['file_types']));
    $print .= '<br>';
    $print .= _("Maximale Gr��e der angeh�ngten Dateien:");
    $print .= sprintf("&nbsp;%sMB", round($max_filesize/1048576,1));
    $print.= "\n</div>";
    $print.="\n<div style=\"margin-top:5px;\">";
    $print.="\n" . _("Klicken Sie auf <b>'Durchsuchen...'</b>, um eine Datei auszuw&auml;hlen.");
    $print.= "\n</div>";
    $print.="\n<div>";
    $print.="\n<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"$max_filesize\">";
    $print.= "<input name=\"the_file\" type=\"file\" size=\"40\">";
    $print.= Button::create(_('Hinzuf�gen'), 'upload', array('onClick' => 'return STUDIP.OldUpload.upload_start(jQuery(this).closest('."'form'".'));'));
    $print.= "\n<input type=\"hidden\" name=\"attachment_message_id\" value=\"".htmlready($attachment_message_id)."\">";
    $print.= "</div>";

    return $print;
}

function get_message_attachments($message_id, $provisional = false)
{
    $db = DBManager::get();
    if(!$provisional){
        $st = $db->prepare("SELECT dokumente.* FROM message INNER JOIN dokumente ON message_id=range_id WHERE message_id=? ORDER BY dokumente.chdate");
    } else {
        $st = $db->prepare("SELECT * FROM dokumente WHERE range_id='provisional' AND description=? ORDER BY chdate");
    }
    return $st->execute(array($message_id)) ? $st->fetchAll(PDO::FETCH_ASSOC) : array();
}

function get_message_data($message_id, $user_id, $sndrec)
{
    $db = DBManager::get();
    $db->exec("SET SESSION group_concat_max_len = 8192");
    if ($sndrec == 'rec') {
        $stmt = $db->prepare("SELECT message.*,message_user.user_id as rec_uid,autor_id as snd_uid, GROUP_CONCAT(dokument_id) as attachments FROM message_user
                    LEFT JOIN message USING (message_id)
                    LEFT JOIN dokumente ON range_id=message_user.message_id
                    WHERE message_user.user_id = :user_id AND message_user.snd_rec = 'rec'
                    AND message_user.deleted = 0
                    AND message.message_id = :mid GROUP BY message_user.message_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':mid', $message_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
      $stmt = $db->prepare("SELECT message.*,autor_id as snd_uid,GROUP_CONCAT(message_user.user_id) as rec_uid, GROUP_CONCAT(dokument_id) as attachments
                FROM message
                LEFT JOIN message_user USING (message_id)
                LEFT JOIN dokumente ON range_id=message_user.message_id
                WHERE autor_id = :user_id AND message_user.snd_rec = 'rec'
                AND message.message_id = :mid GROUP BY message_user.message_id");
      $stmt->bindParam(':user_id', $user_id);
      $stmt->bindParam(':mid', $message_id);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $row ? $row : array();
}
