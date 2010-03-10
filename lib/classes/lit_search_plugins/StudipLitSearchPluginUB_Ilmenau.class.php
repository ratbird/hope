<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginUB_Ilmenau.class.php
//
// 
// Copyright (c) 2003 André Noack <noack@data-quest.de>
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

require_once ("lib/classes/lit_search_plugins/StudipLitSearchPluginGvk.class.php");

/**
* Plugin for retrieval using Z39.50 
*
* 
*
* @access   public  
* @author   André Noack <noack@data-quest.de>
* @package
**/
class StudipLitSearchPluginUB_Ilmenau extends StudipLitSearchPluginGvk{

    function StudipLitSearchPluginUB_Ilmenau(){
        parent::StudipLitSearchPluginGvk();
        $this->description = 'Universit&auml;tsbibliothek Ilmenau';
        $this->z_host = "z3950.gbv.de:20010/ubilm_opc";
        $this->z_profile = array('1016' => _("Basisindex [ALL]"),
                    '4' => _("Titelstichw&ouml;rter [TIT]"),
                    '5' => _("Serienstichw&ouml;rter [SER]"),
                    '21' => _("alle Klassifikationen [SYS]"),
                    '54' => _("Signatur [SGN]"),
                    '1004' => _("Person, Author [PER]"),
                    '1005' => _("K&ouml;rperschaften [KOR]"),
                    '1006' => _("Kongresse [KON]"),
                    '1007' => _("alle Nummern [NUM]"),
                    '5040' => _("Schlagw&ouml;rter [SLW]"),
                    '8062' => _("alle Titelanf&auml;nge [TAF]"),
                    '8580' => _("Verlagsort, Verlag [PUB]")
                    );
    }
}
?>
