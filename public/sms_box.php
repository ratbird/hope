<?
# Lifter002: TODO
# Lifter005: TODO - overlib
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
 * sms_box.php - displays messages in in- and outboxfolders
 *
 * Verwaltung von systeminternen Kurznachrichten - Eingang/ Ausgang
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nils K. Windisch <studip@nkwindisch.de>
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     messaging
 */
use Studip\Button, Studip\LinkButton;
require '../lib/bootstrap.php';

unregister_globals();

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

// initialise session
include ('lib/seminar_open.php');

// -- here you have to put initialisations for the current page
require_once 'lib/functions.php';
require_once ('lib/msg.inc.php');
require_once ('lib/visual.inc.php');
require_once ('lib/include/messagingSettings.inc.php');
require_once ('lib/messaging.inc.php');
require_once ('lib/statusgruppe.inc.php');
require_once ('lib/sms_functions.inc.php');
if(empty ($my_messaging_settings)){
    $my_messaging_settings = json_decode(UserConfig::get($user->id)->__get('my_messaging_settings'),true);
    if (!$my_messaging_settings['show_only_buddys'])
        $my_messaging_settings['show_only_buddys'] = FALSE;
    if (!$my_messaging_settings['delete_messages_after_logout'])
        $my_messaging_settings['delete_messages_after_logout'] = FALSE;
    if (!$my_messaging_settings['start_messenger_at_startup'])
        $my_messaging_settings['start_messenger_at_startup'] = FALSE;
    if (!$my_messaging_settings['default_setted'])
        $my_messaging_settings['default_setted'] = time();
    if (!$my_messaging_settings['last_login'])
        $my_messaging_settings['last_login'] = FALSE;
    if (!$my_messaging_settings['timefilter'])
        $my_messaging_settings['timefilter'] = "30d";
    if (!$my_messaging_settings['opennew'])
        $my_messaging_settings['opennew'] = 1;
    if (!$my_messaging_settings['logout_markreaded'])
        $my_messaging_settings['logout_markreaded'] = FALSE;
    if (!$my_messaging_settings['openall'])
        $my_messaging_settings['openall'] = FALSE;
    if (!$my_messaging_settings['addsignature'])
        $my_messaging_settings['addsignature'] = FALSE;
    if (!$my_messaging_settings['save_snd'])
        $my_messaging_settings['save_snd'] = 1;
    if (!$my_messaging_settings['sms_sig'])
        $my_messaging_settings['sms_sig'] = FALSE;
    if (!$my_messaging_settings['send_view'])
        $my_messaging_settings['send_view'] = FALSE;
    if (!$my_messaging_settings['last_box_visit'])
        $my_messaging_settings['last_box_visit'] = 1;
    if (!$my_messaging_settings['folder']['in'])
        $my_messaging_settings['folder']['in'][0] = "dummy";
    if (!$my_messaging_settings['folder']['out'])
        $my_messaging_settings['folder']['out'][0] = "dummy";
    if (!$my_messaging_settings['confirm_reading'])
        $my_messaging_settings['confirm_reading'] = 3;
}
if (get_config('CHAT_ENABLE')){
    include_once $RELATIVE_PATH_CHAT."/chat_func_inc.php";
    $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
    $chatServer->caching = true;
    $admin_chats = $chatServer->getAdminChats($auth->auth['uid']);
}

// let's register some ...
$_SESSION['sms_data'] = $sms_data;
$_SESSION['sms_show'] = $sms_show;
$msging = new messaging;
$query_showfolder = $query_time_sort = $query_movetofolder = $query_time = '';
$cmd = Request::option('cmd');
$cmd_show = Request::option('cmd_show');
$sms_data = $_SESSION['sms_data'];
$sms_show = $_SESSION['sms_show'];

// determine view
if (Request::option('sms_inout')) {
    $sms_data["view"] = Request::option('sms_inout');
} else if ($sms_data["view"] == "") {
    $sms_data["view"] = "in";
}

