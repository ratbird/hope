<?php
/**
 * AuthUserMd5.class.php
 * model class for table auth_user_md5
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

class AuthUserMd5 extends SimpleORMap
{

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'auth_user_md5';
        $this->default_values['validation_key'] = '';
        parent::__construct($id);
    }
}