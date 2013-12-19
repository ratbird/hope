<?php

class MessageUser extends SimpleORMap {

    /**
     * constructor
     * @param string id: primary key of table dokumente
     * @return null
     */
    function __construct($id = null)
    {
        $this->db_table = 'message_user';

        $this->has_one['user'] = array(
            'class_name' => 'User',
            'foreign_key' => 'user_id'
        );

        $this->has_one['message'] = array(
            'class_name' => 'Message',
            'foreign_key' => 'message_id'
        );

        parent::__construct($id);
    }
}
