<?php
/**
 * MessageUser.class.php
 * model class for table message_user
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author    mlunzena
 * @copyright (c) Authors
 *
 * @property string user_id database column
 * @property string message_id database column
 * @property string readed database column
 * @property string deleted database column
 * @property string snd_rec database column
 * @property string confirmed_read database column
 * @property string answered database column
 * @property string mkdate database column
 * @property string id computed column read/write
 * @property User user belongs_to User
 * @property Message message belongs_to Message
 */

class MessageUser extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'message_user';
        $config['belongs_to']['user'] = array(
            'class_name' => 'User',
            'foreign_key' => 'user_id',
        );
        $config['belongs_to']['message'] = array(
            'class_name' => 'Message',
            'foreign_key' => 'message_id',
        );
        parent::configure($config);
    }

    static function findSendedByMessageId($message_id)
    {
        return self::findOneBySQL("message_id=? AND snd_rec='snd'", array($message_id));
    }

    static function findReceivedByMessageId($message_id)
    {
        return self::findBySQL("message_id=? AND snd_rec='rec'", array($message_id));
    }

    /**
     * Deletes a user message connection. Extends default delete() by
     * removing associated tags as well.
     *
     * @return int number of deleted rows
     * @see SimpleORMap::delete()
     */
    public function delete()
    {
        $message_id = $this->message_id;
        $user_id    = $this->user_id;

        $ret = parent::delete();

        if ($ret) {
            $query = "DELETE FROM message_tags
                      WHERE message_id = :message_id AND user_id = :user_id";
            $statement = DBManager::get()->prepare($query);
            $statement->bindValue(':message_id', $message_id);
            $statement->bindValue(':user_id', $user_id);
            $statement->execute();
            $ret += $statement->rowCount();
        }

        return $ret;
    }
}
