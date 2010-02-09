<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
smiley.class.php - Smiley-Verwaltung von Stud.IP.
Copyright (C) 2004 Tobias Thelen <tthelen@uos.de>
Copyright (C) 2004 Jens Schmelzer <jens.schmelzer@fh-jena.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

require_once('config.inc.php');
require_once('lib/msg.inc.php'); //Funktionen fuer Nachrichtenmeldungen
require_once('lib/visual.inc.php');
require_once('lib/classes/Table.class.php');
require_once('lib/classes/ZebraTable.class.php');


class smiley {

	var $SMILEY_COUNTER;
	var $error;
	var $short_r;
	var $msg;
	var $fc;
	var $where;
	var $db;
	var $smiley_tab;
	var $my_smiley;
	var $user_id;

	function smiley($admin = false){
		global $auth;
		$this->msg = '';
		$this->error = false;
		$this->where = '';
		$this->smiley_tab = array();
		$this->my_smiley = array();
		$this->user_id = $auth->auth['uid'];
		if (!$GLOBALS['SMILEYADMIN_ENABLE']) {
			$this->msg .=  '§error§' . _("Smiley-Modul abgeschaltet.");
			$this->error = true;
		} else {
			$this->SMILEY_COUNTER = (isset($GLOBALS['SMILEY_COUNTER']))? $GLOBALS['SMILEY_COUNTER']:false;
			$this->db = new DB_Seminar;

			$dbsmile = &$this->db;
			// smiley-table empty ?
			$sql_test = 'SELECT Count(*) AS c FROM smiley;';
			$dbsmile->query($sql_test);
			$dbsmile->next_record();
			$sc = ($dbsmile->f('c') == 0)? true : false;
			if ($admin || $sc) { // init smiley-short-notation
				$sa = $GLOBALS['SMILE_SHORT'];
				$this->short_r = array_flip($sa);
			}
			if ($sc){ // fill table
				// read smiley-gif's from harddisc
				$this->update_smiley_table();
				// test again!!
				$dbsmile->query($sql_test);
				$dbsmile->next_record();
				if ($dbsmile->f('c') > 0){
					// search smileys in studip
					$this->search_smileys();
				} else {
					$this->msg .= 'error§'. _("Fehler: Keine Smileys auf dem Server gefunden."). '§';
					$this->error = true;
				}
			}
			if (isset($_REQUEST['fc'])) {
				$this->fc = $_REQUEST['fc'];
			} else {
				$dbsmile->query('SELECT LEFT(smiley_name, 1) AS fc FROM smiley ORDER BY smiley_name LIMIT 0, 1;');
				$this->fc =  ($dbsmile->next_record())? $dbsmile->f('fc'):'a';
			}
		}
	}

	function fill_smiley_array($search){
		if ($this->error) return false;
		$dbsmile = &$this->db;

		$dbsmile->query('SELECT * FROM smiley ORDER BY smiley_name');
		$smiley_tab = array();
		$del = ($search)? 0:1;
		while($dbsmile->next_record()){
			$this->smiley_tab[$dbsmile->f('smiley_name')] =
				array(	'id'=>$dbsmile->f('smiley_id'),
					'width'=>$dbsmile->f('smiley_width'),
					'height'=>$dbsmile->f('smiley_height'),
					'short'=>$dbsmile->f('short_name'),
					'count'=>$dbsmile->f('smiley_counter'),
					'scount'=>$dbsmile->f('short_counter'),
					'fcount'=>$dbsmile->f('fav_counter'),
					'update'=>0,
					'delete'=>$del );
			if ($search){
				$this->smiley_tab[$dbsmile->f('smiley_name')]['new_count'] = 0;
				$this->smiley_tab[$dbsmile->f('smiley_name')]['new_scount'] = 0;
			}
		}
	}

