<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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
if (get_config('CHAT_ENABLE')){
    include_once $GLOBALS['RELATIVE_PATH_CHAT']."/ChatServer.class.php"; //wird für Nachrichten im chat benötigt
}

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
    var $db; //Datenbankanbindung
    var $sig_string; //String, der Signaturen vom eigentlichen Text abgrenzt

    /**
     * Konstruktor
     */
    function messaging()
    {
        $this->sig_string="\n \n -- \n";
        $this->db = new DB_Seminar;
        $this->db2 = new DB_Seminar;
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
        global $user;

        if (!$user_id) {
            $user_id = $user->id;
        }

        $query = "UPDATE message_user SET deleted = '1' WHERE message_id = '".$message_id."' AND user_id = '".$user_id."' AND deleted='0'";

        if(!$force) {
            $query .= " AND dont_delete='0'";
        }

        $db=new DB_Seminar;
        $db2=new DB_Seminar;

        $db->query($query);
        if ($db->affected_rows()) {
            $db2->query("SELECT message_id FROM message_user WHERE message_id = '".$message_id."' AND deleted = '0'");
            if (!$db2->num_rows()) {
                $db2->query("DELETE FROM message WHERE message_id = '".$message_id."'");
                $db2->query("DELETE FROM message_user WHERE message_id = '".$message_id."'");
                // StEP 155: Mail Attachments
                $db2->query("SELECT dokument_id FROM dokumente WHERE range_id = '".$message_id."'");
                while ($db2->next_record())
                    delete_document($db2->f("dokument_id"));
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * delete all messages from user
     *
     * @param $user_id
     * @param $force
     */
    function delete_all_messages($user_id = FALSE, $force = FALSE)
    {
        global $user;

        $db=new DB_Seminar;

        if (!$user_id) {
            $user_id = $user->id;
        }

        $query = "SELECT message_id FROM message_user WHERE user_id = '".$user_id."' AND deleted='0'";
        $db->query("$query");
        while ($db->next_record()) {
            $this->delete_message($db->f("message_id"), $user_id, $force);
        }
    }

    /**
     * update messages as readed
     *
     * @param $message_id
     */
    function set_read_message($message_id)
    {
        global $user;

        $db=new DB_Seminar;
        $user_id = $user->id;
        $query = "UPDATE IGNORE message_user SET readed=1 WHERE user_id = '$user_id' AND message_id = '$message_id'";
        $db->query($query);
    }

    /**
     * delete all messages from user
     */
    function set_read_all_messages()
    {
        global $user;

        $db=new DB_Seminar;

        $user_id = $user->id;

        $query = "SELECT message_id FROM message_user WHERE readed = '0' AND deleted='0' and user_id = '".$user_id."' AND snd_rec = 'rec'";
        $db->query("$query");
        while ($db->next_record()) {
            $this->set_read_message($db->f("message_id"));
        }
    }

    /**
     *
     * @param $userid
     */
    function user_wants_email($userid)
    {
        $db = new DB_Seminar("SELECT email_forward FROM user_info WHERE user_id = '".$userid."'");
        #$db = new DB_Seminar("SELECT email_forward FROM user_info a, auth_user_md5 b WHERE a.user_id = b.user_id AND (b.username = '$userid' OR b.user_id = '$userid')");
        $db->next_record();
        switch ($db->f("email_forward")) {
            case 1:
                return FALSE;
                break;

            case 2:
                return 2;
                break;

            case 3:
                return 3;
                break;

            default:
                return $GLOBALS["MESSAGING_FORWARD_DEFAULT"];
                break;
        }
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
        global $user;

        $db4 = new DB_Seminar("SELECT user_id, Email FROM auth_user_md5 WHERE user_id = '$rec_user_id';");
        $db4->next_record();
        $to = $db4->f("Email");
        $rec_fullname = get_fullname($db4->f("user_id"));

        setTempLanguage($db4->f("user_id"));

        $title = "[Stud.IP - " . $GLOBALS['UNI_NAME_CLEAN'] . "] ".stripslashes(kill_format(str_replace(array("\r","\n"), '', $subject)));

        if ($snd_user_id != "____%system%____") {
            $snd_fullname = get_fullname($snd_user_id);
            $db4->query("SELECT Email FROM auth_user_md5 WHERE user_id = '$user->id'");
            $db4->next_record();
            $reply_to = $db4->f("Email");
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

        if($GLOBALS["ENABLE_EMAIL_ATTACHMENTS"]){
            foreach(get_message_attachments($message_id) as $attachment){
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

        $db = new DB_Seminar;

        $db->query("SELECT smsforward_rec FROM user_info WHERE user_id='".$id."'");
        $db->next_record();
        $db->f("smsforward_rec");
        $forward_id = $db->f("smsforward_rec");

        return $forward_id;
    }

    /**
     *
     * @param $id
     */
    function get_forward_copy($id)
    {

        $db = new DB_Seminar;

        $db->query("SELECT smsforward_copy FROM user_info WHERE user_id='".$id."'");
        $db->next_record();
        $db->f("smsforward_rec");
        $forward_copy = $db->f("smsforward_copy");

        return $forward_copy;
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
        global $_fullname_sql, $user, $my_messaging_settings;

        $db = new DB_Seminar;
        $db2 = new DB_Seminar;
        $db3 = new DB_Seminar;
        $db4 = new DB_Seminar;
        $db5 = new DB_Seminar;

        //ja ich weiss, das ist übel. Aber die zentrale Methode eines überall
        //benutzten Objektes über globale Variablen die nur auf einer
        //Seite sicher zur Verfügung stehen zu steuern ist ein echter php-no-brainer
        if (basename($GLOBALS['PHP_SELF']) == 'sms_send.php'){
            $sms_data = $GLOBALS['sms_data'];
        } else {
            $sms_data["tmpsavesnd"] = $my_messaging_settings["save_snd"];
            $sms_data["sig"] = $my_messaging_settings["addsignature"];
        }

        // wenn kein subject uebergeben
        if(!$subject) $subject = _("Ohne Betreff");

        if($sms_data['tmpreadsnd'] == 1) {
            $reading_confirmation = 1;
        }

        if($sms_data['tmpemailsnd'] == 1) {
            $email_request = 1;
        }

        // wenn keine zeit uebergeben
        if (!$time) $time = time();

        // wenn keine id uebergeben
        if (!$tmp_message_id) $tmp_message_id = md5(uniqid("321losgehtes"));

        // wenn keine user_id uebergeben
        if (!$user_id) $user_id = $user->id;

        # send message now
        if ($user_id != "____%system%____")  { // real-user message

            $snd_user_id = $user_id;
            if ($sms_data["tmpsavesnd"] != "1") { // don't save sms in outbox
                $set_deleted = "1";
            }

            // personal-signatur
            if ($sms_data["sig"] == "1") {
                if(!$signature) {
                    $signature = $my_messaging_settings["sms_sig"];
                }
                $message .= $this->sig_string.$signature;
            }

        } else { // system-message

            $set_deleted = "1";
            // system-signatur
            $snd_user_id = "____%system%____";
            setTempLanguage();
            $message .= $this->sig_string . _("Diese Nachricht wurde automatisch vom Stud.IP-System generiert. Sie können darauf nicht antworten.");

            restoreLanguage();

        }


        // Setzen der Message-ID als Range_ID für angehängte Dateien
        if (isset($this->provisonal_attachment_id) && $GLOBALS["ENABLE_EMAIL_ATTACHMENTS"]){
            foreach(get_message_attachments($this->provisonal_attachment_id, true) as $attachment){
                $db3->query("UPDATE dokumente SET range_id = '$tmp_message_id', description='' WHERE dokument_id = '".$attachment["dokument_id"]."'");
            }
        }

        // insert message
        $db3->query("INSERT INTO message SET message_id = '".$tmp_message_id."', mkdate = '".$time."', message = '".$message."', autor_id = '".$snd_user_id."', subject = '".$subject."', reading_confirmation = '".$reading_confirmation."', priority ='".$priority."'");

        // insert snd
        if (!$set_deleted) { // safe message
            if($sms_data["tmp_save_snd_folder"]) { // safe in specific folder (sender)
                $db3->query("INSERT INTO message_user SET message_id='".$tmp_message_id."',mkdate = '".$time."', user_id='".$snd_user_id."', snd_rec='snd', folder='".$sms_data["tmp_save_snd_folder"]."'");
            } else { // don't safe message in specific folder
                $db3->query("INSERT INTO message_user SET message_id='".$tmp_message_id."',mkdate = '".$time."', user_id='".$snd_user_id."', snd_rec='snd'");
            }
        } else { // save as deleted
            $db3->query("INSERT INTO message_user SET message_id='".$tmp_message_id."',mkdate = '".$time."', user_id='".$snd_user_id."', snd_rec='snd', deleted='1'");
        }

        // heben wir kein array bekommen, machen wir einfach eins ...
        if(!is_array($rec_uname)) {
            $rec_uname = array($rec_uname);
        }

        // wir bastelen ein neues array, das die user_id statt des user_name enthaelt
        for($x=0; $x<sizeof($rec_uname); $x++) {
            $rec_id[$x] = get_userid($rec_uname[$x]);
        }
        // wir gehen das eben erstellt array durch und schauen, ob irgendwer was weiterleiten moechte. diese user_id schreiben wir in ein tempraeres array
        for($x=0; $x<sizeof($rec_id); $x++) {
            $tmp_forward_id = $this->get_forward_id($rec_id[$x]);
            if($tmp_forward_id) {
                $tmp_forward_copy = $this->get_forward_copy($rec_id[$x]);
                $rec_id_tmp[] = $tmp_forward_id;
            }

        }

        // wir mergen die eben erstellten arrays und entfernen doppelte eintraege
        $rec_id = array_merge((array)$rec_id, (array)$rec_id_tmp);
        $rec_id = array_unique($rec_id);


        // hier gehen wir alle empfaenger durch, schreiben das in die db und schicken eine mail
        for($x=0; $x<sizeof($rec_id); $x++) {
            $db3->query("INSERT message_user SET message_id='".$tmp_message_id."',mkdate = '".$time."', user_id='".$rec_id[$x]."', snd_rec='rec'");
            if ($GLOBALS["MESSAGING_FORWARD_AS_EMAIL"]) {
                // mail to original receiver
                $mailstatus_original = $this->user_wants_email($rec_id[$x]);
                if($mailstatus_original == 2 || ($mailstatus_original == 3 && $email_request == 1) || $force_email == TRUE) {
                    $this->sendingEmail($rec_id[$x], $snd_user_id, $message, $subject, $tmp_message_id);
                }
            }
            //Benachrichtigung in alle Chaträume schicken
            $snd_name = ($user_id != "____%system%____") ? get_fullname($user_id) . " (" . get_username($user_id). ")" : "Stud.IP-System";
            if (get_config('CHAT_ENABLE')) {
                $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
                setTempLanguage($rec_id[$x]);
                $chatMsg = sprintf(_("Sie haben eine Nachricht von <b>%s</b> erhalten!"), htmlReady($snd_name));
                restoreLanguage();
                $chatMsg .= "<br></i>".formatReady(stripslashes($message))."<i>";
                foreach($chatServer->chatDetail as $chatid => $wert) {
                    if ($wert['users'][$rec_id[$x]]) {
                        $chatServer->addMsg("system:".$rec_id[$x], $chatid, $chatMsg);
                    }
                }
            }
        }

        return sizeof($rec_id);
    }

    /**
     *
     * @param $message
     * @param $chat_id
     */
    function buddy_chatinv($message, $chat_id)
    {
        global $user;
        $this->db->query("SELECT contact.user_id, username FROM contact LEFT JOIN auth_user_md5 USING (user_id) WHERE owner_id = '$user->id' AND buddy = '1' ");
        while ($this->db->next_record()) {
            $count += $this->insert_chatinv($message, $this->db->f("username"), $chat_id);
        }
        return $count;
    }

    /**
     * Chateinladung absetzen
     *
     * @param $msg
     * @param $rec_uname
     * @param $chat_id
     * @param $user_id
     */
    function insert_chatinv($msg, $rec_uname, $chat_id, $user_id = false)
    {
        global $user,$_fullname_sql,$CHAT_ENABLE;

        if (get_config('CHAT_ENABLE')) {

            $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
            $db=new DB_Seminar;
            $snd = 0;
            if (!is_array($rec_uname)) $rec_uname = array($rec_uname);
            if (!$user_id) {
                $user_id = $user->id;
            }
            $username = get_username($user_id);
            $fullname = get_fullname($user_id);
            $chat_uniqid = $chatServer->chatDetail[$chat_id]['id'];
            if (!$chat_uniqid) {
                return false;   //no active chat
            }
            foreach ($rec_uname as $one_rec){
                $one_rec_id = get_userid($one_rec);
                if (!$one_rec_id) break;    //no user found
                if (!get_visibility_by_id($one_rec_id)) break; // only invite visible users
                setTempLanguage($one_rec_id);
                $subject = sprintf(_("Chateinladung von %s"), $fullname);
                $message = sprintf(_("Sie wurden von %s in den Chatraum %s eingeladen!"),$fullname ." (".$username.")",$chatServer->chatDetail[$chat_id]['name']) . "\n - - - \n" . stripslashes($msg);
                $m_id = md5(uniqid("voyeurism",1));
                $query = "
                INSERT INTO message SET
                    message_id = '$m_id',
                    autor_id = '".$user_id."',
                    mkdate = '".time()."',
                    subject = '".mysql_escape_string($subject)."',
                    message = '".mysql_escape_string($message)."',
                    chat_id = '$chat_uniqid'";
                $db->query($query);
                $snd += $db->affected_rows();
                $query = "
                INSERT INTO message_user SET
                    message_id='$m_id',
                    mkdate = '".time()."',
                    user_id='".$one_rec_id."',
                    snd_rec='rec'";
                $db->query($query);
                $query = "
                INSERT IGNORE INTO message_user SET
                    message_id='$m_id',
                    mkdate = '".time()."',
                    user_id='".$user_id."',
                    snd_rec='snd',
                    deleted='1'";
                $db->query($query);
                //Benachrichtigung in alle Chaträume schicken
                $chatMsg = sprintf(_("Sie wurden von <b>%s</b> in den Chatraum <b>%s</b> eingeladen!"),htmlReady($fullname ." (".$username.")"),htmlReady($chatServer->chatDetail[$chat_id]['name']));
                $chatMsg .= "<br></i>" . formatReady(stripslashes($msg))."<i>";
                foreach($chatServer->chatDetail as $chatid => $wert){
                    if ($wert['users'][$one_rec_id]){
                        $chatServer->addMsg("system:".$one_rec_id,$chatid,$chatMsg);
                    }
                }
            }
            restoreLanguage();
            return $snd;
        } else {
            return FALSE;
        }
    }

    /**
     *
     * @param $user_id
     */
    function delete_chatinv($user_id = false)
    {
        global $user;

        if (get_config('CHAT_ENABLE')){
            if (!$user_id)
                $user_id = $user->id;

            $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
            foreach($chatServer->chatDetail as $chatid => $wert){
                $active_chats[] = $wert['id'];
            }
            if (is_array($active_chats)){
                $clause = " AND chat_id NOT IN('" . join("','",$active_chats) . "')";
            }
            $this->db->query("SELECT STRAIGHT_JOIN message.message_id FROM message  LEFT JOIN message_user USING (message_id) WHERE message_user.user_id = '$user_id' AND snd_rec = 'rec' AND chat_id IS NOT NULL" . $clause);

            while ($this->db->next_record()) {
                $this->db2->query ("DELETE FROM message_user WHERE message_id ='".$this->db->f("message_id")."' ");
                $this->db2->query ("DELETE FROM message WHERE message_id ='".$this->db->f("message_id")."' ");
            }

            return $this->db2->affected_rows();
        } else {
            return false;
        }
    }

    /**
     *
     * @param $chat_id
     * @param $user_id
     */
    function check_chatinv($chat_id, $user_id = false)
    {
        global $user;

        if (get_config('CHAT_ENABLE')){
            if (!$user_id)
                $user_id = $user->id;

            $chatServer = ChatServer::GetInstance($GLOBALS['CHAT_SERVER_NAME']);
            $chat_uniqid = $chatServer->chatDetail[$chat_id]['id'];
            if (!$chat_uniqid){
                return false;   //no active chat
            }
            $this->db->query("SELECT message.message_id FROM message  LEFT JOIN message_user USING (message_id) WHERE message_user.user_id = '$user_id' AND snd_rec = 'rec' AND chat_id='$chat_uniqid' LIMIT 1");
            return $this->db->next_record();
        } else {
            return false;
        }
    }

    /**
     *
     * @param $chat_uniqids
     * @param $user_id
     */
    function check_list_of_chatinv($chat_uniqids, $user_id = false)
    {
        global $user;

        if (get_config('CHAT_ENABLE')){
            if (!$user_id)
                $user_id = $user->id;

            if (!is_array($chat_uniqids)){
                return false;   //no active chat
            }
            $ret = false;
            $this->db->query("SELECT DISTINCT chat_id FROM message  LEFT JOIN message_user USING (message_id) WHERE user_id='$user_id' AND snd_rec = 'rec' AND chat_id IN('" . join("','",$chat_uniqids)."')");
            while ($this->db->next_record()){
                $ret[$this->db->f("chat_id")] = true;
            }
            return $ret;
        } else {
            return false;
        }
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
