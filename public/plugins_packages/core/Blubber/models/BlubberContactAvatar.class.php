<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/BlubberExternalContact.class.php";

class BlubberContactAvatar extends Avatar {
    
    static function getAvatar($user_id) {
        return new BlubberContactAvatar($user_id);
    }
    
    /**
     * Returns the URL to the courses' avatars.
     *
     * @return string     the URL to the avatars
     */
    function getAvatarDirectoryUrl()
    {
        return $GLOBALS['DYNAMIC_CONTENT_URL'] . "/blubbercontact";
    }


    /**
     * Returns the file system path to the courses' avatars
     *
     * @return string      the file system path to the avatars
     */
    function getAvatarDirectoryPath()
    {
        return $GLOBALS['DYNAMIC_CONTENT_PATH'] . "/blubbercontact";
    }
    
    /**
     * Return the default title of the avatar.
     * @return string the default title
     */
    function getDefaultTitle()
    {
        return BlubberExternalContact::find($this->user_id)->name;
    }
    
    /**
     * Return if avatar is visible to the current user.
     * @return boolean: true if visible
     */
    protected function checkAvatarVisibility() {
        //no special conditions for visibility of blubber-contact-avatars
        return true;
    }
    
    function getURL($size, $ext = 'png') {
        $this->checkAvatarVisibility();
        if ($this->is_customized()) {
            return $this->getCustomAvatarUrl($size, $ext);
        } else {
            $contact = new BlubberExternalContact($this->user_id);
            $email = $contact['mail_identifier'];
            $email_hash = md5(strtolower(trim($email)));
            $width = $this->getDimension($size);
            return URLHelper::getURL(
                "http://www.gravatar.com/avatar/".$email_hash,
                array(
                    's' => max(array($width[0], $width[1])),
                    'd' => $this->getNobody()->getCustomAvatarUrl($size, $ext)
                ),
                true
            );
        }
    }
    
}