<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
 * sms_send.php - Verwaltung von systeminternen Kurznachrichten
 *
 * frontend for message-transmission
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Nils K. Windisch <studip@nkwindisch.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     message
 */

require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");
$_SESSION['sms_data'] = $sms_data;
$sms_show = $_SESSION['sms_show'];
include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once 'lib/functions.php';
require_once ('lib/msg.inc.php');
require_once ('lib/visual.inc.php');
if ($GLOBALS["ENABLE_EMAIL_ATTACHMENTS"]) {
    require_once ('lib/datei.inc.php');
}
require_once ('lib/include/messagingSettings.inc.php');
require_once ('lib/messaging.inc.php');
require_once ('lib/statusgruppe.inc.php');
require_once ('lib/sms_functions.inc.php');
require_once ('lib/user_visible.inc.php');

// wofür wird das hier benötigt?
if (get_config('CHAT_ENABLE')) {
    include_once $RELATIVE_PATH_CHAT.'/chat_func_inc.php';
    $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
    $chatServer->caching = true;
    $admin_chats = $chatServer->getAdminChats($auth->auth['uid']);
}

//$sess->register("sms_data");
$msging=new messaging;

check_messaging_default();
$cmd = Request::option('cmd');
# ACTION
###########################################################
// start new message
if ($cmd == 'new') {
    unset($sms_data["p_rec"]);
    unset($sms_data["tmp_save_snd_folder"]);
    unset($sms_data["tmpreadsnd"]);
    unset($sms_data["tmpemailsnd"]);
    unset($cmd);

    if ($my_messaging_settings["save_snd"] == "1") $sms_data["tmpsavesnd"] = "1";
}

// write a chat-invitation, so predefine the messagesubject
   $messagesubject = Request::get('messagesubject');
   $message = Request::get('message');
   $quote = Request::get('quote');
   $signature = Request::get('signature');
if ($cmd == "write_chatinv" && !isset($messagesubject)) $messagesubject = _("Chateinladung");

//wurde eine Datei hochgeladen?
if($GLOBALS["ENABLE_EMAIL_ATTACHMENTS"]){
    $current_size_of_attachments = 0;
    $max_size_of_attachments = $GLOBALS['UPLOAD_TYPES']['attachments']['file_sizes'][$perm->get_perm()];
    foreach(get_message_attachments(Request::option('attachment_message_id'), true) as $document){
        if(Request::submitted('remove_attachment_' . $document['dokument_id'])){
            delete_document($document['dokument_id']);
        } else {
            $current_size_of_attachments += $document['filesize'];
        }
    }
    if(Request::submitted('upload')){
        if ($_FILES['the_file']['error'] === UPLOAD_ERR_OK && validate_upload($_FILES['the_file'])) {
            if($current_size_of_attachments + $_FILES['the_file']['size'] > $max_size_of_attachments){
                $msg = "error§" . sprintf(_("Die Gesamtgröße der angehängten Dateien überschreitet die zulässige Größe von %sMB."), round($max_size_of_attachments/1048576,1));
            } else {
                $document = new StudipDocument();
                $document->setValue('range_id' , 'provisional');
                $document->setValue('seminar_id' , $user->id);
                $document->setValue('name' , Request::removeMagicQuotes(basename($_FILES['the_file']['name'])));
                $document->setValue('filename' , $document->getValue('name'));
                $document->setValue('filesize' , (int)$_FILES['the_file']['size']);
                $document->setValue('autor_host' , $_SERVER['REMOTE_ADDR']);
                $document->setValue('user_id' , $user->id);
                $document->setValue('description', Request::option('attachment_message_id'));

                // TODO (mlunzena): Should one post a notification when attaching files?
                if ($document->store()
                    && @move_uploaded_file($_FILES['the_file']['tmp_name'],
                                           get_upload_file_path($document->getId()))){
                    $msg = "msg§" . _("Die Datei wurde erfolgreich auf den Server &uuml;bertragen!");
                } else {
                    $msg = "error§" . _("Datei&uuml;bertragung gescheitert!");
                }
            }
        } elseif($_FILES['the_file']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $msg = "error§" . sprintf(_("Die Größe der Datei überschreitet die zulässige Größe von %sMB."), round($max_size_of_attachments/1048576,1));
        }
    }
}

