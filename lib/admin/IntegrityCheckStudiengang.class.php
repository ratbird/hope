<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// IntegrityCheckStudiengang.class.php
// Integrity checks for the Stud.IP database
//
// Copyright (c) 2002 Andr� Noack <noack@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once $GLOBALS['RELATIVE_PATH_ADMIN_MODULES']."/IntegrityCheckAbstract.class.php";

/**
* integrity check plugin for 'Studiengang'
*
*
*
* @access   public
* @author   Andr� Noack <andre.noack@gmx.net>
* @package  Admin
* @see      IntegrityCheckAbstract
*/
class IntegrityCheckStudiengang extends IntegrityCheckAbstract{

    /**
    * constructor
    *
    * calls the base class constructor and initializes checklist array
    * @access   public
    */
    function IntegrityCheckStudiengang(){
        $baseclass = strtolower(get_parent_class($this));
        //parent::$baseclass(); //calling the baseclass constructor
        $this->$baseclass(); //calling the baseclass constructor PHP < 4.1.0
        $this->master_table = "studiengaenge";
        $this->checklist[] = array('detail_table' => 'admission_seminar_user',
                                    'query' => 'view:STUD_ADMISSONUSER:');
        $this->checklist[] = array('detail_table' => 'user_studiengang',
                                    'query' => 'view:STUD_USER:');
    }

}
?>