	function search_smileys(){
		if ($this->error) return false;
		global $DB_STUDIP_DATABASE, $SMILE_SHORT;

		$this->fill_smiley_array(1);
		$smiley_tab = &$this->smiley_tab;
		$smile_error = array();

		//array( array (Tabelle , Feld), array (Tabelle , Feld), ... )
		$tab = array(
			array('guestbook', 'content'),
			array('datafields_entries','content'),
			array('kategorien', 'content'),
			array('message', 'message'),
			array('news', 'body'),
			array('scm', 'content'),
			array('user_info', 'hobby'),
			array('user_info', 'lebenslauf'),
			array('user_info', 'publi'),
			array('user_info', 'schwerp'),
			array('px_topics', 'description'),
			array('wiki', 'body')
			);

		$dbsmile= &$this->db;

		// search in all tables
		for($i = 0; $i < count($tab); $i++) {
			$sqltxt = "SELECT " . $tab[$i][1]. " AS txt FROM " . $tab[$i][0];
			if ($tab[$i][0] == 'wiki') {  // only the actual wiki page ...
				$sqltxt = "SELECT MAX(CONCAT( LPAD(version, 5, '0'),' ', " . $tab[$i][1] . ')) AS txt FROM  ' . $tab[$i][0] . ' GROUP BY range_id, keyword';
			}
			$dbsmile->query($sqltxt);
			// and all entrys
			while($dbsmile->next_record()){
				$txt = $dbsmile->f('txt');
				// all smileys
				if (preg_match_all ("/(\>|^|\s):([_a-zA-Z][_a-z0-9A-Z-]*):(?=$|\<|\s)/", $txt, $matches)) {
					for ($k = 0; $k < count($matches[2]); $k++) {
						if (isset($smiley_tab[$matches[2][$k]])) {
							$smiley_tab[$matches[2][$k]]['new_count'] += 1;
						} else {
							if (isset($smiley_error[$matches[2][$k]]))
								$smiley_error[$matches[2][$k]]['count'] += 1;
							else	$smiley_error[$matches[2][$k]]['count'] = 1;
						}
					}
				}
				// and now the short-notation
				$short_smile = &$GLOBALS['SMILE_SHORT'];
				reset($short_smile);
				while (list($key,$value) = each($short_smile)) {
					if ($anz = preg_match_all ("/(\>|^|\s)" . preg_quote($key) . "(?=$|\<|\s)/", $txt, $matches)) {
						if (isset( $smiley_tab[$value])) {
							$smiley_tab[$value]['new_scount'] += $anz;
						}
					}
				}
			}
		}
		$anderungen = 0;
		foreach($smiley_tab as $smiley_name => $smile ) {
			if($smile['count'] != $smile['new_count'] || $smile['scount'] != $smile['new_scount'] ) {
				$sql_smile = 'UPDATE smiley SET smiley_counter='.$smile['new_count'].', short_counter='.$smile['new_scount'].', chdate=UNIX_TIMESTAMP() WHERE smiley_id='.$smile['id'];
				$dbsmile->query($sql_smile);
				$aenderungen++;
			}
		}
		$this->msg .= 'msg§'. sprintf(_("%d Zählerstände aktualisiert"), $aenderungen). '§';
		return true;
	}

	function update_smiley_table(){
		if ($this->error) return false;
		$dbsmile = &$this->db;
		$this->fill_smiley_array(0);
		$smiley_tab = &$this->smiley_tab;

		$path = realpath($GLOBALS['DYNAMIC_CONTENT_PATH'] . '/smile');
		$folder = dir($path);

		while ($entry = $folder->read()){
			$dot = strrpos($entry,'.');
			$l = strlen($entry) - $dot;
			$name = substr($entry,0,$dot);
			$ext = strtolower(substr($entry,$dot+1,$l));
			if ($dot AND !is_dir($path.'/'.$entry) AND $ext=='gif'){
				$img = getImageSize($path.'/'.$entry);
				if ($img[2] != 1) continue;
				$short = (isset($this->short_r[$name]))? $this->short_r[$name]:'';
				if (array_key_exists($name, $smiley_tab)) {
					$smiley_tab[$name]['delete'] = 0;
					if (($smiley_tab[$name]['width'] != $img[0]) || ($smiley_tab[$name]['height'] != $img[1]) || ($smiley_tab[$name]['short'] != $short)){
						$smiley_tab[$name]['update'] = 1;
						$smiley_tab[$name]['width'] = $img[0];
						$smiley_tab[$name]['height'] = $img[1];
						$smiley_tab[$name]['short'] = $short;
					}
				} else { // hm, new smiley at filesystem ...
					$smiley_tab[$name] = array(	'id'=>0,
									'width'=>$img[0],
									'height'=>$img[1],
									'short'=>"$short",
									'count'=>0,
									'scount'=>0,
									'fcount'=>0,
									'update'=>0,
									'delete'=>0 );
				}
			}
		}
		$folder->close();

		reset($smiley_tab);
		$sql_smile_insert = '';
		$sql_smile_del = '';
		$c_update = $c_insert = $c_delete = 0;
		foreach($smiley_tab as $smiley_name => $smile ) {
			if(!$smile['id']) { // new smiley
				if ($sql_smile_insert != '') $sql_smile_insert .= ',';
				$sql_smile_insert .= "('".$smiley_name."', ".$smile['width'].', '. $smile['height'].', "'. $smile['short'].'", '.$smile['count'].', '.$smile['scount'].', '.$smile['fcount'].', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() )';
				$c_insert++;
			} elseif($smile['update'] == 1) { // new data for smiley
				$sql_smile = "UPDATE smiley SET short_name='".$smile['short']."', smiley_width=".$smile['width'].', smiley_height='.$smile['height'].', chdate=UNIX_TIMESTAMP() WHERE smiley_id='.$smile['id'];
				$dbsmile->query($sql_smile);
				$c_update++;
			} elseif($smile['delete'] == 1) { // smiley is erased...
				$sql_smile_del .= (($sql_smile_del == '')? '':',') . $smile['id'];
				$c_delete++;
			}
		}
		if ($sql_smile_insert != '') {
			$sql_smile_insert = 'INSERT INTO smiley (smiley_name, smiley_width, smiley_height, short_name, smiley_counter, short_counter, fav_counter, mkdate, chdate) VALUES' . $sql_smile_insert;
			$dbsmile->query($sql_smile_insert);
		}
		if ($sql_smile_del != '') {
			$dbsmile->query('DELETE FROM smiley WHERE smiley_id IN (' . $sql_smile_del .')');
		}
		$this->msg .= 'msg§'. sprintf(_("%d Smileys aktualisiert"), $c_update). ' / '. sprintf(_("%d Smileys eingefügt"), $c_insert). ' / '. sprintf(_("%d Smileys gelöscht"), $c_delete). '§';
	}


