<?php

$output = array();

if (!empty($result)) {
    foreach ($result as $user) {
        $output[] = array('user_name' => $user->username, 'avatar' => Avatar::getAvatar($user->id)->getURL(Avatar::MEDIUM), 'value' => $user->nachname . ", " . $user->vorname, 'desc' => htmlReady($user->perms) . " (". htmlReady($user->username) .")");
    }
}
print json_encode(studip_utf8encode($output));
