<?php
# Lifter002: TEST
# Lifter003: TEST
# Lifter007: TODO
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
SkipLinks::addIndex(html_entity_decode(_("Persönlicher Chatraum")), 'chat_own');

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

// Prepare infobox
if (!$chatter) {
    $chat_tip = _('Es ist niemand im Chat');
} elseif ($chatter == 1) {
    $chat_tip =_('Es ist eine Person im Chat');
} else {
    $chat_tip = sprintf(_('Es sind %s Personen im Chat'), $chatter);
}
if ($active_chats == 1) {
    $chat_tip .= ', ' . _('ein aktiver Chatraum');
} elseif ($active_chats > 1) {
    $chat_tip .= ', ' . sprintf(_('%s aktive Chaträume'), $active_chats);
}

$infobox = array(
    'picture' => 'infobox/seminars.jpg',
    'content' => array(
        array(
            'kategorie'  => _('Information:'),
            'eintrag'    => array(
                array(
                    'icon' => 'icons/16/black/info.png',
                    'text' => $chat_tip
                )
            )
        ),
        array(
            'kategorie' => _('Symbole:'),
            'eintrag'   => array(
                array(
                    'icon' => 'icons/16/grey/chat.png',
                    'text' => _('Dieser Chatraum ist leer')
                ),
                array(
                    'icon' => 'icons/16/red/new/chat.png',
                    'text' => _('Eine oder mehrere Personen befinden sich in diesem Chatraum')
                )
            )
        )
        
    )
);

// Create, prepare and display template
$template = $GLOBALS['template_factory']->open('chat/online');
$template->set_layout($GLOBALS['template_factory']->open('layouts/base'));

$template->active_user_chats = $active_user_chats;
$template->hidden_user_chats = $hidden_user_chats;
$template->active_sem_chats  = $active_sem_chats;
$template->active_inst_chats = $active_inst_chats;
$template->infobox = $infobox;

echo $template->render();

page_close();