	function imaging() {
		if ($this->error) return false;
		if (!isset($GLOBALS['imgfile_name']) || $GLOBALS['imgfile_name'] == '') { //keine Datei ausgewählt!
			$this->msg .= 'error§' . _("Sie haben keine Datei zum Hochladen ausgewählt!"). '§';
			return false;
		} else {
			$img_name = $GLOBALS['imgfile_name'];
		}
		//Dateiendung bestimmen
		$ext = '';
		$dot = strrpos($img_name,'.');
		if ($dot) {
			$l = strlen($img_name) - $dot;
			$smiley_name = substr($img_name,0,$dot);
			$ext = strtolower(substr($img_name,$dot+1,$l));
		}
		//passende Endung ?
		if ($ext != 'gif' ) {
			$this->msg .= 'error§' . sprintf(_("Der Dateityp der Bilddatei ist falsch (%s).<br>Es ist nur die Dateiendung .gif erlaubt!"), $ext). '§';
			$this->error = true;
			return false;
		}

		//na dann kopieren wir mal...
		$newfile = $GLOBALS['DYNAMIC_CONTENT_PATH'] . '/smile/' . $img_name;

		$smiley_id = 0;
		$this->db->query("SELECT smiley_id FROM smiley WHERE smiley_name LIKE '".$smiley_name."'");
		if ($this->db->next_record()){
			$smiley_id = $this->db->f('smiley_id');
			if (!isset($_POST['replace'])){
				$this->msg .= 'error§' . sprintf(_("Es ist bereits eine Bildatei mit dem Namen \"%s\" vorhanden."),$img_name). '§';
				return false;
			}
		}
		if(!move_uploaded_file($GLOBALS['imgfile'],$newfile)) {
			$this->msg .= 'error§' . _("Es ist ein Fehler beim Kopieren der Datei aufgetreten. Das Bild wurde nicht hochgeladen!"). '§';
			$this->error = true;
			return false;
		} elseif($smiley_id) {
			$this->msg .= 'msg§' .sprintf( _("Die Bilddatei \"%s\" wurde erfolgreich ersetzt."), $img_name). '§';
			$img = getImageSize($newfile);
			$sql_smile = "UPDATE smiley SET smiley_name='".$smiley_name."', smiley_width=".$img[0].' , smiley_height='.$img[1].', chdate=UNIX_TIMESTAMP() WHERE smiley_id = '.$smiley_id;
		} else {
			$this->msg .= 'msg§' .sprintf( _("Die Bilddatei \"%s\" wurde erfolgreich hochgeladen."), $img_name). '§';
			$img = getImageSize($newfile);
			$sql_smile = 'INSERT INTO smiley (smiley_name, smiley_width, smiley_height, short_name, smiley_counter, short_counter, fav_counter, mkdate, chdate) VALUES ';
			$sql_smile .= "('".$smiley_name."', ".$img[0].', '. $img[1].', "", 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() )';
		}
		chmod($newfile, 0666 & ~umask());       // set permissions for uploaded file
		$this->db->query($sql_smile);
		$this->fc = $smiley_name{0};
		return true;
	}

