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

/**
 * Controller called by the main periodical ajax-request. It collects data,
 * converts the textstrings to utf8 and returns it as a json-object to the
 * internal javascript-function "STUDIP.JSUpdater.process(json)".
 */
class JsupdaterController extends AuthenticatedController
{
    // Allow nobody to prevent login screen
    // Refers to http://develop.studip.de/trac/ticket/4771
    protected $allow_nobody = true;

    /**
     * Checks whether we have a valid logged in user,
     * send "Forbidden" otherwise
     *
     * @param String $action The action to perform
     * @param Array  $args   Potential arguments
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // Check for a valid logged in user (only when an ajax request occurs)
        if (Request::isXHR() && (!is_object($GLOBALS['user']) || $GLOBALS['user']->id === 'nobody')) {
            $this->response->set_status(403);
            $action = 'nop';
        }
    }

    /**
     * Does and renders absolute nothing.
     */
    public function nop_action()
    {
        $this->render_nothing();
    }

    /**
     * Main action that returns a json-object like
     * {
     *  'js_function.sub_function': data,
     *  'anotherjs_function.sub_function': moredata
     * }
     * This action is called by STUDIP.JSUpdater.poll and the result processed
     * the internal STUDIP.JSUpdater.process method
     */
    public function get_action()
    {
        $data = UpdateInformation::getInformation();
        $data = array_merge($data, $this->coreInformation());
        $data = studip_utf8encode($data);

        $this->set_content_type('application/json;charset=utf-8');
        $this->render_text(json_encode($data));
    }

    /**
     * Marks a personal notification as read by the user so it won't be displayed
     * in the list in the header.
     * @param string $id : hash-id of the notification
     */
    public function mark_notification_read_action($id)
    {
        PersonalNotifications::markAsRead($id);
        if (Request::isXhr()) {
            $this->render_nothing();
        } else {
            $notification = new PersonalNotifications($id);
            if ($notification->url) {
                $this->redirect(URLHelper::getUrl(TransformInternalLinks($notification->url)));
            } else {
                $this->render_nothing();
            }
        }
    }

    /**
     * Sets the background-color of the notification-number to blue, so it does
     * not annoy the user anymore. But he/she is still able to see the notificaion-list.
     * Just sets a unix-timestamp in the user-config NOTIFICATIONS_SEEN_LAST_DATE.
     */
    public function notifications_seen_action()
    {
        UserConfig::get($GLOBALS['user']->id)->store('NOTIFICATIONS_SEEN_LAST_DATE', time());
        $this->render_text(time());
    }

    /**
     * SystemPlugins may call UpdateInformation::setInformation to set information
     * to be sent via ajax to the main request. Core-functionality-data should be
     * collected and set here.
     * @return array: array(array('js_function' => $data), ...)
     */
    protected function coreInformation()
    {
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
        $page_info = Request::getArray("page_info");
        if (stripos(Request::get("page"), "dispatch.php/messages") !== false) {
            $messages = Message::findNew(
                $GLOBALS["user"]->id,
                $page_info['Messages']['received'],
                $page_info['Messages']['since'],
                $page_info['Messages']['tag']
            );
            $template_factory = $this->get_template_factory();
            foreach ($messages as $message) {
                $data['Messages.newMessages']['messages'][$message->getId()] = $template_factory
                        ->open("messages/_message_row.php")
                        ->render(compact("message") + array('controller' => $this));
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
    protected function recursive_studip_utf8encode(array $data)
    {
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