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

class Message extends SimpleORMap
{
    static public function markAllAs($user_id = null, $state_of_flag = 1)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        $statement = DBManager::get()->prepare("
            UPDATE personal_notifications_user
                INNER JOIN personal_notifications
            SET seen = '1'
            WHERE personal_notifications_user.user_id = :user_id
                AND personal_notifications.html_id LIKE 'message_%'
        ");
        $statement->execute(array(
            'user_id' => $user_id
        ));

        $statement = DBManager::get()->prepare("
            UPDATE message_user
            SET readed = :flag
            WHERE user_id = :user_id
        ");
        return $statement->execute(array(
            'user_id' => $user_id,
            'flag' => $state_of_flag
        ));
    }

    static public function getUserTags($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        $statement = DBManager::get()->prepare("
            SELECT DISTINCT tag FROM message_tags WHERE user_id = :user_id ORDER BY tag ASC
        ");
        $statement->execute(array('user_id' => $user_id));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    static public function findNew($user_id, $receiver = true, $since = 0, $tag = null)
    {
        if ($tag) {
            $messages_data = DBManager::get()->prepare("
                SELECT *
                FROM message
                    INNER JOIN message_user ON (message_user.message_id = message.message_id)
                    INNER JOIN message_tags ON (message_tags.message_id = message.message_id
                        AND message_user.user_id = message_tags.user_id)
                WHERE message_user.user_id = :me
                    AND snd_rec = :sender_receiver
                    AND message_tags.tag = :tag
                    AND message.mkdate > :since
                ORDER BY message.mkdate ASC
            ");
            $messages_data->execute(array(
                'me' => $user_id,
                'tag' => $tag,
                'sender_receiver' => $receiver ? "rec" : "snd",
                'since' => $since
            ));
        } else {
            $messages_data = DBManager::get()->prepare("
                SELECT *
                FROM message
                    INNER JOIN message_user ON (message_user.message_id = message.message_id)
                WHERE message_user.user_id = :me
                    AND snd_rec = :sender_receiver
                    AND message.mkdate > :since
                ORDER BY message.mkdate ASC
            ");
            $messages_data->execute(array(
                'me' => $user_id,
                'sender_receiver' => $receiver ? "rec" : "snd",
                'since' => $since
            ));
        }
        $messages_data = $messages_data->fetchAll(PDO::FETCH_ASSOC);
        $messages = array();
        foreach ($messages_data as $data) {
            $message = new Message();
            $message->setData($data);
            $message->setNew(false);
            $messages[] = $message;
        }
        return $messages;
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'message';
        $config['has_one']['author'] = array(
            'class_name' => 'User',
            'foreign_key' => 'autor_id',
            'assoc_foreign_key' => 'user_id'
        );
        $config['has_one']['originator'] = array(
            'class_name' => 'MessageUser',
            'assoc_func' => 'findSendedByMessageId'
        );
        $config['has_many']['receivers'] = array(
            'class_name' => 'MessageUser',
            'assoc_func' => 'findReceivedByMessageId'
        );
        $config['has_many']['attachments'] = array(
            'class_name' => 'StudipDocument',
            'assoc_foreign_key' => 'range_id'
        );
        parent::configure($config);
    }

    public function getSender()
    {
        return $this->author;
    }

    public function getRecipients()
    {
        return new SimpleCollection(User::findMany($this->receivers->pluck('user_id'), 'ORDER BY Nachname'));
    }

    public function markAsRead($user_id)
    {
        PersonalNotifications::markAsReadByHTML('message_'.$this->getId(), $user_id);
        return $this->markAs($user_id, 1);
    }

    public function markAsUnread($user_id)
    {
        return $this->markAs($user_id, 0);
    }

    private function markAs($user_id, $state_of_flag)
    {
        $changed = 0;
        $mu = array();
        if ($user_id == $this->autor_id) {
            $mu[] = $this->originator;
        }
        $receiver = $this->receivers->findOneBy('user_id', $user_id);
        if ($receiver) {
            $mu[] = $receiver;
        }
        foreach ($mu as $message_user) {
            $message_user->readed = $state_of_flag;
            $changed += $message_user->store();
        }
        return $changed;
    }

    public function isRead($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        return (bool)MessageUser::findOneBySQL("message_id = ? AND user_id = ? AND snd_rec IN('rec','snd') AND readed = 1", array($this->message_id, $user_id));
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

    public function permissionToRead($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        return (bool) MessageUser::countBySQL("message_id = ? AND user_id = ? AND snd_rec IN('rec','snd') AND deleted = 0", array($this->message_id, $user_id));
    }

    /**
     * Returns all tags for the message for the given user.
     * @param null $user_id : user-id of the user that tags should be related. null if it's the current user.
     * @return array of string : tags
     */
    public function getTags($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        $statement = DBManager::get()->prepare("
            SELECT tag FROM message_tags WHERE message_id = :message_id AND user_id = :user_id ORDER BY tag ASC
        ");
        $statement->execute(array(
            'message_id' => $this->getId(),
            'user_id' => $user_id
        ));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function addTag($tag, $user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        $statement = DBManager::get()->prepare("
            INSERT INTO message_tags
            SET message_id = :message_id,
                user_id = :user_id,
                tag = :tag,
                mkdate = UNIX_TIMESTAMP()
            ON DUPLICATE KEY
                UPDATE chdate = UNIX_TIMESTAMP()
        ");
        return $statement->execute(array(
            'message_id' => $this->getId(),
            'user_id' => $user_id,
            'tag' => strtolower($tag)
        ));
    }

    public function removeTag($tag, $user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        $statement = DBManager::get()->prepare("
            DELETE FROM message_tags
            WHERE message_id = :message_id
                AND user_id = :user_id
                AND tag = :tag
        ");
        return $statement->execute(array(
            'message_id' => $this->getId(),
            'user_id' => $user_id,
            'tag' => strtolower($tag)
        ));
    }

}