// where do we save the message?
if(Request::get('tmp_save_snd_folder')) {

    if(Request::get('tmp_save_snd_folder') == "dummy") {
        unset($sms_data["tmp_save_snd_folder"]);
    } else {
        $sms_data["tmp_save_snd_folder"] = Request::get('tmp_save_snd_folder');
    }

}

// do we like save the transmitted sms?
if(!$sms_data["tmpsavesnd"]) {
    $sms_data["tmpsavesnd"] = $my_messaging_settings["save_snd"];
} else if(Request::submitted('add_tmpsavesnd_button')) {
    $sms_data["tmpsavesnd"] = 1;
} else if(Request::submitted('rmv_tmpsavesnd_button_x')) {
    $sms_data["tmpsavesnd"] = 2;
}

// email-forwarding?
if (Request::submitted('rmv_tmpemailsnd_button_x')) $sms_data['tmpemailsnd'] = "";
if (Request::submitted('add_tmpemailsnd_button_x')) $sms_data['tmpemailsnd'] = 1;

//reading-confirmation?
if (Request::submitted('rmv_tmpreadsnd_button_x')) $sms_data["tmpreadsnd"] = "";
if (Request::submitted('add_tmpreadsnd_button_x')) $sms_data["tmpreadsnd"] = 1;


// check if active chat avaiable
if (($cmd == "write_chatinv") && (!is_array($admin_chats))) $cmd='';

// send message
if (Request::submitted('cmd_insert')) {

    $count = 0;
    if (!empty($sms_data["p_rec"])) {
        $time = date("U");
        $tmp_message_id = md5(uniqid("321losgehtes"));
        if (Request::option('chat_id')) {
            $count = $msging->insert_chatinv($message, $sms_data["p_rec"], Request::option('chat_id'));
        } else {
            $msging->provisonal_attachment_id = Request::option('attachment_message_id');
            $count = $msging->insert_message($message, $sms_data["p_rec"], FALSE, $time, $tmp_message_id, FALSE, $signature, $messagesubject);
        }
    }

    if ($count) {

        $msg = "msg§";
        if ($count == "1") $msg .= sprintf(_("Ihre Nachricht an %s wurde verschickt!"), get_fullname_from_uname($sms_data["p_rec"][0],'full',true))."<br>";
        if ($count >= "2") $msg .= sprintf(_("Ihre Nachricht wurde an %s Empfänger verschickt!"), $count)."<br>";
        unset($signature);
        unset($message);
        $sms_data["sig"] = $my_messaging_settings["addsignature"];

        if (Request::option('answer_to')) {
            $query = "UPDATE message_user
                      SET answered = 1
                      WHERE message_id = ? AND user_id = ? AND snd_rec = 'rec'";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                Request::option('answer_to'), $user->id,
            ));
        }
    }

    if ($count < 0) {
        $msg = 'error§' . _('Ihre Nachricht konnte nicht gesendet werden. Die Nachricht enthält keinen Text.');
    } else if ((!$count) && (!$group_count)) {
        $msg = 'error§' . _('Ihre Nachricht konnte nicht gesendet werden.');
    }

    // redirect to source_page if set
    $sms_source_page = Request::get('sms_source_page');
    if (!preg_match('§^([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9/#_?&=-]*)$§',$sms_source_page)) $sms_source_page = '';

    if ($sms_source_page) {
        $_SESSION['sms_msg'] = $msg;
        if ($sms_source_page == "about.php") {
            $header_info = "Location: ".$sms_source_page."?username=".$sms_data["p_rec"][0];
        } else {
            $header_info = "Location: ".$sms_source_page;
        }
        header ($header_info);
        die;
    }

    unset($sms_data["p_rec"]);
    unset($sms_data["tmp_save_snd_folder"]);
    unset($sms_data["tmpreadsnd"]);
    unset($sms_data["tmpemailsnd"]);
    unset($messagesubject);
    $attachments = array();

    if($my_messaging_settings["save_snd"] == "1") $sms_data["tmpsavesnd"]  = "1";

}