PageLayout::setTitle(_("Systeminterne Nachrichten"));
PageLayout::setHelpKeyword("Basis.InteraktionNachrichten");
Navigation::activateItem('/messaging/' . $sms_data['view']);

// Output of html head and Stud.IP head
include ('lib/include/html_head.inc.php');
include ('lib/include/header.php');

if (Request::option('readingconfirmation')) {
    $sms_data['tmpreadsnd'] = "";

    $query = "SELECT mkdate, subject FROM message WHERE message_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(Request::option('readingconfirmation')));
    $temp = $statement->fetch(PDO::FETCH_ASSOC);

    $date = date('d.m.y, H:i', $temp['mkdate']);
    $orig_subject = $temp['subject'];

    $query = "UPDATE message_user SET confirmed_read = 1 WHERE message_id = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(Request::option('readingconfirmation'), $user->id));

    if ($statement->rowCount()) {
        $uname_snd = Request::get('uname_snd');

        setTempLanguage(get_userid($uname_snd));
        $subject = sprintf (_("Lesebestätigung von %s"), $user->getFullName());
        $message = sprintf (_("Ihre Nachricht an %s mit dem Betreff: %s vom %s wurde gelesen."), "%%".$user->getFullName()."%%", "%%".$orig_subject."%%", "%%".$date."%%");
        restoreLanguage();
        $msging->insert_message(mysql_escape_string($message), $uname_snd, "____%system%____", FALSE, FALSE, 1, FALSE, mysql_escape_string($subject));
    }
}

// do we have any selected messages for move-to-different-folder-action but no click on possible folder so undo selection
if ($sms_data['tmp']['move_to_folder'] && !Request::option('move_folder')) {
    unset($sms_data['tmp']['move_to_folder']);
}

// delete selected messages
if (Request::submitted('delete_selected_button') || $cmd == "delete_selected") {
    $l = 0;
    if (Request::optionArray('sel_sms')) {
        foreach (Request::optionArray('sel_sms') as $a) {
            $count_deleted_sms = $msging->delete_message($a);
            $l = $l+$count_deleted_sms;
        }
    }
    if ($l) {
        if ($l == "1") {
            $msg = "msg§"._("Es wurde eine Nachricht gel&ouml;scht.");
        } else {
            $msg = "msg§".sprintf(_("Es wurden %s Nachrichten gel&ouml;scht."), $l);
        }
    } else {
        $msg = "error§"._("Es konnten keine Nachrichten gel&ouml;scht werden.");
    }
}

// open festlegen
if (Request::option('mclose')) {
    $sms_data["open"] = '';
} else if (Request::option('mopen')) {
    $sms_data["open"] = Request::option('mopen');
}

// do we like to memorize all messages as allready readed?
if ($cmd == "mark_allsmsreaded") {
    $msging->set_read_all_messages();
    $msg = "msg§".sprintf(_("Es wurden alle ungelesenen Nachrichten als gelesen gespeichert."), $l);
}

// how many messages do we have
$count_newsms = count_messages_from_user($sms_data['view'], "AND deleted='0' AND readed='0'");
$show_folder = Request::option('show_folder');
// open default folder if there are new messages
//$neux -> global from lib/Navigation/MessagingNavigation.php 
if ($neux && !$show_folder) {
    $show_folder = "all";
}

// close open folder or open the selectet one
if ($show_folder == "close") { // close folder
    $sms_show['folder'][$sms_data['view']] = "close";
    unset($my_messaging_settings["folder"]['active'][$sms_data['view']]);
} else if ($show_folder != "") { // open specified folder
    $sms_show['folder'][$sms_data['view']] = $show_folder;
    $my_messaging_settings["folder"]['active'][$sms_data['view']] = $sms_show['folder'][$sms_data['view']];
}

//
if (empty($sms_show['folder'][$sms_data['view']])) { // waehle den letzten besuchten ordner, falls keiner gewaehlt
    $sms_show['folder'][$sms_data['view']] = $my_messaging_settings["folder"]['active'][$sms_data['view']];
}