	function show_upload_form() {
		if ($this->error) return false;
		echo '<form enctype="multipart/form-data" action="'.URLHelper::getLink('').'" method="POST">';
		echo '<input type="hidden" name="cmd" value="upload">';
		echo '<input type="hidden" name="fc" value="',$this->fc,'">';
		$table=new ZebraTable(array('bgcolor'=>'#eeeeee', 'align'=>'center', 'padding'=>2));
		echo $table->headerRow(array('<b>' . _("Neues Smiley hochladen") . '</b>',''));
		echo $table->row(array(_("existierende Datei überschreiben:"),' <input type="checkbox" name="replace" value="1">'));
		echo $table->row(array(_("1. Bilddatei auswählen:"),' <input name="imgfile" type="file" cols=45>'));
		echo $table->row(array(_("2. Bilddatei hochladen:"),' <input type="IMAGE" ' . makeButton('absenden', 'src') . ' border=0 value="absenden">'));
		echo $table->close(), '&nbsp;<br>';
		echo '</form>';

	}

	function show_menue(){
		if ($this->error) return false;
		$style = 'smiley_redborder';
		$style2 = 'blank';
		if ($this->fc == 'all') {
			$this->where = 'ORDER BY smiley_name';
		} elseif ($this->fc == 'top20') {
			$this->where = 'WHERE smiley_counter > 0 OR short_counter > 0 ORDER BY smiley_counter+short_counter DESC, smiley_name ASC LIMIT 20';
		} elseif ($this->fc == 'used') {
			$this->where = 'WHERE smiley_counter > 0 OR short_counter > 0 ORDER BY smiley_counter+short_counter DESC, smiley_name ASC';
		} elseif ($this->fc == 'none') {
			$this->where = 'WHERE smiley_counter=0 AND short_counter=0 ORDER BY smiley_name';
		} elseif ($this->fc == 'short') {
			$this->where = "WHERE short_name != '' ORDER BY smiley_name";
		} else {
			$this->fc = $this->fc{0};
			$this->where = "WHERE smiley_name LIKE '".$this->fc."%' ORDER BY smiley_name";
		}
		echo '<table width="80%"><tr><td valign=top>';

		$table=new ZebraTable(array('bgcolor'=>'#eeeeee', 'align'=>'center', 'padding'=>'2'));

		echo $table->open();
		echo $table->openHeaderRow(), $table->cell('<b>' . _("Auswahl") . '</b>', array('align'=>'center', 'colspan'=>2)), $table->closeRow();
		echo $table->openHeaderRow();
		echo $table->cell('<b>' . _("1. Zeichen") . '</b>', array('align'=>'center'));
		echo $table->cell( _("Anzahl") , array('align'=>'right'));
		echo $table->closeRow();

		$this->db->query('SELECT COUNT(smiley_name) AS c, LEFT(smiley_name, 1) AS firstchar FROM smiley GROUP BY LEFT(smiley_name,1)');
		while($this->db->next_record()){
			echo $table->openRow();
			echo $table->cell('<a href="'.URLHelper::getLink('?fc='.$this->db->f('firstchar')).'">'.$this->db->f('firstchar').'</a>', array('align'=>'center', 'class'=>($this->fc == $this->db->f('firstchar'))? $style:$style2));
			echo $table->cell('('.$this->db->f('c').')', array('align'=>'right', 'style'=>'font-size:9pt;'));
			echo $table->closeRow();
		}
		echo $table->close();

		echo '</td><td valign="top">';

		echo $table->open();
		echo $table->openHeaderRow(), $table->cell('<b>' . _("Auswahl") . '</b>', array('align'=>'center', 'colspan'=>2)), $table->closeRow();
		echo $table->openRow(), $table->cell('<a href="'.URLHelper::getLink('?fc=all').'">'._("alle").'</a>', array('align'=>'center', 'colspan'=>2, 'class'=>($this->fc == 'all')? $style:$style2)), $table->closeRow();
		echo $table->openRow(), $table->cell('<a href="'.URLHelper::getLink('?fc=top20').'">'._("Top 20").'</a>', array('align'=>'center', 'colspan'=>2, 'class'=>($this->fc == 'top20')? $style:$style2)), $table->closeRow();
		echo $table->openRow(), $table->cell('<a href="'.URLHelper::getLink('?fc=used').'">'._("benutzte").'</a>', array('align'=>'center', 'colspan'=>2, 'class'=>($this->fc == 'used')? $style:$style2)), $table->closeRow();
		echo $table->openRow(), $table->cell('<a href="'.URLHelper::getLink('?fc=none').'">'._("nicht benutzte").'</a>', array('align'=>'center', 'colspan'=>2, 'class'=>($this->fc == 'none')? $style:$style2)), $table->closeRow();
		echo $table->openRow(), $table->cell('<a href="'.URLHelper::getLink('?fc=short').'">'._("nur mit Kürzel").'</a>', array('align'=>'center', 'colspan'=>2, 'class'=>($this->fc == 'short')? $style:$style2)), $table->closeRow();
		echo '<tr><td colspan="2" class="blank">&nbsp;</td></tr>', "\n";

		echo $table->openHeaderRow(), $table->cell('<b>' . _("Aktionen") . '</b>', array('align'=>'center', 'colspan'=>2)), $table->closeRow();
		echo $table->openRow(), $table->cell('<a href="'.URLHelper::getLink('?cmd=updatetable&fc='.$this->fc).'">'._("Tabelle aktualisieren").'</a>', array('align'=>'center', 'colspan'=>2)), $table->closeRow();
		echo $table->openRow(), $table->cell('<a href="'.URLHelper::getLink('?cmd=countsmiley&fc='.$this->fc).'">'._("Smileys zählen").'</a>', array('align'=>'center', 'colspan'=>2)), $table->closeRow();
		echo $table->openRow(), $table->cell('<a href="'.URLHelper::getLink('show_smiley.php').'" target="_smileys">'._("Smiley-Übersicht öffnen").'</a>', array('align'=>'center', 'colspan'=>2)),  $table->closeRow();
		echo '<tr><td colspan="2" class="blank">&nbsp;</td></tr>', "\n";

		$info = $this->get_info();
		echo $table->openHeaderRow(), $table->cell('<b>' . _("Smileys") . '</b>', array('align'=>'center', 'colspan'=>2)), $table->closeRow();
		echo $table->openRow(), $table->cell(_("vorhanden:"), array('align'=>'left')), $table->cell($info['count_all'], array('align'=>'right')), $table->closeRow();
		echo $table->openRow(), $table->cell(_("davon benutzt:"), array('align'=>'left')), $table->cell($info['count_used'], array('align'=>'right')), $table->closeRow();
		echo $table->openRow(), $table->cell(_("insgesamt benutzt:"), array('align'=>'left')), $table->cell($info['sum'], array('align'=>'right')), $table->closeRow();
		echo $table->openRow(), $table->cell(_("letzte Änderung:"), array('align'=>'left', 'colspan'=>2)), $table->closeRow();
		echo $table->openRow(), $table->cell(strftime('%d.%m.%Y %H:%M:%S',$info['last_change']), array('align'=>'center', 'colspan'=>2)), $table->closeRow();
		echo $table->close();

		echo '</td></tr></table>';
	}

