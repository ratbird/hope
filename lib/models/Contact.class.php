<?php
/**
 * Contact.class.php - model class for table contact
 *
 * @author      <mlunzena@uos.de>
 * @license GPL 2 or later
 * @property string contact_id database column
 * @property string id alias column for contact_id
 * @property string owner_id database column
 * @property string user_id database column
 * @property string buddy database column
 * @property string calpermission database column
 * @property SimpleORMapCollection infos has_many ContactUserinfo
 * @property User owner belongs_to User
 * @property User friend belongs_to User
 */
class Contact extends SimpleORMap
{

    function __construct($id = array())
    {

        $this->db_table = 'contact';
        $this->belongs_to['owner'] = array('class_name' => 'User',
                                           'foreign_key' => 'owner_id');

        $this->belongs_to['friend'] = array('class_name' => 'User',
                                            'foreign_key' => 'user_id');

        $this->has_many['infos'] = array('class_name' => 'ContactUserinfo',
                                         'assoc_foreign_key' => 'contact_id');

        parent::__construct($id);
    }

    public function findByOwner_id($id, $order = 'ORDER BY contact_id ASC')
    {
        return self::findBySQL('contact.owner_id = ? ' . $order, array($id));
    }
}