// do we answer someone and did we came from somewhere != sms-page
if (Request::option('answer_to')) {
    $query = "SELECT username
              FROM message
              JOIN auth_user_md5 ON (message.autor_id = auth_user_md5.user_id)
              WHERE message_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(Request::option('answer_to')));
    $u_name = $statement->fetchColumn();

    if ($u_name) {
        if($quote) {
            $quote_username = $u_name;
        }
        $sms_data['p_rec'] = array($u_name);
        
    }
    $sms_data['sig'] = $my_messaging_settings['addsignature'];
}

$rec_uname=Request::get('rec_uname');
if (isset($rec_uname)) {
    if (!get_visibility_by_username($rec_uname)) {
        if ($perm->get_perm() == "dozent") {
            $the_user = User::findByUsername($rec_uname)->user_id;

            $query = "SELECT 1
                      FROM seminar_user AS a, seminar_user AS b
                      WHERE a.Seminar_id = b.Seminar_id
                        AND a.user_id = ? AND a.status = 'dozent'
                        AND b.user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id, $the_user));

            if (!$statement->fetchColumn()) {
                $rec_uname = "";
                $sms_data["p_rec"] = "";
            }
        } else {
            $rec_uname = "";
            $sms_data["p_rec"] = "";
        }
    }
}

if (Request::option('msgid')) {
    $query = "SELECT username
              FROM message_user
              JOIN auth_user_md5 USING (user_id)
              WHERE message_id = ? AND snd_rec = 'snd'";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(Request::option('msgid')));
    $rec_uname = $statement->fetchColumn();

    $sms_data['p_rec'] = '';
}

// send message at group of a study profession
// created by nimuelle, step00194
if (Request::option('sp_id') && $perm->have_perm('root')) {

    // be sure to send it as email
    if(Request::int('emailrequest') == 1) {
        $sms_data['tmpemailsnd'] = 1;
    }

    // predefine subject
    $query = "SELECT DISTINCT username
              FROM user_studiengang
              JOIN auth_user_md5 USING (user_id)
              WHERE studiengang_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(Request::option('sp_id')));
    $add_group_members = $statement->fetchAll(PDO::FETCH_COLUMN);

    $sms_data['p_rec'] = '';
    if (!empty($add_group_members)) {
        $sms_data['p_rec'] = array_add_value($add_group_members, $sms_data['p_rec']);
    } else {
        $msg = 'error§' . _('Das gewählte Studienfach enthält keine Mitglieder.');
        unset($sms_data['p_rec']);
    }

    // append signature
    $sms_data['sig'] = $my_messaging_settings['addsignature'];
}

// if send message at group of a study degree
// created by nimuelle, step00194
if (Request::option('sd_id') && $perm->have_perm('root')) {

    // be sure to send it as email
    if(Request::int('emailrequest') == 1) {
        $sms_data['tmpemailsnd'] = 1;
    }

    // predefine subject
    if(Request::get('subject')) {
        $messagesubject = Request::get('subject');
    }

    $query = "SELECT DISTINCT username
              FROM user_studiengang
              JOIN auth_user_md5 USING (user_id)
              WHERE abschluss_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(Request::option('sd_id')));
    $add_group_members = $statement->fetchAll(PDO::FETCH_COLUMN);

    $sms_data['p_rec'] = '';
    if (!empty($add_group_members)) {
        $sms_data['p_rec'] = array_add_value($add_group_members, $sms_data['p_rec']);
    } else {
        $msg = 'error§' . _('Der gewählte Studienabschluss enthält keine Mitglieder.');
        unset($sms_data['p_rec']);
    }

    // append signature
    $sms_data['sig'] = $my_messaging_settings['addsignature'];
}

// if send message at group studys with profession and degree
// created by nimuelle, step00194
if (Request::option('prof_id') && Request::option('deg_id') && $perm->have_perm('root')) {

    // be sure to send it as email
    if (Request::int('emailrequest') == 1) {
        $sms_data['tmpemailsnd'] = 1;
    }

    // predefine subject
    if (Request::get('subject')) {
        $messagesubject = Request::get('subject');
    }

    $query = "SELECT DISTINCT auth_user_md5.username 
              FROM user_studiengang 
              JOIN auth_user_md5 USING (user_id)
              WHERE studiengang_id = ? AND abschluss_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        Request::option('prof_id'), Request::option('deg_id'),
    ));
    $add_group_members = $statement->fetchAll(PDO::FETCH_COLUMN);

    $sms_data['p_rec'] = '';
    if (!empty($add_group_members)) {
        $sms_data['p_rec'] = array_add_value($add_group_members, $sms_data['p_rec']);
    } else {
        $msg = 'error§' . _('Der gewählte Studiengang enthält keine Mitglieder.');
        unset($sms_data['p_rec']);
    }

    // append signature
    $sms_data["sig"] = $my_messaging_settings["addsignature"];
}

