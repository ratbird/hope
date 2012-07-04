<?php
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
studipim.php - Instant Messenger for Studip
Copyright (C) 2001 André Noack <andre.noack@gmx.net>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

use Studip\Button, Studip\LinkButton;

/**
* Close the actual window if PHPLib shows login screen
* @const CLOSE_ON_LOGIN_SCREEN
*/

require '../lib/bootstrap.php';

unregister_globals();
define("CLOSE_ON_LOGIN_SCREEN",true);
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

    require_once ('lib/seminar_open.php');
    require_once ('lib/visual.inc.php');
    require_once 'lib/functions.php';
    require_once ('config.inc.php');
    require_once ('lib/messaging.inc.php');
    require_once ('lib/sms_functions.inc.php');

$cmd = Request::option('cmd');

if ($auth->auth["uid"] != "nobody"){
    ($cmd=="write") ? $refresh=0 : $refresh=30;

    $db = new DB_Seminar;
    $sms= new messaging;

    $online = get_users_online(5, 'no_title');

    //Count new and old msg's
    $old_msg = count_messages_from_user('in', " AND message_user.readed = 1 ");
    $new_msg = count_messages_from_user('in', " AND message_user.readed = 0 ");
    $new_msgs = array();
    $msg_id = Request::option('msg_id');
    if ($new_msg){
        //load the data from new messages
        $query =  "SELECT message.message_id, message.mkdate, autor_id, message, subject
        FROM message_user LEFT JOIN message USING (message_id)
        WHERE deleted = 0 AND message_user.readed = 0 AND snd_rec = 'rec' AND message_user.user_id ='".$user->id."'
        ORDER BY message.mkdate";
        $db->query($query);

        while ($db->next_record()){
            if ($cmd=="read" && $msg_id==$db->f("message_id")){
                // "open" the message (display it in the messenger)
                $msg_text = $db->f("message");
                $msg_snd = get_username($db->f("autor_id"));
                $msg_autor_id = $db->f("autor_id");
                $msg_subject = $db->f("subject");
            }
            if ($db->f("autor_id") == "____%system%____"){
                $new_msgs[]=date("H:i",$db->f("mkdate")) . sprintf(_(" <b>Systemnachricht</b> %s[lesen]%s"),"<a href='".URLHelper::getLink('?cmd=read&msg_id='.$db->f("message_id"))."'>","</a>");
            } else {
                $new_msgs[]=date("H:i",$db->f("mkdate")). sprintf(_(" von <b>%s</b> %s[lesen]%s"),get_fullname($db->f("autor_id"),'full',true),"<a href='".URLHelper::getLink('?cmd=read&msg_id='.$db->f("message_id"))."'>","</a>");
            }
        }
        $refresh+=10;
    }
    //set a msg to readed
    if ($cmd=="read") {
        $query = sprintf ("UPDATE message_user SET readed = 1 WHERE message_id = '%s' AND user_id ='%s'", $msg_id, $user->id);
        $db->query($query);
    }
}


// Start of Output
PageLayout::setTitle(sprintf(_('Messenger (%s)'), $auth->auth['uname']));
$_SESSION['messenger_started'] = true; //html_head should NOT try to open us again!
include ('lib/include/html_head.inc.php'); // Output of html head
?>
<script language="JavaScript">
<!--

<?if ($auth->auth["uid"]=="nobody") echo "close();"; //als nobody macht der IM keinen Sinn?>

function coming_home(url)
    {
     if (opener)
        {
          opener.location.href = url;
        opener.focus();

          }
     else
        {
          top.open(url,'');
          }
    }

function again_and_again()
    {
    <? if ($cmd!="write")
        ($cmd) ? print("location.replace('".URLHelper::getURL()."');\n") : print("location.reload();\n"); ?>
    }


setTimeout('again_and_again();',<? print($refresh*1000);?>);
<?
if ($new_msgs[0] OR $cmd)  print ("self.focus();\n");
?>
//-->
</script>

