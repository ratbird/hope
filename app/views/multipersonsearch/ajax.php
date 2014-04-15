<?php

$output = array();

foreach ($result as $user) {
    $output[] = array('user_id' => $user->id, 'avatar' => Avatar::getAvatar($user->id)->getURL(Avatar::SMALL), 'text' => $user->nachname . ", " . $user->vorname . " -- " . htmlReady($user->perms) . " (".htmlReady($user->username).")", 'member' => in_array($user->id, $alreadyMember));
}
print json_encode(studip_utf8encode($output));
