<?php
# Lifter002: DONE - no html and mails are already templates
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: DONE - not applicable
/**
 * mesaging.inc.php - Funktionen fuer das Messaging
 *
 * several functions and classes used for the systeminternal messages
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
 * @package     messaging
 */

require_once ('lib/language.inc.php');
require_once 'lib/functions.php';
require_once ('lib/user_visible.inc.php');
require_once ('lib/contact.inc.php');
require_once ('lib/datei.inc.php');
require_once ('lib/sms_functions.inc.php');

//
function CheckChecked($a, $b)
{
    if ($a == $b) {
        return "checked";
    } else {
        return FALSE;
    }
}

//
function CheckSelected($a, $b)
{
    if ($a == $b) {
        return "selected";
    } else {
        return FALSE;
    }
}
//
function array_add_value($add, $array)
{
    foreach ($add as $a) {
        if (!empty($array)) {
            if (!in_array($a, $array)) {
                $x = array_push($array, $a);
            }
        } else {
            $array = array($a);
        }
    }
    return $array;
}

//
function array_delete_value($array, $value)
{
    for ($i=0;$i<count($array);$i++) {
        if ($array[$i] == $value)
            array_splice($array, $i, 1);
        }
    return $array;
}


class messaging
{
    var $sig_string; //String, der Signaturen vom eigentlichen Text abgrenzt

    /**
     * Konstruktor
     */
    function messaging()
    {
        $this->sig_string="\n \n -- \n";
    }

    /**
     * Nachricht loeschen
     *
     * @param $message_id
     * @param $user_id
     * @param $force
     */
    function delete_message($message_id, $user_id = FALSE, $force = FALSE)
    {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }

        $query = "UPDATE message_user
                  SET deleted = '1'
                  WHERE message_id = ? AND user_id = ? AND deleted = '0'";
        if(!$force) {
            $query .= " AND dont_delete = '0'";
        }

        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($message_id, $user_id));

        if ($statement->rowCount() == 0) {
            return false;
        }

        $query = "SELECT 1 FROM message_user WHERE message_id = ? AND deleted = '0'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($message_id));
        if (!$statement->fetchColumn()) {
            $this->remove_message($message_id);

            // StEP 155: Mail Attachments
            $query = "SELECT dokument_id FROM dokumente WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($message_id));
            $document_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

            array_map('delete_document', $document_ids);
        }
        return true;
    }

    /**
     * Removes a message or a list of messages from the database
     *
     * @param mixed $id Id(s) of the message(s) in question
     * @return bool Returns false if not a single message was removed
     */
    private function remove_message($id)
    {
        if (empty($id)) {
            return true;
        }

        $query = "DELETE message, message_user
                  FROM message
                  LEFT JOIN message_user USING(message_id)
                  WHERE message.message_id IN (?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        return $statement->rowCount() > 0;
    }

    /**
     * delete all messages from user
     *
     * @param $user_id
     * @param $force
     */
    function delete_all_messages($user_id = FALSE, $force = FALSE)
    {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }

        $query = "SELECT message_id FROM message_user WHERE user_id = ? AND deleted = '0'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($user_id));
        $message_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

        foreach ($message_ids as $message_id) {
            $this->delete_message($message_id, $user_id, $force);
        }
    }

    /**
     * update messages as readed
     *
     * @param $message_id
     */
    function set_read_message($message_id)
    {
        $query = "UPDATE IGNORE message_user
                  SET readed = 1
                  WHERE user_id = ? AND message_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($GLOBALS['user']->id, $message_id));
    }

    /**
     * delete all messages from user
     */
    function set_read_all_messages()
    {
        $query = "UPDATE IGNORE message_user
                  SET readed = 1
                  WHERE user_id = ? AND readed = '0' AND deleted = '0' AND snd_rec = 'rec'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($GLOBALS['user']->id));
    }

    /**
     *
     * @param $userid
     */
    function user_wants_email($userid)
    {
        $query = "SELECT email_forward FROM user_info WHERE user_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($userid));
        $setting = $statement->fetchColumn();

        if ($setting == 1) {
            return false;
        }
        if (in_array($setting, array(2, 3))) {
            return $setting;
        }
        return $GLOBALS['MESSAGING_FORWARD_DEFAULT'];
    }

    /**
     *
     * @param $rec_user_id
     * @param $snd_user_id
     * @param $message
     * @param $subject
     * @param $message_id
     */
    function sendingEmail($rec_user_id, $snd_user_id, $message, $subject, $message_id)
    {
        $receiver     = User::find($rec_user_id);
        $to           = $receiver->Email;
        
        // do not try to send mails to users without a mail address
        if (!$to) {
            return;
        }
        
        $rec_fullname = $receiver->getFullName();

        setTempLanguage($rec_user_id);

        $title = "[Stud.IP - " . $GLOBALS['UNI_NAME_CLEAN'] . "] ".stripslashes(kill_format(str_replace(array("\r","\n"), '', $subject)));

        if ($snd_user_id != "____%system%____") {
            $sender = User::find($snd_user_id);

            $snd_fullname = $sender->getFullName();
            $reply_to     = $sender->Email;
        }

        $template = $GLOBALS['template_factory']->open('mail/text');
        $template->set_attribute('message', kill_format(stripslashes($message)));
        $template->set_attribute('rec_fullname', $rec_fullname);
        $mailmessage = $template->render();

        $template = $GLOBALS['template_factory']->open('mail/html');
        $template->set_attribute('lang', getUserLanguagePath($rec_user_id));
        $template->set_attribute('message', stripslashes($message));
        $template->set_attribute('rec_fullname', $rec_fullname);
        $mailhtml = $template->render();

        restoreLanguage();

        // Now, let us send the message
        $mail = new StudipMail();
        $mail->setSubject($title)
             ->addRecipient($to, $rec_fullname)
             ->setReplyToEmail('')
             ->setBodyText($mailmessage);
        if (strlen($reply_to)) {
            $mail->setSenderEmail($reply_to)
                 ->setSenderName($snd_fullname);
        }
        $user_cfg = UserConfig::get($rec_user_id);
        if ($user_cfg->getValue('MAIL_AS_HTML')) {
            $mail->setBodyHtml($mailhtml);
        }

        if ($GLOBALS['ENABLE_EMAIL_ATTACHMENTS']) {
            foreach (get_message_attachments($message_id) as $attachment) {
                $mail->addStudipAttachment($attachment['dokument_id']);
            }
        }
        $mail->send();
    }

    /**
     *
     * @param $id
     */
    function get_forward_id($id)
    {
        return User::find($id)->smsforward_rec;
    }

    /**
     *
     * @param $id
     */
    function get_forward_copy($id)
    {
        return User::find($id)->smsforward_copy;
    }

    /**
     *
     * @param $message
     * @param $rec_uname
     * @param $user_id
     * @param $time
     * @param $tmp_message_id
     * @param $set_deleted
     * @param $signature
     * @param $subject
     * @param $force_email
     * @param $priority
     */
    function insert_message($message, $rec_uname, $user_id='', $time='', $tmp_message_id='', $set_deleted='', $signature='', $subject='', $force_email='', $priority='normal')
    {
        global $user;

        $my_messaging_settings = UserConfig::get($user->id)->MESSAGING_SETTINGS;

        if (basename($_SERVER['PHP_SELF']) == 'sms_send.php'){
            $sms_data = $_SESSION['sms_data'];
        } else {
            $sms_data['tmpsavesnd'] = $my_messaging_settings['save_snd'];
            $sms_data['sig'] = $my_messaging_settings['addsignature'];
        }

        // wenn kein subject uebergeben
        $subject = $subject ?: _('Ohne Betreff');

        $reading_confirmation = ($sms_data['tmpreadsnd'] == 1) ? 1 : 0;
        $email_request = ($sms_data['tmpemailsnd'] == 1) ? 1 : 0;

        // wenn keine zeit uebergeben
        $time = $time ?: time();

        // wenn keine id uebergeben
        $tmp_message_id = $tmp_message_id ?: md5(uniqid('321losgehtes', true));

        // wenn keine user_id uebergeben
        $user_id = $user_id ?: $user->id;

        # send message now
        if ($user_id != '____%system%____')  { // real-user message
            $snd_user_id = $user_id;
            $set_deleted = $set_deleted ?: ($sms_data['tmpsavesnd'] != '1'); // don't save sms in outbox

            // personal-signatur
            if ($sms_data['sig'] == '1') {
                $signature = $signature ?: $my_messaging_settings["sms_sig"];
                $message .= $this->sig_string.$signature;
            }
        } else { // system-message
            $set_deleted = '1';
            // system-signatur
            $snd_user_id = '____%system%____';
            setTempLanguage();
            $message .= $this->sig_string;
            $message .= _('Diese Nachricht wurde automatisch vom Stud.IP-System generiert. Sie k�nnen darauf nicht antworten.');

            restoreLanguage();
        }


        // Setzen der Message-ID als Range_ID f�r angeh�ngte Dateien
        if (isset($this->provisonal_attachment_id) && $GLOBALS['ENABLE_EMAIL_ATTACHMENTS']) {
            $query = "UPDATE dokumente SET range_id = ?, description = '' WHERE dokument_id = ?";
            $statement = DBManager::get()->prepare($query);
            foreach (get_message_attachments($this->provisonal_attachment_id, true) as $attachment) {
                $statement->execute(array($tmp_message_id, $attachment['dokument_id']));
            }
        }

        // insert message
        $query = "INSERT INTO message (message_id, autor_id, subject, message, reading_confirmation, priority, mkdate)
                  VALUES (?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $tmp_message_id, $snd_user_id,
            $subject, $message,
            $reading_confirmation, $priority,
        ));
        // insert snd
        $query = "INSERT INTO message_user (message_id, user_id, snd_rec, folder, deleted, mkdate)
                  VALUES (?, ?, 'snd', ?, ?, UNIX_TIMESTAMP())";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $tmp_message_id,
            $snd_user_id,
            $sms_data['tmp_save_snd_folder'] ?: 0, // save in specific folder (sender)
            $set_deleted ? 1 : 0,                  // save message?
        ));

        // heben wir kein array bekommen, machen wir einfach eins ...
        if (!is_array($rec_uname)) {
            $rec_uname = array($rec_uname);
        }

        // wir bastelen ein neues array, das die user_id statt des user_name enthaelt
        for ($x=0; $x < sizeof($rec_uname); $x++) {
            $rec_id[$x] = User::findByUsername($rec_uname[$x])->user_id;
        }
        // wir gehen das eben erstellt array durch und schauen, ob irgendwer was weiterleiten moechte.
        // diese user_id schreiben wir in ein tempraeres array
        for ($x=0; $x<sizeof($rec_id); $x++) {
            $tmp_forward_id = $this->get_forward_id($rec_id[$x]);
            if ($tmp_forward_id && User::find($tmp_forward_id)) {
                // Unused?
                // $tmp_forward_copy = $this->get_forward_copy($rec_id[$x]);
                $rec_id_tmp[] = $tmp_forward_id;
            }
        }

        // wir mergen die eben erstellten arrays und entfernen doppelte eintraege
        $rec_id = array_merge((array)$rec_id, (array)$rec_id_tmp);
        $rec_id = array_unique($rec_id);

        // hier gehen wir alle empfaenger durch, schreiben das in die db und schicken eine mail
        $query  = "INSERT INTO message_user (message_id, user_id, snd_rec, mkdate)
                   VALUES (?, ?, 'rec', UNIX_TIMESTAMP())";
        $insert = DBManager::get()->prepare($query);
        $snd_name = ($user_id != '____%system%____')
            ? User::find($user_id)->getFullName() . ' (' . User::find($user_id)->username . ')'
            : 'Stud.IP-System';
        for ($x=0; $x<sizeof($rec_id); $x++) {
            $insert->execute(array($tmp_message_id, $rec_id[$x]));
            if ($GLOBALS['MESSAGING_FORWARD_AS_EMAIL']) {
                // mail to original receiver
                $mailstatus_original = $this->user_wants_email($rec_id[$x]);
                if ($mailstatus_original == 2 || ($mailstatus_original == 3 && $email_request == 1) || $force_email) {
                    $this->sendingEmail($rec_id[$x], $snd_user_id, $message, $subject, $tmp_message_id);
                }
            }
        }
        PersonalNotifications::add(
            $rec_id,
            URLHelper::getUrl("sms_box.php?sms_inout=in&mopen=$tmp_message_id#$tmp_message_id", array('cid' => null)),
            sprintf(_('Sie haben eine Nachricht von %s erhalten!'), $snd_name),
            'message_'.$tmp_message_id,
            Assets::image_path("icons/40/blue/mail")
        );


        return sizeof($rec_id);
    }

    /**
     * Buddy aus der Buddyliste loeschen
     *
     * @param $username
     */
    function delete_buddy($username)
    {
        RemoveBuddy($username);
    }

    /**
     * Buddy zur Buddyliste hinzufuegen
     *
     * @param $username
     */
    function add_buddy($username)
    {
        AddNewContact(get_userid($username));
        AddBuddy($username);
    }

    /**
     *
     * @param $foldername
     */
    function check_newmsgfoldername($foldername)
    {
        if ($foldername == "new" || $foldername == "all" || $foldername == "free" || $foldername == "dummy") {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}