	function show_smiley_list() {
		if ($this->error) return false;
		echo '<form action="'.URLHelper::getLink('').'" method="POST">', "\n";
		echo '<input type="hidden" name="cmd" value="update">';
		echo '<input type="hidden" name="fc" value="',$this->fc,'">';
		$table = new ZebraTable(array('bgcolor'=>'#eeeeee', 'align'=>'center', 'padding'=>'2'));
		echo $table->open();
		echo $table->openHeaderRow();
		echo $table->cell('<b>' . _("Nr.") . '</b>', array('align'=>'center'));
		echo $table->cell('<b>' . _("Smiley") . '</b>', array('align'=>'center'));
		echo $table->cell('<b>' . _("Smileyname") . '</b>', array('align'=>'center'));
		echo $table->cell('&nbsp;&nbsp;&Sigma;&nbsp;&nbsp;', array('align'=>'center'));
		echo $table->cell('<b>' . _("Kürzel") . '</b>', array('align'=>'center'));
		echo $table->cell('&nbsp;&nbsp;&Sigma;&nbsp;&nbsp;', array('align'=>'center'));
		echo $table->cell('<b>' . _("Löschen") . '</b>', array('align'=>'center'));
		echo $table->closeRow();
		$this->db->query('SELECT * FROM smiley '.$this->where);
		$count=0;
		while($this->db->next_record()) {
			$smile_name = $this->db->f('smiley_name');
			$count++;
			$urlname=urlencode($smile_name);
			echo $table->openRow();
			echo $table->cell($count.'&nbsp;', array('align'=>'right'));
			echo $table->cell('<img src="' . $GLOBALS['DYNAMIC_CONTENT_URL'] . '/smile/' . $urlname . '.gif" alt="' . $smile_name . '" title="' . $smile_name . '" width="'.$this->db->f('smiley_width').'" height="'.$this->db->f('smiley_height').'">', array('align' => 'center'));
			echo $table->cell('<input name="rename_'.$urlname.'" value="'.$smile_name.'" size=20>');
			echo $table->cell($this->db->f('smiley_counter'), array('align'=>'center'));
			//echo $table->cell('<input readonly name="short_'.$urlname.'.gif" value="'.$db->f('short').'" size="5">');
			echo $table->cell($this->db->f('short_name'), array('align'=>'center'));
			echo $table->cell((($this->db->f('short_name'))?  $this->db->f('short_counter') : '-'), array('align'=>'center'));
			echo $table->cell('&nbsp;<a href="'.URLHelper::getLink('?cmd=delete&img='.$this->db->f('smiley_id').(($this->fc != '')?'&fc='.$this->fc:'')).'" alt="delete" title="'.sprintf(_("Smiley %s löschen"),'&quot;'.$smile_name.'&quot;').'"><img src="'.$GLOBALS['ASSETS_URL'].'images/trash.gif" border="0" width="12" height="17"></a>&nbsp;', array('align'=>'center'));
			echo $table->closeRow();
		}
		echo $table->openRow();
		if ($count == 0) {
			print $table->cell('<h4>' . _("Keine Smileys vorhanden.") . '</h4>', array('colspan'=>7, 'class'=>'blank'));
		} else {
			echo $table->cell('<input type=image '.makeButton('absenden','src').'>', array('colspan'=>7, 'align'=>'center'));
		}
		echo $table->closeRow();
		echo $table->close();
	}

