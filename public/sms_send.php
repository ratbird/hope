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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");
$sms_data =& $_SESSION['sms_data'];
$sms_show =& $_SESSION['sms_show'];
include ('lib/seminar_open.php'); // initialise Stud.IP-Session
// -- here you have to put initialisations for the current page
require_once 'lib/functions.php';
require_once ('lib/msg.inc.php');
require_once ('lib/visual.inc.php');
if ($GLOBALS["ENABLE_EMAIL_ATTACHMENTS"]) {
    require_once ('lib/datei.inc.php');
}
require_once ('lib/messaging.inc.php');
require_once ('lib/statusgruppe.inc.php');
require_once ('lib/sms_functions.inc.php');
require_once ('lib/user_visible.inc.php');


$msging=new messaging;

$my_messaging_settings = UserConfig::get($user->id)->MESSAGING_SETTINGS;
$my_messaging_hash = md5(serialize($my_messaging_settings));
$my_messaging_observer =
function () use (&$my_messaging_settings, $my_messaging_hash, $user) {
    if ($my_messaging_hash != md5(serialize($my_messaging_settings))) {
            UserConfig::get($user->id)->store('MESSAGING_SETTINGS', $my_messaging_settings);
    }
};
NotificationCenter::addObserver($my_messaging_observer, '__invoke', 'PageCloseDidExecute');

$cmd = Request::option('cmd');
# ACTION
###########################################################
// start new message
if ($cmd == 'new') {
    unset($sms_data["p_rec"]);
    unset($sms_data["tmp_save_snd_folder"]);
    unset($sms_data["tmpreadsnd"]);
    $sms_data["tmpemailsnd"] = $my_messaging_settings["request_mail_forward"];
    unset($cmd);

    if ($my_messaging_settings["save_snd"] == "1") $sms_data["tmpsavesnd"] = "1";
}

   $messagesubject = Request::get('messagesubject');
   $message = Request::get('message');
   $quote = Request::option('quote');
   $signature = Request::get('signature');
   $forward = Request::option('forward');

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
                $msg = "error�" . sprintf(_("Die Gesamtgr��e der angeh�ngten Dateien �berschreitet die zul�ssige Gr��e von %sMB."), round($max_size_of_attachments/1048576,1));
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
                    $msg = "msg�" . _("Die Datei wurde erfolgreich auf den Server &uuml;bertragen!");
                } else {
                    $msg = "error�" . _("Datei&uuml;bertragung gescheitert!");
                }
            }
        } elseif($_FILES['the_file']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $msg = "error�" . sprintf(_("Die Gr��e der Datei �berschreitet die zul�ssige Gr��e von %sMB."), round($max_size_of_attachments/1048576,1));
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
} else if(Request::submitted('rmv_tmpsavesnd_button')) {
    $sms_data["tmpsavesnd"] = 2;
}

// email-forwarding?
if (Request::submitted('rmv_tmpemailsnd_button')) $sms_data['tmpemailsnd'] = "";
if (Request::submitted('add_tmpemailsnd_button')) $sms_data['tmpemailsnd'] = 1;

//reading-confirmation?
if (Request::submitted('rmv_tmpreadsnd_button')) $sms_data["tmpreadsnd"] = "";
if (Request::submitted('add_tmpreadsnd_button')) $sms_data["tmpreadsnd"] = 1;


