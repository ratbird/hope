<?
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/**
 * chat_func_inc.php - Chat Functions
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     chat
 */


require_once $GLOBALS['RELATIVE_PATH_CHAT'].'/ChatServer.class.php';
//Studip includes
require_once 'lib/visual.inc.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/functions.php';
require_once 'lib/contact.inc.php';

function chat_kill_chat($chatid)
{
    if (get_config('CHAT_ENABLE')) {
        if (chat_get_entry_level($chatid) == "admin"){
            $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
            $chatServer->caching = false;
            $chatServer->removeChat($chatid);
            $chatServer->caching = true;
        }
    }
}

function chat_get_chat_icon($chatter, $chatinv, $is_active, $as_icon = false, $color = 'grey', $active_color = 'red', $class = "middle")
{
    if (get_config('CHAT_ENABLE')) {
        #$pic_prefix = ($as_icon) ? "icon-" : "";
        $image = 'icons/16/';
        if (!$chatter){
            $image .= $color.'/chat.png';
            $title = _("Dieser Chatraum ist leer");
        } elseif ($chatinv){
            $image .= $active_color.'/new/chat.png';
            $title = _("Sie haben eine gültige Einladung für diesen Chatraum")
                . " " . (($chatter == 1) ? _("Es ist eine Person in diesem Chatraum.") : sprintf(_("Es sind %s Personen in diesem Chatraum"),$chatter));
        } elseif ($chatter == 1 && $is_active) {
            $image .= $active_color.'/new/chat.png';
            $title = _("Sie sind alleine in diesem Chatraum");
        } else {
            $image .= $active_color.'/new/chat.png';
            $title = ($chatter == 1) ? _("Es ist eine Person in diesem Chatraum.") : sprintf(_("Es sind %s Personen in diesem Chatraum"),$chatter);
        }
        return Assets::img($image, array('title' => $title, 'class' => $class));
    } else {
        return false;
    }
}

function chat_get_entry_level($chatid)
{
    global $perm,$user,$auth;
    $object_type = get_object_type($chatid);
    $chat_entry_level = false;
    if (!$perm->have_perm("root")){;
        switch($object_type){
            case "user":
            if ($chatid == $user->id){
                $chat_entry_level = "admin";
            } elseif (CheckBuddy($auth->auth['uname'], $chatid)){
                $chat_entry_level = "user";
            }
            break;

            case "sem" :
            if ($perm->have_studip_perm("tutor",$chatid)){
                $chat_entry_level = "admin";
            } elseif ($perm->have_studip_perm("user",$chatid)){
                $chat_entry_level = "user";
            }
            break;

            case "inst" :
            case "fak" :
            if ($perm->have_studip_perm("admin",$chatid)){
                $chat_entry_level = "admin";
            } elseif ($perm->have_studip_perm("autor",$chatid)){
                $chat_entry_level = "user";
            }
            break;

            default:
            if ($chatid == "studip"){
                $chat_entry_level = "user";
            }
        }
    } else {
        $chat_entry_level = "admin";
    }
    return $chat_entry_level;
}

function chat_get_name($chatid)
{
    if ($chatid == 'studip') {
        return 'Stud.IP Global Chat';
    }

    $db = DBManager::get();

    // Chatting in a seminar
    $statement = $db->prepare("SELECT Name from seminare WHERE Seminar_id = ?");
    $statement->execute(array($chatid));
    if ($name = $statement->fetchColumn()) {
        return $name;
    }

    // Chatting in an institute
    $statement = $db->prepare("SELECT Name from Institute WHERE Institut_id = ?");
    $statement->execute(array($chatid));
    if ($name = $statement->fetchColumn()) {
        return $name;
    }

    // Chatting with a user
    $statement = $db->prepare("SELECT {$GLOBALS['_fullname_sql']['full']} "
           . "FROM auth_user_md5 a "
           . "LEFT JOIN user_info USING (user_id) "
           . "WHERE a.user_id = ?");
    $statement->execute(array($chatid));
    if ($name = $statement->fetchColumn()) {
        return $name;
    }

    return false;
}

