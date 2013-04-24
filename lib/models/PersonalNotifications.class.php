<?php

/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

/**
 * This is an easy to use class to display personal notifications to the user,
 * that are shown in real-time. Users can deactivate the feature of personal
 * notifications, but by default it is turned on.
 *
 * For example if user A subscribed to the changes of a special wiki-page, and
 * another user B changed this wiki-page, you can inform user A about the change
 * by the following one-liner:
 * 
 * PersonalNotifications::add(
 *      $user_A_user_id, //id of user A or array of �multiple user_ids
 *      $url_of_wiki_page, //when user A clicks this URL he/she should jump directly to the changed wiki-page
 *      "User B changed wiki-page xyz", //a small text that describes the notification
 *      "wiki_page_1234", //an (optional) html-id of the content of the wiki page. If the user is looking at the content already, the notification will disappear automatically
 *      Assets::image_path("icons/40/blue/wiki"), //an (optional) icon that is displayed next to the notification-text
 * );
 *
 * Appearing to the user, deleting by the user and so on of the notification is
 * handled by this class automatically.
 */
class PersonalNotifications extends SimpleORMap {

    /**
     * Central function to add a personal notification to the user. This could be
     * anything that needs to catch the attention of the user. The notification
     * will be displayed in realtime to the user and he/she can get to the url.
     * @param array|string $user_ids : array of user_ids or a single md5-user_id
     * @param string $url : URL of the point of interest of the notification
     * @param string $text : a displayed text that describes the notification
     * @param null|string $html_id : id in the html-document. If user reaches
     *   this html-element the notification will be marked as read, so the user
     *   does not need to handle the information twice. Optional. Default: null
     * @param string $avatar : URL of an image for the notification. Best size: 40px x 40px
     * @return boolean : true on success
     */
    static public function add($user_ids, $url, $text, $html_id = null, $avatar = null) {
        if (!is_array($user_ids)) {
            $user_ids = array($user_ids);
        }
        if (!count($user_ids)) {
            return false;
        }
        $notification = new PersonalNotifications();
        $notification['html_id'] = $html_id;
        $notification['url'] = $url;
        $notification['text'] = $text;
        $notification['avatar'] = $avatar;
        $notification->store();
        
        foreach ($user_ids as $user_id) {
            if (self::isActivated($user_id)) {
                $db = DBManager::get();
                $insert_statement = $db->prepare(
                    "INSERT INTO personal_notifications_user " .
                    "SET user_id = :user_id, " .
                        "personal_notification_id = :id, " .
                        "seen = '0' " .
                "");
                $insert_statement->execute(array(
                    'id' => $notification->getId(), 
                    'user_id' => $user_id
                ));
            }
        }
        return true;
    }