// folder festlegen
if ($sms_show['folder'][$sms_data['view']] != "all") { // ist ein persoenlicher
    $query_showfolder = "AND message_user.folder='".$sms_show['folder'][$sms_data['view']]."'";
    $infotext_folder = "&nbsp;("._("Ordner").":&nbsp;".htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"][$sms_data["view"]], $sms_show['folder'][$sms_data['view']]))).")";
} else { // ist der allgemeine
    $query_showfolder = "AND message_user.folder=''";
    if ($sms_data["view"] == "in") {
        $infotext_folder = "&nbsp;("._("Ordner: Posteingang").")";
    } else {
        $infotext_folder = "&nbsp;("._("Ordner: Postausgang").")";
    }
}

// insert new folder
if (Request::option('new_folder') != "" && Request::submitted('new_folder_button')) {
    if ($msging->check_newmsgfoldername($new_folder) == FALSE) { // check auf erlaubte ordnernamen
        $msg = "error§".sprintf(_("Der gewählte Ordnername ist vom System belegt. Bitte wählen Sie einen anderen."));
    } else { // ordnername ok und los
        $my_messaging_settings["folder"][$sms_data["view"]][] = Request::option('new_folder');
        $msg = "msg§".sprintf(_("Der Ordner %s wurde angelegt."), htmlready(Request::option('new_folder')));
    }
}

// remove selected folder
if (Request::option('delete_folder') && Request::submitted('delete_folder_button')) {
    if ($sms_data["view"] == "in") {
        $tmp_sndrec = "rec";
    } else {
        $tmp_sndrec = "snd";
    }
    $delete_folder = Request::quoted('delete_folder');
    $msg = "msg§".sprintf(_("Der Ordner %s wurde gelöscht."), htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"][$sms_data["view"]], $delete_folder))));

    $query = "UPDATE message_user
              SET folder = ''
              WHERE folder = ? AND snd_rec = ? AND user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($delete_folder, $tmp_sndrec, $user->id));

    $my_messaging_settings["folder"][$sms_data["view"]][$delete_folder] = "dummy";
}

// rename specific folder
if (Request::submitted('ren_folder_button')) {
    if ($sms_data["view"] == "in") {
        $tmp_sndrec = "rec";
    } else {
        $tmp_sndrec = "snd";
    }
    $orig_folder_name = Request::quoted('orig_folder_name');
    $new_foldername = Request::quoted('new_foldername');
    $msg = "msg§".sprintf(_("Der Ordner %s wurde in %s umbenannt."), htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"][$sms_data["view"]], $orig_folder_name))), htmlready($new_foldername));
    $my_messaging_settings["folder"][$sms_data["view"]][$orig_folder_name] = $new_foldername;
}

// determine if we like to see all messages opened
if (empty($my_messaging_settings["openall"])) {
    $my_messaging_settings["openall"] = "2";
}

// determine and memorize timefilter
if (Request::option('sms_time')) {
    $sms_data["time"] = Request::option('sms_time');
} else if ($sms_data["time"] == "" && empty($my_messaging_settings["timefilter"])) {
    $sms_data["time"] = "all";
    $my_messaging_settings["timefilter"] = "all";
} else if ($sms_data["time"] == "" && !empty($my_messaging_settings["timefilter"])) {
    $sms_data["time"] = $my_messaging_settings["timefilter"];
}

// determine several later displayed texts in relation to the selected view
if ($sms_data['view'] == "in") {
    $info_text_001 = Assets::img('icons/16/blue/mail.png', array('class' => 'text-top', 'title' =>_('empfangene systeminterne Nachrichten anzeigen')))."</b>";
    $info_text_002 = _("Posteingang");
    $no_message_text_box = _("im Posteingang");
    $tmp_snd_rec = "rec";
    // add skip link
    SkipLinks::addIndex(_("Posteingang"), 'main_content', 100);
} else if ($sms_data['view'] == "out") {
    $info_text_001 = Assets::img('icons/16/blue/mail.png', array('class' => 'text-top', 'title' =>_('gesendete systeminterne Nachrichten anzeigen')))."</b>";
    $info_text_002 = _("Postausgang");
    $no_message_text_box = _("im Postausgang");
    $tmp_snd_rec = "snd";
    // add skip link
    SkipLinks::addIndex(_("Postausgang"), 'main_content', 100);
}

// memorize del-lock for selected items
if (Request::option('sel_lock')) {
    if ($cmd == "safe_selected") { // close del-lock
        $tmp_dont_delete = "1";
        $msg = "msg§"._("Der Lösch-Schutz wurde für die gewählte Nachricht aktiviert.");
    } else if ($cmd == "open_selected") { // open del-lock
        $tmp_dont_delete = "0";
        $msg = "msg§"._("Der Lösch-Schutz wurde für die gewählte Nachricht aufgehoben.");
    }
    
    $query = "UPDATE message_user
              SET dont_delete = ?
              WHERE user_id = ? AND message_id = ? AND snd_rec = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $tmp_dont_delete, $user->id, Request::option('sel_lock'), $tmp_snd_rec,
    ));

    $tmp_dont_delete = "";
    $tmp_snd_rec = "";
}

