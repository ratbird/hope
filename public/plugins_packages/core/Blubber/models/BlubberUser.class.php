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
}

class BlubberUser extends User implements BlubberContact {
    
    public function getName() {
        return trim($this['Vorname']." ".$this['Nachname']);
    }
    
    public function getURL() {
        return URLHelper::getURL("about.php", array('username' => $this['username']), true);
    }
    
    public function getAvatar() {
        return Avatar::getAvatar($this->getId());
    }
}