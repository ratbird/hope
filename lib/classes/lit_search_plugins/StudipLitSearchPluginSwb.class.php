<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginSwb.class.php
// 
// 
// Copyright (c) 2008 Andr� Noack <noack@data-quest.de>
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
class StudipLitSearchPluginSwb extends StudipLitSearchPluginZ3950Abstract{
    
    
    function StudipLitSearchPluginSwb(){
        parent::StudipLitSearchPluginZ3950Abstract();
        $this->description = "SWB-Online-Katalog";
        $this->z_host = "193.197.31.10:210/swblite";
        //$this->z_options = array('user' => '999', 'password' => 'abc');
        $this->z_syntax = "UNIMARC";
        $this->convert_umlaute = true;
        $this->z_accession_bib = "12";
        $this->z_accession_re = '/[0-9]{8}[0-9X]{1}/';
        $this->z_profile = array('1016' => _("Alle W�rter [ALL]"),
                     '4' => _("Titelstichw�rter [TIT]"),
                     '1' => _("Person [PST]"),                                       
                     '1004 ' => _("Person, Autor [PER]"),                                        
                     '7' => _("ISBN [ISB]"),                                         
                     '8' => _("ISSN [ISN]"),                                         
                     '1007' => _("alle Nummern (ISBN, ISSN, ...) [NUM]"),                                        
                     '1018' => _("Ort,Verlag (Stichwort) [PUB]"),                                        
                     '12' => _("PICA Prod.-Nr. [PPN]"),                                      
                    ); /*  '5' => _("Serie, Zeitschrift (Stichwort) [SER]"),
                           herausgenommen, da #5 standardm��ig auf Phrase gemappt ist und eine Stichwortsuche nicht m�glich ist.
                           Eine Stichwortsuche m�sste �ber das Structure-Attribut (#4=2) kenntlich gemacht werden. Dies wird aber 
                           von Stud.IP nicht unterst�tzt.
                       */
    }
}
?>