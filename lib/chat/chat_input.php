<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Input Window for the Chat
*
* This script prints a HTML input form and handles color changing and quitting the chat with some JavaScript
*
* @author       André Noack <andre.noack@gmx.net>
* @access       public
* @modulegroup      chat_modules
* @module       chat_input
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
$chatid = Request::option('chatid');
$chatInput = Request::quoted('chatInput');
require_once $RELATIVE_PATH_CHAT.'/ChatServer.class.php';
//Studip includes
require_once 'lib/msg.inc.php';
require_once 'lib/visual.inc.php';

$chatServer = ChatServer::GetInstance($CHAT_SERVER_NAME);
$chatServer->caching = true;

?>
<html>
<head>
    <title>ChatInput</title>
    <link rel="stylesheet" href="<?=$GLOBALS['ASSETS_URL']?>stylesheets/style.css" type="text/css">
<script type="text/javascript">
    function strltrim() {
        return this.replace(/^\s+/,'');
    }
    function strrtrim() {
        return this.replace(/\s+$/,'');
    }
    function strtrim() {
        return this.replace(/^\s+/,'').replace(/\s+$/,'');
    }

    String.prototype.ltrim = strltrim;
    String.prototype.rtrim = strrtrim;
    String.prototype.trim = strtrim;
/**
* JavaScript
*/
    function doQuit(){
        document.inputform.chatInput.value="/quit bye";
        document.inputform.submit();
    }

/**
* JavaScript
*/
    function doColorChange(){
        for(i=0;i<document.inputform.chatColor.length;++i)
            if(document.inputform.chatColor.options[i].selected == true){
            document.inputform.chatInput.value="/color " +
            document.inputform.chatColor.options[i].value;
            document.inputform.submit();
            }
    }

/**
* JavaScript
*/
    function doCheck(){
        var the_string = document.inputform.chatInput.value.trim();
        if (the_string.substring(0,the_string.indexOf(" ")) == "/password"){
            document.inputform.chatInput.value = "/password " +
                parent.MD5(parent.chatuniqid + ":" + the_string.substring(the_string.indexOf(" "),the_string.length).trim());
            document.inputform.submit();
            return false;
        } else {
            return true;
        }
    }


</script>

</head>
<body style="background-color:#EEEEEE; background-color: #f3f5f8;">
<?
//darf ich überhaupt hier sein ?
if (!$chatServer->isActiveUser($user->id,$chatid)) {
    ?><table width="100%"><tr><?
    my_error('<font size="-1">'._("Sie sind nicht in diesem Chat angemeldet!").'</font>',"chat",1,false);
    ?></tr></table></body></html><?
    page_close();
    die;
}


//neue chatnachricht einfügen
if ($chatInput) {
    var_dump($chatInput);
    var_dump('inpv');
    if ($chatServer->isActiveUser($user->id,$chatid)){
       var_dump($chatInput);
       var_dump('inpnactive');
        $chatServer->addMsg($user->id,$chatid,$chatInput);
        //evtl Farbe umstellen
        $cmdStr = trim(substr($chatInput." ",1,strpos($chatInput," ")-1));
        $msgStr = trim(strstr($chatInput," "));
        if ($cmdStr == "color" && $msgStr != "" && $msgStr != "\n" && $msgStr != "\r")
            $chatServer->chatDetail[$chatid]["users"][$user->id]["color"] = $msgStr;

        }

}

?>
<form method="post" action="chat_dispatcher.php?target=chat_input.php" name="inputform" onSubmit="return doCheck();">
<?= CSRFProtection::tokenTag() ?>
<input type="hidden" name="chatid" value="<?=$chatid?>">
<div align="center">
    <table width="98%" border="0" bgcolor="white" cellspacing="0" cellpadding="0" align="center">
        <tr>
            <td align="center" colspan=2 valign="middle">
                <table width="100%" border="0" cellspacing="3">
                <tr valign="middle">
                    <td align="left" valign="middle">Message:</td>
                    <td width="60%" valign="middle">
                        <div align="center" valign="middle">
                        <input type="text" name="chatInput" size=18 style="width: 100%" >
                        </div>
                    </td>
                    <td align="left" valign="middle">
                        <select name="chatColor" onChange="doColorChange();">
                        <?
                        foreach($chatColors as $c){
                            print "<option style=\"color:$c;\" value=\"$c\" ";
                            if ($chatServer->chatDetail[$chatid]["users"][$user->id]["color"] == $c){
                                $selected = true;
                                print " selected ";
                            }
                            print ">$c</option>\n";
                        }
                        if (!$selected) {
                            print "<option style=\"color:" . $chatServer->chatDetail[$chatid]["users"][$user->id]["color"].";\"
                                value=\"".$chatServer->chatDetail[$chatid]["users"][$user->id]["color"] . "\" selected>user</option>\n";
                        }
                        ?>
                        </select>
                    </td>
                    <td align="center" valign="middle">
                        <?= Button::createAccept(_('Absenden'), array('title' => _("Nachricht senden"))) ?>
                    </td>
                    <td align="right" valign="middle">
                        <?= LinkButton::create(_('Chat beenden'), 'javascript:doQuit();', array('title' => _("Chat verlassen"))) ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</div>
</form>
<script  type="text/javascript">
document.inputform.chatInput.focus();
</script>
</body>
</html>
<?
page_close();
?>