	function user_menue($txt){
		if ($this->error) return false;
		$style = ' class="smiley_redborder"';
		switch ($this->fc) {
			case 'all':
				$this->where = 'ORDER BY smiley_name';
				break;
			case 'top20':
				$this->where = 'WHERE smiley_counter > 0 OR short_counter > 0 ORDER BY smiley_counter+short_counter DESC, smiley_name ASC LIMIT 20';
				break;
			case 'short':
				$this->where = "WHERE short_name != '' ORDER BY smiley_name";
				break;
			default:
				$this->fc = $this->fc{0};
				$this->where = "WHERE smiley_name LIKE '".$this->fc."%' ORDER BY smiley_name";
		}

		echo '<table align="center"><tr><td class="smiley_th">',$txt,'</td>';
		echo '<td align="center"',(($this->fc == 'all')? $style:''),'>&nbsp;<a href="'.URLHelper::getLink('?fc=all').'">',_("alle"),'</a>&nbsp;</td>',"\n";
		$this->db->query('SELECT LEFT(smiley_name, 1) AS fc FROM smiley GROUP BY LEFT(smiley_name,1)');
		while($this->db->next_record()){
			echo '<td align="center"',(($this->fc == $this->db->f('fc'))? $style:''),'>&nbsp;<a href="', URLHelper::getLink('?fc='.$this->db->f('fc')).'">', $this->db->f('fc'),'</a>&nbsp;</td>',"\n";
		}
		echo '<td align="center"',(($this->fc == 'short')? $style:''),'>&nbsp;<a href="', URLHelper::getLink('?fc=short').'">',_("Kürzel"),'</a>&nbsp;</td>',"\n";
		if($this->SMILEY_COUNTER) echo '<td align="center"',(($this->fc == 'top20')? $style:''),'>&nbsp;<a href="', URLHelper::getLink('?fc=top20'), '">',_("Top 20"),'</a>&nbsp;</td>',"\n";
		if ($GLOBALS['auth']->auth['jscript'])
			echo '<td class="smiley_th">&nbsp;<a href="javascript:void(0);" onclick="window.close();">' , _("Fenster schließen"),'</a>&nbsp;</td>';
		echo '</tr></table>';
	}

	function user_smiley_list() {
		if ($this->error) return false;

		echo '<table align="center" width="100%"><tr><td valign="top" align="center">';
		$tabstart = '<table cellspacing="2" cellpadding="2" class="blank" bgcolor="#94a6bc">'. "\n";
		$tabstart .= '<tr><td class="smiley_th">' .  _("Bild") . '</td><td class="smiley_th">' .  _("Schreibweise") . '</td><td class="smiley_th">' . _("Kürzel") . '</td>';
		if($this->SMILEY_COUNTER) $tabstart .= '<td class="smiley_th"> &Sigma; </td>';
		$tabstart .= "</tr>\n";
		echo $tabstart;
		$this->db->query('SELECT count(*) AS c FROM smiley '.$this->where);
		$this->db->next_record();
		$count = $this->db->f('c');
		if ($this->fc == 'top20' && $count > 20) $count = 20;
		$count3 = ($count < 3)? 1 : $count / 3;
		$this->db->query('SELECT * FROM smiley '.$this->where);
		$c=0;
		while($this->db->next_record()) {

			if ($c >= $count3) {
				echo '</table>';
				echo '</td><td valign="top" align="center">';
				echo $tabstart;
				$c = 0;
			}
			$c++;
			$smile_name = $this->db->f('smiley_name');
			$urlname=urlencode($smile_name);
			echo '<tr>';
			echo '<td align="center" class="blank">';
			if ($this->user_id != 'nobody') {
				$sid = $this->db->f('smiley_id');
				echo '<a href="'.URLHelper::getLink('?cmd=addfav&fc='.$this->fc.'&img='.$sid.'#anker'.$sid).'" name="anker',$sid,'">';
				$tooltiptxt = sprintf(_("%s zu meinen Favoriten hinzufügen"),$smile_name);
			} else {
				$tooltiptxt = $smile_name;
			}
			echo '<img src="' , $GLOBALS['DYNAMIC_CONTENT_URL'] , '/smile/' , $urlname , '.gif" ',  tooltip($tooltiptxt), ' width="', $this->db->f('smiley_width'), '" height="', $this->db->f('smiley_height'), '" border="0">';
			if ($this->user_id != 'nobody') echo '</a>';
			echo '</td><td align="center" class="blank"> :'.$smile_name.': </td>';
			echo '<td align="center" class="blank">', $this->db->f('short_name'), '</td>';
			if($this->SMILEY_COUNTER) echo '<td align="center" class="blank">',$this->db->f('smiley_counter')+$this->db->f('short_counter'), '</td>';
			echo "</tr>\n";
		}
		if (!$count) {
			print '<tr><td align="center" colspan="3"><h4>' . _("Keine Smileys vorhanden.") . '</h4></td></tr>';
		}
		echo '</table>', "\n";
		echo '</td></tr></table>', "\n";
	}


