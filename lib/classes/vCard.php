<?php

/**
 * vCard.php - HelperClass to ceate vCard string of a user object
 *
 * Use the export function to create a vCard string of a single user
 * element or an array of userelements
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @copyright   2014 Stud.IP Core-Group
 */
class vCard {

    public static function export($users) {

        // Non array fallback
        if (!(is_array($users) || $users instanceof SimpleCollection)) {
            $users = array($users);
        }

        foreach ($users as $user) {
            $export .= self::exportUser($user);
        }

        return $export;
    }

    private static function exportUser($user) {

        // vCard exportheader
        $vCard['BEGIN'] = 'VCARD';
        $vCard['VERSION'] = '3.0';
        $vCard['PRODID'] = 'Stud.IP//' . $GLOBALS['UNI_NAME_CLEAN'] . '//DE';
        $vCard['REV'] = date('Y-m-d  H:i:s');
        $vCard['TZ'] = date('O');

        // User specific data
        //Fullname
        $vCard['FN'] = studip_utf8encode($user->getFullname());

        //Name
        $vCard['N'][] = studip_utf8encode($user->Nachname);
        $vCard['N'][] = studip_utf8encode($user->Vorname);
        $vCard['N'][] = studip_utf8encode($user->info->title_rear);
        $vCard['N'][] = studip_utf8encode($user->info->title_front);

        // Adress
        $vCard['ADR;TYPE=HOME'] = studip_utf8encode($user->info->privadr);

        // Tel
        $vCard['TEL;TYPE=HOME'] = studip_utf8encode($user->info->privatnr);
        $vCard['TEL;TYPE=CELL'] = studip_utf8encode($user->info->privatcell);

        // Email
        $vCard['EMAIL'] = studip_utf8encode($user->email);

        // Photo
        $vCard['PHOTO;JPEG;ENCODING=BASE64'] = base64_encode(file_get_contents(Avatar::getAvatar($user->id)->getFilename(Avatar::NORMAL)));

        // vCard end
        $vCard['END'] = 'VCARD';

        // Produce string
        foreach ($vCard as $index => $value) {
            $exportString .= $value ? $index . ':' . (is_array($value) ? join(';', $value) : $value) . "\r\n" : "";
        }

        return $exportString;
    }

}
