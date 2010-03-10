<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* Status Window for the Chat
*
* This script prints a status bar for the chat
*
* @author       André Noack <andre.noack@gmx.net>
* @access       public
* @modulegroup      chat_modules
* @module       chat_status
* @package      Chat
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// chat_status.php
//
// Copyright (c) 2003 André Noack <noack@data-quest.de>
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
require_once $RELATIVE_PATH_CHAT.'/ChatServer.class.php';
//Studip includes
require_once 'lib/msg.inc.php';
require_once 'lib/visual.inc.php';

$chatServer =& ChatServer::GetInstance($CHAT_SERVER_NAME);
$chatServer->caching = true;

?>
<html>
<head>
    <title>ChatStatus</title>
    <link rel="stylesheet" href="<?=$GLOBALS['ASSETS_URL']?>stylesheets/style.css" type="text/css">
<script type="text/javascript">
/**
* JavaScript
*/
    function printhelp(){
        parent.frames['frm_input'].document.inputform.chatInput.value="/help";
        parent.frames['frm_input'].document.inputform.submit();
    }

    function doLock(){
        parent.frames['frm_input'].document.inputform.chatInput.value="/lock";
        parent.frames['frm_input'].document.inputform.submit();
    }

    function doUnlock(){
        parent.frames['frm_input'].document.inputform.chatInput.value="/unlock";
        parent.frames['frm_input'].document.inputform.submit();
    }

    function doLogStart(){
        parent.frames['frm_input'].document.inputform.chatInput.value="/log start";
        parent.frames['frm_input'].document.inputform.submit();
    }

    function doLogStop(){
        parent.frames['frm_input'].document.inputform.chatInput.value="/log stop";
        parent.frames['frm_input'].document.inputform.submit();
    }

    function doLogSend(){
        parent.frames['frm_input'].document.inputform.chatInput.value="/log send";
        parent.frames['frm_input'].document.inputform.submit();
    }

</script>

</head>
<body style="background-color:#EEEEEE;background-image:url('<?= $GLOBALS['ASSETS_URL'] ?>images/steel1.jpg');">
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
    <table width="98%" border="0" bgcolor="white" cellspacing="0" cellpadding="0" align="center">
        <tr>
            <td width="80%" align="left" class="topic" >
            <?
            if ($chatServer->getPerm($user->id,$chatid)){
                ?>
                <a href="javascript:<?=(($chatServer->chatDetail[$chatid]['password']) ? "doUnlock();" : "doLock();")?>">
                <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/<?=(($chatServer->chatDetail[$chatid]['password']) ? "closelock.gif" : "openlock.gif")?>"
                    border="0" align="absmiddle"
                    <?=tooltip(($chatServer->chatDetail[$chatid]['password']) ? _("Zugangsschutz für diesen Chat aufheben") : _("Diesen Chat absichern"))?>>
                </a>
                <?
            } else {
                ?>
                <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/<?=(($chatServer->chatDetail[$chatid]['password']) ? "closelock.gif" : "openlock.gif")?>"
                    border="0" align="absmiddle"
                    <?=tooltip(($chatServer->chatDetail[$chatid]['password']) ? _("Dieser Chat ist zugangsbeschränkt.") : _("Dieser Chat ist nicht zugangsbeschränkt."))?>>
                <?
            }
            if (count($chatServer->chatDetail[$chatid]['log'])){
                ?>
                <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/logging.gif" border="0" align="absmiddle"
                    <?=tooltip(_("Dieser Chat wird aufgezeichnet."))?>>
                <?
            }
            ?>
            <b>Chat - <?=htmlReady($chatServer->chatDetail[$chatid]["name"])?></b>
            </td>
            <td width="20%" align="right" class="topic" >
            <?
            if ($chatServer->getPerm($user->id,$chatid)){
                if ($chatServer->chatDetail[$chatid]['users'][$user->id]['log']){
                    ?>
                    <a href="javascript:doLogSend();">
                    <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/file.gif"
                        border="0" hspace="5" align="absmiddle"
                        <?=tooltip(_("Download des letzten Chatlogs"))?>>
                    </a>
                    <?
                }
                ?>
                <a href="javascript:<?=(($chatServer->chatDetail[$chatid]['log'][$user->id]) ? "doLogStop();" : "doLogStart();")?>">
                <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/<?=(($chatServer->chatDetail[$chatid]['log'][$user->id]) ? "stop_log.gif" : "start_log.gif")?>"
                    border="0" hspace="5" align="absmiddle"
                    <?=tooltip(($chatServer->chatDetail[$chatid]['log'][$user->id]) ? _("Die Aufzeichnung für diesen Chat beenden.") : _("Eine Aufzeichnung für diesen Chat starten."))?>>
                </a>
                <?
            }
            ?>
            <a href="javascript:printhelp();">
            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/hilfe.gif" border=0 hspace="5" align="texttop" <?=tooltip(_("Chat Kommandos einblenden"))?>>
            </a>
            <a href="<?=$CANONICAL_RELATIVE_PATH_STUDIP?>show_smiley.php" target="_blank">
            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/asmile.gif" hspace="5" border=0 align="absmiddle" <?=tooltip(_("Alle verfügbaren Smileys anzeigen"))?>>
            </a></td>
        </tr>
    </table>
</div>
</body>
</html>
<?
page_close();
?>