// do we have selected items for move-to-different-folder-action?
if (Request::optionArray('move_to_folder')) {
    $sms_data['tmp']['move_to_folder'] = Request::optionArray('move_to_folder');
}

// wenn mehrere verschieben-button gedrueckt
if (Request::submitted('move_selected_button') && Request::optionArray('sel_sms')) {
    $sms_data['tmp']['move_to_folder'] = Request::optionArray('sel_sms');
}
$move_folder = Request::option('move_folder');
// let's move some messages
if ($move_folder) {
    if ($move_folder == "free") {
        $move_folder = "";
    }
    $l = 0;
    if (is_array($sms_data['tmp']['move_to_folder'])) {
        $query = "UPDATE message_user
                  SET folder = ?
                  WHERE message_id = ? AND user_id = ? AND snd_rec = ?";
        $statement = DBManager::get()->prepare($query);

        foreach ($sms_data['tmp']['move_to_folder'] as $a) {
            $statement->execute(array($move_folder, $a, $user->id, $tmp_snd_rec));
            $l += $statement->rowCount();
        }
    }
    if ($l) {
        if ($l == "1") {
            $msg = "msg§"._("Es wurde eine Nachricht verschoben.");
        } else {
            $msg = "msg§".sprintf(_("Es wurden %s Nachrichten verschoben."), $l);
        }
    } else {
        $msg = "error§"._("Es konnten keine Nachrichten verschoben werden.");
    }
    unset($sms_data['tmp']['move_to_folder']);
    $move_folder = "";
    $tmp_snd_rec = "";
}

// query wenn nachrichten verschieben
if ($sms_data['tmp']['move_to_folder']) {
    if (sizeof($sms_data['tmp']['move_to_folder']) == "1") { // verschieben wir von einem button aus oder doch via checkbox...
        if ($sms_data['tmp']['move_to_folder'][1] == "") {
            $tmp_partquery = $sms_data['tmp']['move_to_folder'][0];
        } else {
            $tmp_partquery = $sms_data['tmp']['move_to_folder'][1];
        }
        $query_movetofolder = "AND message.message_id='".$tmp_partquery."'"; // es wird nur diese nachricht angezeigt
    } else {
        $query_movetofolder = "AND (message.message_id='".$sms_data['tmp']['move_to_folder'][0]."'";
        for($x=1;$x<sizeof($sms_data['tmp']['move_to_folder']);$x++) {
            $query_movetofolder .= " OR message.message_id='".$sms_data['tmp']['move_to_folder'][$x]."'";
        }
        $query_movetofolder .= ")";
    }
}

