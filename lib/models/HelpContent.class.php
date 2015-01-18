<?php
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2014 Arne Schr�der <schroeder@data-quest>,
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
 * @author   Arne Schr�der <schroeder@data-quest>
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
 * @property string author_email database column
 * @property string installation_id database column
 * @property string mkdate database column
 * @property string chdate database column
 */
class HelpContent extends SimpleORMap {
    
    /**
     * fetches set of content from database for given route
     * 
     * @param string $route           route for help content
     * @param string $language        language
     * @return array                  set of help content
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
                  WHERE route LIKE CONCAT(?, '%') AND language = ? AND visible = 1";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($route, $language));
        $ret = $statement->fetchGrouped(PDO::FETCH_ASSOC);
        foreach ($ret as $index => $data)
            if (! match_route($data['route'], $route))
                unset($ret[$index]);
        return $ret;
    }

    /**
     * fetches content for given content_id
     * 
     * @param string $id              id of help content
     * @return array                  help content object
     */
    public static function GetContentByID($id = '')
    {
        $query = "SELECT content_id AS idx, help_content.*
                  FROM help_content
                  WHERE content_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        $ret = $statement->fetchGrouped(PDO::FETCH_ASSOC);
        return current(HelpContent::GetContentObjects($ret));
    }

    /**
     * fetches set of help content from database filtered by parameters
     * 
     * @param string $term            search term for content
     * @param boolean $as_objects     include HelpContent objects in result array
     * @return array                  set of help content
     */
    public static function GetContentByFilter($term = '')
    {
        $params = array();
        $condition = '';
        if (strlen(trim($term)) >= 3) { 
            $condition =  "WHERE content LIKE CONCAT('%', ?, '%')";
            $params[] = $term;
        }
        $query = "SELECT content_id AS idx, help_content.*
                  FROM help_content
                  $condition
                  ORDER BY route ASC";
        $statement = DBManager::get()->prepare($query);
        $statement->execute($params);
        $ret = $statement->fetchGrouped(PDO::FETCH_ASSOC);
        return HelpContent::GetContentObjects($ret);
    }

    /**
     * builds help content objects for given set of content data
     * 
     * @param array $content_result   content set
     * @return array                  set of content objects
     */
    public static function GetContentObjects($content_result)
    {
        $objects = array();
        if (is_array($content_result)){
            foreach($content_result as $id => $result){
                $objects[$id] = new HelpContent();
                $objects[$id]->setData($result, true);
                $objects[$id]->setNew(false);
            }
        }
        return $objects;
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
