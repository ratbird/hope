<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginUlb.class.php
// 
// 
// Copyright (c) 2003 André Noack <noack@data-quest.de>
// Modified by Cornelius Hempel, 2004
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

require_once ("lib/classes/lit_search_plugins/StudipLitSearchPluginZ3950Abstract.class.php");

/**
* Plugin for retrieval using Z39.50 
*
* 
*
* @access   public  
* @author   André Noack <noack@data-quest.de>
* @package  
**/
class StudipLitSearchPluginUlb extends StudipLitSearchPluginZ3950Abstract{
    
    
    function StudipLitSearchPluginUlb(){
        parent::StudipLitSearchPluginZ3950Abstract();
        $this->description = "Universitäts- und Landesbibliothek (ULB)";
        $this->z_host = "z3950.gbv.de:20010/ulbhalle_opc";
        $this->z_options = array('user' => '999', 'password' => 'abc');
        $this->z_syntax = "UNIMARC";
        $this->convert_umlaute = true;



        $this->z_profile = array('1016' => _("Basisindex [ALL]"), '2' => _("Körperschaftsname [KOS]"),
                                '3' => _("Kongress [KNS]"),'4' => _("Titelstichwörter [TIT]"),
                                '5' => _("Serienstichwörter [SER]"), '7' => _("ISBN [ISB]"),
                                '8' => _("ISSN [ISS]"), '12' => _("PICA Prod.-Nr [PPN]"),
                                '21' => _("alle Klassifikationen [SYS]"), '1004' => _("Person, Author [PER]"),
                                '1005' => _("Körperschaften [KOR]"), '1006' => _("Kongresse [KON]"),
                                '1007' => _("alle Nummern [NUM]"));
    }
}
?>