// set timefilter and depanding displayed-texts
$query_time_sort = "";
if ($sms_data["time"] == "all") {
    $no_message_text = sprintf(_("Es liegen keine systeminternen Nachrichten%s %s vor."), $infotext_folder, $no_message_text_box);
} else if ($sms_data["time"] == "new") {
    if ($sms_data["view"] == "in") {
        $LastLogin = UserConfig::get($user->id)->_get('LastLogin');
        $query_time_sort = " AND message_user.mkdate > ".(int)$LastLogin;
    } else {
        $CurrentLogin = UserConfig::get($user->id)->_get('CurrentLogin');
        $query_time_sort = " AND message_user.mkdate > ".(int)$CurrentLogin;
    }
    $no_message_text = sprintf(_("Es liegen keine neuen systeminternen Nachrichten%s %s vor."), $infotext_folder, $no_message_text_box);
} else if ($sms_data["time"] == "24h") {
    $query_time_sort = " AND message_user.mkdate > ".(date("U")-86400);
    $no_message_text = sprintf(_("Es liegen keine systeminternen Nachrichten%s aus den letzten 24 Stunden %s vor."), $infotext_folder, $no_message_text_box);
} else if ($sms_data["time"] == "7d") {
    $query_time_sort = " AND message_user.mkdate > ".(date("U")-(7*86400));
    $no_message_text = sprintf(_("Es liegen keine systeminternen Nachrichten%s aus den letzten 7 Tagen %s vor."), $infotext_folder, $no_message_text_box);
} else if ($sms_data["time"] == "30d") {
    $query_time_sort = " AND message_user.mkdate > ".(date("U")-(30*86400));
    $no_message_text = sprintf(_("Es liegen keine systeminternen Nachrichten%s aus den letzten 30 Tagen %s vor."), $infotext_folder, $no_message_text_box);
} else if ($sms_data["time"] == "older") {
    $query_time_sort = " AND message_user.mkdate < ".(date("U")-(30*86400));
    $no_message_text = sprintf(_("Es liegen keine systeminternen Nachrichten%s %s vor, die &auml;lter als 30 Tage sind."), $infotext_folder, $no_message_text_box);
}
$query_time = $query_time_sort;
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td class="blank" valign="top" id="main_content"    > <?
        if ($msg) { // if info ($msg) for user
            print ("<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\" width=\"99%\"><tr><td valign=\"top\">");
            parse_msg($msg);
            print ("</td></tr></table>");
        } ?>
        <table cellpadding="3" cellspacing="0" border="0" width="100%">
            <tr>
                <td class="blank" align="right" valign="bottom">&nbsp; <?
                    if ($cmd != "admin_folder" && !$sms_data['tmp']['move_to_folder']) {
                        echo LinkButton::create(_('Neuer Ordner'), URLHelper::getURL("?cmd=admin_folder&cmd_2=new"));
                    } else {
                        echo LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL("?cmd="));
                    }
                    ?>
                </td>
            </tr>
        </table> <?

        // rename or make folder
        if ($cmd == "admin_folder") {
            // we would like to make a new folder
            if (Request::option('cmd_2') == "new") {
                $tmp[0] = "new_folder";
                $tmp[1] = _("einen neuen Ordner anlegen");
                $tmp[2] = "new_folder_button";
                $tmp[3] = "";
                $tmp[4] = "";
            }
            // we would like to rename a folder
            if (Request::get('ren_folder')) {
                $tmp[0] = "new_foldername";
                $tmp[1] = _("einen bestehenden Ordner umbennen");
                $tmp[2] = "ren_folder_button";
                $tmp[3] = " value=\"".htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"][$sms_data["view"]], Request::get('ren_folder'))))."\"";
                $tmp[4] = "<input type=\"hidden\" name=\"orig_folder_name\" value=\"".htmlready(Request::get('ren_folder'))."\">";
            }
            $titel = "  <input type=\"text\" name=\"".$tmp[0]."\"".$tmp[3]." style=\"font-size: 8pt\">";
            echo "\n<form action=\"".URLHelper::getURL()."\" method=\"post\"><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
            echo CSRFProtection::tokenTag();
            printhead(0, 0, FALSE, "open", FALSE, ' ' . Assets::img('icons/16/blue/add/folder-empty.png', array('class' => 'text-top')) . ' ', $titel, FALSE);
            echo "</tr></table> ";
            $content_content = $tmp[1]."<div align=\"center\">".$tmp[4];
            $content_content .= Button::create(_('Übernehmen'), $tmp[2], array('align' => 'absmiddle'));
            $content_content .= Button::createCancel(_('Abbrechen'), 'a', array('align' => 'absmiddle'));
            $content_content .= " <div>";
            
            echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
            printcontent("99%",0, $content_content, FALSE);
            echo "</form></tr></table>";
        }

        // show standard folder
        $count = count_messages_from_user($sms_data['view'], "AND folder=''");
        $count_timefilter = count_x_messages_from_user($sms_data['view'], "all", $query_time_sort." AND folder=''");
        $open = folder_openclose($sms_show['folder'][$sms_data['view']], "all");
        if ($sms_data['tmp']['move_to_folder'] && $open == "close") {
            $picture = 'icons/16/yellow/arr_2right.png';
            $link = URLHelper::getLink("?move_folder=free");
        } else {
            $picture = showfoldericon("all", $count);
        }
        if (!$sms_data['tmp']['move_to_folder']) {
            $link = folder_makelink("all");
            $link_add = "&cmd_show=openall";
        }
        $titel = "<a href=\"".$link."\" class=\"tree\" >".$info_text_002."</a>";
        $symbol = "<a href=\"".$link.$link_add."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$picture."\"></a>";
        echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
        $zusatz = show_nachrichtencount($count, $count_timefilter);
        printhead(0, 0, $link, $open, FALSE, "<a href=\"".$link.$link_add."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$picture."\"></a>", $titel, $zusatz);
        echo "</tr></table>";
        if (!$move_to_folder) {
            $content_content = "<div align=\"center\">
                <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">" .
                CSRFProtection::tokenTag() .
                "<div class=\"button-group\"><input type=\"hidden\" name=\"cmd\" value=\"select_all\">"
                . Button::create(_('Alle auswählen'), 'select', array('align' => 'absmiddle')) .    
                "</form>
                <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                CSRFProtection::tokenTag() .
                Button::create(_('Löschen'), 'delete_selected_button', array('align' => 'absmiddle'));
                if (have_msgfolder($sms_data['view']) == TRUE) {                    
                    $content_content .= Button::create(_('Verschieben'), 'move_selected_button', array('align' => 'absmiddle'));
                }
                $content_content .= "</div><br></div>";
            if (folder_openclose($sms_show['folder'][$sms_data['view']], "all") == "open") {
                echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
                if ($count_timefilter != "0") {
                    echo "<td class=\"blank\" background=\"".$GLOBALS['ASSETS_URL']."images/forumstrichgrau.gif\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"100%\" width=\"10px\"></td>";
                }
                printcontent("99%",0, $content_content, FALSE);
                echo "</tr></table> ";
            }
        }
        if (folder_openclose($sms_show['folder'][$sms_data['view']], "all") == "open") print_messages();

        // do we have any personal folders? if, show them here
        if (have_msgfolder($sms_data['view']) == TRUE) {
            // walk throw personal folders
            for($x="0";$x<sizeof($my_messaging_settings["folder"][$sms_data['view']]);$x++) {
                if (htmlready(stripslashes(return_val_from_key($my_messaging_settings["folder"][$sms_data["view"]], $x))) != "dummy") {
                    // how many items are in the folder
                    $count = count_messages_from_user($sms_data['view'], "AND folder='".$x."'");
                    // how many items match the timefilter?
                    $count_timefilter = count_x_messages_from_user($sms_data['view'], $x, $query_time_sort);
                    // this folder is open?
                    $open = folder_openclose($sms_show['folder'][$sms_data['view']], $x);
                    if ($sms_data['tmp']['move_to_folder'] && $open == "close") {
                        $picture = 'icons/16/yellow/arr_2right.png';
                        $link = URLHelper::getLink("?move_folder=".$x);
                    } else {
                        $link = URLHelper::getLink("?cmd=");
                        $picture = showfoldericon($x, $count);
                    }
                    if (!$sms_data['tmp']['move_to_folder']) {
                         $link = folder_makelink($x);
                         $link_add = "&cmd_show=openall";
                        
                    }
                    // titel
                    $titel = "<a href=\"".$link."\" class=\"tree\" >".htmlready(stripslashes($my_messaging_settings["folder"][$sms_data['view']][$x]))."</a>";
                    // titel suffix
                    $zusatz = show_nachrichtencount($count, $count_timefilter);
                    // display titel
                    echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\"><tr>";
                    printhead(0, 0, $link, $open, FALSE, "<a href=\"".$link.$link_add."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".$picture."\"></a>", $titel, $zusatz);
                    echo "</tr></table> ";
                    // do we move messages?
                    if (!$move_to_folder) {
                        $content_content = _("Ordner:")."&nbsp;".$sms_show['folder'][$sms_data['view']]."<br>";
                        if ($open == "open") {
                            $content_content = "<div align=\"center\">"._("Ordneroptionen:")."
                                <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                                    CSRFProtection::tokenTag() .
                                    "<input type=\"hidden\" name=\"delete_folder\" value=\"".$x."\">"
                                      . Button::create(_('Löschen'), 'delete_folder_button', array('align' => 'absmiddle')) .
                                "</form>
                                <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                                    CSRFProtection::tokenTag() .
                                    "<input type=\"hidden\" name=\"cmd\" value=\"admin_folder\">
                                    <input type=\"hidden\" name=\"ren_folder\" value=\"".$x."\">"
                                    . Button::create(_('Umbenennen'), 'x', array('align' => 'absmiddle')) .
                                "</form>";
                            if ($count_timefilter != "0") {
                                $content_content .= "
                                    <br><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"5\"><br>"._("markierte Nachrichten:")."
                                    <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                                        CSRFProtection::tokenTag() .
                                        "<input type=\"hidden\" name=\"cmd\" value=\"select_all\">"
                                        . Button::create(_('Alle auswählen'), 'select', array('align' => 'absmiddle')) .
                                        "</form>
                                        <form action=\"".URLHelper::getURL()."\" method=\"post\" style=\"display: inline\">".
                                        CSRFProtection::tokenTag()
                                        . Button::create(_('Löschen'), 'delete_selected_button', array('align' => 'absmiddle'))
                                        . Button::create(_('Verschieben'), 'move_selected_button', array('align' => 'absmiddle'))
                                        . "<br>";
                            }
                            $content_content .= "</div>";
                            echo "\n<table cellpadding=\"0\" cellspacing=\"0\" width=\"99%\" align=\"center\">\n\t<tr>";
                            if ($count_timefilter != "0") {
                                echo "\n\t<td class=\"blank\" background=\"".$GLOBALS['ASSETS_URL']."images/forumstrichgrau.gif\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" height=\"100%\" width=\"10px\"></td>\n";
                            }
                            printcontent("99%",0, $content_content, FALSE);
                            echo "</tr></table> ";
                        }
                    }
                    // if folder is open show some messages
                    if (folder_openclose($sms_show['folder'][$sms_data['view']], $x) == "open") print_messages();
                }
            }
        }
        print("</form>");
        ?>
    </td>
    <td class="blank" width="270" align="right" valign="top"> <?

        // build infobox_content > viewfilter
        $time_by_links = "";
        $time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=all")."\">".Assets::img(show_icon($sms_data["time"], "all"), array('width' => '16', 'class' => 'text-bottom'))." "._("alle Nachrichten")."</a><br>";
        $time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=24h")."\">".Assets::img(show_icon($sms_data["time"], "24h"), array('width' => '16', 'class' => 'text-bottom'))." "._("letzte 24 Stunden")."</a><br>";
        $time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=7d")."\">".Assets::img(show_icon($sms_data["time"], "7d"), array('width' => '16', 'class' => 'text-bottom'))." "._("letzte 7 Tage")."</a><br>";
        $time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=30d")."\">".Assets::img(show_icon($sms_data["time"], "30d"), array('width' => '16', 'class' => 'text-bottom'))." "._("letzte 30 Tage")."</a><br>";
        $time_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=older")."\">".Assets::img(show_icon($sms_data["time"], "older"), array('width' => '16', 'class' => 'text-bottom'))." "._("&auml;lter als 30 Tage")."</a>";

        $view_by_links = "";
        $view_by_links .= "<a href=\"".URLHelper::getLink("?sms_time=new")."\">".Assets::img(show_icon($sms_data["time"], "new"), array('width' => '16', 'class' => 'text-bottom'))." "._("neue Nachrichten")."</a><br>";

        // did we came from a ...?
        if ($SessSemName[0] && $SessSemName["class"] == "inst") {
            $tmp_array_1 = array("kategorie" => _("Zur&uuml;ck:"),"eintrag" => array(array("icon" => "icons/16/black/info.png", "text" => "<a href=\"institut_main.php\">"._("Zur&uuml;ck zur ausgew&auml;hlten Einrichtung")."</a>")));
        } else if ($SessSemName[0]) {
            $tmp_array_1 = array("kategorie" => _("Zur&uuml;ck:"),"eintrag" => array(array("icon" => "icons/16/black/info.png", "text" => "<a href=\"seminar_main.php\">"._("Zur&uuml;ck zur ausgew&auml;hlten Veranstaltung")."</a>")));
        }
        // how many items do we have?
        $neum = count_messages_from_user('in', " AND message_user.readed = 0 ");
        $altm = count_messages_from_user('in', " AND message_user.readed = 1 ");
        $show_message_count = sprintf(_("Sie haben %s empfangene und %s gesendete Nachrichten."), ($altm+$neum), count_messages_from_user("snd"));
        if ($neum == "1") {
            $show_message_count .= "<br>"._("Eine Nachricht ist ungelesen.");
        } else if ($neum > "1") {
            $show_message_count .= "<br>".sprintf(_("%s Nachrichten sind ungelesen."), ($neum));
        }
        // assemble infobox
        $infobox = array($tmp_array_1,
            array("kategorie" => _("Information:"),"eintrag" => array(
                array('icon' => 'icons/16/black/info.png', "text" => $show_message_count))),
            array("kategorie" => _("nach Zeit filtern:"),"eintrag" => array(
                array('icon' => 'icons/16/black/new/mail.png', "text" => $time_by_links))),
            array("kategorie" => _("weitere Ansichten:"),"eintrag" => array(
                array('icon' => 'icons/16/black/new/mail.png', "text" => $view_by_links))),
            array("kategorie" => _("Optionen:"),"eintrag" => array(
                array("icon" => 'icons/16/black/admin.png', "text" => "<a href=\"".URLHelper::getLink("?cmd_show=openall")."\">"._("Alle Nachrichten aufklappen")."</a><br><a href=\"".URLHelper::getLink("?cmd=mark_allsmsreaded")."\">"._("Alle als gelesen speichern")."</a>"),
                array("icon" => 'icons/16/black/add/folder-empty.png', "text" => "<a href=\"".URLHelper::getLink("?cmd=admin_folder&cmd_2=new")."\">"._("Neuen Ordner erstellen")."</a>")
            ))
        );
        // display infobox
        print_infobox($infobox, "infobox/messages.jpg"); ?>
    </td>
</tr>
<tr>
    <td class="blank" colspan="2">&nbsp;</td>
</tr>
</table>
<?php

if ($my_messaging_settings["last_box_visit"] < time()) {
    $my_messaging_settings["last_box_visit"] = time();
}
include ('lib/include/html_end.inc.php');
page_close();
