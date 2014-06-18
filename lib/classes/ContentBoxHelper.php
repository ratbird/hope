<?php
/**
 * ContentBoxHelper.php
 * 
 * The ContentBoxHelper controls ids of contentboxes
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.0
 */
class ContentBoxHelper {

    /**
     * Returns the class open if the id was clicked
     * 
     * @param String $id id of the content box
     * @return String open if the contentbox is open otherwise an empty String 
     */
    public static function classes($id) {
        return Request::get('contentbox_open') == $id ? 'open' : '';
    }

    /**
     * Produces an html link to open a contentbox if javascript is not active
     * 
     * @param String $id Id of the content box
     * @param Array $params other needed parameters
     * @return String Url to open the contentbox
     */
    public static function switchhref($id, $params = array()) {
        if (Request::get('contentbox_open') != $id || $params) {
            $params['contentbox_open'] = $id;
        } else {
            $params['contentbox_close'] = $id;
        }
        return URLHelper::getURL("#$id", $params);
    }

    /**
     * Link to the contentbox (Required when some action should take place)
     * 
     * @param String $id Id of the content box
     * @param Array $params other needed parameters
     * @return String Url to the contentbox
     */
    public static function href($id, $params = array()) {
        $params['contentbox_open'] = $id;
        return URLHelper::getURL("#$id", $params);
    }
    
    public static function visitType($type) {
        $object_id = Request::get('contentbox_open');
        if ($object_id) {
            ObjectVisit::visit($object_id, $type);
        }
    }

}
