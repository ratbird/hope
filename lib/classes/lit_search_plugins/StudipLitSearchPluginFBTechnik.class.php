<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginUlb.class.php
//
//
// Copyright (c) 2003 Andr� Noack <noack@data-quest.de>
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

require_once 'StudipLitSearchPluginGvk.class.php';

/**
* Plugin for retrieval using Z39.50
*
*
*
* @access   public
* @author   Andr� Noack <noack@data-quest.de>
* @package
**/
class StudipLitSearchPluginFBTechnik extends StudipLitSearchPluginGvk
{


    function __construct()
    {
        parent::__construct();
        $this->description = "Gesamtbestand der Hochschulbibliothek der Hochschule Merseburg (FH)";
        $this->z_host = "sru.gbv.de/opac-de-542";
        $this->z_profile = array('1016' => _("Basisindex [ALL]"), '2' => _("K�rperschaftsname [KOS]"),
                                '3' => _("Kongress [KNS]"),'4' => _("Titelstichw�rter [TIT]"),
                                '5' => _("Serienstichw�rter [SER]"), '12' => _("PICA Prod.-Nr [PPN]"),
                                '21' => _("alle Klassifikationen [SYS]"), '1004' => _("Person, Author [PER]"),
                                '1005' => _("K�rperschaften [KOR]"), '1006' => _("Kongresse [KON]"),
                                '1007' => _("alle Nummern [NUM]"));
    }
}
?>
