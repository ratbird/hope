<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/**
 * messagingSettings.php - displays editable personal messaging-settings
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nils K. Windisch <studip@nkwindisch.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     message
 */

use Studip\Button, Studip\LinkButton;

require_once ('lib/language.inc.php');
require_once ('config.inc.php');
require_once 'lib/functions.php';
require_once ('lib/visual.inc.php');
require_once ('lib/user_visible.inc.php');
require_once ('lib/messaging.inc.php');
require_once ('lib/contact.inc.php');

// access to user's config setting
$user_cfg = UserConfig::get($GLOBALS['user']->id);

$reset_txt = '';

## ACTION ##

// add forward_receiver
if (Request::submitted('add_smsforward_rec')) {
    $query = "UPDATE user_info
              SET smsforward_rec = ?, smsforward_copy = 1
              WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        get_userid(Request::get('smsforward_rec')),
        $user->id
    ));
}

// del forward receiver
if (Request::submitted('del_forwardrec')) {
    $query = "UPDATE user_info
              SET smsforward_rec = '', smsforward_copy = 1
              WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
}

$query = "SELECT smsforward_copy, smsforward_rec, email_forward
          FROM user_info
          WHERE user_id='".$user->id."'";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($user->id));
$row = $statement->fetch(PDO::FETCH_ASSOC);

$smsforward['copy'] = $row['smsforward_copy'];
$smsforward['rec']  = $row['smsforward_rec'];
$email_forward      = $row['email_forward'];

if ($email_forward == 0) {
    $email_forward = $GLOBALS['MESSAGING_FORWARD_DEFAULT'];
}

//vorgenommene Anpassungen der Ansicht in Uservariablen schreiben

if (Request::option('messaging_cmd')=="change_view_insert" && !Request::submitted('set_msg_default') && Request::submitted('newmsgset')) {
    $send_as_email = Request::int('send_as_email');

    $query = "UPDATE user_info SET email_forward = ? WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(
        $send_as_email,
        $user->id
    ));
    $email_forward = $send_as_email;

    // write to user config table
    $user_cfg->store('ONLINE_NAME_FORMAT', Request::option('online_format'));
    $user_cfg->store('MAIL_AS_HTML', Request::int('mail_format'));

    $my_messaging_settings['changed'] = TRUE;
    $my_messaging_settings['delete_messages_after_logout'] = Request::int('delete_messages_after_logout');
    $my_messaging_settings['start_messenger_at_startup']   = Request::int('start_messenger_at_startup');
    $my_messaging_settings['sms_sig']              = Request::get('sms_sig');
    $my_messaging_settings['timefilter']           = Request::option('timefilter');
    $my_messaging_settings['openall']              = Request::int('openall');
    $my_messaging_settings['opennew']              = Request::int('opennew', 2);
    $my_messaging_settings['logout_markreaded']    = Request::int('logout_markreaded');
    $my_messaging_settings['addsignature']         = Request::int('addsignature');
    $my_messaging_settings['confirm_reading']      = Request::int('confirm_reading');
    $my_messaging_settings['save_snd']             = Request::int('save_snd', 2);
    $my_messaging_settings['request_mail_forward'] = Request::int('request_mail_forward', 0);

    $sms_data['sig']  = $my_messaging_settings['addsignature'];
    $sms_data['time'] = $my_messaging_settings['timefilter'];

    UserConfig::get($GLOBALS['user']->id)->store("my_messaging_settings",json_encode($my_messaging_settings));
    if ($smsforward['rec']) {
        if (Request::int('smsforward_copy') && !$smsforward['copy'])  {
            $query = "UPDATE user_info SET smsforward_copy = 1 WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id));
        }
        if (!Request::int('smsforward_copy') && $smsforward['copy'])  {
            $query = "UPDATE user_info SET smsforward_copy = 0 WHERE user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($user->id));
        }
    }
} else if (Request::option('messaging_cmd')=="change_view_insert" && Request::submitted('set_msg_default')) {
    $reset_txt = _('Durch das Zurücksetzen werden die persönliche Messaging-Einstellungen auf die Startwerte zurückgesetzt <b>und</b> die persönlichen Nachrichten-Ordner gelöscht. <b>Nachrichten werden nicht entfernt.</b>');
}