	function process_commands() {
		if ($this->error) return false;
		$count=0;
		$path = $GLOBALS['DYNAMIC_CONTENT_PATH'].'/smile/';
		foreach($_POST as $key => $val) {
			$matches=array();
			preg_match('/(short|rename)_(.*)/', $key, $matches);
			if ($matches[1] == 'rename') {
				if ($matches[2] != $val) {
					$this->db->query("SELECT COUNT(smiley_id) AS c FROM smiley WHERE smiley_name LIKE '".urldecode($val)."'");
					$this->db->next_record();
					if ($this->db->f('c') > 0) {
						$this->msg .= 'error§' . sprintf( _("Es existiert bereits eine Datei mit dem Namen \"%s\"."),  urldecode($val). '.gif'). '§';
					} else {
						if ( rename($path.urldecode($matches[2]).'.gif', $path.urldecode($val).'.gif')) {
							$sql_smile = "UPDATE smiley SET smiley_name='".urldecode($val)."', chdate=UNIX_TIMESTAMP() WHERE smiley_name = '".urldecode($matches[2])."'";
							$this->db->query($sql_smile);
							$count++;
						} else {
							$this->msg .= 'error§' . sprintf( _("Die Datei \"%s\" konnte nicht umbenannt werden."),  urldecode($matches[2]).'.gif'). '§';
						}
					}
				}
			}
		}
		if ($count > 0) {
			if ($count == 1) {
				$this->msg .= 'msg§'._("Es wurde 1 Smiley umbenannt."). '§';
			} else {
				$this->msg .= 'msg§'.sprintf(_("Es wurden %d Smileys umbenannt."), $count). '§';
			}
		}
	}

	function delete_smiley(){
		if ($this->error) return false;
		$img = (isset($_GET['img']))? intval($_GET['img']):0;
		if (!$img) return false;
		$this->db->query('SELECT * FROM smiley WHERE smiley_id = ' . $img);
		if ($this->db->next_record()) {
			$file = $this->db->f('smiley_name') . '.gif';
			if (unlink(''.$GLOBALS['DYNAMIC_CONTENT_PATH'].'/smile/'.$file)) {
				$this->db->query('DELETE FROM smiley WHERE smiley_id = ' . $img);
				$this->msg .= 'msg§' .sprintf( _("Smiley \"%s\" erfolgreich gelöscht."),$file) . '§';
				return true;
			}
		}
		$this->msg .= 'error§'. sprintf(_("Fehler: Smiley \"%s\" konnte nicht gelöscht werden."),$file). '§';
		return false;
	}

	function display_msg(){

		if ($this->msg != '') {
			echo '<table>', parse_msg($this->msg), '</table>';
		}
		$this->msg = '';
	}

	function get_info(){
		$info = array('count_all'=>0, 'count_used'=>0, 'sum'=>0, 'last_change'=>0);
		$this->db->query('SELECT COUNT(smiley_id) AS c FROM smiley');
		$this->db->next_record();
		$info['count_all'] = $this->db->f('c');
		$this->db->query('SELECT COUNT(smiley_id) AS c, SUM(smiley_counter + short_counter) AS s FROM smiley WHERE smiley_counter > 0 OR short_counter > 0');
		$this->db->next_record();
		$info['count_used'] = $this->db->f('c');
		$info['sum'] = $this->db->f('s');
		$this->db->query('SELECT chdate FROM smiley');
		$this->db->next_record();
		$info['last_change'] = $this->db->f('chdate');
		return $info;
	}

