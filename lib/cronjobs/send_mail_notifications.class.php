<?php
/**
 * send_mail_notifications.php - Sends daily email notifications.
 *
 * @author  André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @access  public
 */

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// send_mail_notifications.php
//
// Copyright (C) 2013 Jan-Hendrik Willms <tleilax+studip@gmail.com>
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


// TODO: notifications for plugins not implemented

class SendMailNotificationsJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('Versendet tägliche E-Mailbenachrichtigungen');
    }
    
    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Versendet die täglichen E-Mailbenachrichtigungen an alle Nutzer, die diese aktiviert haben');
    }
    
    /**
     * Setup method. Loads neccessary classes and checks environment. Will
     * bail out with an exception if environment does not match requirements.
     */
    public function setUp()
    {
        require_once 'lib/language.inc.php';
        require_once 'lib/functions.php';
        require_once 'lib/deputies_functions.inc.php';
        require_once 'lib/classes/StudipMail.class.php';
        require_once 'lib/classes/ModulesNotification.class.php';

        if (!Config::get()->MAIL_NOTIFICATION_ENABLE) {
            throw new Exception('Mail notifications are disabled in this Stud.IP installation.');
        }
        if (empty($GLOBALS['ABSOLUTE_URI_STUDIP'])) {
            throw new Exception('To use mail notifications you MUST set correct values for $ABSOLUTE_URI_STUDIP in config_local.inc.php!');
        }
    }
    
    /**
     * Executes the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                           returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                          were defined during scheduling.
     *                          Only valid parameter at the moment is
     *                          "verbose" which toggles verbose output while
     *                          purging the cache.
     */
    public function execute($last_result, $parameters = array())
    {
        $notification = new ModulesNotification();

        $query = "SELECT user_id, aum.username, Email,
                         {$GLOBALS['_fullname_sql']['full']} AS fullname
                  FROM seminar_user AS su
                  INNER JOIN auth_user_md5 AS aum USING (user_id)
                  LEFT JOIN user_info AS ui USING (user_id)
                  WHERE notification != 0";
        if (Config::get()->DEPUTIES_ENABLE) {
            $query .= " UNION " . getMyDeputySeminarsQuery('notification_cli', '', '', '', '');
        }
        $query .= " GROUP BY user_id";
        $statement = DBManager::get()->query($query);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            setTempLanguage($row['user_id']);
            $to = $row['Email'];
            $title = "[" . $GLOBALS['UNI_NAME_CLEAN'] . "] " . _("Tägliche Benachrichtigung");
            $mailmessage = $notification->getAllNotifications($row['user_id']);
            if ($mailmessage) {
                if (UserConfig::get($row['user_id'])->MAIL_AS_HTML) {
                    $smail = new StudipMail();
                    $ok = $smail->setSubject($title)
                                ->addRecipient($to)
                                ->setBodyHtml($mailmessage['html'])
                                ->setBodyText($mailmessage['text'])
                                ->send();
                } else {
                    $ok = StudipMail::sendMessage($to, $title, $mailmessage['text']);
                }
            }
        }
    }
}