if (Request::option('messaging_cmd') == "reset_msg_settings") {
    $user_id = $user->id;
    unset($my_messaging_settings);
    if (!$my_messaging_settings['show_only_buddys'])
        $my_messaging_settings['show_only_buddys'] = FALSE;
    if (!$my_messaging_settings['delete_messages_after_logout'])
        $my_messaging_settings['delete_messages_after_logout'] = FALSE;
    if (!$my_messaging_settings['start_messenger_at_startup'])
        $my_messaging_settings['start_messenger_at_startup'] = FALSE;
    if (!$my_messaging_settings['default_setted'])
        $my_messaging_settings['default_setted'] = time();
    if (!$my_messaging_settings['last_login'])
        $my_messaging_settings['last_login'] = FALSE;
    if (!$my_messaging_settings['timefilter'])
        $my_messaging_settings['timefilter'] = "30d";
    if (!$my_messaging_settings['opennew'])
        $my_messaging_settings['opennew'] = 1;
    if (!$my_messaging_settings['logout_markreaded'])
        $my_messaging_settings['logout_markreaded'] = FALSE;
    if (!$my_messaging_settings['openall'])
        $my_messaging_settings['openall'] = FALSE;
    if (!$my_messaging_settings['addsignature'])
        $my_messaging_settings['addsignature'] = FALSE;
    if (!$my_messaging_settings['save_snd'])
        $my_messaging_settings['save_snd'] = 1;
    if (!$my_messaging_settings['sms_sig'])
        $my_messaging_settings['sms_sig'] = FALSE;
    if (!$my_messaging_settings['send_view'])
        $my_messaging_settings['send_view'] = FALSE;
    if (!$my_messaging_settings['last_box_visit'])
        $my_messaging_settings['last_box_visit'] = 1;
    if (!$my_messaging_settings['folder']['in'])
        $my_messaging_settings['folder']['in'][0] = "dummy";
    if (!$my_messaging_settings['folder']['out'])
        $my_messaging_settings['folder']['out'][0] = "dummy";
    if (!$my_messaging_settings['confirm_reading'])
        $my_messaging_settings['confirm_reading'] = 3;
    UserConfig::get($GLOBALS['user']->id)->store("my_messaging_settings",json_encode($my_messaging_settings));
    $query = "UPDATE user_info
              SET smsforward_copy = 0, smsforward_rec = ''
              WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));

    $query = "UPDATE message_user SET folder = 0 WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user_id));
}

$add_user = Request::option('add_user');
if (Request::submitted('do_add_user')) {
    $msging = new messaging;
    $msging->add_buddy ($add_user);
}

## FUNCTION ##

function change_messaging_view($my_messaging_settings)
{
    global $_fullname_sql, $user,
           $reset_txt, $email_forward, $user_cfg;

    if ($reset_txt) {
        echo createQuestion("Möchten Sie fortfahren?\n\n" . strip_tags($reset_txt), array(
            'messaging_cmd' => 'reset_msg_settings',
            'change_view'   => true,
            'view'          => 'Messaging'
        ), array(
            'view' => 'Messaging'
        ));
    }

    $query = "SELECT smsforward_copy AS copy, smsforward_rec AS rec
              FROM user_info
              WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($user->id));
    $smsforward = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$smsforward['rec'] && Request::submitted('gosearch')) {
        $vis_query = get_vis_query('auth_user_md5');
        $query = "SELECT user_id, username, {$_fullname_sql['full_rev']} AS fullname, perms
                  FROM auth_user_md5
                  LEFT JOIN user_info USING (user_id)
                  WHERE (username LIKE CONCAT('%', :needle, '%') OR
                         Vorname LIKE CONCAT('%', :needle, '%') OR
                         Nachname LIKE CONCAT('%', :needle, '%'))
                    AND user_id != :user_id AND {$vis_query}
                  ORDER BY Nachname ASC";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':needle', Request::get('search_exp'));
        $statement->bindValue(':user_id', $user->id);
        $statement->execute();
        $matches = $statement->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $matches = false;
    }

    $send_as_email = array(
        1 => _('nie'),
        2 => _('immer'),
        3 => _('wenn vom Absender gewünscht'),
    );
    $mail_formats = array(
        0 => _('Text'),
        1 => _('HTML'),
    );
    $confirmation_types = array(
        1 => _('ignorieren'),
        2 => _('immer automatisch bestätigen'),
        3 => _('je Nachricht selbst entscheiden'),
    );
    $timefilters = array(
        'new'   => _('neue Nachrichten'),
        'all'   => _('alle Nachrichten'),
        '24h'   => _('letzte 24 Stunden'),
        '7d'    => _('letzte 7 Tage'),
        '30d'   => _('letzte 30 Tage'),
        'older' => _('älter als 30 Tage'),
    );

    $template = $GLOBALS['template_factory']->open('settings/messaging');

    $template->settings      = $my_messaging_settings;
    $template->name_format   = $user_cfg->getValue('ONLINE_NAME_FORMAT');
    $template->mail_format   = $user_cfg->getValue('MAIL_AS_HTML');
    $template->email_forward = $email_forward;
    $template->smsforward    = $smsforward;
    $template->matches       = $matches;

    $template->send_as_email      = $send_as_email;
    $template->mail_formats       = $mail_formats;
    $template->confirmation_types = $confirmation_types;
    $template->timefilters        = $timefilters;

    echo $template->render();
}