// if send message at group (adressbook or groups in courses)
if (Request::option('group_id')) {

    // be sure to send it as email
    if (Request::int('emailrequest') == 1) {
        $sms_data['tmpemailsnd'] = 1;
    }

    // predefine subject
    if (Request::get('subject')) {
        $messagesubject = Request::get('subject');
    }

    $query = "SELECT username
              FROM statusgruppe_user
              JOIN auth_user_md5 USING (user_id)
              WHERE statusgruppe_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(Request::option('group_id')));
    $add_group_members = $statement->fetchAll(PDO::FETCH_COLUMN);

    $sms_data['p_rec'] = '';
    if (!empty($add_group_members)) {
        $sms_data['p_rec'] = array_add_value($add_group_members, $sms_data["p_rec"]);
    } else {
        $msg = 'error§' . _('Die gewählte Adressbuchgruppe enthält keine Mitglieder.');
        unset($sms_data['p_rec']);
    }

    // append signature
    $sms_data["sig"] = $my_messaging_settings["addsignature"];

}
// if send message at single/multiple user coming from teilnehmer.php
if (isset($_REQUEST['rec_uname'])  || isset($_REQUEST['filter']))
{
    //$sms_data für neue Nachricht vorbereiten
    unset($sms_data['p_rec']);
    unset($sms_data['tmp_save_snd_folder']);
    unset($sms_data['tmpreadsnd']);
    unset($sms_data['tmpemailsnd']);
    
    $course_id = Request::option('course_id');
    $cid = Request::option('cid');
    // predefine subject
    if(Request::get('subject')) {
        $messagesubject = Request::get('subject');
    }
    
    // [tlx] omg, wtf is this?
    if ((in_array($_REQUEST['filter'], words('all prelim waiting')) && $course_id) || ($_REQUEST['filter'] == 'send_sms_to_all' && isset($_REQUEST['who'])) && $perm->have_studip_perm('tutor', $course_id) || ($_REQUEST['filter'] == 'inst_status' && isset($_REQUEST['who']) && $perm->have_perm('admin') && isset($cid)))
    {
        // Stores parameters for query
        $parameters = array($course_id);
        
        //Datenbank abfragen für die verschiedenen Filter
        switch (Request::option('filter')) {
            case 'send_sms_to_all':
                $query = "SELECT username
                          FROM seminar_user
                          JOIN auth_user_md5 USING (user_id)
                          WHERE Seminar_id = ? AND status = ?
                          ORDER BY Nachname, Vorname";
                $parameters[] = Request::option('who');
                break;
            case 'all':
                $query = "SELECT username
                          FROM seminar_user
                          JOIN auth_user_md5 USING (user_id)
                          WHERE Seminar_id = ?
                          ORDER BY Nachname, Vorname";
                break;
            case 'prelim':
                $query = "SELECT username
                          FROM admission_seminar_user
                          JOIN auth_user_md5 USING (user_id)
                          WHERE seminar_id = ? AND status = 'accepted'
                          ORDER BY Nachname, Vorname";
                break;
            case 'waiting':
                $query = "SELECT username
                          FROM admission_seminar_user
                          JOIN auth_user_md5 USING (user_id)
                          WHERE seminar_id = ? AND status IN ('awaiting', 'claiming')
                          ORDER BY Nachname, Vorname";
                break;
            case 'inst_status':
                $query = "SELECT username
                          FROM user_inst
                          JOIN auth_user_md5 USING (user_id)
                          WHERE Institut_id = ? AND inst_perms = ?
                          ORDER BY Nachname, Vorname";
                $parameters[0] = $cid; // Replace parameter
                $parameters[] = Request::option('who');
                break;
        }
        
        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);
        $usernames = $statement->fetchAll(PDO::FETCH_COLUMN);

        // Ergebnis der Query als Empfänger setzen
        $sms_data['p_rec'] = array_add_value($usernames, $sms_data['p_rec']);

        if (Request::int('emailrequest') == 1) {
            $sms_data['tmpemailsnd'] = 1;
        }
    }
    //Nachricht wurde nur an bestimmte User versendet
    if (is_array($_REQUEST['rec_uname']))
        foreach (Request::getArray('rec_uname') as $var) {
            if(get_userid($var) != "")
                $sms_data['p_rec'][] = $var;
        }
    elseif (isset($_REQUEST['rec_uname']) && get_userid(Request::get('rec_uname')) != "")
        $sms_data['p_rec'] = array(Request::get('rec_uname'));
    // append signature
    $sms_data["sig"] = $my_messaging_settings["addsignature"];
}

