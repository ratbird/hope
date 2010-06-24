<?php


// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// QuickSearch.class.php
// Copyright (C) 2010 Rasmus Fuhse <fuhse@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

/**
 * A class-structure for alle search-objects in Stud.IP.
 * It is (mainly?) used in QuickSearch to display searchresults and the
 * layout of them.
 * 
 * @author Rasmus Fuhse
 * 
 */
class SearchType {
    /**
     * title of the search like "search for courses" or just "courses"
     * @return string
     */
    public function getTitle() {
        return "";
    }
    
    /**
     * Returns an URL to a picture of that type. Return "" for nothing found.
     * For example: "return CourseAvatar::getAvatar($id)->getURL(Avatar::SMALL)".
     * @param id: string
     * @return: string URL to a picture
     */
    public function getAvatar($id) {
        return "";
    }
    
    /**
     * Returns an HTML-Tag of a picture of that type. Return "" for nothing found.
     * For example: "return CourseAvatar::getAvatar($id)->getImageTag(Avatar::SMALL)".
     * @param id: string
     * @return: string HTML of a picture
     */
    public function getAvatarImageTag($id) {
        return "";
    }
    
    /**
     * Returns the results to a given keyword. To get the results is the
     * job of this routine and it does not even need to come from a database.
     * The results should be an array in the form
     * array (
     *   array($key, $name),
     *   array($key, $name),
     *   ...
     * )
     * where $key is an identifier like user_id and $name is a displayed text
     * that should appear to represent that ID.
     * @param keyword: string
     * @return array
     */
    public function getResults($keyword, $contextual_data = array()) {
        return array(array("", _("Die Suchklasse, die Sie verwenden, enthält keine Methode getResults.")));
    }
    
    /**
     * Returns the path to this file, so that this class can be autoloaded and is 
     * always available when necessary.
     * 
     * @return string   path to this file
     */
    public function includePath() {
        return __file__;
    }
}

