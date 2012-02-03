<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* client script for the chat using AJAX
*
* 
*
* @author       André Noack <andre.noack@gmx.net>
* @access       public
* @modulegroup      chat_modules
* @module       chat_client_ajax
* @package      Chat
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// chat_client_ajax.php
// 
// Copyright (c) 2006 André Noack <andre.noack@gmx.net>
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

include 'sajax_chat_functions.php';
?>
<html>
<head>
    <title>Chat(<?=$auth->auth["uname"]?>) -
    <?=htmlReady($chatServer->chatDetail[$chatid]["name"])?></title>
    <link rel="stylesheet" href="<?=$GLOBALS['ASSETS_URL']?>stylesheets/style.css" type="text/css">
    <meta http-equiv="Content-Type" content="text/html; charset=WINDOWS-1252">
    <script type="text/javascript" language="javascript" src="<?= $GLOBALS['ASSETS_URL'] ?>javascripts/md5.js"></script>
<script type="text/javascript">
    //
    // JavaScript
    //
    var chatuniqid = '<?=$chatServer->chatDetail[$chatid]["id"]?>';
    var chat_id = '<?=$chatid?>';
    var check_interval = <?=floor(CHAT_SLEEP_TIME * 2 / 1000)?>;
    
    <?=sajax_show_javascript();?>

    function coming_home(url) {
        if (opener.closed) alert('<?=_("Das Hauptfenster wurde geschlossen,\\ndiese Funktion kann nicht mehr ausgeführt werden!")?>');
        else {
            opener.location.href = url;
            opener.focus();
        }
        return false;
    }
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
        doSubmit();
    }

/**
* JavaScript 
*/
    function doColorChange(){
        for(i=0;i<document.inputform.chatColor.length;++i)
            if(document.inputform.chatColor.options[i].selected == true){
            document.inputform.chatInput.value="/color " +
            document.inputform.chatColor.options[i].value;
            doSubmit();
            }
    }

/**
* JavaScript
*/
    function printhelp(){
        document.inputform.chatInput.value="/help";
        doSubmit();
    }

    function doLock(){
        document.inputform.chatInput.value="/lock";
        doSubmit();
    }

    function doUnlock(){
        document.inputform.chatInput.value="/unlock";
        doSubmit();
    }

    function doLogStart(){
        document.inputform.chatInput.value="/log start";
        doSubmit();
    }

    function doLogStop(){
        document.inputform.chatInput.value="/log stop";
        doSubmit();
    }

    function doLogSend(){
        document.inputform.chatInput.value="/log send";
        doSubmit();
    }
    
    function doSubmit(){
        var the_string = document.inputform.chatInput.value.trim();
        if (the_string.substring(0,the_string.indexOf(" ")) == "/password"){
            the_string = "/password " + MD5(chatuniqid + ":" + the_string.substring(the_string.indexOf(" "),the_string.length).trim());
        }
        if (the_string != ''){
            x_insert_message(chat_id, the_string, dummy_cb);
            document.inputform.chatInput.value = '';
            document.inputform.chatInput.focus();
        }
        return false;
    }
    
    function set_chat_status_cb(status_html){
        document.getElementById('chat_status').innerHTML = status_html;
    }
    
    function set_chat_nicklist_cb(nick_html){
        document.getElementById('chat_nick').innerHTML = nick_html;
    }
    function set_chat_color_chooser_cb(color_html){
        document.getElementById('color_chooser').innerHTML = color_html;
    }
    function add_messages_cb(messages_html){
        objDiv = document.getElementById('chat_msg');
        objDiv.innerHTML += messages_html;
        objDiv.scrollTop = objDiv.scrollHeight;
        if (messages_html.match(/<reload>/)){
            x_get_chat_status(chat_id, set_chat_status_cb);
            x_get_chat_nicklist(chat_id, set_chat_nicklist_cb);
        }
        if (messages_html.match(/<colorchange>/)){
            x_get_chat_color_chooser(chat_id, set_chat_color_chooser_cb);
        }
        if (messages_html.match(/<close>/)){
            setTimeout('window.close()', 3000);
            return;
        }
        if (messages_html.match(/<logout>/)){
            return;
        }
        setTimeout('x_check_and_get_messages("' + chat_id + '", add_messages_cb)', check_interval);
    }
    
    function dummy_cb(dummy){
        //alert(dummy);
    }
    
    </script>
</head>
<body style="background-color:#EEEEEE;background-image:url('<?= $GLOBALS['ASSETS_URL'] ?>images/steel1.jpg');">
<div id="chat_msg" style="margin-left:2px;font-size:10pt;position:absolute;top:0px;left:0px;width:440px;height:418px;overflow:auto">
<?
echo "\n<b>" . sprintf(_("Hallo %s,<br> willkommen im Raum: %s"),htmlReady($chatServer->getNick($user->id,$chatid)),
    htmlReady($chatServer->chatDetail[$chatid]["name"])) . "</b><br>";
?>
</div>
<div id="chat_nick" style="margin-left:2px;margin-right:2px;position:absolute;top:0px;left:440px;width:200px;height:418px;overflow:auto">
</div>
<div id="chat_status" style="margin-left:2px;margin-right:2px;position:absolute;top:420px;left:0px;width:640px;height:21px;overflow:hidden">
</div>
<div id="chat_input" style="margin-left:2px;margin-right:2px;position:absolute;top:441px;left:0px;width:640px;height:30px;overflow:hidden">
<form name="inputform" onSubmit="return doSubmit();">
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
                    <td align="left" valign="middle" id="color_chooser">
                    </td>
                    <td align="center" valign="middle">
                        <?= Button::create(_('Absenden'), array('title' => _("Nachricht senden"))) ?>
                    </td>
                    <td align="right" valign="middle">
                        <?= LinkButton::create(_("Chat verlassen"), 'javascript:doQuit();') ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</form>
<script  type="text/javascript">
document.inputform.chatInput.focus();
x_get_chat_status(chat_id, set_chat_status_cb);
x_get_chat_nicklist(chat_id, set_chat_nicklist_cb);
x_get_chat_color_chooser(chat_id, set_chat_color_chooser_cb);
setTimeout('x_check_and_get_messages("' + chat_id + '", add_messages_cb)', check_interval);
</script>
</div>

</body>
</html>
<?
?>
