<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginGvk.class.php
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

require_once ("lib/classes/lit_search_plugins/StudipLitSearchPluginZ3950Abstract.class.php");

/**
* Plugin for retrieval using Z39.50
*
*
*
* @access   public
* @author   Andr� Noack <noack@data-quest.de>
* @package
**/
class StudipLitSearchPluginGvk extends StudipLitSearchPluginZ3950Abstract{


    function __construct()
    {
        parent::__construct();
        $this->description = "Gemeinsamer Verbundkatalog - GVK
Der GVK erfasst die Bibliotheksbest�nde der Bundesl�nder: Bremen, Hamburg, Niedersachsen, Sachsen-Anhalt, Schleswig-Holstein, Th�ringen, Mecklenburg-Vorpommern und der Stiftung Preu�ischer Kulturbesitz (Berlin).
Zus�tzlich sind die Zeitschriftennachweise aller subito-Lieferbibliotheken aus Deutschland und �sterreich sowie weiterer deutscher Universit�tsbibliotheken enthalten.";
        $this->z_host = "sru.gbv.de/gvk";
        $this->z_record_encoding = 'utf-8';
        $this->z_syntax = 'USMARC';
        $this->convert_umlaute = true;
        $this->z_accession_bib = "12";
        $this->z_accession_re = '/[0-9]{8}[0-9X]{1}/';
        $this->z_profile = array('1016' => _("Alle W�rter [ALL]"),
                     '4' => _("Titelstichw�rter [TIT]"),
                     '21' => _("Schlagw�rter [SLW]"),
                     '5' => _("Serie, Zeitschrift (Phrase) [GTI]"),
                     '1004 ' => _("Person, Autor [PER]"),
                     '7' => _("ISBN [ISB]"),
                     '8' => _("ISSN [ISN]"),
                     '1007' => _("alle Nummern (ISBN, ISSN, ...) [NUM]"),
                     '2' => _("K�rperschaftsname (Phrase) [KOS]"),
                     '1005' => _("K�rperschaft (Stichwort) [KOR]"),
                     '3' => _("Kongress (Phrase) [KNS]"),
                     '1006' => _("Kongress (Stichwort) [KON]"),
                     '1018' => _("Ort,Verlag (Stichwort) [PUB]"),
                     '20' => _("Basisklassifikation [BKL]"),
                     '12' => _("PICA Prod.-Nr. [PPN]"),
                    ); /*  '5' => _("Serie, Zeitschrift (Stichwort) [SER]"),
                           herausgenommen, da #5 standardm��ig auf Phrase gemappt ist und eine Stichwortsuche nicht m�glich ist.
                           Eine Stichwortsuche m�sste �ber das Structure-Attribut (#4=2) kenntlich gemacht werden. Dies wird aber
                           von Stud.IP nicht unterst�tzt.
                       */
    }
}
?>

