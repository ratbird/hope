<?php
/**
 * access_controlled_webservice.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2011 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'lib/classes/WebserviceAccessRule.class.php';

class AccessControlledService extends Studip_Ws_Service
{
    function before_filter($name, &$args)
    {

        $api_key = current($args);

        if (!WebserviceAccessRule::checkAccess($api_key, $name, $_SERVER['REMOTE_ADDR'])) {
            return new Studip_Ws_Fault('Could not authenticate client.');
        }
    }
}
