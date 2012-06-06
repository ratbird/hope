<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
logout.php - Ausloggen aus Stud.IP und aufräumen
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>, André Noack <andre.noack@gmx.net>,
Cornelis Kater <ckater@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/


require '../lib/bootstrap.php';

unregister_globals();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

require_once 'lib/messaging.inc.php';

if (get_config('CHAT_ENABLE')){
    include_once $RELATIVE_PATH_CHAT."/ChatServer.class.php"; //wird für Nachrichten im chat benötigt
}

//nur wenn wir angemeldet sind sollten wir dies tun!
if ($auth->auth["uid"]!="nobody")
{
    $sms = new messaging();
    //User aus allen Chatraeumen entfernen
    if ($CHAT_ENABLE) {
        $chatServer = ChatServer::GetInstance($CHAT_SERVER_NAME);
        $chatServer->logoutUser($user->id);
    }

    //Wenn Option dafuer gewaehlt, vorliegende Nachrichen loeschen
    if ($my_messaging_settings["delete_messages_after_logout"]) {
        $sms->delete_all_messages();
    }

    //Wenn Option dafuer gewaehlt, alle ungelsesenen Nachrichten als gelesen speichern
    if ($my_messaging_settings["logout_markreaded"]) {
        $sms->set_read_all_messages();
    }

    $logout_user=$user->id;

    // TODO this needs to be generalized or removed
    //erweiterung cas
    if ($auth->auth["auth_plugin"] == "cas"){
        $casauth = StudipAuthAbstract::GetInstance('cas');
        $docaslogout = true;
    }
    //Logout aus dem Sessionmanagement
    $auth->logout();
    $sess->delete();

    page_close();

    //Session changed zuruecksetzen
    $timeout=(time()-(15 * 60));
    $user->set_last_action($timeout);

    //der logout() Aufruf fuer CAS (dadurch wird das Cookie (Ticket) im Browser zerstoert)
    if ($docaslogout){
        $casauth->logout();
    }
} else {
    $sess->delete();
    page_close();
}

header("Location:" . URLHelper::getURL("index.php?logout=true&_language=$_language"));

?>
