<?php
/**
 * VoteAnswerUser.php
 * model class for table voteanswers_user
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.0
 */
class VoteAnswerUser extends SimpleORMap
{
    protected static function configure()
    {
        $config['db_table'] = 'voteanswers_user';
        $config['has_one']['user'] = array (
            'class_name' => 'User',
            'foreign_key' => 'user_id'
        );
        parent::configure($config);
    }
}
