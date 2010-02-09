<?php
/*
 * ChatNavigation.php - navigation for chat
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once $GLOBALS['RELATIVE_PATH_CHAT'] . '/chat_func_inc.php';

class ChatNavigation extends Navigation
{
    /**
     * Initialize a new Navigation instance.
     */
    public function __construct()
    {
        global $user;

        parent::__construct(_('Chat'), 'chat_online.php');

        $db = DBManager::get();
        $chatServer = ChatServer::GetInstance(get_config('CHAT_SERVER_NAME'));
        $chatServer->caching = true;
        $sms = new messaging();
        $sms->delete_chatinv();
        $chatter = $chatServer->getAllChatUsers();
        $active_chats = count($chatServer->chatDetail);

        $result = $db->query("SELECT COUNT(*) FROM message
                                JOIN message_user USING (message_id)
                                WHERE message_user.user_id = '{$user->id}'
                                  AND snd_rec = 'rec' AND chat_id IS NOT NULL");

        $chatm = $result->fetchColumn();

        if ($chatm == 1) {
            $chat_tip[] = _('Sie haben eine Chateinladung');
        } else if ($chatm > 1) {
            $chat_tip[] = sprintf(_('Sie haben %s Chateinladungen'), $chatm);
        }
        
        if ($chatter == 0) {
            $chat_tip[] = _('Es ist niemand im Chat');
            $chatimage = 'header_chat1';
        } else if ($chatter == 1 && $chatServer->chatUser[$user->id]) {
            $chat_tip[] =_('Nur Sie sind im Chat');
            $chatimage = 'header_chat3';
        } else if ($chatter == 1) {
            $chat_tip[] =_('Es ist eine Person im Chat');
            $chatimage = 'header_chat2';
        } else {
            $chat_tip[] = sprintf(_('Es sind %d Personen im Chat'), $chatter);
            $chatimage = 'header_chat2';
        }

        if ($chatm > 0) {
            $chatimage = 'header_chateinladung';
        }

        if ($active_chats == 1) {
            $chat_tip[] = _('ein aktiver Chatraum');
        } else if ($active_chats > 1) {
            $chat_tip[] = sprintf(_('%d aktive Chaträume'), $active_chats);
        }

        $this->setImage($chatimage, array('title' => join(', ', $chat_tip)));
    }
}
