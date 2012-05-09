<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

$chatServer = ChatServer::GetInstance($CHAT_SERVER_NAME);
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
    <table width="98%" border="0" bgcolor="white" cellspacing="0" cellpadding="0" align="center">
        <tr>
            <td width="80%" align="left" class="topic" >
            <?
            if ($chatServer->getPerm($user->id,$chatid)) {
                if ($chatServer->chatDetail[$chatid]['password']) {
                ?>
                    <a href="javascript:doUnlock();">
                    <?= Assets::img('icons/16/white/lock-locked.png', array('title' => _("Zugangsschutz für diesen Chat aufheben"), 'class' => 'text-top')) ?>
                    </a>
                <? } else { ?>
                    <a href="javascript:doLock();">
                    <?= Assets::img('icons/16/white/lock-unlocked.png', array('title' => _("Diesen Chat absichern"), 'class' => 'text-top')) ?>
                    </a>
                <? }
            } else {
                if ($chatServer->chatDetail[$chatid]['password']) {
                ?>
                    <?= Assets::img('icons/16/white/lock-locked.png', array('title' => _("Dieser Chat ist zugangsbeschränkt."), 'class' => 'text-top')) ?>
                <? } else { ?>
                    <?= Assets::img('icons/16/white/lock-unlocked.png', array('title' => _("Dieser Chat ist nicht zugangsbeschränkt."), 'class' => 'text-top')) ?>
                <? }
            }
            if (count($chatServer->chatDetail[$chatid]['log'])){
               echo Assets::img('icons/16/white/log.png', array('class' => 'text-top', 'title' =>_("Dieser Chat wird aufgezeichnet.")));
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
                    <?= Assets::img('icons/16/blue/download.png', tooltip(_("Download des letzten Chatlogs"))) ?>
                    </a>
                    <?
                }
                ?>
                <a href="javascript:<?=(($chatServer->chatDetail[$chatid]['log'][$user->id]) ? "doLogStop();" : "doLogStart();")?>">
                <img src="<?=(($chatServer->chatDetail[$chatid]['log'][$user->id]) ? Assets::image_path('icons/16/white/stop.png') : Assets::image_path('icons/16/white/play.png') )?>"
                <?=tooltip(($chatServer->chatDetail[$chatid]['log'][$user->id]) ? _("Die Aufzeichnung für diesen Chat beenden.") : _("Eine Aufzeichnung für diesen Chat starten."))?> class="text-top">
                </a>
                <?
            }
            ?>
            <a href="javascript:printhelp();">
            <?= Assets::img('icons/16/white/info.png', array('title' => _("Chat Kommandos einblenden"), 'class' => 'text-top')) ?>
            </a>
            <a href="<?= URLHelper::getLink('dispatch.php/smileys') ?>" target="_blank">
            <?= Assets::img('icons/16/white/smiley.png', array('class' => 'text-top', 'title' => _("Alle verfügbaren Smileys anzeigen"))) ?>
            </a></td>
        </tr>
    </table>
</div>
</body>
</html>
<? page_close();
