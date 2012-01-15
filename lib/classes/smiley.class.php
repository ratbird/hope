<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO - large areas remained untouched for lifter002 to fix
# Lifter003: TODO - $this->where still seems a bit odd
# Lifter010: TODO
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

require_once 'config.inc.php';
require_once 'lib/msg.inc.php'; //Funktionen fuer Nachrichtenmeldungen
require_once 'lib/visual.inc.php';
require_once 'lib/classes/Table.class.php';
require_once 'lib/classes/ZebraTable.class.php';


class smiley {
    var $SMILEY_COUNTER;
    var $error;
    var $short_r;
    var $msg;
    var $fc;
    var $where;
    var $smiley_tab;
    var $my_smiley;
    var $user_id;

    function smiley($admin = false) {
        $this->msg = '';
        $this->error = false;
        $this->where = '';

        $this->smiley_tab = array();
        $this->my_smiley = array();
        $this->user_id = $GLOBALS['auth']->auth['uid'];

        if (!get_config('SMILEYADMIN_ENABLE')) {
            $this->msg .=  '§error§' . _("Smiley-Modul abgeschaltet.");
            $this->error = true;
        } else {
            $this->SMILEY_COUNTER = $GLOBALS['SMILEY_COUNTER'] ?: false;

            // smiley-table empty ?
            $smileys = DBManager::get()->query("SELECT 1 FROM smiley")->fetchColumn();
            if ($admin || !$smileys) { // init smiley-short-notation
                $sa = $GLOBALS['SMILE_SHORT'];
                $this->short_r = array_flip($sa);
            }
            if (!$smileys) { // fill table
                // read smiley-gif's from harddisc
                $this->update_smiley_table();

                // test again!!
                $smileys = DBManager::get()->query("SELECT 1 FROM smiley")->fetchColumn();
                if ($smileys) {
                    // search smileys in studip
                    $this->search_smileys();
                } else {
                    $this->msg .= 'error§'. _('Fehler: Keine Smileys auf dem Server gefunden.'). '§';
                    $this->error = true;
                }
            }
            if (!$this->fc = Request::option('fc')) {
                $this->fc = DBManager::get()
                    ->query("SELECT LEFT(smiley_name, 1) FROM smiley ORDER BY smiley_name LIMIT 1")
                    ->fetchColumn();
            }
            if (!$this->fc) {
                $this->fc = 'a';
            }
        }
    }

