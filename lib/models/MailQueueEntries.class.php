<?php

/*
 *  Copyright (c) 2013  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

class MailQueueEntries extends SimpleORMap {

    static public function add(StudipMail $mail, $message_id = null, $user_id = null)
    {
        $queue_entry = new MailQueueEntries();
        $queue_entry['mail'] = $mail;
        $queue_entry['message_id'] = $message_id;
        $queue_entry['user_id'] = $user_id;
        $queue_entry['tries'] = 0;
        $queue_entry->store();
        return $queue_entry;
    }


    static public function sendNew()
    {
        $mail_queue_entries = MailQueueEntries::findBySQL("tries = '0'");
        foreach ($mail_queue_entries as $mail_queue_entry) {
            $mail_queue_entry->send();
        }
    }

    static public function sendAll()
    {
        $mail_queue_entries = MailQueueEntries::findBySQL(
            "tries = '0' " .
            "OR (last_try > (UNIX_TIMESTAMP() - 60 * 60) AND tries < 25) "
        );
        foreach ($mail_queue_entries as $mail_queue_entry) {
            $mail_queue_entry->send();
        }
    }

    public function __construct($id = null)
    {
        $this->db_table = 'mail_queue_entries';
        $this->registerCallback('before_store', 'cbSerializeMail');
        $this->registerCallback('after_store after_initialize', 'cbUnserializeMail');
        parent::__construct($id);
    }

    protected function cbSerializeMail()
    {
        if ($this->content['mail']) {
            $this->content['mail'] = serialize($this->content['mail']);
        }
        if ($this->content_db['mail']) {
            $this->content_db['mail'] = serialize($this->content_db['mail']);
        }
        return true;
    }

    protected function cbUnserializeMail()
    {
        if ($this->content['mail']) {
            $this->content['mail'] = unserialize($this->content['mail']);
        }
        if ($this->content_db['mail']) {
            $this->content_db['mail'] = unserialize($this->content_db['mail']);
        }
        return true;
    }

    public function send()
    {
        if (is_a($this->mail, "StudipMail")) {
            $success = $this->mail->send();
            if ($success) {
                if ($this['message_id'] && $this['user_id']) {
                    //Noch in message_user als versendet vermerken?
                }
                $this->delete();
            } else {
                $this['tries'] = $this['tries'] + 1;
                $this['last_try'] = time();
                $this->store();
            }
        }
    }
}
