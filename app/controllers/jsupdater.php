<?php
/*
 * Copyright (c) 2011  Rasmus Fuhse
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/UpdateInformation.class.php';
require_once 'lib/classes/PersonalNotifications.class.php';

/**
 * Controller called by the main periodical ajax-request. It collects data,
 * converts the textstrings to utf8 and returns it as a json-object to the
 * javascript-function "STUDIP.JSUpdater.processUpdate(json)".
 */
class JsupdaterController extends AuthenticatedController {

    /**
     * Main action that returns a json-object like
     * {
     *  'js_function.sub_function': data,
     *  'anotherjs_function.sub_function': moredata
     * }
     * This action is called by STUDIP.JSUpdater.call and the result processed by
     * STUDIP.JSUpdater.processUpdate
     */
    public function get_action() {
        $data = UpdateInformation::getInformation();
        $data = array_merge($data, $this->coreInformation());
        $data = studip_utf8encode($data);
        $this->render_text(json_encode($data));
    }

    public function mark_notification_read_action($id) {
        PersonalNotifications::markAsRead($id);
        if (Request::isXhr()) {
            $this->render_nothing();
        } else {
            $notification = new PersonalNotifications($id);
            if ($notification->url) {
                $this->redirect(UrlHelper::getUrl($notification->url));
            } else {
                $this->render_nothing();
            }
        }
    }

    /**
     * SystemPlugins may call UpdateInformation::setInformation to set information
     * to be sent via ajax to the main request. Core-functionality-data should be
     * collected and set here.
     * @return array: array(array('js_function' => $data), ...)
     */
    protected function coreInformation() {
        $data = array();
        if (PersonalNotifications::isActivated()) {
            $notifications = PersonalNotifications::getMyNotifications();
            if ($notifications && count($notifications)) {
                $ret = array();
                foreach ($notifications as $notification) {
                    $info = $notification->toArray();
                    $info['html'] = $notification->getLiElement();
                    $ret[] = $info;
                }
                $data['PersonalNotifications.newNotifications'] = $ret;
            } else {
                $data['PersonalNotifications.newNotifications'] = array();
            }
        }
        return $data;
    }

    /**
     * Converts all strings within an array (except for indexes)
     * from windows 1252 to utf8. PHP-objects are ignored.
     * @param array $data: any array with strings in windows-1252 encoded
     * @return array: almost the same array but strings are now utf8-encoded
     */
    protected function recursive_studip_utf8encode(array $data) {
        foreach ($data as $key => $component) {
            if (is_array($component)) {
                $data[$key] = $this->recursive_studip_utf8encode($component);
            } elseif(is_string($component)) {
                $data[$key] = studip_utf8encode($component);
            }
        }
        return $data;
    }
}