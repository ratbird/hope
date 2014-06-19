<?php
/*
 * WidgetHelper.php - utility functions for Widget-Parameter Handling
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   Nadine Werner <nadine.werner@uni-osnabrueck.de>
 * @author   Andr� Kla�en <klassen@elan-ev.de>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Stud.IP
 * @package  index
 * @since    3.1
 */

class WidgetHelper {
    /**
     * array of submitted widget parameter values
     *
     */
    private static $params = array();

    /**
     * array of submitted widget parameter values
     */
    private static $activeWidget;


    /**
     * Set the last activ Widget
     * @param string $activeWidget
     */
    static function setActiveWidget($activeWidget)
    {
        self::$activeWidget = $activeWidget;
    }

    /**
     * Returns the position in the two column layout on the Startpage
     * If no position is stored in UserConfig, the widget will be displayed on the right side.
     * @return the position as array matrix
     */
    static function getWidgetPosition($pluginid)
    {
        $query = "SELECT position FROM widget_user where id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($pluginid));
        $pos = $statement->fetchColumn();

        return $pos;
    }

    /**
     *

    static function storeNewPosition($pos, $col, $id)
    {
        $pos_string = $pos .",". $col ;
        $query = "update widget_user set position = ? where id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($pos_string, $id));

    }
     */

    /**
     *
     */
    static function storeNewPositions($ids)
    {
        if(is_array($ids))
        {
             foreach ($ids as $pos => $id)
             {
                  $pos = intVal($pos);
                  $query = "UPDATE widget_user set position = ? where id = ?";
                  $statement = DBManager::get()->prepare($query);
                  $statement->execute(array($pos, $id));
             }
        }
    }

    /**
     * addInitialPositons
     */
    static function addInitialPositons($col, $ids, $range_id){
        if(is_array($ids))
        {
             foreach ($ids as $pos => $id)
             {
                  $pos = intVal($pos);
                  $query = "REPLACE INTO widget_user (`pluginid`, `position`, `range_id`) VALUES (?,?,?);";
                  $statement = DBManager::get()->prepare($query);
                  $statement->execute(array($id, $pos, $range_id));
             }
        }
    }

    /**
     * storeInitialPositions
     *
     *
     */

     static function storeInitialPositions($col, $ids, $perm)
     {
         $statement = DBManager::get()->prepare('DELETE FROM widget_default WHERE `perm` = ? AND `column` = ?;');
         $statement->execute(array($perm, $col));

         if(is_array($ids)) {
             foreach ($ids as $pos => $id)
             {
                 if($id != ""){
                     $pos = intVal($pos);
                     $statement = DBManager::get()->prepare("REPLACE INTO widget_default (`pluginid`,`column`, `row`, `perm`) VALUES (?,?,?,?);");
                     $statement->execute(array($id, $col, $pos, $perm));
                 }

             } return true;
         } else return false;
     }


     static function getInitialPositions($perm)
     {
         $query = "SELECT * FROM widget_default WHERE perm=?";
         $statement = DBManager::get()->prepare($query);
         $statement->execute(array($perm));
         $widgets = array();
         while ($db_widget = $statement->fetch(PDO::FETCH_ASSOC)) {
             $widgets[] = $db_widget;
         }
         return $widgets;
     }


    /**
     *
     */
    static function getUserWidgets($id)
    {
        $plugin_manager = PluginManager::getInstance();
        $query = "SELECT * FROM widget_user WHERE range_id=? ORDER BY position";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        $widgets = array();
        while ($db_widget = $statement->fetch(PDO::FETCH_ASSOC)) {
            $widget = clone $plugin_manager->getPluginById($db_widget['pluginid']);
            $widget->widget_id = $db_widget['id'];
            $widgets[$db_widget['position']] = $widget;
        }
        return $widgets;
    }


    /**
     * creates user_config entry for widget newly added by a user
     */
    static function addWidgetUserConfig($id, $pluginName, $confArray )
    {
        UserConfig::get($id)->store($pluginName, $confArray );
    }


    /**
     * creates user_config entry for widget newly added by a user
     * @param $id userId
     * @param $pluginName Name des Plugins
     */
    static function getWidgetUserConfig($id, $pluginName)
    {
        return UserConfig::get($id)->getValue($pluginName);

    }

    /**
     * removes a widget for a user
     */
    static function removeWidget($id, $pluginName, $range_id)
    {
        $query = "DELETE FROM user_config WHERE field = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($pluginName));

        $query = "DELETE FROM widget_user WHERE id = ? AND range_id = ?";
        $statement = DBManager::get()->prepare($query);

        return $statement->execute(array($id, $range_id));
    }

    /**
     * adds a widget for a user
     */
    static function addWidget($id, $range_id)
    {
        $db = DBManager::get();
        $statement = $db->query('SELECT MAX(position) + 1 as pos FROM widget_user');
        $statement->execute();
        $position = $statement->fetchColumn(0);

        if ($position === null) {
            $position = 0;
        }

        $statement = $db->prepare('INSERT INTO widget_user (pluginid, position, range_id) VALUES (:id, :position, :range_id)');

        return $statement->execute(array(
            ':id' => $id,
            ':position' => $position,
            ':range_id' => $range_id,
        ));
    }

    /**
     * get Name for a given widgetid
     */

    static function getWidgetName($id) {

        $query = "SELECT `pluginid` FROM `widget_user` WHERE `id`=?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        $pid = $statement->fetch(PDO::FETCH_ASSOC);

        $plugin_manager = PluginManager::getInstance();
        $plugin_info = $plugin_manager->getPluginById($pid['pluginid']);
        return $plugin_info->getPluginName();

    }


    /**
     * retrieves a  widgets for a given pluginid and plugintype
     */

    static function getWidget($pluginid)
    {
        return PluginManager::getInstance()->getPluginById($pluginid);
    }
}
?>
