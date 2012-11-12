<?php
# Lifter010: TODO
/*
 * MessagingNavigation.php - navigation for messaging area
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'lib/sms_functions.inc.php';

class MessagingNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $user, $neux;

        parent::__construct(_('Nachrichten'));

        $my_messaging_settings = UserConfig::get($user->id)->MESSAGING_SETTINGS;

        $neum = count_messages_from_user('in', ' AND message_user.readed = 0 ');
        $altm = count_messages_from_user('in', ' AND message_user.readed = 1 ');
        $neux = count_x_messages_from_user('in', 'all',
            'AND mkdate > '.(int)$my_messaging_settings['last_box_visit'].' AND message_user.readed = 0 ');

        $mailclass = $neum ? 'new' : '';
        $this->setBadgeNumber($neum);

        if ($neux > 0) {
            $tip = sprintf(ngettext('Sie haben %d neue ungelesene Nachricht',
                                    'Sie haben %d neue ungelesene Nachrichten', $neux), $neux);
        } else if ($neum > 1) {
            $tip = sprintf(ngettext('Sie haben %d ungelesene Nachricht',
                                    'Sie haben %d ungelesene Nachrichten', $neum), $neum);
        } else if ($altm > 1) {
            $tip = sprintf(ngettext('Sie haben %d alte empfangene Nachricht',
                                    'Sie haben %d alte empfangene Nachrichten', $altm), $altm);
        } else {
            $tip = _('Sie haben keine alten empfangenen Nachrichten');
        }

        $this->setImage('header/mail.png', array('title' => $tip, 'class' => $mailclass, "@2x" => TRUE));
    }

    /**
     * Initialize the subnavigation of this item. This method
     * is called once before the first item is added or removed.
     */
    public function initSubNavigation()
    {
        parent::initSubNavigation();

        $this->addSubNavigation('in', new Navigation(_('Posteingang'), 'sms_box.php', array('sms_inout' => 'in')));
        $this->addSubNavigation('out', new Navigation(_('Gesendet'), 'sms_box.php', array('sms_inout' => 'out')));
        $this->addSubNavigation('write', new Navigation(_('Neue Nachricht schreiben'), 'sms_send.php?cmd=new'));
    }
}
