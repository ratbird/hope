<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/BlubberUser.class.php";
require_once dirname(__file__)."/BlubberContactAvatar.class.php";

class BlubberExternalContact extends SimpleORMap implements BlubberContact {
    
    static public function find($user_id) {
        $user = parent::find($user_id);
        if (class_exists($user['contact_type'])) {
            $new_user = new $user['contact_type']();
            $new_user->setData($user->toArray());
            return $new_user;
        } else {
            return $user;
        }
    }
    
    static public function findByEmail($email) {
        $email = strtolower($email);
        $user = self::findBySQL("mail_identifier = ".DBManager::get()->quote($email));
        if (!count($user)) {
            $user = new BlubberExternalContact();
            $user['mail_identifier'] = $email;
            return $user;
        }
        return $user[0];
    }
    
    public function getName() {
        return $this->content['name'];
    }
    
    public function getURL() {
        return $this['mail_identifier'] ? "mailto:".$this['mail_identifier'] : null;
    }
    
    public function getAvatar() {
        return BlubberContactAvatar::getAvatar($this->getId());
    }
    
    /**
     * This sends an email to the user to recognize him/her that he/she was 
     * mentioned in a blubber.
     * @param type $posting 
     */
    public function mention($posting) {
        $url = $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins.php/blubber/streams/thread/"
            . $posting['root_id']
            . ($posting['context_type'] === "course" ? '?cid='.$posting['Seminar_id'] : "");
        $message = sprintf(
            _("%s hat Sie in einem Blubber erwähnt. Zum Beantworten klicken auf Sie auf folgenen Link:\n\n%s\n"),
            get_fullname(), 
            $url
        );
        StudipMail::sendMessage($this['mail_identifier'], _("Sie wurden erwähnt."), $message);
    }
    
    function __construct($id = null)
    {
        $this->db_table = 'blubber_external_contact';
        $this->registerCallback('before_store', 'cbSerializeData');
        $this->registerCallback('after_store after_initialize', 'cbUnserializeData');
        parent::__construct($id);
    }
    
    function cbSerializeData()
    {
        $this->content['data'] = serialize($this->content['data']);
        return true;
    }

    function cbUnserializeData()
    {
        $this->content['data'] = (array) unserialize($this->content['data']);
        return true;
    }
}