    /**
     * Returns all notifications fitting to the parameters.
     * @param boolean $only_unread : true for getting only unread notifications, false for all.
     * @param null|string $user_id : ID of special user the notification should belong to or (default:) null for current user
     * @return array of \PersonalNotifications in ascending order of mkdate
     */
    static public function getMyNotifications($only_unread = true, $user_id = null) {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        $statement = DBManager::get()->prepare(
            "SELECT pn.* " .
            "FROM personal_notifications AS pn " .
                "INNER JOIN personal_notifications_user AS u ON (u.personal_notification_id = pn.personal_notification_id) " .
            "WHERE u.user_id = :user_id " .
                ($only_unread ? "AND u.seen = '0' " : "") .
            "GROUP BY pn.url " .
            "ORDER BY mkdate ASC " .
        "");
        $statement->execute(array('user_id' => $user_id));
        $notifications = array();
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $data) {
            $notification = new PersonalNotifications();
            $notification->setData($data);
            $notifications[] = $notification;
        }
        return $notifications;
    }

    /**
     * Mark a notification as read by the user. It won't appear anymore in the
     * notification-list on top of its site.
     * @param string $notification_id : ID of the notification
     * @param string|null $user_id : ID of special user the notification should belong to or (default:) null for current user
     * @return boolean : true on success, false if it failed.
     */
    static public function markAsRead($notification_id, $user_id = null) {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        $pn = new PersonalNotifications($notification_id);
        $statement = DBManager::get()->prepare(
            "UPDATE personal_notifications_user AS pnu " .
                "INNER JOIN personal_notifications AS pn ON (pn.personal_notification_id = pnu.personal_notification_id) " .
            "SET pnu.seen = '1' " .
            "WHERE pnu.user_id = :user_id " .
                "AND pn.url = :url " .
        "");
        return $statement->execute(array(
            'user_id' => $user_id,
            'url' => $pn['url']
        ));
    }

    /**
     * Activates personal notifications for a given user.
     * @param string|null $user_id : ID of special user the notification should belong to or (default:) null for current user
     */
    static public function activate($user_id = null) {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        UserConfig::get($user_id)->store("PERSONAL_NOTIFICATIONS_DEACTIVATED", "0");
    }
    
    /**
     * Deactivates personal notifications for a given user.
     * @param string|null $user_id : ID of special user the notification should belong to or (default:) null for current user
     */
    static public function deactivate($user_id = null) {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        UserConfig::get($user_id)->store("PERSONAL_NOTIFICATIONS_DEACTIVATED", "1");
    }
    
    /**
     * Activates audio plopp for new personal notifications for a given user.
     * @param string|null $user_id : ID of special user the notification should belong to or (default:) null for current user
     */
    static public function activateAudioFeedback($user_id = null) {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        UserConfig::get($user_id)->store("PERSONAL_NOTIFICATIONS_AUDIO_DEACTIVATED", "0");
    }
    
    /**
     * Deactivates audio plopp for new personal notifications for a given user.
     * @param string|null $user_id : ID of special user the notification should belong to or (default:) null for current user
     */
    static public function deactivateAudioFeedback($user_id = null) {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        UserConfig::get($user_id)->store("PERSONAL_NOTIFICATIONS_AUDIO_DEACTIVATED", "1");
    }

    /**
     * Checks if personal notifications are activated for the whole Stud.IP. This
     * could be false for performance issues.
     * @return boolean : true if activated else false
     */
    static public function isGloballyActivated()
    {
        $config = Config::GetInstance();
        return !empty($config['PERSONAL_NOTIFICATIONS_ACTIVATED']);
    }
    
    /**
     * Checks if a given user should see the personal notification. Either the 
     * Stud.IP or the user could deactivate personal notification. If neither is
     * the case, this function returns true.
     * @param string|null $user_id : ID of special user the notification should belong to or (default:) null for current user
     * @return boolean : true if activated else false
     */
    static public function isActivated($user_id = null) {
        if (!PersonalNotifications::isGloballyActivated()) {
            return false;
        }
        
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        return UserConfig::get($user_id)->getValue("PERSONAL_NOTIFICATIONS_DEACTIVATED") ? false : true;
    }
    
    /**
     * Checks if a given user should hear audio plopp for new personal notification.
     * Either the Stud.IP or the user could deactivate personal notification or
     * audio feedback. If neither is the case, this function returns true.
     * @param string|null $user_id : ID of special user the notification should belong to or (default:) null for current user
     * @return boolean : true if activated else false
     */
    static public function isAudioActivated($user_id = null) {
        if (!PersonalNotifications::isGloballyActivated()) {
            return false;
        }
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        return UserConfig::get($user_id)->getValue("PERSONAL_NOTIFICATIONS_AUDIO_DEACTIVATED") ? false : true;
    }
    
    /**
     * Constructor of PersonalNotifications
     * @param type $id
     */
    function __construct($id = null)
    {
        $this->db_table = "personal_notifications";
        $this->default_values['text'] = '';
        parent::__construct($id);
    }

    /**
     * Returns HTML-represantation of the notification which is a list-element.
     * @return string : html-output;
     */
    public function getLiElement() {
        return $GLOBALS['template_factory']
                ->open("personal_notifications/notification.php")
                ->render(array('notification' => $this));
    }

    public function more_unseen() {
        $statement = DBManager::get()->prepare(
            "SELECT count(*) " .
            "FROM personal_notifications AS pn " .
                "INNER JOIN personal_notifications_user AS u ON (pn.personal_notification_id = u.personal_notification_id) " .
            "WHERE pn.personal_notification_id != :pn_id " .
                "AND u.user_id = :user_id " .
                "AND u.seen = '0' " .
        "");
        $statement->execute(array(
            'pn_id' => $this->getId(),
            'user_id' => $GLOBALS['user']->id
        ));
        $number = $statement->fetch(PDO::FETCH_COLUMN, 0);
        return $number;
    }

}