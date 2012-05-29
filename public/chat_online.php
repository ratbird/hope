<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
 * chat_online.php - overview of studip chatrooms
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @author      Suchi & Berg GmbH <info@data-quest.de>
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     chat
 */

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once ('lib/visual.inc.php');
require_once ('lib/user_visible.inc.php');
if (get_config('CHAT_ENABLE')) {
    include_once $RELATIVE_PATH_CHAT."/chat_func_inc.php";
    $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
    $chatServer->caching = true;
    if ($_REQUEST['kill_chat']){
        chat_kill_chat($_REQUEST['kill_chat']);
    }
    $sms = new messaging();
} else {
    page_close();
    die;
}

function print_chat_info($chatids)
{
    global $chatServer,$auth,$sms,$chat_online_id,$PHP_SELF;

    for ($i = 0; $i < count($chatids); ++$i) {
        $chat_id = $chatids[$i];
        if ($chatServer->isActiveUser($_REQUEST['search_user'], $chat_id)) {
            $chat_online_id[$chat_id] = true;
        }
        $chatter = $chatServer->isActiveChat($chat_id);
        $chatinv = $sms->check_chatinv($chat_id);
        $is_active = $chatServer->isActiveUser($auth->auth['uid'],$chat_id);
        $chatname = ($chatter) ? $chatServer->chatDetail[$chat_id]['name'] : chat_get_name($chat_id);
        $link = $PHP_SELF . "?chat_id=" . $chat_id . "&cmd=" . (($chat_online_id[$chat_id]) ? "close" : "open");
        $link_name = "<a class=\"tree\" href=\"$link\">" . htmlReady($chatname) . "</a>";
        echo "\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><tr>";
        printhead(0,0,$link,(($chat_online_id[$chat_id])) ? "open" : "close", true, chat_get_chat_icon($chatter, $chatinv, $is_active), $link_name, "");
        echo "\n</tr></table>";
        if ($chat_online_id[$chat_id]){
            echo "\n<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">";
            echo chat_get_content($chat_id, $chatter, $chatinv, $chatServer->chatDetail[$chat_id]['password'], $is_active, $chatServer->getUsers($chat_id));
            echo "\n</table>";
        }
    }
}

PageLayout::setHelpKeyword("Basis.InteraktionChat");
PageLayout::setTitle(_("Chat-Online"));
Navigation::activateItem('/community/chat');
// add skip link
SkipLinks::addIndex(_("Allgemeiner Chatraum"), 'chat_studip', 100);
SkipLinks::addIndex(html_entity_decode(_("Pers&ouml;nlicher Chatraum")), 'chat_own');


// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

if (!$sess->is_registered("chat_online_id")){
    $sess->register("chat_online_id");
}
if (!$_REQUEST['chat_id'] && !$_REQUEST['kill_chat']){
    $chat_online_id = null;
} else {
    $chat_online_id[$_REQUEST['chat_id']] = ($_REQUEST['cmd'] == "open") ? true : false;
}
$chatter = $chatServer->getAllChatUsers();
$active_chats = count($chatServer->chatDetail);
if ($active_chats){
    $chatids = array_keys($chatServer->chatDetail);
    if (count($chatids)){
        $vis_query = get_vis_query('a', 'chat'); // 'a' is auth_user_md5
        $query = "SELECT a.user_id, {$vis_query} AS is_visible
                  FROM auth_user_md5 AS a
                  LEFT JOIN user_visibility USING (user_id)
                  WHERE user_id IN (?) AND user_id != ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($chatids, $auth->auth['uid']));
        foreach ($statement as $row) {
            if ($row['is_visible']) {
                $active_user_chats[] = $row['user_id'];
            } else {
                $hidden_user_chats[] = $row['user_id'];
            }
        }

        $query = "SELECT Seminar_id FROM seminare WHERE Seminar_id IN (?) AND visible = 1";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($chatids));
        $active_sem_chats = $statement->fetchAll(PDO::FETCH_COLUMN);

        $query = "SELECT Institut_id FROM Institute WHERE Institut_id IN (?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($chatids));
        $active_inst_chats = $statement->fetchAll(PDO::FETCH_COLUMN);
    }
}
chat_get_javascript();
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr valign="top">
        <td valign="top" class="blank" align="center">
            <table border="0" cellpadding="1" cellspacing="0" width="98%" align="center" valign="top" class="blank">
                <tr>
                    <td class="blank">
                <?=_("Hier sehen Sie eine &Uuml;bersicht aller aktiven Chatr&auml;ume.")?>
                <br>&nbsp;</td>
                </tr>
                <tr>
                    <td class="topic" >
                    <font size="-1">
                    &nbsp;<b><?=_("Allgemeiner Chatraum")?></b>
                    </font>
                    </td>
                </tr>
                <tr>
                    <td id="chat_studip">
                    <? print_chat_info(array('studip'));?>
                    </td>
                </tr>
                <tr>
                    <td class="blank">&nbsp;</td>
                </tr>
                <tr>
                    <td class="topic" >
                    <font size="-1">
                    &nbsp;<b><?=_("Pers&ouml;nlicher Chatraum")?></b>
                    </font>
                    </td>
                </tr>
                <tr>
                    <td id="chat_own">
                    <? print_chat_info(array($auth->auth['uid']));?>
                    </td>
                </tr>
                <tr>
                    <td class="blank">&nbsp;</td>
                </tr>
