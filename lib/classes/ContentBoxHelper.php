<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ContentBoxHelper
 *
 * @author intelec
 */
class ContentBoxHelper {

    public static function classes($id) {
        return Request::get('contentbox_open') == $id ? 'open' : '' . '"';
    }

    public static function switchhref($id, $params = array()) {
        if (Request::get('contentbox_open') != $id || $params) {
            $params['contentbox_open'] = $id;
        } else {
            $params['contentbox_close'] = $id;
        }
        return URLHelper::getLink("#$id", $params);
    }

    public static function href($id, $params = array()) {
        $params['contentbox_open'] = $id;
        return URLHelper::getLink("#$id", $params);
    }

}
