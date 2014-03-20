<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// Universit�t Trier  -  J�rg R�pke  -  <roepke@uni-trier.de>
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginUB_Trier.class.php
//
//
// Copyright (c) 2005 J�rg R�pke  -  <roepke@uni-trier.de>
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

require_once ("lib/classes/lit_search_plugins/StudipLitSearchPluginZ3950Abstract_Aleph.class.php");

class StudipLitSearchPluginUB_Trier extends StudipLitSearchPluginZ3950Abstract_Aleph{

    function StudipLitSearchPluginUB_Trier() {
        parent::StudipLitSearchPluginZ3950Abstract_Aleph();
        $this->description = "Universit�tsbibliothek Trier";
        //$this->z_host = "tcp:ub-a18t.uni-trier.de:9991/TRI01";
        $this->z_host = "tcp:ub-a18.uni-trier.de:9991/TRI01";
        $this->z_options = array('user' => 'z39studip', 'password' => 'ubtstudip');
        $this->z_syntax = "USMARC";
        $this->convert_umlaute = true;
        $this->z_sort = '1=30 > 1=1 < 1=4 <';
        $this->z_profile = array('1016' => _("Basisindex [ALL]"),
                                    '4' => _("Titelstichw�rter [TIT]"),
                                 '1004' => _("Person, Author [PER]"),
                                    '3' => _("K�rperschaft [KOR]"),
                                   '46' => _("Schlagw�rter [SLW]"),
                                    '7' => _("ISBN [ISB]"));
    }
}
?>
