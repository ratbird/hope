<?php
/**
 * ContactUserinfo.class.php - model class for table contact_userinfo
 *
 * @author      <mlunzena@uos.de>
 * @license GPL 2 or later
 * @property string userinfo_id database column
 * @property string id alias column for userinfo_id
 * @property string contact_id database column
 * @property string name database column
 * @property string content database column
 * @property string priority database column
 * @property Contact contact belongs_to Contact
 */
class ContactUserinfo extends SimpleORMap
{

    function __construct($id = array())
    {

        $this->db_table = 'contact_userinfo';
        $this->belongs_to['contact'] = array('class_name' => 'Contact',
                                             'foreign_key' => 'contact_id',
                                             'assoc_foreign_key' => 'contact_id');

        parent::__construct($id);
    }
}
