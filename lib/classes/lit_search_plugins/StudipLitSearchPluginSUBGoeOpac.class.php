<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginRkgoe.class.php
//
//
// Copyright (c) 2003 Andr� Noack <noack@data-quest.de>
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
* @author   Andr� Noack <noack@data-quest.de>
* @package
**/
class StudipLitSearchPluginSUBGoeOpac extends StudipLitSearchPluginGvk
{


    function __construct()
    {
        parent::__construct();
        $this->description = "G�ttinger Universit�tskatalog (GUK)
Enthalten sind die Best�nde folgender Einrichtungen:
Nieders�chsische Staats- und Universit�tsbibliothek G�ttingen (SUB)
(Monografien 1946 - 1976 sowie Dissertationen vor 1909 und 1937 - 1977 noch unvollst�ndig)
Bereichsbibliotheken (vollst�ndig), Instituts- und Seminarbibliotheken (im Aufbau)";
        $this->z_host = "sru.gbv.de/opac-de-7";
        $this->z_profile = array('1016' => _("Basisindex [ALL]"), '4' => _("Titelstichw�rter [TIT]"),
                                '5' => _("Serienstichw�rter [SER]"), '21' => _("alle Klassifikationen [SYS]"),
                                '1004' => _("Person, Author [PER]"), '1005' => _("K�rperschaften [KOR]"),
                                '1006' => _("Kongresse [KON]"), '1007' => _("alle Nummern [NUM]"),
                                '5040' => _("Schlagw�rter [SLW]"),'8062' => _("alle Titelanf�nge [TAF]"),
                                '8580' => _("Verlagsort, Verlag [PUB]"), '54' => _("Signatur [SGN]"));
    }
}
?>
