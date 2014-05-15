<?php
/**
 * StudyCourse.class.php
 * model class for table studiengang
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 * @property string studiengang_id database column
 * @property string id alias column for studiengang_id
 * @property string name database column
 * @property string beschreibung database column
 * @property string mkdate database column
 * @property string chdate database column
 */
class StudyCourse extends SimpleORMap
{

    protected $db_table = 'studiengaenge';

}
