<?
/**
 * InvalidSecurityTokenException.php
 *
 * This exception is thrown when a request does not verify its authenticity.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      mlunzena@uos.de
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class InvalidSecurityTokenException extends AccessDeniedException
{
    /**
     * @param string this parameter is ignored but required by PHP
     */
    function __construct($message = NULL) {
        parent::__construct(_("Ung�ltiges oder fehlendes Sicherheits-Token."));
    }
}
