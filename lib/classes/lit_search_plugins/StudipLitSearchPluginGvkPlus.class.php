<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginGvk.class.php
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
class StudipLitSearchPluginGvkPlus extends StudipLitSearchPluginZ3950Abstract{
    
    
    function StudipLitSearchPluginGvkPlus(){
        parent::StudipLitSearchPluginZ3950Abstract();
        $this->description = "Gemeinsamer Verbundkatalog inkl. Online-Contents [GVK-PLUS]";
        $this->z_host = "z3950.gbv.de:20010/gvkplus";
        $this->z_options = array('user' => '8030', 'password' => '024206');
        $this->z_syntax = "USMARC";
        $this->convert_umlaute = true;
        $this->z_accession_bib = "12";
        $this->z_accession_re = '/[0-9]{8}[0-9X]{1}/';
        $this->z_profile = array('1016' => _("Alle Wörter [ALL]"),
                     '4' => _("Titel (Stichwort) [TIT]"),
                     '21' => _("Schlagwörter [SLW]"),                                        
                     '5' => _("Serie, Zeitschrift (Phrase) [GTI]"),
                     '1004' => _("Person, Autor [PER]"),                                         
                     '7' => _("ISBN [ISB]"),                                         
                     '8' => _("ISSN [ISN]"),                                         
                     '1007' => _("Nummern (allgemein) [NUM]"),                                       
                     '2' => _("Körperschaftsname (Phrase) [KOS]"),                                       
                     '1005' => _("Körperschaft (Stichwort) [KOR]"),                                      
                     '3' => _("Kongress (Phrase) [KNS]"),                                        
                     '1006' => _("Kongress (Stichwort) [KON]"),                                      
                     '1018' => _("Ort,Verlag (Stichwort) [PUB]"),                                        
                     '20' => _("Basisklassifikation [BKL]"),                                         
                     '12' => _("PICA Prod.-Nr. [PPN]")                                   
                    ); /*  '5' => _("Serie, Zeitschrift (Stichwort) [SER]"),
                           herausgenommen, da #5 standardmäßig auf Phrase gemappt ist und eine Stichwortsuche nicht möglich ist.
                           Eine Stichwortsuche müsste über das Structure-Attribut (#4=2) kenntlich gemacht werden. Dies wird aber 
                           von Stud.IP nicht unterstützt.
                       */
    }
}
?>

