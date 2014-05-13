<?php
/**
 * Message.class.php
 * model class for table message
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author    mlunzena
 * @copyright (c) Authors
 *
 * @property string message_id database column
 * @property string id alias column for message_id
 * @property string chat_id database column
 * @property string autor_id database column
 * @property string subject database column
 * @property string message database column
 * @property string mkdate database column
 * @property string readed database column
 * @property string reading_confirmation database column
 * @property string priority database column
 * @property SimpleORMapCollection users has_many MessageUser
 * @property SimpleORMapCollection attachments has_many StudipDocument
 * @property User author has_one User
 */

class Message extends SimpleORMap {

    protected static function configure()
    {
        $config['db_table'] = 'message';
        $config['has_one']['author'] = array(
            'class_name' => 'User',
            'foreign_key' => 'autor_id',
            'assoc_foreign_key' => 'user_id'

        );
        $config['has_many']['users'] = array(
            'class_name' => 'MessageUser'
        );
        $config['has_many']['attachments'] = array(
            'class_name' => 'StudipDocument',
            'assoc_foreign_key' => 'range_id'
        );
        parent::configure($config);
    }

    public function getSender()
    {
        return $this->users->filter(function ($mu) {return $mu->snd_rec === 'snd';})->first()->user;
    }

    public function getRecipients()
    {
        return $this->users
            ->filter(function ($mu) { return $mu->snd_rec === 'rec'; })
            ->map(function ($mu) { return $mu->user; });
    }

    public function markAsRead($user_id)
    {
        return $this->markAs($user_id, 1);
    }

    public function markAsUnread($user_id)
    {
        return $this->markAs($user_id, 0);
    }

    private function markAs($user_id, $state_of_flag)
    {
        $changed = 0;
        foreach ($this->users->findBy('user_id', $user_id) as $mu) {
            $mu->readed = $state_of_flag;
            $changed += $mu->store();
        }
        if ($changed) {
            $this->users->refresh();
        }
        return $changed;
    }

    public static function send($sender, $recipients, $subject, $message)
    {

        $messaging = new \messaging();
        $result = $messaging->insert_message($message,
                                             $recipients,
                                             $sender,
                                             time(),
                                             $message_id = md5(uniqid('message', true)),
                                             false, // deleted
                                             '', // force email
                                             $subject);
        return $result ? self::find($message_id) : null;
    }
}