// send message
if (Request::submitted('cmd_insert')) {
    if (empty($messagesubject)) {
        $msg = 'error�' . _('Sie k�nnen keine leere Nachricht versenden. Bitte geben Sie zumindest einen Betreff an.');
    } else {
        $count = 0;
        if (!empty($sms_data["p_rec"])) {
            $time = date("U");
            $tmp_message_id = md5(uniqid("321losgehtes"));
            $msging->provisonal_attachment_id = Request::option('attachment_message_id');
            $count = $msging->insert_message($message, $sms_data["p_rec"], FALSE, $time, $tmp_message_id, FALSE, $signature, $messagesubject);
        }

        if ($count) {

            $msg = "msg�";
            if ($count == "1") $msg .= sprintf(_("Ihre Nachricht an %s wurde verschickt!"), get_fullname_from_uname($sms_data["p_rec"][0],'full',true))."<br>";
            if ($count >= "2") $msg .= sprintf(_("Ihre Nachricht wurde an %s Empf�nger verschickt!"), $count)."<br>";
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
            $msg = 'error�' . _('Ihre Nachricht konnte nicht gesendet werden. Die Nachricht enth�lt keinen Text.');
        } else if ((!$count) && (!$group_count)) {
            $msg = 'error�' . _('Ihre Nachricht konnte nicht gesendet werden.');
        }

        // redirect to source_page if set
        $sms_source_page = Request::get('sms_source_page');
        if (!preg_match('�^([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9/#_?&=-]*)$�',$sms_source_page)) $sms_source_page = '';

        if ($sms_source_page) {
            $_SESSION['sms_msg'] = $msg;
            if ($sms_source_page == "dispatch.php/profile") {
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
        $sms_data["tmpemailsnd"] = $my_messaging_settings["request_mail_forward"];
        unset($messagesubject);
        $attachments = array();

        if($my_messaging_settings["save_snd"] == "1") $sms_data["tmpsavesnd"]  = "1";
    }
}

// do we answer someone and did we came from somewhere != sms-page
if (Request::option('answer_to') && Request::isGet()) {
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

$rec_uname=Request::username('rec_uname');
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
        $msg = 'error�' . _('Das gew�hlte Studienfach enth�lt keine Mitglieder.');
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
        $msg = 'error�' . _('Der gew�hlte Studienabschluss enth�lt keine Mitglieder.');
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
        $msg = 'error�' . _('Der gew�hlte Studiengang enth�lt keine Mitglieder.');
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
        $msg = 'error�' . _('Die gew�hlte Adressbuchgruppe enth�lt keine Mitglieder.');
        unset($sms_data['p_rec']);
    }

    // append signature
    $sms_data["sig"] = $my_messaging_settings["addsignature"];

}

// if send message at single/multiple user coming from teilnehmer.php

// We expect either an array of the recipients' usernames or the username of
// a single recipient or nothing (thus we need array_filter to remove invalid entries)
$rec_unames = Request::usernameArray('rec_uname') ?: array_filter(array(Request::username('rec_uname')));
if (count($rec_unames) > 0  || Request::get('filter'))
{
    //$sms_data f�r neue Nachricht vorbereiten
    unset($sms_data['p_rec']);
    unset($sms_data['tmp_save_snd_folder']);
    unset($sms_data['tmpreadsnd']);
    $sms_data["tmpemailsnd"] = $my_messaging_settings["request_mail_forward"];

    $course_id = Request::option('course_id');
    $cid = Request::option('cid');
    // predefine subject
    if(Request::get('subject')) {
        $messagesubject = Request::get('subject');
    }

    // [tlx] omg, wtf is this?
    $filter = Request::get('filter');
    if ((in_array($filter, words('all prelim waiting')) && $course_id) || Request::get('filter') == 'send_sms_to_all' && Request::get('who') && $perm->have_studip_perm('tutor', $course_id) || (Request::get('filter') == 'inst_status' && Request::get('who') && $perm->have_perm('admin') && isset($cid)))
    {
        // Stores parameters for query
        $parameters = array($course_id);

        //Datenbank abfragen f�r die verschiedenen Filter
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

        // Ergebnis der Query als Empf�nger setzen
        $sms_data['p_rec'] = array_add_value($usernames, $sms_data['p_rec']);

        if (Request::int('emailrequest') == 1) {
            $sms_data['tmpemailsnd'] = 1;
        }
    }
    //Nachricht wurde nur an bestimmte User versendet
    foreach ($rec_unames as $var) {
        if (get_userid($var) != '') {
            $sms_data['p_rec'][] = $var;
        }
    }
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
} else if (Request::submitted('add_sig_button')) {
    $sms_data["sig"] = "1";
} else if (Request::submitted('rmv_sig_button')) {
    $sms_data["sig"] = "0";
}
// add a reciever from adress-members
if (Request::submitted('add_receiver_button') && Request::usernameArray('add_receiver')) {
    $sms_data["p_rec"] = array_add_value(Request::usernameArray('add_receiver'), $sms_data["p_rec"]);

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
if (Request::submitted('add_freesearch') && Request::username("adressee")) {
    $sms_data["p_rec"] = array_add_value(array(Request::username("adressee")), $sms_data["p_rec"]);
}


// remove all from receiverlist
if (Request::submitted('del_allreceiver_button')) { unset($sms_data["p_rec"]); }


// aus empfaengerliste loeschen
if (Request::submitted('del_receiver_button')) {
    foreach (Request::usernameArray('del_receiver') as $a) {
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



$txt = array();
$txt['001'] = _("aktuelle Empf&auml;ngerInnen");
$txt['002'] = _("m&ouml;gliche Empf&auml;ngerInnen");
$txt['attachment'] = _("Dateianhang");
$txt['003'] = _("Signatur");
$txt['004'] = _("Vorschau");
$txt['005'] = _("Nachricht");
$txt['006'] = _("Nachricht speichern");
$txt['007'] = _("als Email senden");
$txt['008'] = _("Lesebest�tigung");

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
    if(Request::option('answer_to')) {
         echo '<input type="hidden" name="answer_to" value="'. htmlReady(Request::option('answer_to')). '">';
    }

    echo '<input type="hidden" name="sms_source_page" value="'. htmlReady(Request::get('sms_source_page')) .'">';
    echo '<input type="hidden" name="cmd" value="'.htmlReady($cmd).'">';

    // we like to quote something
    if ($quote) {
        $temp = get_message_data($quote, $user->id, $forward ? $forward : 'rec');
        $tmp_subject = $temp['subject'];
        if (!$forward) {
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
        } else {
            $messagesubject = 'FWD: ' . $temp['subject'];
            $message = _("-_-_ Weitergeleitete Nachricht _-_-");
            $message .= "\n" . _("Betreff") . ": " . $temp['subject'];
            $message .= "\n" . _("Datum") . ": " . strftime('%x %X', $temp['mkdate']);
            $message .= "\n" . _("Von") . ": " . get_fullname($temp['snd_uid']);
            $message .= "\n" . _("An") . ": " . join(', ', array_map('get_fullname', explode(',', $temp['rec_uid'])));
            $message .= "\n\n" . $temp['message'];
            Request::set('attachment_message_id', md5(uniqid('message', true)));
            foreach(array_filter(array_map(array('StudipDocument','find'), array_unique(explode(',', $temp['attachments'])))) as $attachment) {
                $attachment->range_id = 'provisional';
                $attachment->seminar_id = $user->id;
                $attachment->autor_host = $_SERVER['REMOTE_ADDR'];
                $attachment->user_id = $user->id;
                $attachment->description = Request::option('attachment_message_id');
                $new_attachment = $attachment->toArray();
                unset($new_attachment['dokument_id']);
                StudipDocument::createWithFile(get_upload_file_path($attachment->getId()), $new_attachment);
            }
            unset($quote);
        }
    }
    // we simply answer, not more or less
    else if (!Request::get('messagesubject') && Request::option('answer_to')) {
        $query = "SELECT subject FROM message WHERE message_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(Request::option('answer_to')));
        $tmp_subject = $statement->fetchColumn();

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
                            <td valign="top" class="content_seperator">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['001']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="table_row_odd">
                                <?=show_precform()?>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="content_seperator">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['002']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="table_row_odd">
                                <?=show_addrform()?>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="content_seperator">
                                <font size="-1" color="#FFFFFF"><b><?= _('Optionen') ?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="table_row_odd">
                                <?=show_msgsaveoptionsform()?>
                            </td>
                        </tr>
                        <? if ($GLOBALS["MESSAGING_FORWARD_AS_EMAIL"]) { ?>
                        <tr>
                            <td valign="top" class="table_row_odd">
                                <?=show_msgemailoptionsform()?>
                            </td>
                        </tr>
                        <? } ?>
                        <tr>
                            <td valign="top" class="table_row_odd">
                                <?=show_msgreadconfirmoptionsform()?>
                            </td>
                        </tr>
                    </table>

                </td>
                <td colspan="2" valign="top" width="70%" class="blank">

                    <table cellpadding="5" cellspacing="0" border="0" height="10" width="100%">
                        <tr>
                            <td valign="top" class="content_seperator">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['005']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="table_row_odd">
                                <?=show_msgform()?>
                            </td>
                        </tr>
                        <? // StEP 155: Mail Attachments
                        if ($GLOBALS["ENABLE_EMAIL_ATTACHMENTS"] == true) {
                            ?>
                            <tr>
                                <td valign="top" class="content_seperator">
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
                            <td valign="top" class="content_seperator">
                                <font size="-1" color="#FFFFFF"><b><?=$txt['003']?></b></font>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="printcontent">
                                <?=show_sigform()?>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" class="content_seperator">
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
            $emailforwardinfo = _("Die Nachricht wird auch als E-Mail weitergeleitet, sofern die Empf�ngerIn sich nicht ausdr�cklich gegen die E-Mail-Weiterleitung entschieden hat.");
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
    $smsinfos .= " " . _("Lesebest�tigung")."<br>";

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

    $smsinfos = array("kategorie" => _("�bersicht:"),"eintrag" => array(array("icon" => "icons/16/black/info.png", "text" => sprintf($smsinfos))));
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
