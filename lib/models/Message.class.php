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
 * @property string priority database column
 * @property SimpleORMapCollection receivers has_many MessageUser
 * @property SimpleORMapCollection attachments has_many StudipDocument
 * @property User author has_one User
 * @property MessageUser originator has_one MessageUser
 */

class Message extends SimpleORMap
{
    public static function markAllAs($user_id = null, $state_of_flag = 1)
    {
        PersonalNotifications::markAsReadByHTML('message_%', $user_id ?: $GLOBALS['user']->id);

        $query = "UPDATE message_user
                  SET readed = :flag
                  WHERE user_id = :user_id";
        $statement = DBManager::get()->prepare($query);
        return $statement->execute(array(
            'user_id' => $user_id ?: $GLOBALS['user']->id,
            'flag'    => $state_of_flag
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
                SELECT message.*
                FROM message_user
                    INNER JOIN message ON (message_user.message_id = message.message_id)
                    INNER JOIN message_tags ON (message_tags.message_id = message_user.message_id
                        AND message_user.user_id = message_tags.user_id)
                WHERE message_user.user_id = :me
                    AND snd_rec = :sender_receiver
                    AND message_tags.tag = :tag
                    AND message_user.mkdate > :since
                ORDER BY message_user.mkdate ASC
            ");
            $messages_data->execute(array(
                'me' => $user_id,
                'tag' => $tag,
                'sender_receiver' => $receiver ? "rec" : "snd",
                'since' => $since
            ));
        } else {
            $messages_data = DBManager::get()->prepare("
                SELECT message.*
                FROM message_user
                    INNER JOIN message ON (message_user.message_id = message.message_id)
                WHERE message_user.user_id = :me
                    AND snd_rec = :sender_receiver
                    AND message_user.mkdate > :since
                ORDER BY message_user.mkdate ASC
            ");
            $messages_data->execute(array(
                'me' => $user_id,
                'sender_receiver' => $receiver ? "rec" : "snd",
                'since' => $since
            ));
        }
        $messages_data->setFetchMode(PDO::FETCH_ASSOC);
        $messages = array();
        foreach ($messages_data as $data) {
            $messages[] = Message::buildExisting($data);
        }
        return $messages;
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'message';
        $config['belongs_to']['author'] = array(
            'class_name' => 'User',
            'foreign_key' => 'autor_id'
        );
        $config['has_one']['originator'] = array(
            'class_name' => 'MessageUser',
            'assoc_func' => 'findSendedByMessageId',
            'on_store' => 'store',
            'on_delete' => 'delete'
        );
        $config['has_many']['receivers'] = array(
            'class_name' => 'MessageUser',
            'assoc_func' => 'findReceivedByMessageId',
            'on_store' => 'store',
            'on_delete' => 'delete'
        );
        $config['has_many']['attachments'] = array(
            'class_name' => 'StudipDocument',
            'assoc_foreign_key' => 'range_id',
            'on_store' => 'store',
            'on_delete' => function($message) {
                return array_sum(array_map('delete_document', $message->attachments->pluck('id')));
            }
        );
        parent::configure($config);
    }

    public function getSender()
    {
        return $this->author;
    }

    public function getRecipients()
    {
        if ($this->relations['receivers'] === null) {
            $sql = "SELECT user_id,vorname,nachname,username,title_front,title_rear,perms,motto FROM
                    message_user
                    INNER JOIN auth_user_md5 aum USING(user_id)
                    LEFT JOIN user_info ui USING(user_id)
                    WHERE message_id=? AND snd_rec='rec'
                    ORDER BY Nachname";
            $params = array($this->id);
        } else {
            $sql = "SELECT user_id,vorname,nachname,username,title_front,title_rear,perms,motto FROM
                    auth_user_md5 aum
                    LEFT JOIN user_info ui USING(user_id)
                    WHERE aum.user_id IN(?)
                    ORDER BY Nachname";
            $params = array($this->receivers->pluck('user_id'));
        }
        $db = DbManager::get();
        return new SimpleCollection(
            $db->fetchAll($sql,
                             $params,
                             function ($data) {
                                 $user_id = $data['user_id'];
                                 unset($data['user_id']);
                                 $user = User::build($data);
                                 $ret = $user->toArray('username vorname nachname');
                                 $ret['fullname'] = $user->getFullname();
                                 $ret['user_id'] = $user_id;
                                 return $ret;
                             })
            );
    }

    public function getNumRecipients()
    {
        return MessageUser::countBySQL("message_id=? AND snd_rec='rec'", array($this->id));
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
        $receiver = MessageUser::findOneBySQL("message_id = ? AND user_id = ? AND snd_rec ='rec'", array($this->id, $user_id));
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
        return (bool)MessageUser::countBySQL("message_id = ? AND user_id = ? AND snd_rec IN('rec','snd') AND readed = 1", array($this->message_id, $user_id));
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

    public function getNumAttachments()
    {
        return StudipDocument::countBySQL("range_id=?", array($this->id));
    }

    /**
     * Deletes the message if all references in message_user indicate 'deleted'
     * @return bool
     */
    public function removeIfOrphaned()
    {
        if (!MessageUser::countBySQL("message_id = ? AND snd_rec IN('rec','snd') AND deleted = 0", array($this->message_id))) {
            return (bool)$this->delete();
        }
        return false;
    }

}