// if send message at inst, only for admins
if (Request::option('inst_id') && $perm->have_studip_perm('admin', Request::option('inst_id'))) {

    // be sure to send it as email
    if (Request::int('emailrequest') == 1) {
        $sms_data['tmpemailsnd'] = 1;
    }

    // predefine subject

    if (Request::get('subject')) {
        $messagesubject = Request::get('subject');
    }

    $query = "SELECT username
              FROM user_inst
              JOIN auth_user_md5 USING (user_id)
              WHERE inst_perms != 'user' AND Institut_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(Request::option('inst_id')));
    $add_course_members = $statement->fetchAll(PDO::FETCH_COLUMN);

    $sms_data['p_rec'] = array_add_value($add_course_members, $sms_data['p_rec']);

    // append signature
    $sms_data['sig'] = $my_messaging_settings['addsignature'];

}

// attach signature
if (!isset($sms_data["sig"])) {
    $sms_data["sig"] = $my_messaging_settings["addsignature"];
} else if (Request::submitted('add_sig_button_x')) {
    $sms_data["sig"] = "1";
} else if (Request::submitted('rmv_sig_button_x')) {
    $sms_data["sig"] = "0";
}
// add a reciever from adress-members
if (Request::submitted('add_receiver_button_x') && Request::getArray('add_receiver')) {
    $sms_data["p_rec"] = array_add_value(Request::getArray('add_receiver'), $sms_data["p_rec"]);

}


// add all reciever from adress-members
if (Request::submitted('add_allreceiver_button')) {
    $query = "SELECT username
              FROM contact
              JOIN auth_user_md5 USING (user_id)
              WHERE owner_id = ?
              ORDER BY Nachname";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));

    while ($username = $statement->fetchColumn()) {
        if (empty($sms_data['p_rec'])) {
            $add_rec[] = $username;
        } else if (!in_array($username, $sms_data['p_rec'])) { 
            $add_rec[] = $username;
        }
    }

    $sms_data['p_rec'] = array_add_value($add_rec, $sms_data['p_rec']);
    unset($add_rec);
}


// add receiver from freesearch
if (Request::submitted('add_freesearch_x') && Request::get("adressee")) {
    $sms_data["p_rec"] = array_add_value(array(Request::get("adressee")), $sms_data["p_rec"]);
}


// remove all from receiverlist
if (Request::submitted('del_allreceiver_button_x')) { unset($sms_data["p_rec"]); }


// aus empfaengerliste loeschen
if (Request::submitted('del_receiver_button_x') && Request::optionArray('del_receiver')) {
    foreach (Request::optionArray('del_receiver') as $a) {
        $sms_data["p_rec"] = array_delete_value($sms_data["p_rec"], $a);
    }
}


# OUTPUT
###########################################################

PageLayout::setHelpKeyword("Basis.InteraktionNachrichten");
PageLayout::setTitle(_("Systeminterne Nachrichten"));
Navigation::activateItem('/messaging/write');
// add skip link
SkipLinks::addIndex(_("Neue Nachricht schreiben"), 'main_content', 100);

// includes
include ('lib/include/html_head.inc.php'); // Output of html head