	function read_favorite(){
		if ($this->error) return false;		
		$this->db->query("SHOW COLUMNS FROM user_info LIKE 'smiley_favorite%'");
		if (!$this->db->next_record()) return false;
		$this->my_smiley = array();
		$this->db->queryf("SELECT smiley_favorite FROM user_info WHERE user_id = '%s'", $this->user_id);
		if ($this->db->next_record()){
			$sm_list = $this->db->f('smiley_favorite');
			if (strlen($sm_list) > 1) {
				$this->db->query('SELECT * FROM smiley WHERE smiley_id IN ('.$sm_list.') ORDER BY smiley_name');
				while($this->db->next_record()){
					$this->my_smiley[$this->db->f('smiley_name')] =
						array(	'id'=>$this->db->f('smiley_id'),
							'width'=>$this->db->f('smiley_width'),
							'height'=>$this->db->f('smiley_height'));
				}
			}
		} else return false;
		return true;
	}

	function show_favorite(){
		if ($this->error) return false;
		if ($this->read_favorite()){
			$zeile[0][1] = $zeile[0][2] = $zeile[0][3] = '';
			$zeile[1][1] = $zeile[1][2] = $zeile[1][3] = '';
			$c = 1;
			foreach($this->my_smiley as $smile=>$value){
				$i = ($c <= 10)? 0:1;
				$zeile[$i][1] .= '<td class="smiley_th">'.$c++.'</td>';
				$zeile[$i][2] .= '<td class="blank"><a href="'.URLHelper::getLink('?cmd=delfav&fc='.$this->fc.'&img='.$value['id']).'">';
				$zeile[$i][2] .= '<img src="' . $GLOBALS['DYNAMIC_CONTENT_URL'] . '/smile/' . $smile . '.gif" ' . tooltip(sprintf(_("%s  entfernen"),$smile)) . ' width="'. $value['width']. '" height="'. $value['height']. '" border="0"></a></td>'."\n";
				$zeile[$i][3] .= '<td class="blank">&nbsp;:'.$smile.':&nbsp;</td>'."\n";
			}
			echo '<table width="100%" class="blank" border="0" cellpadding="0" cellspacing="0" >', "\n";
			echo '<tr><td class="topic"><b>&nbsp;' . _("meine Smiley-Favoriten") . '</b></td></tr>', "\n";
			echo '<tr><td class="blank"><blockquote>&nbsp;<br>' , _("Klicken Sie auf ein Smiley um es zu Ihren Favoriten hinzuzufügen. Wenn Sie auf einen Favoriten klicken, wird er wieder entfernt.") ,'<br>',_("Sie können maximal 20 Smileys aussuchen."), '<br>&nbsp;</blockquote></td></tr>', "\n";
			//echo '</table>', "\n";
			echo '<tr><td class="blank">';
			echo '<table align=center><tr><td align=left>', "\n";
			for($i = 0; $i <= count($zeile); $i++){
				if ($zeile[$i][1]){
					echo '<table bgcolor="#94a6bc"><tr align=center><td class="smiley_th">',_("Favoriten"),'</td>', $zeile[$i][1], '</tr>';
					echo '<tr align="center"><td class="smiley_th">',_("Smiley"),'</td>', $zeile[$i][2], '</tr>';
					echo '<tr align="center"><td class="smiley_th">',_("Schreibweise"),'</td>', $zeile[$i][3], '</tr></table>';
				}
			}
			echo '</td></tr></table>&nbsp;<br>';
			echo '</td></tr></table>';
			return true;
		}
		return false;
	}

	function del_favorite(){
		if ($this->error) return false;
		if ($this->read_favorite()){
			$sm_list = '';
			$img = (isset($_GET['img']))? intval($_GET['img']):0;
			foreach($this->my_smiley as $smile=>$value)
				if ($value['id'] != $img) $sm_list = ($sm_list)? $sm_list.','.$value['id']:$value['id'];
			$this->db->query("UPDATE user_info SET smiley_favorite='".$sm_list."' WHERE user_id = '".$this->user_id."'");
		} else return false;
		return true;
	}

	function add_favorite(){
		if ($this->error) return false;
		if ($this->read_favorite()){
			$sm_list = '';
			$c = 20;
			$add = true;
			$img = (isset($_GET['img']))? intval($_GET['img']):0;
			foreach($this->my_smiley as $smile=>$value) {
				if ($value['id'] == $img) $add = false; // already favorite
				$sm_list = ($sm_list)? $sm_list.','.$value['id']:$value['id'];
				$c--;
			}
			if ($add && $c > 0 && $img > 0) $sm_list = ($sm_list)? $sm_list.','.$img:$img;
			$this->db->query("UPDATE user_info SET smiley_favorite='".$sm_list."' WHERE user_id = '".$this->user_id."'");
		} else return false;
		return true;
	}
}
?>
