<?php
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2014 Arne Schröder <schroeder@data-quest>,
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
//require_once 'lib/object.inc.php';

/**
 * HelpContent.class.php - model class for Stud.IP help content
 *
 *
 *
 *
 * @author   Arne Schröder <schroeder@data-quest>
 * @access   public
 *
 * @property string content_id database column
 * @property string language database column
 * @property string label database column
 * @property string icon database column
 * @property string content database column
 * @property string route database column
 * @property string studip_version database column
 * @property string position database column
 * @property string custom database column
 * @property string visible database column
 * @property string author_id database column
 * @property string installation_id database column
 * @property string mkdate database column
 * @property string id computed column read/write
 */
class HelpContent extends SimpleORMap {
    
    /**
     * fetches set of content from database for given route
     * 
     * @param string $route           route for tours to begin
     * @param boolean $as_objects     include HelpTour objects in result array
     * @return array                  set of tours
     */
    public static function GetContentByRoute($route = '', $language = '')
    {
        $language = $language ?: substr($GLOBALS['user']->preferred_language, 0, 2);
        if (!$language)
            $language = 'de';
        $version = Config::get()->getValue('HELP_CONTENT_CURRENT_VERSION');
        if (!$version)
            return array();
        $route = get_route($route);
        $query = "SELECT *
                  FROM help_content
                  WHERE route LIKE CONCAT(?, '%') AND language = ? AND studip_version = ? AND visible = 1
                  ORDER BY position ASC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($route, $language, $version));
        $ret = $statement->fetchGrouped(PDO::FETCH_ASSOC);
        foreach ($ret as $index => $data)
            if (! match_route($data['route'], $route))
                unset($ret[$index]);
        return $ret;
    }

    /**
     * configure SORM
     * 
     * @param array $config           configuration 
     */
    protected static function configure($config = array())
    {
        $config['db_table'] = 'help_content';
        
        parent::configure($config);
    }
}
