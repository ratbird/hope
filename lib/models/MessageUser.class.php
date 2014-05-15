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
 * @property string folder database column
 * @property string confirmed_read database column
 * @property string answered database column
 * @property string mkdate database column
 * @property string id computed column read/write
 * @property User user has_one User
 * @property Message message has_one Message
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
}
