<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Ausgabe der Nicklist
*
*
*
* @author       André Noack <andre.noack@gmx.net>
* @access       public
* @modulegroup  chat_modules
* @module       chat_nicklist
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
require_once $RELATIVE_PATH_CHAT.'/ChatServer.class.php';

//Studip includes
require_once 'lib/msg.inc.php';
require_once 'lib/visual.inc.php';


$chatServer = ChatServer::GetInstance($CHAT_SERVER_NAME);

$chatServer->caching = true;

?>
<html>
<head>
    <title>Chat Nicklist</title>
    <link rel="stylesheet" href="<?=$GLOBALS['ASSETS_URL']?>stylesheets/style.css" type="text/css">
</head>
<body style="background-color:#EEEEEE; background-color: #f3f5f8;">
<?
//darf ich überhaupt hier sein ?
if (!$chatServer->isActiveUser($user->id,$chatid)) {
    ?><table width="100%"><tr><?
    my_error('<font size="-1">'._("Sie sind nicht in diesem Chat angemeldet!").'</font>','chat',1,false);
    ?></tr></table></body></html><?
    page_close();
    die;
}
?>
<div align="center">
<table align="center" border="0" bgcolor="#FFFFFF" cellpadding="0" cellspacing="2"  width="95%">
<tr>
    <td align="center">
        <table align="center" border="0" cellpadding="1" cellspacing="1" width="100%">
            <tr>
                <td class="table_header_bold" align="center"><b>Nicklist</b></td>
            </tr>
            <?
            $is_admin = $chatServer->getPerm($user->id,$chatid);
            $chat_users = $chatServer->getUsers($chatid);
            foreach ($chat_users as $chatUserId => $chatUserDetail){
                    if ($chatUserDetail["action"]){
                        echo "\n<tr><td><span style=\"font-size:10pt\">";
                        if ($chatUserDetail["perm"])  echo "<b>";
                        echo "<a href=\"#\" ". tooltip(_("Profil aufrufen"),false)
                            . "onClick=\"return parent.coming_home('{$CANONICAL_RELATIVE_PATH_STUDIP}about.php?username=".$chatUserDetail["nick"]."')\">"
                            . htmlReady($chatUserDetail["fullname"])."</a><br>";
                        if ($chatUserId != $user->id){
                            if ($is_admin){
                                echo "\n<a href=\"#\" " . tooltip(_("diesen Nutzer / diese Nutzerin aus dem Chat werfen"),false)
                            . "onClick=\"parent.frames['frm_input'].document.inputform.chatInput.value='/kick "
                            . $chatUserDetail["nick"] . " ';parent.frames['frm_input'].document.inputform.submit();return false;\">#</a>&nbsp;";
                            }
                            echo "\n<a href=\"#\" " . tooltip(_("diesem Nutzer / dieser Nutzerin eine private Botschaft senden"),false)
                            . "onClick=\"parent.frames['frm_input'].document.inputform.chatInput.value='/private "
                            . $chatUserDetail["nick"] . " ';return false;\">@</a>&nbsp;";
                        }
                        echo "(".$chatUserDetail["nick"].")";
                        if ($chatUserDetail["perm"])  echo "</b>";
                        echo "</span></td></tr>";
                    }
            }
            ?>
        </table>
    </td>
</tr>
</table>
</div>
</body>
</html>
<?
page_close();

?>
