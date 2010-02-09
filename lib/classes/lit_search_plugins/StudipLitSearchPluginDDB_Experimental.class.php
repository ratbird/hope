<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
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

/* insert this in the local.inc :
//EXPERIMENTAL!!
//Die Deutsche Bibliothek (http://www.ddb.de) (Iltis-Datenbank - http://z3950gw.dbf.ddb.de/ )
// Beim kostenlosen Zugang zur Deutschen Bibliothek werden die Daten nur im SUTRS-Format bereitgestellt,
// welches kein ordentliches Parsen zuläßt. Kann kein Titel (dc_title) erkannt werden, werden
// als Titel die ersten 40 Zeichen des Suchergebnisses eingetragen.
// Zusätzlich wird immer das gesammte Suchergebis im Feld "Inhaltliche Beschreibung" (dc_description)
// abgelegt, so das der User notfalls die Datenzuordnung selbst vornehmen kann.
$_lit_search_plugins[] = array('name' => 'DDB_Experimental', 'link' => '');
*/

require_once ("lib/classes/lit_search_plugins/StudipLitSearchPluginZ3950Abstract.class.php");

/**
* Plugin for retrieval using Z39.50
*
*
*
* @access	public
* @author	Jens Schmelzer
* @package
**/
class StudipLitSearchPluginDDB_Experimental extends StudipLitSearchPluginZ3950Abstract{

//SUTRS-Format is EXPERIMENTAL!!!
	function StudipLitSearchPluginDDB_Experimental(){
		parent::StudipLitSearchPluginZ3950Abstract();
		$this->description = 'EXPERIMENTAL: Die Deutsche Bibliothek <a href="http://www.ddb.de" target="_ddb">http://www.ddb.de</a> (Gast-Zugang / Iltis-Datenbank <a href="http://z3950gw.dbf.ddb.de" target="_ddb">http://z3950gw.dbf.ddb.de</a>)';
		$this->z_host = 'z3950.dbf.ddb.de:210/iltis';
		$this->z_syntax = 'SUTRS';
		$this->z_options = array('user' => 'gast', 'password' => 'gast');
		$this->convert_umlaute = true;
		//$this->z_accession_bib = "12";
		//$this->z_accession_re = '/[0-9]{8}[0-9X]{1}/';
		$this->z_profile = array(	'all' => _("alle Wortfelder"),
						'allpers' => _("alle Personenfelder"),
						'allnum' => _("alle Nummernfelder"),
						'1' => _("Personennamen"),
						'2' => _("K&ouml;rperschaftsname"),
						'4' => _("Titelstichwort"),
						'6' => _("Einheitssachtitel"),
						'7' => _("ISBN"),
						'8' => _("ISSN"),
						'12' => _("Lokale Identifikationsnummer"),
						'20' => _("Sachgruppe"),
						'21' => _("Schlagwort"),
						'31' => _("Erscheinungsjahr"),
						'46' => _("Schlagwort RSWK"),
						'48' => _("Nationalbibliographie-Nummer"),
						'52' => _("Lokale Identifikationsnummer"),
						'53' => _("Signatur"),
						'54' => _("Sprachencode"),
						'55' => _("L&auml;ndercode"),
						'1004' => _("Person, Author"),
						'1005' => _("K&ouml;rperschaften"),
						'1007' => _("Sonstige Nummer"),
						'1009' => _("Personenschlagwort"),
						'1018' => _("Verlag"),
						'1080' => _("Schlagwort vor 1986")
						);
	}

