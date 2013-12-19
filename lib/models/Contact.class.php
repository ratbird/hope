<?php
/**
 * Contact.class.php - model class for table contact
 *
 * @author      <mlunzena@uos.de>
 * @license GPL 2 or later
 */
class Contact extends SimpleORMap
{

    function __construct($id = array())
    {
/*
  `contact_id` varchar(32) NOT NULL DEFAULT '',
  `owner_id` varchar(32) NOT NULL DEFAULT '',
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `buddy` tinyint(4) NOT NULL DEFAULT '1',
  `calpermission` tinyint(2) unsigned NOT NULL DEFAULT '1',
  */

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
