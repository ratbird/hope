<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// StudipLitSearchPluginIWFdigiClips.class.php
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
* @access	public	
* @author	André Noack <noack@data-quest.de>
* @package	
**/
class StudipLitSearchPluginIWFdigiClips extends StudipLitSearchPluginGvk {
	
	
	function StudipLitSearchPluginIWFdigiClips(){
		parent::StudipLitSearchPluginGvk();
		$this->description = "IWF - Wissen und Medien";
		$this->z_host = "z3950.gbv.de:20010/iwf";
		$this->z_profile = array('1016' => _("Alle Wörter [ALL]"),
								'1' => _("Personennamen [PRS]"),
								'4' => _("Titelstichwörter [TIT]"),
								'5' => _("Serienstichwörter [SER]"),
								'12' => _("PICA Prod.-Nr. [PPN]"),
								'21' => _("Schlagwörter [SLW]"),
								'59' => _("Erscheinungsort [PLC]"),
								'1004' => _("Person, Author [PER]"),
								'1007' => _("alle Nummern [NUM]"),
								'1018' => _("Ort,Verlag (Stichwort) [PUB]"),
								'1031' => _("Materialart [MAT]"),
								'8621' => _("Flash Video (bei Suchbegriff 'flash' eingeben)")
								);
		$this->mapping['USMARC'][500] = array('field' => 'dc_relation', 'callback' => 'simpleMap', 'cb_args' => '$a' . chr(10));
		$this->mapping['USMARC'][520] = array('field' => 'dc_description', 'callback' => 'simpleMap', 'cb_args' => '$a' . chr(10));
		$this->mapping['USMARC'][773] = array('field' => 'dc_relation', 'callback' => 'simpleMap', 'cb_args' => '$t, $g, $d');

	}
}
?>
