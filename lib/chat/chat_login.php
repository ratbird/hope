<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Login script for the Chat
*
* This script checks user permissions and builds a frameset for the chat
*
* @author       André Noack <andre.noack@gmx.net>
* @access       public
* @modulegroup      chat_modules
* @module       chat_login
* @package      Chat
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// chat_nicklist.php
// Shows the nicklist
// Copyright (c) 2002 André Noack <andre.noack@gmx.net>
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

/**
* Close the actual window if PHPLib shows login screen
* @const CLOSE_ON_LOGIN_SCREEN
*/
define("CLOSE_ON_LOGIN_SCREEN",true);
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

//chat eingeschaltet?
if (!$CHAT_ENABLE) {
    page_close();
    die;
}
include ('lib/seminar_open.php'); // initialise Stud.IP-Session

require_once $RELATIVE_PATH_CHAT.'/chat_func_inc.php';
//Studip includes
require_once 'lib/msg.inc.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';

//korrekte chatid ?
if (!$chatid || !in_array(get_object_type($chatid), array('user','sem','inst','fak','global'))) {
    page_close();
    die;
}
$chatServer = ChatServer::GetInstance($CHAT_SERVER_NAME);
$chatServer->caching = true;
$sms = new messaging();
$chat_entry_level = chat_get_entry_level($chatid);
$chat_entry_check = $chat_entry_level;
if ($chat_entry_level != "admin" && $chatServer->isActiveChat($chatid) && ($chatServer->chatDetail[$chatid]["password"] || $chat_entry_level === false)){
    $chat_entry_check = false;
    if ($_REQUEST['chat_password'] ){
        if ($chatServer->chatDetail[$chatid]['password'] == $_REQUEST['chat_password']){
            $chat_entry_check = true;
        } else {
            $msg = "error§<font size=\"-1\">". _("Das eingegebene Passwort ist falsch!") . "</font>§";
        }
    } else {
        if ($sms->check_chatinv($chatid)){
                $chat_entry_check = true;
        } else {
                $msg = "error§<font size=\"-1\">" . _("Es wurde keine g&uuml;ltige Einladung f&uuml;r diesen Chat gefunden!") . "</font>§";
        }
    }
    if (!$chat_entry_check && $chat_entry_level){
        $msg .= "info§" . "<form name=\"chatlogin\" method=\"post\" onSubmit=\"doSubmit();return false;\"action=\"".URLHelper::getLink('?chatid='.$chatid)."\">"
            . CSRFProtection::tokenTag()
            . "<b>" ._("Passwort erforderlich")
            . "</b><br><font size=\"-1\" color=\"black\">" . _("Um an diesem Chat teilnehmen zu k&ouml;nnen, m&uuml;ssen Sie das Passwort eingeben.")
            . "<br><input type=\"password\" style=\"vertical-align:middle;\" name=\"chat_password\">&nbsp;&nbsp;"
            . Button::createAccept(_('Absenden'), 'submit')."</font></form>§";
            }
}
if (!$chat_entry_check && !$chat_entry_level){
    $msg .= "error§" . "<b>" ._("Chatlogin nicht m&ouml;glich")
        . "</b><br><font size=\"-1\" color=\"black\">" . _("Sie k&ouml;nnen ohne eine g&uuml;ltige Einladung nicht an diesem Chat teilnehmen.") . "</font>§";
}

if ($chat_entry_check){
    $chatServer->addChat($chatid, chat_get_name($chatid));
    if (!$chatServer->addUser($user->id,$chatid,$auth->auth["uname"],get_fullname(),(($chat_entry_level == "admin") ? true : false))){
        $chat_entry_check = false;
        $msg = "error§<b>" ._("Chatlogin nicht m&ouml;glich"). "</b><br><font size=\"-1\" color=\"black\">"
            . _("Vermutlich sind Sie noch aus einer fr&uuml;heren Chat-Session angemeldet. Es dauert ca. 3-5 Sekunden bis Sie automatisch aus dem Chat entfernt werden. Versuchen Sie es etwas sp&auml;ter noch einmal.")
            . "</font>§";
    }
}
if (!$chat_entry_check){
    ?>
    <html>
    <head>
     <title>Stud.IP</title>
    <link rel="stylesheet" href="<?=$GLOBALS['ASSETS_URL']?>stylesheets/style.css" type="text/css">
    <script type="text/javascript" language="javascript" src="<?= $GLOBALS['ASSETS_URL'] ?>javascripts/md5.js"></script>
    <script type="text/javascript">
    /**
    * JavaScript
    */
    var chatuniqid = '<?=$chatServer->chatDetail[$chatid]["id"]?>';
    function doSubmit() {
        if (document.chatlogin.chat_password.value){
            document.chatlogin.chat_password.value = MD5(chatuniqid + ":" + document.chatlogin.chat_password.value);
            document.chatlogin.submit();
        }
        return false;
    }
    </script>
    </head>
    <body>
    <table border=0 bgcolor="#000000" align="center" cellspacing=0 cellpadding=0 width=70%>
    <tr valign=top align=middle>
        <td class="topic" align="left"><b>&nbsp;<?=_("Zugriff verweigert")?></b></td>
        </tr>
        <tr><td class="blank">&nbsp;</td></tr>
        <?
        parse_msg ($msg, "§", "blank", 1);
        ?>
        <tr><td class="blank"><font size=-1>&nbsp;<a href="javascript:window.close()"><?=_("Fenster schließen")?></a><br>&nbsp;</font>
        </td></tr>
    </table>
    </body>
    </html>

    <?
    page_close();
    die;
}
//evtl Chateinladungen löschen
$sms->delete_chatinv();
if (UserConfig::get($user->id)->CHAT_USE_AJAX_CLIENT ){
    include "chat_client_ajax.php";
} else {

?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat(<?=$auth->auth["uname"]?>) -
    <?=htmlReady($chatServer->chatDetail[$chatid]["name"])?></title>
<script type="text/javascript">
    //
    // JavaScript
    //
    var chatuniqid = '<?=$chatServer->chatDetail[$chatid]["id"]?>';
    function coming_home(url) {
        if (opener.closed) alert('<?=_("Das Hauptfenster wurde geschlossen,\\ndiese Funktion kann nicht mehr ausgeführt werden!")?>');
        else {
            opener.location.href = url;
            opener.focus();
        }
        return false;
    }

    </script>
</head>
<frameset rows="83%,30,*,0" FRAMEBORDER=NO FRAMESPACING=0 FRAMEPADDING=0 border=0>
    <frameset cols="*,25%" FRAMEBORDER=NO FRAMESPACING=0 FRAMEPADDING=0 border=0>
        <frame name="frm_chat" src="chat_dispatcher.php?target=chat_client.php&chatid=<?=$chatid?>" marginwidth=1 marginheight=1>
        <frame name="frm_nicklist" src="chat_dispatcher.php?target=chat_nicklist.php&chatid=<?=$chatid?>"  marginwidth=1 marginheight=1>
    </frameset>
<frame name="frm_status" src="chat_dispatcher.php?target=chat_status.php&chatid=<?=$chatid?>" marginwidth=1 marginheight=2 >
<frame name="frm_input" src="chat_dispatcher.php?target=chat_input.php&chatid=<?=$chatid?>" marginwidth=1 marginheight=0 >
<frame name="frm_dummy" src="chat_dispatcher.php?target=chat_dummy.php&chatid=<?=$chatid?>" marginwidth=0 marginheight=0 scrolling=no noresize >
</frameset>
</html>
<?
}
page_close();
?>