    function fill_smiley_array($search) {
        if ($this->error) {
            return false;
        }

        $del = $search ? 0 : 1;
        $this->smiley_tab = array();

        $statement = DBManager::get()->query("SELECT * FROM smiley ORDER BY smiley_name");
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $this->smiley_tab[$row['smiley_name']] = array(
                'id'     => $row['smiley_id'],
                'width'  => $row['smiley_width'],
                'height' => $row['smiley_height'],
                'short'  => $row['short_name'],
                'count'  => $row['smiley_counter'],
                'scount' => $row['short_counter'],
                'fcount' => $row['fav_counter'],
                'update' =>0,
                'delete' =>$del
            );
            if ($search) {
                $this->smiley_tab[$row['smiley_name']]['new_count'] = 0;
                $this->smiley_tab[$row['smiley_name']]['new_scount'] = 0;
            }
        }
    }

    function search_smileys() {
        if ($this->error) {
            return false;
        }

        $this->fill_smiley_array(1);
        $smiley_tab = &$this->smiley_tab;
        $smile_error = array();

        //array( array (Tabelle , Feld), array (Tabelle , Feld), ... )
        $table_data = array(
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

        // search in all tables
        foreach ($table_data as $table) {
            $query = "SELECT ? AS txt FROM ?"; // $table1, $table0
            if ($table[0] == 'wiki') {  // only the actual wiki page ...
                $sqltxt = "SELECT MAX(CONCAT(LPAD(version, 5, '0'),' ', ?)) AS txt FROM ? GROUP BY range_id, keyword";
            }
            $statement = DBManager::get()->prepare($query);
            $statement->bindParam(1, $table[1], StudipPDO::PARAM_COLUMN);
            $statement->bindParam(2, $table[0], StudipPDO::PARAM_COLUMN);
            $statement->execute(array());
            // and all entrys
            while ($txt = $statement->fetchColumn()) {
                // all smileys
                if (preg_match_all('/(\>|^|\s):([_a-zA-Z][_a-z0-9A-Z-]*):(?=$|\<|\s)/', $txt, $matches)) {
                    for ($k = 0; $k < count($matches[2]); $k++) {
                        $name = $matches[2][$k];
                        if (isset($smiley_tab[$name])) {
                            $smiley_tab[$name]['new_count'] += 1;
                        } else if(isset($smiley_error[$name])) {
                            $smiley_error[$name]['count'] += 1;
                        } else {
                            $smiley_error[$name]['count'] = 1;
                        }
                    }
                }
                // and now the short-notation
                foreach ($GLOBALS['SMILE_SHORT'] as $key => $value) {
                    $regexp = '/(\>|^|\s)' . preg_quote($key) . '(?=$|\<|\s)/';
                    if ($anz = preg_match_all($regexp, $txt, $matches)) {
                        if (isset($smiley_tab[$value])) {
                            $smiley_tab[$value]['new_scount'] += $anz;
                        }
                    }
                }
            }
        }

        $query = "UPDATE smiley "
               . "SET smiley_counter = ?, short_counter = ?, chdate = UNIX_TIMESTAMP() "
               . "WHERE smiley_id = ?";
        $update = DBManager::get()->prepare($query)

        $anderungen = 0;
        foreach ($smiley_tab as $smiley_name => $smile) {
            if ($smile['count'] != $smile['new_count'] or $smile['scount'] != $smile['new_scount']) {
                $update->execute(array($smile['new_count'] , $smile['new_scount'], $smile['id']));
                $aenderungen++;
            }
        }
        $this->msg .= 'msg§'. sprintf(_('%d Zählerstände aktualisiert'), $aenderungen). '§';
        return true;
    }

    function update_smiley_table(){
        if ($this->error) {
            return false;
        }

        $this->fill_smiley_array(0);
        $smiley_tab = &$this->smiley_tab;

        $path = realpath($GLOBALS['DYNAMIC_CONTENT_PATH'] . '/smile');
        $folder = dir($path);

        while ($entry = $folder->read()) {
            $dot = strrpos($entry, '.');
            $l = strlen($entry) - $dot;
            $name = substr($entry, 0, $dot);
            $ext = strtolower(substr($entry, $dot + 1, $l));
            if ($dot and !is_dir($path . '/' . $entry) and $ext == 'gif') {
                $img = getImageSize($path . '/' . $entry);
                if ($img[2] != IMAGETYPE_GIF) {
                    continue;
                }
                $short = $this->short_r[$name] ?: '';
                if (array_key_exists($name, $smiley_tab)) {
                    $smiley_tab[$name]['delete'] = 0;
                    if ($smiley_tab[$name]['width'] != $img[0] || $smiley_tab[$name]['height'] != $img[1] || $smiley_tab[$name]['short'] != $short) {
                        $smiley_tab[$name]['update'] = 1;
                        $smiley_tab[$name]['width'] = $img[0];
                        $smiley_tab[$name]['height'] = $img[1];
                        $smiley_tab[$name]['short'] = $short;
                    }
                } else { // hm, new smiley at filesystem ...
                    $smiley_tab[$name] = array(
                        'id'     => 0,
                        'width'  => $img[0],
                        'height' => $img[1],
                        'short'  => $short,
                        'count'  => 0,
                        'scount' => 0,
                        'fcount' => 0,
                        'update' => 0,
                        'delete' => 0
                    );
                }
            }
        }
        $folder->close();

        $query = "INSERT INTO smiley "
               . "(smiley_name, smiley_width, smiley_height, short_name, "
               . " smiley_counter, short_counter, fav_counter, mkdate, chdate) "
               . "VALUES (?, ?, ?, ?, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
        $insert = DBManager::get()->prepare($query);

        $query = "UPDATE smiley "
               . "SET short_name = ?, smiley_width = ?, smiley_height = ?, chdate = UNIX_TIMESTAMP() "
               . "WHERE smiley_id = ?";
        $update = DBManager::get()->prepare($query);

        $query = "DELETE FROM smiley WHERE smiley_id = ?";
        $delete = DBManager::get()->prepare($query);

        $c_update = $c_insert = $c_delete = 0;
        foreach ($smiley_tab as $smiley_name => $smile ) {
            if (!$smile['id']) { // new smiley
                $insert->execute(array(
                   $smiley_name, $smile['width'], $smile['height'], $smile['short']
                ));
                $c_insert++;
            } elseif ($smile['update'] == 1) { // new data for smiley
                $update->execute(array(
                    $smile['short'], $smile['width'], $smile['height'], $smile['id']
                ));
                $c_update++;
            } elseif ($smile['delete'] == 1) { // smiley is erased...
                $delete->execute(array(
                    $smile['id']
                ));
                $c_delete++;
            }
        }
        $this->msg .= 'msg§' . sprintf(_('%d Smileys aktualisiert'), $c_update)
                    . ' / ' . sprintf(_('%d Smileys eingefügt'), $c_insert)
                    . ' / ' . sprintf(_('%d Smileys gelöscht'), $c_delete) . '§';
    }


    function imaging() {
        if ($this->error) {
            return false;
        }
        if (empty($GLOBALS['imgfile_name'])) { //keine Datei ausgewählt!
            $this->msg .= 'error§' . _('Sie haben keine Datei zum Hochladen ausgewählt!') . '§';
            return false;
        }

        //Dateiendung bestimmen
        $img_name = $GLOBALS['imgfile_name'];
        $ext = '';
        $dot = strrpos($img_name,'.');
        if ($dot) {
            $l = strlen($img_name) - $dot;
            $smiley_name = substr($img_name, 0, $dot);
            $ext = strtolower(substr($img_name, $dot + 1, $l));
        }
        //passende Endung ?
        if ($ext != 'gif') {
            $this->msg .= 'error§' . sprintf(_("Der Dateityp der Bilddatei ist falsch (%s).<br>Es ist nur die Dateiendung .gif erlaubt!"), $ext) . '§';
            $this->error = true;
            return false;
        }

        //na dann kopieren wir mal...
        $newfile = $GLOBALS['DYNAMIC_CONTENT_PATH'] . '/smile/' . $img_name;

        $query = "SELECT smiley_id FROM smiley WHERE smiley_name LIKE ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($smiley_name));
        $smiley_id = $statement->fetchColumn() ?: 0;

        if (!isset($_POST['replace'])) {
            $this->msg .= 'error§' . sprintf(_('Es ist bereits eine Bildatei mit dem Namen "%s" vorhanden.'), $img_name) . '§';
            return false;
        }

        if (!move_uploaded_file($GLOBALS['imgfile'], $newfile)) {
            $this->msg .= 'error§' . _('Es ist ein Fehler beim Kopieren der Datei aufgetreten. Das Bild wurde nicht hochgeladen!') . '§';
            $this->error = true;
            return false;
        } else if ($smiley_id) {
            $this->msg .= 'msg§' . sprintf(_('Die Bilddatei "%s" wurde erfolgreich ersetzt.'), $img_name) . '§';
            $img = getImageSize($newfile);

            $query = "UPDATE smiley "
                   . "SET smiley_name = ?, smiley_width = ?, smiley_height = ?, chdate = UNIX_TIMESTAMP() "
                   . "WHERE smiley_id = ?";
            DBManager::get()
                ->prepare($query)
                ->execute(array(
                   $smiley_name, $img[0], $img[1], $smiley_id
                ));
        } else {
            $this->msg .= 'msg§' . sprintf(_('Die Bilddatei "%s" wurde erfolgreich hochgeladen.'), $img_name) . '§';
            $img = getImageSize($newfile);

            $query = "INSERT INTO smiley "
                   . "(smiley_name, smiley_width, smiley_height, short_name, "
                   . " smiley_counter, short_counter, fav_counter, mkdate, chdate) "
                   . "VALUES (?, ?, ?, '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
            DBManager::get()
                ->prepare($query)
                ->execute(array(
                   $smiley_name, $img[0], $img[1]
                ));
        }
        chmod($newfile, 0666 & ~umask());       // set permissions for uploaded file
        $this->fc = $smiley_name{0};

        return true;
    }

    function show_upload_form() {
        if ($this->error) {
            return false;
        }
        echo '<form enctype="multipart/form-data" action="'.URLHelper::getLink('').'" method="POST">';
        echo CSRFProtection::tokenTag();
        echo '<input type="hidden" name="cmd" value="upload">';
        echo '<input type="hidden" name="fc" value="',$this->fc,'">';
        $table=new ZebraTable(array('bgcolor'=>'#eeeeee', 'align'=>'center', 'padding'=>2));
        echo $table->headerRow(array('<b>' . _("Neues Smiley hochladen") . '</b>',''));
        echo $table->row(array(_("existierende Datei überschreiben:"),' <input type="checkbox" name="replace" value="1">'));
        echo $table->row(array(_("1. Bilddatei auswählen:"),' <input name="imgfile" type="file" cols=45>'));
        echo $table->row(array(_("2. Bilddatei hochladen:"),' <input type="image" ' . makeButton('absenden', 'src') . ' border=0 value="absenden">'));
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

        $query = "SELECT COUNT(smiley_name) AS c, LEFT(smiley_name, 1) AS firstchar "
               . "FROM smiley GROUP BY LEFT(smiley_name, 1)";
        $statement = DBManager::get()->query($query);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            echo $table->openRow();
            echo $table->cell('<a href="'.URLHelper::getLink('?fc='.$row['firstchar']).'">'.$row['firstchar'].'</a>', array('align'=>'center', 'class'=>($this->fc == $row['firstchar'])? $style:$style2));
            echo $table->cell('('.$row['c'].')', array('align'=>'right', 'style'=>'font-size:9pt;'));
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
        if ($this->error) {
            return false;
        }
        echo '<form action="'.URLHelper::getLink('').'" method="POST">', "\n";
        echo CSRFProtection::tokenTag();
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

        $count=0;

        $statement = DBManager::get()->query("SELECT * FROM smiley ".$this->where);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $smile_name = $row['smiley_name'];
            $count++;
            $urlname=urlencode($smile_name);
            echo $table->openRow();
            echo $table->cell($count.'&nbsp;', array('align'=>'right'));
            echo $table->cell('<img src="' . $GLOBALS['DYNAMIC_CONTENT_URL'] . '/smile/' . $urlname . '.gif" alt="' . $smile_name . '" title="' . $smile_name . '" width="'.$row['smiley_width'].'" height="'.$row['smiley_height'].'">', array('align' => 'center'));
            echo $table->cell('<input name="rename_'.$urlname.'" value="'.$smile_name.'" size=20>');
            echo $table->cell($row['smiley_counter'], array('align'=>'center'));
            //echo $table->cell('<input readonly name="short_'.$urlname.'.gif" value="'.$db->f('short').'" size="5">');
            echo $table->cell($row['short_name'], array('align'=>'center'));
            echo $table->cell($row['short_name'] ? $row['short_counter'] : '-', array('align'=>'center'));
            echo $table->cell('<a href="'.URLHelper::getLink('?cmd=delete&img='.$row['smiley_id'].(($this->fc != '')?'&fc='.$this->fc:'')).'" alt="delete" title="'.sprintf(_("Smiley %s löschen"),$smile_name).'">'. Assets::img('icons/16/blue/trash.png', array('class' => 'text-top')) . '</a>', array('align'=>'center'));
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

    function user_menue($txt) {
        if ($this->error) {
            return false;
        }
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

        $query = "SELECT LEFT(smiley_name, 1) FROM smiley GROUP BY LEFT(smiley_name, 1)";
        $statement = DBManager::get()->query($query);
        while ($fc = $statement->fetchColumn()) {
            echo '<td align="center"',(($this->fc == $fc) ? $style : ''),'>&nbsp;<a href="', URLHelper::getLink('?fc='.$fc).'">', $fc,'</a>&nbsp;</td>',"\n";
        }

        echo '<td align="center"',(($this->fc == 'short')? $style:''),'>&nbsp;<a href="', URLHelper::getLink('?fc=short').'">',_("Kürzel"),'</a>&nbsp;</td>',"\n";
        if($this->SMILEY_COUNTER) echo '<td align="center"',(($this->fc == 'top20')? $style:''),'>&nbsp;<a href="', URLHelper::getLink('?fc=top20'), '">',_("Top 20"),'</a>&nbsp;</td>',"\n";
        if ($GLOBALS['auth']->auth['jscript'])
            echo '<td class="smiley_th">&nbsp;<a href="javascript:void(0);" onclick="window.close();">' , _("Fenster schließen"),'</a>&nbsp;</td>';
        echo '</tr></table>';
    }

    function user_smiley_list() {
        if ($this->error) {
            return false;
        }

        echo '<table align="center" width="100%"><tr><td valign="top" align="center">';
        $tabstart = '<table cellspacing="2" cellpadding="2" class="blank" bgcolor="#94a6bc">'. "\n";
        $tabstart .= '<tr><td class="smiley_th">' .  _("Bild") . '</td><td class="smiley_th">' .  _("Schreibweise") . '</td><td class="smiley_th">' . _("Kürzel") . '</td>';
        if ($this->SMILEY_COUNTER) {
            $tabstart .= '<td class="smiley_th"> &Sigma; </td>';
        }
        $tabstart .= "</tr>\n";
        echo $tabstart;

        $count = DBManager::get()
            ->query("SELECT COUNT(*) FROM smiley " . $this->where)
            ->fetchColumn();
        if ($this->fc == 'top20' && $count > 20) {
            $count = 20;
        }
        $count3 = ($count < 3) ? 1 : $count / 3;

        $c = 0;
        $statement = DBManager::get()->query("SELECT * FROM smiley " . $this->where);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($c >= $count3) {
                echo '</table>';
                echo '</td><td valign="top" align="center">';
                echo $tabstart;
                $c = 0;
            }
            $c++;
            $smile_name = $row['smiley_name'];
            $urlname = urlencode($smile_name);
            echo '<tr>';
            echo '<td align="center" class="blank">';
            if ($this->user_id != 'nobody') {
                $sid = $row['smiley_id'];
                echo '<a href="'.URLHelper::getLink('?cmd=addfav&fc='.$this->fc.'&img='.$sid.'#anker'.$sid).'" name="anker',$sid,'">';
                $tooltiptxt = sprintf(_("%s zu meinen Favoriten hinzufügen"),$smile_name);
            } else {
                $tooltiptxt = $smile_name;
            }
            printf('<img src="%s/smile/%s.gif" %s width="%u" height="%u" border="0">',
                   $GLOBALS['DYNAMIC_CONTENT_URL'], $urlname, tooltip($tooltiptxt),
                   $row['smiley_width'], $row['smiley_height']);
            if ($this->user_id != 'nobody') {
                echo '</a>';
            }
            echo '</td><td align="center" class="blank"> :'.$smile_name.': </td>';
            echo '<td align="center" class="blank">' . $row['short_name'] . '</td>';
            if ($this->SMILEY_COUNTER) {
                $counter = $row['smiley_counter'] + $row['short_counter'];
                echo '<td align="center" class="blank">' . $counter . '</td>';
            }
            echo "</tr>\n";
        }
        if (!$count) {
            echo '<tr><td align="center" colspan="3"><h4>' . _("Keine Smileys vorhanden.") . '</h4></td></tr>';
        }
        echo '</table>', "\n";
        echo '</td></tr></table>', "\n";
    }


    function process_commands() {
        if ($this->error) {
            return false;
        }

        $query = "SELECT 1 FROM smiley WHERE smiley_name = ?";
        $existence = DBManager::get()->prepare($query);

        $query = "UPDATE smiley SET smiley_name = ?, chdate = UNIX_TIMESTAMP() WHERE smiley_name = ?";
        $update = DBManager::get()->prepare($query);

        $count = 0;
        $path = $GLOBALS['DYNAMIC_CONTENT_PATH'] . '/smile/';
        foreach ($_POST as $key => $val) {
            $matches = array();
            preg_match('/(short|rename)_(.*)/', $key, $matches);
            if ($matches[1] == 'rename' and $matches[2] != $val) {
                $val = urldecode($val);
                $matches[2] = urldecode($matches[2]);

                $existence->execute(array($val));
                if ($existence->fetchColumn()) {
                    $message = sprintf(_('Es existiert bereits eine Datei mit dem Namen "%s".'),  $val . '.gif');
                    $this->msg .= 'error§' . $message . '§';
                } elseif (rename($path . $matches[2] . '.gif', $path . $val . '.gif')) {
                    $update->execute(array($val, $matches[2]));
                    $count++;
                } else {
                    $message = sprintf(_('Die Datei "%s" konnte nicht umbenannt werden.'), $matches[2].'.gif');
                    $this->msg .= 'error§' . $message . '§';
                }
                $existence->closeCursor();
            }
        }

        if ($count == 1) {
            $this->msg .= 'msg§' . _('Es wurde 1 Smiley umbenannt.') . '§';
        } else if ($count > 0) {
            $this->msg .= 'msg§' . sprintf(_('Es wurden %d Smileys umbenannt.'), $count) . '§';
        }
    }

    function delete_smiley(){
        if ($this->error) {
            return false;
        }

        $smiley_id = Request::int('img', 0);
        if (!$smiley_id) {
            return false;
        }

        $statement = DBManager::get()->prepare("SELECT smiley_name FROM smiley WHERE smiley_id = ?");
        $statement->execute(array($smiley_id));
        $smiley_name = $statement->fetchColumn();

        if ($smiley_name) {
            $filename = sprintf('%s/smile/%s.gif', $GLOBALS['DYNAMIC_CONTENT_PATH'], $smiley_name);

            if (unlink($filename)) {
                DBManager::get()
                    ->prepare("DELETE FROM smiley WHERE smiley_id = ?")
                    ->execute(array($smiley_id));
                $this->msg .= 'msg§' . sprintf( _('Smiley "%s" erfolgreich gelöscht.'), $smiley_name) . '§';
                return true;
            }
        }

        $this->msg .= 'error§' . sprintf(_('Fehler: Smiley "%s" konnte nicht gelöscht werden.'), $smiley_name) . '§';
        return false;
    }

    function display_msg(){

        if ($this->msg != '') {
            echo '<table>', parse_msg($this->msg), '</table>';
        }
        $this->msg = '';
    }

    function get_info(){
        $db = DBManager::get();

        $query = "SELECT COUNT(smiley_id) AS c, SUM(smiley_counter + short_counter) AS s "
               . "FROM smiley "
               . "WHERE smiley_counter > 0 OR short_counter > 0";
        $temp = $db->query($query)->fetch(PDO::FETCH_ASSOC);

        $info = array(
            'count_all'   => $db->query("SELECT COUNT(smiley_id) FROM smiley")->fetchColumn(),
            'count_used'  => $temp['c'],
            'sum'         => $temp['s'],
            'last_change' => $db->query("SELECT chdate FROM smiley")->fetchColumn(),
        );
        return $info;
    }

    function read_favorite(){
        if ($this->error) {
            return false;
        }

        $db = DBManager::get();

        // smiley favorites active?
        $active = $db->query("SHOW COLUMNS FROM user_info LIKE 'smiley_favorite%'")->fetchColumn();
        if (!$active) {
            return false;
        }

        // reset smiley favorites
        $this->my_smiley = array();

        // load favorites
        $query = "SELECT smiley_favorite FROM user_info WHERE user_id = ?";
        $statement = $db->prepare($query);
        $statement->execute(array($this->user_id));
        $sm_list = $statement->fetchColumn();

        if ($sm_list === null) {
            return false;
        }
        $ids = explode(',', $sm_list);

        // load actual smileys
        $query = "SELECT smiley_id, smiley_name, smiley_width, smiley_height "
               . "FROM smiley WHERE smiley_id IN (?) ORDER BY smiley_name";
        $statement = $db->prepare($query);
        $statement->execute(array($ids));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $this->my_smiley[$row['smiley_name']] = array(
                'id'     => $row['smiley_id'],
                'width'  => $row['smiley_width'],
                'height' => $row['smiley_height']
            );
        }

        return true;
    }

    function show_favorite(){
        if ($this->error or !$this->read_favorite()) {
            return false;
        }

        $zeile[0][1] = $zeile[0][2] = $zeile[0][3] = '';
        $zeile[1][1] = $zeile[1][2] = $zeile[1][3] = '';
        $c = 1;
        foreach ($this->my_smiley as $smile => $value) {
            $i = ($c <= 10) ? 0:1;
            $zeile[$i][1] .= '<td class="smiley_th">'.$c++.'</td>';
            $zeile[$i][2] .= '<td class="blank"><a href="'.URLHelper::getLink('?cmd=delfav&fc='.$this->fc.'&img='.$value['id']).'">';
            $zeile[$i][2] .= '<img src="' . $GLOBALS['DYNAMIC_CONTENT_URL'] . '/smile/' . $smile . '.gif" ' . tooltip(sprintf(_("%s  entfernen"),$smile)) . ' width="'. $value['width']. '" height="'. $value['height']. '" border="0"></a></td>'."\n";
            $zeile[$i][3] .= '<td class="blank">&nbsp;:'.$smile.':&nbsp;</td>'."\n";
        }
        echo '<table width="100%" class="blank" border="0" cellpadding="0" cellspacing="0" >', "\n";
        echo '<tr><td class="topic"><b>&nbsp;' . _("meine Smiley-Favoriten") . '</b></td></tr>', "\n";
        echo '<tr><td class="blank"><blockquote>&nbsp;<br>' , _("Klicken Sie auf ein Smiley um es zu Ihren Favoriten hinzuzufügen. Wenn Sie auf einen Favoriten klicken, wird er wieder entfernt.") ,'<br>',_('Sie können maximal 20 Smileys aussuchen.'), '<br>&nbsp;</blockquote></td></tr>', "\n";
        //echo '</table>', "\n";
        echo '<tr><td class="blank">';
        echo '<table align=center><tr><td align=left>', "\n";
        for ($i = 0; $i <= count($zeile); $i++) {
            if ($zeile[$i][1]) {
                echo '<table bgcolor="#94a6bc"><tr align=center><td class="smiley_th">',_("Favoriten"),'</td>', $zeile[$i][1], '</tr>';
                echo '<tr align="center"><td class="smiley_th">',_("Smiley"),'</td>', $zeile[$i][2], '</tr>';
                echo '<tr align="center"><td class="smiley_th">',_("Schreibweise"),'</td>', $zeile[$i][3], '</tr></table>';
            }
        }
        echo '</td></tr></table>&nbsp;<br>';
        echo '</td></tr></table>';
        return true;
    }

    function del_favorite(){
        if ($this->error or !$this->read_favorite()) {
            return false;
        }

        $smiley_id = Request::int('img', 0);

        $favorites = array();
        foreach ($this->my_smiley as $value) {
            if ($value['id'] != $smiley_id) {
                $favorites[] = $value['id'];
            }
        }

        $sm_list = implode(',', $favorites);
        DBManager::get()
            ->prepare("UPDATE user_info SET smiley_favorite = ? WHERE user_id = ?")
            ->execute(array($sm_list, $this->user_id));

        return true;
    }

    function add_favorite(){
        if ($this->error or !$this->read_favorite()) {
            return false;
        }

        // maxmimum of 20 smileys allowed
        if (count($this->my_smiley) >= 20) {
            return false;
        }

        $smiley_id = Request::int('img', 0);

        // collect ids from favorites
        $favorites = array();
        foreach ($this->my_smiley as $value) {
            if ($value['id'] == $smiley_id) {
                return false; // already favorite
            }
            $favorites[] = $value['id'];
        }
        // add selected smiley id
        $favorites[] = $smiley_id;

        // store favorite list
        $sm_list = implode(',', $favorites);
        DBManager::get()
            ->prepare("UPDATE user_info SET smiley_favorite = ? WHERE user_id = ?")
            ->execute(array($sm_list, $this->user_id));

        return true;
    }
}
