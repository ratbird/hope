<?
# Lifter002: TODO
# Lifter005: TEST
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* several functions used for the systeminternal messages
*
* @author               Nils K. Windisch <studip@nkwindisch.de>
* @access               public
* @modulegroup  Messaging
* @module               sms_functions.inc.php
* @package          Stud.IP Core
*/
/*
sms_functions.inc.php -
Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Nils K. Windisch <info@nkwindisch.de>

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

function get_message_attachments($message_id, $provisional = false)
{
    $db = DBManager::get();
    if (! $provisional) {
        $st = $db->prepare("SELECT dokumente.* FROM message INNER JOIN dokumente ON message_id=range_id WHERE message_id=? ORDER BY dokumente.chdate");
    } else {
        $st = $db->prepare("SELECT * FROM dokumente WHERE range_id='provisional' AND description=? ORDER BY chdate");
    }
    return $st->execute(array(
        $message_id
    )) ? $st->fetchAll(PDO::FETCH_ASSOC) : array();
}
