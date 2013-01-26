<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

interface BlubberContact {
    public function getName();
    
    public function getURL();
    
    public function getAvatar();
    
    public function mention($posting);
}

class BlubberUser extends User implements BlubberContact {
    
    public function getName() {
        return trim($this['Vorname']." ".$this['Nachname']);
    }
    
    public function getURL() {
        return $GLOBALS['ABSOLUTE_URI_STUDIP']."about.php?username=".$this['username'];
    }
    
    public function getAvatar() {
        return Avatar::getAvatar($this->getId());
    }
    
    public function mention($posting) {
        $messaging = new messaging();
        $url = $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins.php/blubber/streams/thread/"
            . $posting['root_id']
            . ($posting['context_type'] === "course" ? '?cid='.$posting['Seminar_id'] : "");
        $messaging->insert_message(
            sprintf(
                _("%s hat Sie in einem Blubber erwähnt. Zum Beantworten klicken auf Sie auf folgenen Link:\n\n%s\n"),
                get_fullname(),
                $url
            ),
            $this['username'],
            $GLOBALS['user']->id,
            null, null, null, null,
            _("Sie wurden erwähnt.")
        );
    }
}