//StEP 155: Mail Attachments
//JS Routinen einbinden, wenn benoetigt. Wird in der Funktion gecheckt, ob noetig...
if ($GLOBALS["ENABLE_EMAIL_ATTACHMENTS"] == true) {
    JS_for_upload();
    echo "\n<body onUnLoad=\"STUDIP.OldUpload.upload_end()\">";
}

include ('lib/include/header.php');   // Output of Stud.IP head

check_messaging_default();

$txt = array();
$txt['001'] = _("aktuelle Empf&auml;ngerInnen");
$txt['002'] = _("m&ouml;gliche Empf&auml;ngerInnen");
$txt['attachment'] = _("Dateianhang");
$txt['003'] = _("Signatur");
$txt['004'] = _("Vorschau");
$txt['005'] = (($cmd=="write_chatinv") ? _("Chateinladung") : _("Nachricht"));
$txt['006'] = _("Nachricht speichern");
$txt['007'] = _("als Email senden");
$txt['008'] = _("Lesebestätigung");

?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
<tr>
    <td class="blank" valign="top"><?
    if ($msg) {
        echo '<table width="100%">';
        parse_msg($msg);
        echo '</table>';
    }

    echo '<form enctype="multipart/form-data" name="upload_form" action="'.URLHelper::getURL().'" method="post">';
    echo CSRFProtection::tokenTag();
    if($_REQUEST['answer_to']) {
         echo '<input type="hidden" name="answer_to" value="'. htmlReady($_REQUEST['answer_to']). '">';
    }

    echo '<input type="hidden" name="sms_source_page" value="'. htmlReady(Request::get('sms_source_page')) .'">';
    echo '<input type="hidden" name="cmd" value="'.htmlReady($cmd).'">';

    // we like to quote something
    if ($quote) {
        $query = "SELECT subject, message FROM message WHERE message_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(Request::option('quote')));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        $tmp_subject = addslashes($temp['subject']);
        if(substr($tmp_subject, 0, 3) != "RE:") {
            $messagesubject = "RE: ".$tmp_subject;
        } else {
            $messagesubject = $tmp_subject;
        }
        if (strpos($temp['message'], $msging->sig_string)) {
            $tmp_sms_content = substr($temp['message'], 0, strpos($temp['message'], $msging->sig_string));
        } else {
            $tmp_sms_content = $temp['message'];
        }
    }
    // we simply answer, not more or less
    else if (!Request::get('messagesubject') && Request::option('answer_to')) {
        $query = "SELECT subject FROM message WHERE message_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(Request::option('answer_to')));
        $tmp_subject = addslashes($statement->fetchColumn());

        if (substr($tmp_subject, 0, 3) != 'RE:') {
            $messagesubject = 'RE: '.$tmp_subject;
        } else {
            $messagesubject = $tmp_subject;
        }
    }

    ?>
        <table cellpadding="0" cellspacing="0" border="0" height="10" width="99%" id="main_content">
            <tr>
                <td colspan="2" valign="top" width="30%" height="10" class="blank" style="border-right: dotted 1px">

                    <table cellpadding="5" cellspacing="0" border="0" height="10" width="100%">
                        <tr>
                            <td valign="top" class="steelgraudunkel">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['001']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="steelgraulight">
                                <?=show_precform()?>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="steelgraudunkel">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['002']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="steelgraulight">
                                <?=show_addrform()?>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="steelgraudunkel">
                                <font size="-1" color="#FFFFFF"><b><?= _('Optionen') ?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="steelgraulight">
                                <?=show_msgsaveoptionsform()?>
                            </td>
                        </tr>
                        <? if ($GLOBALS["MESSAGING_FORWARD_AS_EMAIL"]) { ?>
                        <tr>
                            <td valign="top" class="steelgraulight">
                                <?=show_msgemailoptionsform()?>
                            </td>
                        </tr>
                        <? } ?>
                        <tr>
                            <td valign="top" class="steelgraulight">
                                <?=show_msgreadconfirmoptionsform()?>
                            </td>
                        </tr>
                    </table>

                </td>
                <td colspan="2" valign="top" width="70%" class="blank">

                    <table cellpadding="5" cellspacing="0" border="0" height="10" width="100%">
                        <?=show_chatselector()?>
                        <tr>
                            <td valign="top" class="steelgraudunkel">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['005']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="steelgraulight">
                                <?=show_msgform()?>
                            </td>
                        </tr>
                        <? // StEP 155: Mail Attachments
                        if ($GLOBALS["ENABLE_EMAIL_ATTACHMENTS"] == true) {
                            ?>
                            <tr>
                                <td valign="top" class="steelgraudunkel">
                                    <font size="-1" color="#FFFFFF"><b><?=$txt['attachment']?></b></font>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" class="printcontent">
                                    <?=show_attachmentform()?>
                                </td>
                            </tr>
                            <?
                        }
                        ?>
                        <tr>
                            <td valign="top" class="steelgraudunkel">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['003']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="printcontent">
                                <?=show_sigform()?>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="steelgraudunkel">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['004']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="printcontent">
                                <?=show_previewform()?>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>
        <?

    $emailforwardinfo = '';

    if($GLOBALS["MESSAGING_FORWARD_AS_EMAIL"] == TRUE) {
        if($sms_data["tmpemailsnd"] == 1) {
            $emailforwardinfo = _("Die Nachricht wird auch als E-Mail weitergeleitet, sofern die EmpfängerIn sich nicht ausdrücklich gegen die E-Mail-Weiterleitung entschieden hat.");
        } else {
            $emailforwardinfo = _("Ihre Nachricht wird nicht gleichzeitig als E-Mail weitergeleitet.");
        }
        $emailforwardinfo = array("kategorie" => _("Emailweiterleitung:"),"eintrag" => array(array("icon" => "icons/16/black/mail.png", "text" => sprintf($emailforwardinfo))));
    }

    $smsinfos = "";

    // emailforwarding?!
    if($GLOBALS["MESSAGING_FORWARD_AS_EMAIL"] == TRUE) {
        if($sms_data["tmpemailsnd"] == 1) {
            $smsinfos .= Assets::img('icons/16/green/accept.png');
        } else {
            $smsinfos .= Assets::img('icons/16/red/decline.png');
        }
        $smsinfos .= " "  ._("Emailweiterleitung")."<br>";
    }

    // readingconfirmation?!
    if($sms_data["tmpreadsnd"] == 1) {
        $smsinfos .= Assets::img('icons/16/green/accept.png');
    } else {
        $smsinfos .= Assets::img('icons/16/red/decline.png');
    }
    $smsinfos .= " " . _("Lesebestätigung")."<br>";

    // save the message?!
    if($sms_data["tmpsavesnd"] == 1) {
        $smsinfos .= Assets::img('icons/16/green/accept.png');
    } else {
        $smsinfos .= Assets::img('icons/16/red/decline.png');
    }
    $smsinfos .= " " . _("Speichern")."<br>";

    // signature?!
    if($sms_data["sig"] == 1) {
        $smsinfos .= Assets::img('icons/16/green/accept.png');
    } else {
        $smsinfos .= Assets::img('icons/16/red/decline.png');
    }
    $smsinfos .= " "  ._("Signatur");

    $smsinfos = array("kategorie" => _("Übersicht:"),"eintrag" => array(array("icon" => "icons/16/black/info.png", "text" => sprintf($smsinfos))));
?>
        </form>
    </td>
    <td class="blank" width="270" align="right" valign="top">
<?
    $help_url_smil = format_help_url("Basis.VerschiedenesSmileys");
    $help_url_format = format_help_url("Basis.VerschiedenesFormat");
    $infobox = array(
        $smsinfos,
        $emailforwardinfo,
        array(
            "kategorie" => _("Smilies & Textformatierung:"),
            "eintrag" => array(
                array(
                    "icon" => "icons/16/black/smiley.png",
                    "text" => sprintf(_("%s Liste mit allen Smilies %s Hilfe zu Smilies %s Hilfe zur Textformatierung %s"), "<a href=\"" . URLHelper::getURL('dispatch.php/smileys') . "\" target=\"_blank\">", "</a><br><a href=\"".$help_url_smil."\" target=\"_blank\">", "</a><br><a href=\"".$help_url_format."\" target=\"_blank\">", "</a>")
                )
            )
        )
    );

    print_infobox($infobox, "infobox/messages.jpg"); ?>

    </td>
</tr>
</table>

<?php
include ('lib/include/html_end.inc.php');
page_close();