<table width="100%" border=0 cellpadding=2 cellspacing=0>
<tr>
    <td class="topic" colspan=2><?=Assets::img('icons/16/grey/person.png', array('class' => 'text-top')) ?><b>&nbsp;Stud.IP-Messenger (<?=$auth->auth["uname"]?>)</b></td>
</tr>
<tr><td class="blank" width="50%" valign="top"><br><table width="100%" border=0 cellpadding=1 cellspacing=0 valign="top">
<?php
if ($auth->auth["uid"] != "nobody"){
    $c=0;
    if (is_array($online)) {
        foreach($online as $tmp_uname => $detail){
            if ($detail['is_buddy']){
                if (!$c){
                    echo "<tr><td class=\"blank\" colspan=2 align=\"left\" ><font size=-1><b>" . _("Buddies:") . "</b></td></tr>";
                }
                echo "<tr><td class='blank' width='90%' align='left'><font size=-1><a " . tooltip(sprintf(_("letztes Lebenszeichen: %s"),date("i:s",$detail['last_action'])),false) . " href=\"javascript:coming_home('about.php?username=$tmp_uname');\">".htmlReady($detail['name'])."</a></font></td>\n";
                echo "<td  class='blank' width='10%' align='middle'><font size=-1><a href='".URLHelper::getLink('?cmd=write&msg_rec='.$tmp_uname).">" . Assets::img('icons/16/blue/mail.png', array('class' => 'text-top', 'title' =>_('Nachricht an Benutzer verschicken'))) . "</a></font></td></tr>";
                $c++;
            }
        }
    } else {
        echo "<tr><td class='blank' colspan='2' align='left' ><font size=-1>" . _("Kein Nutzer ist online.") . "</font>";
    }

    if (!$my_messaging_settings["show_only_buddys"]) {
        if ((sizeof($online)-$c) == 1) {
            echo "<tr><td class=\"blank\" colspan=2 align=\"left\"><font size=-1>" . _("Es ist ein anderer Nutzer online.");
            printf ("&nbsp;<a href=\"javascript:coming_home('online.php')\"><font size=-1>" . _("Wer?") . "</font></a>");
        }
        elseif((sizeof($online)-$c) > 1) {
            printf ("<tr><td class=\"blank\" colspan=2 align=\"left\"><font size=-1>" . _("Es sind %s andere Nutzer online.") , sizeof($online)-$c);
            printf ("&nbsp;<a href=\"javascript:coming_home('online.php')\"><font size=-1>" . _("Wer?") . "</font></a>");
        }
    }
    ?>
    </td></tr></table></td><td class="blank" width="50%" valign="top"><br><font size=-1>
    <?
    if ($old_msg)
        printf(_("%s alte Nachricht(en)&nbsp;%s[lesen]%s"),$old_msg,"<a href=\"javascript:coming_home('sms_box.php?sms_inout=in')\">","</a><br>");
    elseif (!$new_msg)
        print (_("Keine Nachrichten") . "<br>");
    else
        print (_("Keine alten Nachrichten") . "<br>");

    if ($new_msg) {
        printf ("<br><b>"._("%s neue Nachrichten:") . "</b><br>", $new_msg);
        foreach ($new_msgs as $val)
                print "<br>".$val;
    }

    ?>
    </font><br>&nbsp</td></tr>
    <?
    $msg_rec = Request::quoted('msg_rec');
    $nu_msg = Request::quoted('nu_msg');
    $msg_subject = Request::quoted('msg_subject');
    if ($cmd=="send_msg" AND $nu_msg AND $msg_rec) {
        $nu_msg=trim($nu_msg);
        if (!$msg_subject) {
            $msg_subject = _("Ohne Betreff");
        }
        if ($sms->insert_message ($nu_msg, $msg_rec, FALSE, FALSE, FALSE, FALSE, FALSE, $msg_subject))
            echo"\n<tr><td class='blank' colspan='2' valign='middle'><font size=-1>"
                . sprintf(_("Ihre Nachricht an <b>%s</b> wurde verschickt!"),get_fullname_from_uname($msg_rec,'full',true)) . "</font></td></tr>";
        else
            echo"\n<tr><td class='blank' colspan='2' valign='middle'><font size=-1 color='red'><b>"
                . _("Ihre Nachricht konnte nicht verschickt werden!") . "</b></font></td></tr>";
    }


    if ($cmd=="read" AND $msg_text){
        if ($msg_autor_id == "____%system%____"){
            echo"\n<tr><td class='blank' colspan='2' valign='middle'><font size=-1><b>"
            . _("automatisch erzeugte Systemnachricht:") . " </b><hr>".formatReady($msg_text)."</font></td></tr>";
        } else {
            echo"\n<tr><td class='blank' colspan='2' valign='middle'><font size=-1>"
            . sprintf(_("Nachricht von: <b>%s</b>"),get_fullname_from_uname($msg_snd,'full',true)) ."<hr>".formatReady($msg_text)."</font></td></tr>";
            echo"\n<tr><td class='blank' colspan='2' valign='middle' align='center'><font size=-1>";

            echo LinkButton::create(_("Antworten"), "?cmd=write&msg_id=$msg_id");
            echo LinkButton::create(_("Zitieren"), "?cmd=write&msg_id=$msg_id&quote=1");
            echo LinkButton::createCancel(_("Abbrechen"), "?cmd=cancel");
        }
    }

    if ($cmd == "write"){
        if ($msg_id){
            $query = "SELECT message, subject, autor_id
                    FROM message_user LEFT JOIN message USING (message_id)
                    WHERE snd_rec = 'rec' AND message_user.user_id ='".$user->id."'
                    AND message_user.message_id='$msg_id'";
            $db->query($query);
            if ($db->next_record()){
                $msg_autor_id = $db->f('autor_id');
                $msg_subject = (substr($db->f("subject"), 0, 3) != "RE:" ? "RE: " . $db->f('subject')  :  $db->f('subject') );
                if(Request::int('quote')){
                    if (strpos($db->f("message"),$sms->sig_string)) $msg_text = substr($db->f("message"), 0, strpos($db->f("message"),$sms->sig_string));
                    else $msg_text = $db->f('message');
                    $msg_text = quotes_encode($msg_text,get_fullname($msg_autor_id));
                }
                $msg_rec = get_username($msg_autor_id);
            }
        }
        if ($msg_rec){
            echo "\n<tr><td class='blank' colspan='2' valign='middle'><font size=-1>";
            echo    sprintf(_("Ihre Nachricht an <b>%s:</b>"),get_fullname_from_uname($msg_rec,'full',true)) . "</font>";
            echo "</td></tr>";
            echo "\n<form  name='eingabe' action='".URLHelper::getLink('?cmd=send_msg')." method='POST'>";
            echo CSRFProtection::tokenTag();
            echo "<input type='HIDDEN'  name='msg_rec' value='".$msg_rec."'>";
            echo "<input type='HIDDEN'  name='msg_subject' value='".HtmlReady($msg_subject)."'>";
            echo "\n<tr><td class='blank' colspan='2' valign='middle'>";
            echo "<textarea  style=\"width: 100%\" name='nu_msg' rows='4' cols='44' wrap='virtual'>".htmlready($msg_text)."</textarea></font><br>";
            echo "<font size=-1><a target=\"_blank\" href=\"" . URLHelper::getLink('dispatch.php/smileys') . "\">" . _("Smileys</a> k&ouml;nnen verwendet werden") . " </font>\n</td></tr>";
            echo "\n<tr><td class='blank' colspan='2' valign='middle' align='center'><font size=-1>&nbsp;";
            echo '<div class="button-group">';
            echo Button::createAccept(_("Absenden")), LinkButton::createCancel(_("Abbrechen"), "?cmd=cancel");
            echo '</div>';
            echo "</form></font></td></tr>";

            echo "\n<script language=\"JavaScript\">\n<!--\ndocument.eingabe.nu_msg.focus();\n//-->\n</script>";
        }
    }
}
?>
</table>
<?
include ('lib/include/html_end.inc.php');
// Save data back to database.
page_close();
?>
