#!/usr/bin/php -q
<?php
# Lifter003: TEST
# Lifter007: TODO
/**
* send_mail_notifications.php
*
*
*
*
* @author       André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// send_mail_notifications.php
//
// Copyright (C) 2005 André Noack <noack@data-quest.de>,
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
require_once dirname(__FILE__) . '/studip_cli_env.inc.php';
require_once 'lib/language.inc.php';
require_once 'lib/functions.php';
require_once 'lib/classes/ModulesNotification.class.php';


get_config('MAIL_NOTIFICATION_ENABLE') || trigger_error('Mail notifications are disabled in this Stud.IP installation.', E_USER_ERROR);
($ABSOLUTE_URI_STUDIP) || trigger_error('To use mail notifications you MUST set correct values for $ABSOLUTE_URI_STUDIP in config_local.inc.php!', E_USER_ERROR);

set_time_limit(60*60*2);

// note: notifications for plugins not implemented

$notification = new ModulesNotification();

$query = "SELECT user_id, aum.username, Email,
                 {$GLOBALS['_fullname_sql']['full']} AS fullname
          FROM seminar_user AS su
          INNER JOIN auth_user_md5 AS aum USING (user_id)
          LEFT JOIN user_info AS ui USING (user_id)
          WHERE notification != 0";
if (get_config('DEPUTIES_ENABLE')) {
    $query .= " UNION ".getMyDeputySeminarsQuery('notification_cli', '', '', '', '');
}
$query .= " GROUP BY user_id";
$statement = DBManager::get()->query($query);
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $user = new Seminar_User($row['user_id']);
    setTempLanguage($row['user_id']);
    $to = $row['Email'];
    $title = "[" . $GLOBALS['UNI_NAME_CLEAN'] . "] " . _("Tägliche Benachrichtigung");
    $mailmessage = $notification->getAllNotifications($row['user_id']);
    if ($mailmessage) {
        $user_cfg = UserConfig::get($row['user_id']);
        if ($user_cfg->getValue('MAIL_AS_HTML')) {
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