	function getZRecord($rn){
		$record = yaz_record($this->z_id,$rn,"string");
//echo "<b>Treffer $rn:</b>\n<pre>", htmlentities($record), '</pre><hr>';
		$plugin_mapping = $this->mapping[$this->z_syntax];
		if ($record){
			$cat_element = new StudipLitCatElement();
			$cat_element->setValue('user_id', $GLOBALS['auth']->auth['uid']);
			$cat_element->setValue('catalog_id', $this->sess_var_name . '__' . $rn );
			$cat_element->setValue('lit_plugin', $this->getPluginName());
			// iso 5426 !? konvertierung
			$tr = array(	chr(0xfb) => chr(0xdf), // ß
					chr(0xf9) => chr(0xf8), // ø
					chr(0xe8) => 'L', // ?
					chr(0xcf) => '', // ?
					chr(0xc9).'A' => chr(0xc4), // Ä
					chr(0xc9).'O' => chr(0xd6), // Ö
					chr(0xc9).'U' => chr(0xdc), // Ü
					chr(0xc9).'a' => chr(0xe4), // ä
					chr(0xc9).'o' => chr(0xf6), // ö
					chr(0xc9).'u' => chr(0xfc), // ü
					chr(0xc4).'n' => chr(0xf1), // ñ
					chr(0xc2).'e' => chr(0xe9), // é
					chr(0xc2).'o' => chr(0xf3), // ó
					chr(0xc2).'a' => chr(0xe1) // á
					);
			$record = strtr($record, $tr);
			$cat_element->setValue('dc_description', $record);
			$line = explode("\n", $record);
			$dc_identifier = $dc_creator = '';
			if (preg_match('#(isbn\s\S+)\s#im',$record, $m2)){
				$dc_identifier = $m2[1];
			}
			if (preg_match('#(issn\s\S+)\s#im',$record, $m2)){
				$dc_identifier .= (($dc_identifier)? ' ':'') . $m2[1];
			}
			if ($dc_identifier) {
				$cat_element->setValue('dc_identifier', $dc_identifier);
			}
			if ($line[0] && preg_match('#^([^/]+):$#',$line[0], $m0)) {
				$dc_creator = $m0[1];
				$cat_element->setValue('dc_creator', $m0[1]);
				$record = substr($record, strlen($m0[1])+1);
			}
			if (preg_match('#^([^/]+)/#i',$record, $m1)) {
				$cat_element->setValue('dc_title', str_replace("\n",' ',$m1[1]));
				$record = substr($record, strlen($m1[1]));
				$reg = ($dc_creator)? '#^ */[^;]+;(.+?)\s-\s#is':'#^ */(.+?)\s-\s#is';
				if(preg_match($reg,$record, $m4)) {
					$cat_element->setValue('dc_contributor', str_replace("\n",' ',$m4[1]));
				}
			} elseif (preg_match('#^([^-]+) -#i',$record, $m7)) {
				$cat_element->setValue('dc_title', str_replace("\n",' ',$m7[1]));
				$record = substr($record, strlen($m7[1]));
				$reg = ($dc_creator)? '#^ */?[^;]+;(.+?)\s-\s#is':'#^ */?(.+?)\s-\s#is';
				if(preg_match($reg,$record, $m4)) {
					$cat_element->setValue('dc_contributor', str_replace("\n",' ',$m4[1]));
				}
			} else {
				$punkte = (strlen($record) > 40)? '...':'';
				$cat_element->setValue('dc_title', substr(str_replace("\n",' ',$record),0,40). $punkte);
			}
			if (preg_match('#- ([^:-]+)\:([^,]+),\s*\[?(\d+)#im',$record, $m3)) {
				$cat_element->setValue('dc_date', $m3[3] . '-01-01');
				$cat_element->setValue('dc_publisher', str_replace("\n",' ',$m3[1] . ': '. $m3[2]));
			}
			if (preg_match('#^SW:([^:]+)((\S\S\:)|$)#im',$record, $m5)) {
				$treffer = str_replace("\n",' ',$m5[1]);
				if (preg_match_all ( '#\w\|([^;@\|]+)#', $treffer, $m6, PREG_PATTERN_ORDER)){
					$cat_element->setValue('dc_subject', join('; ',$m6[1]));
				}
			}

			$this->search_result[$rn] = $cat_element->getValues();
			return 1;
		} else {
			$this->addError("error",sprintf(_("Datensatz Nummer %s konnte nicht abgerufen werden."), $rn));
			return 0;
		}
	}

	function parseSearchValues(){
		$rpn = false;
		$search_values = $this->search_values;
		if (is_array($search_values)){
			$rpn_front = '';
			$rpn_end = '';
			for ($i = 0 ; $i < count($search_values); ++$i){
				$term = $search_values[$i]['search_term'];
				if (strlen($term)){
					if ($this->convert_umlaute){
						$term = $this->ConvertUmlaute($term);
					}
					switch ($search_values[$i]['search_truncate']){
						case "left":
							$truncate = "2";
							break;
						case "right":
							$truncate = "1";
							break;
						case "none":
						default:
							$truncate = "100";
							break;
					}
					if (substr($search_values[$i]['search_field'],0,3) == 'all' ) {
						$rpn_front_c = 0;
						switch ($search_values[$i]['search_field']) {
							case 'all':
								$a = array(4, 6, 20, 21, 46, 1018, 1080);
								break;
							case 'allpers':
								$a = array(1, 2, 1004, 1005, 1009);
								break;
							case 'allnum':
							default:
								$a = array(7, 8, 12, 48, 52, 53, 54, 55, 1007);
						}
						foreach($a as $v){
							$rpn_end .= ' @attr 1=' . $v . ' @attr 5='.$truncate . ' "'. $term . '" ';
							$rpn_front_c++;
						}
						if ($i == 0 && $rpn_front_c > 0) $rpn_front_c--;
						$rpn_front = str_repeat(' @or ', $rpn_front_c) . $rpn_front;
					} else {
						$rpn_end .= ' @attr 1=' . $search_values[$i]['search_field'] . ' ';

						$rpn_end .= " @attr 5=$truncate ";
						$rpn_end .= ' "' . $term . '" ';
						if ($i > 0){
							switch ($search_values[$i]['search_operator']){
								case "AND":
								$rpn_front = " @and " . $rpn_front;
								break;
								case "OR":
								$rpn_front = " @or " . $rpn_front;
								break;
								case "NOT":
								$rpn_front = " @not " . $rpn_front;
								break;
							}
						}
					}
				} else if ($i == 0) {
			$this->addError("error", _("Der erste Suchbegriff fehlt."));
			return false;
				}
			}
		}
		$rpn = $rpn_front . $rpn_end;
//echo '<b>Anfrage:</b><br>', $rpn, "<br><hr>\n";
		return (strlen($rpn)) ? $rpn : false;
	}
}
?>