function chat_show_info($chatid)
{
    global $auth;
    if (get_config('CHAT_ENABLE')) {
        $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
        $sms = new messaging();
        $chatter = $chatServer->isActiveChat($chatid);
        $chatinv = $sms->check_chatinv($chatid);
        $is_active = $chatServer->isActiveUser($auth->auth['uid'],$chatid);
        $chatname = ($chatter) ? $chatServer->chatDetail[$chatid]['name'] : chat_get_name($chatid);
        if (chat_get_entry_level($chatid) || $is_active || $chatinv){
            // add skip link
            SkipLinks::addIndex(_("Chatraum"), 'chat_show_info');
            //Ausgabe der Kopfzeile
            chat_get_javascript();
            echo "\n<table class=\"index_box\" role=\"article\" style=\"width: 100%;\" id=\"chat_show_info\">";
            echo "\n<tr><td class=\"topic\" colspan=\"2\">";
            echo "\n" . chat_get_chat_icon($chatter, $chatinv, $is_active, false, 'white', 'white');
            echo "\n <b>" . _("Chatraum:") . " " . htmlReady($chatname) . "</b></td></tr>";
            echo chat_get_content($chatid,$chatter,$chatinv,$chatServer->chatDetail[$chatid]['password'],$is_active,$chatServer->getUsers($chatid));
            echo "\n</table>";
            return true;
        }
    }
    return false;
}

function chat_get_content($chatid, $chatter, $chatinv, $password, $is_active, $chat_user)
{
    $pic_path = $GLOBALS['ASSETS_URL']."images/";
    $ret = "\n<tr><td class=\"steel1\" width=\"50%\" valign=\"center\"><p class=\"info\">";
    if (($entry_level = chat_get_entry_level($chatid)) || $chatinv){
        if (!$is_active){
            $ret .= "<a href=\"#\" onClick=\"javascript:return open_chat('$chatid');\">";
            $ret .= "<img src=\"".Assets::image_path('icons/16/grey/chat.png')."\" " . tooltip(_("Diesen Chatraum betreten")) ." ></a> ";
            $ret .= sprintf(_("Sie k&ouml;nnen diesen Chatraum %sbetreten%s."),"<a href=\"#\" onClick=\"javascript:return open_chat('$chatid');\">","</a>");
            if ($chatinv){
                $ret .= "&nbsp;" . _("(Sie wurden eingeladen.)");
            }
        } else {
            $ret .= "<img src=\"".Assets::image_path('icons/16/grey/chat.png')."\" " . tooltip(_("Sie haben diesen Chatraum bereits betreten.")) ."> ";
            $ret .= _("Sie haben diesen Chatraum bereits betreten.");
        }
        if ($password){
            $ret .= "<br><img border=\"0\" align=\"absmiddle\" src=\"".Assets::image_path('icons/16/grey/lock-locked.png')."\" >&nbsp;&nbsp;";
            $ret .= _("Dieser Chatraum ist mit einem Passwort gesichert.");
        }
        if ($chatter && $entry_level == "admin"){
            $ret .= "<br><a href=\"" . $GLOBALS['PHP_SELF'] . "?kill_chat=$chatid\">";
            $ret .= "<img src=\"".Assets::image_path('icons/16/grey/trash.png')."\" " . tooltip(_("Diesen Chatraum leeren")) ."></a> ";
            $ret .= sprintf(_("Diesen Chatraum %sleeren%s"),"<a href=\"" . $GLOBALS['PHP_SELF'] . "?kill_chat=$chatid\">","</a>");
        }
        if ($entry_level == "admin" && count($_SESSION['chat_logs'][$chatid])){
            $ret .= '<br>'._("Ihre gespeicherten Aufzeichnungen:");
            $ret .= '<ol style="margin:3px;padding:3px;">';
            foreach($_SESSION['chat_logs'][$chatid] as $log_id => $chat_log){
                $ret .= '<li style="list-style-image:url('.Assets::image_path('icons/16/grey/log.png').');list-style-position:inside">';
                $ret .= '<a href="#" onclick="window.open(\'chat_dispatcher.php?target=chat_dummy.php&log_id='.$log_id.'&chatid='.$chatid.'\', \'chat_dummy\', \'scrollbars=no,width=100,height=100,resizable=no\');return false;">';
                $ret .= _("Start") . ': ' . strftime('%X', $chat_log['start']) . ', ' . (int)count($chat_log['msg']) . ' ' . _("Zeilen");
                $ret .= '</li>';
                $ret .= '</a>';
            }
            $ret .= '</ol>';

        }
    } else {
        $ret .=  Assets::img('icons/16/grey/decline/chat.png', array('class' => 'text-top'));
        $ret .= "&nbsp;&nbsp;". _("Um diesen Chatraum zu betreten, brauchen Sie eine g&uuml;ltige Einladung.");
    }
    $ret .= "</p></td><td class=\"steel1\" width=\"50%\" valign=\"center\"><p class=\"info\">";
    if (!$chatter){
        $ret .= _("Dieser Chatraum ist leer.");
    } else {
        $ret .= ($chatter == 1) ? _("Es ist eine Person in diesem Chatraum.") : sprintf(_("Es sind %s Personen in diesem Chatraum"),$chatter);
        $ret .= "<br>(";
        $c = 0;
        foreach ($chat_user as $chat_user_id => $detail){
            $ret .= "<a href=\"{$GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP']}about.php?username={$detail['nick']}\">"
            . htmlReady($detail['fullname']) . "</a>";
            if (++$c != $chatter){
                $ret .= ", ";
            }
        }
        $ret .= ")";
    }
    $ret .= "</p></td></tr>";
    return $ret;
}