<?if(!empty($active_user_chats) || !empty($hidden_user_chats)){?>
<? SkipLinks::addIndex(html_entity_decode(_("Chatr&auml;ume anderer NutzerInnen")), 'chat_user') ?>
                <tr>
                    <td class="topic" >
                    <font size="-1">
                    &nbsp;<b><?=_("Chatr&auml;ume anderer NutzerInnen")?></b>
                    </font>
                    </td>
                </tr>
                <tr>
                    <td id="chat_user">
                    <? print_chat_info($active_user_chats);?>
                    <?php
                    if (is_array($hidden_user_chats)) {
                        if (sizeof($hidden_user_chats) == 1) {
                            echo _("+1 weiterer, unsichtbarer Chatraum.");
                        } else {
                            sprintf(_("+%s weitere, unsichtbare Chaträume."), sizeof($invisible_user_chats));
                        }
                    }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td class="blank">&nbsp;</td>
                </tr>
<? } ?>
<?if(!empty($active_sem_chats)){?>
<? SkipLinks::addIndex(html_entity_decode(_("Chatr&auml;ume f&uuml;r Veranstaltungen")), 'chat_sem') ?>
                <tr>
                    <td class="topic" >
                    <font size="-1">
                    &nbsp;<b><?=_("Chatr&auml;ume f&uuml;r Veranstaltungen")?></b>
                    </font>
                    </td>
                </tr>
                <tr>
                    <td id="chat_sem">
                    <? print_chat_info($active_sem_chats);?>
                    </td>
                </tr>
                <tr>
                    <td class="blank">&nbsp;</td>
                </tr>
<? } ?>
<?if(!empty($active_inst_chats)){?>
<? SkipLinks::addIndex(html_entity_decode(_("Chatr&auml;ume f&uuml;r Einrichtungen")), 'chat_inst') ?>
                <tr>
                    <td class="topic" >
                    <font size="-1">
                    &nbsp;<b><?=_("Chatr&auml;ume f&uuml;r Einrichtungen")?></b>
                    </font>
                    </td>
                </tr>
                <tr>
                    <td id="chat_inst">
                    <? print_chat_info($active_inst_chats);?>
                    </td>
                </tr>
<? } ?>
                <tr>
                    <td class="blank">&nbsp;</td>
                </tr>
                </table>
<?


//Info-field on the right side
?>

</td>
<td class="blank" width="270" align="right" valign="top">
<?

// Berechnung der uebrigen Seminare

if (!$chatter){
    $chat_tip = _("Es ist niemand im Chat");
} elseif ($chatter == 1){
    $chat_tip =_("Es ist eine Person im Chat");
} else {
    $chat_tip = sprintf(_("Es sind %s Personen im Chat"), $chatter);
}
if ($active_chats == 1){
    $chat_tip .= ", " . _("ein aktiver Chatraum");
} elseif ($active_chats > 1){
    $chat_tip .= ", " . sprintf(_("%s aktive Chaträume"), $active_chats);
}

$infobox = array    (
    array  ("kategorie"  => _("Information:"),
        "eintrag" => array  (
            array("icon" => "icons/16/black/info.png",
                  "text"  => $chat_tip
            )
        )
    ),
    array  ("kategorie" => _("Symbole:"),
        "eintrag" => array  (
            array("icon" => "icons/16/grey/chat.png",
                  "text" => _("Dieser Chatraum ist leer")
            ),
            array("icon" => "icons/16/red/new/chat.png",
                  "text" => _("Eine oder mehrere Personen befinden sich in diesem Chatraum")
            )
        )
    )
);

// print the info_box
print_infobox ($infobox, "infobox/seminars.jpg");
?>
        </td>
    </tr>
</table>
<?php
    include ('lib/include/html_end.inc.php');
    page_close();
?>