function chat_get_online_icon($user_id = false, $username = false, $pref_chat_id = false)
{
    global $i_page;
    if (get_config('CHAT_ENABLE')) {
        if ($user_id && !$username){
            $username = get_username($user_id);
        }
        if (!$user_id && $username){
            $user_id = get_userid($username);
        }
        if (!$user_id && !$username){
            return false;
        }
        #$pic_path = $GLOBALS['ASSETS_URL']."images/";

        $stud_path = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'];
        $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
        $admin_chats = $chatServer->getAdminChats($GLOBALS['auth']->auth['uid']);
        if ($tmp_num_chats = $chatServer->chatUser[$user_id]) {
            $ret  = "<a href=\"{$stud_path}chat_online.php?search_user={$user_id}\">";
            $ret .= Assets::img('icons/16/blue/new/chat.png', tooltip(($tmp_num_chats == 1) ? _("Dieser Benutzer befindet sich in einem Chatraum.") : sprintf(_("Dieser Benutzer befindet sich in %s Chaträumen"), $tmp_num_chats)));
            $ret .= "</a>";
        } elseif (is_array($admin_chats)) {
            $ret = "<a href=\"{$stud_path}sms_send.php?sms_source_page=$i_page&cmd=write_chatinv&rec_uname=$username";
            if ($pref_chat_id && $admin_chats[$pref_chat_id]){
                $ret .= "&selected_chat_id=$pref_chat_id";
            }
            $ret .= "\">".Assets::img('icons/16/blue/add/chat.png', array('title' => _("zum Chatten einladen"), 'class' => 'text-bottom'))."</a>";
        } else {
            $ret = Assets::img('icons/16/grey/decline/chat.png', array('title' => _("Sie haben in keinem aktiven Chatraum die Berechtigung andere NutzerInnen einzuladen"), 'class' => 'text-bottom'));
        }
        return $ret;
    } else {
        return "&nbsp;";
    }
}

function chat_get_javascript()
{
    global $auth;
    echo "\t\t<script type=\"text/javascript\">\n";
    echo "\t\tfunction open_chat(chatid) {\n";
    echo "\t\t\tif(!chatid){\n";
    printf ("\t\t\t\talert('%s');\n", _("Sie sind bereits in diesem Chat angemeldet!"));
    echo "\t\t\t} else {\n\t\t\tfenster=window.open(\"chat_dispatcher.php?target=chat_login.php&chatid=\" + chatid,\"chat_\" + chatid + \"_".$auth->auth["uid"]."\",\"scrollbars=no,width=640,height=480,resizable=yes\");\n";
    echo "\t\t}\nreturn false;\n}\n";
    echo "\t\t</script>\n";
}