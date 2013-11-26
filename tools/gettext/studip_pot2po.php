#!/usr/bin/php
<?php
/**
* studip_pot2po.php
*
* erzeugt aus einer nicht übersetzten gettext (.pot) Datei eine
* 1:1 übersetzte, d.h. kopiert alle msgid nach msgstr, z.B. für eine
* Deutsch -> Deutsch Übersetzung. Parameter ist der Pfad zur pot Datei.
*
* z.B.:
* chdir studip/locale
* mkdir de/LC_MESSAGES
* studip_pot2po.php en/LC_MESSAGES/studip.pot > de/LC_MESSAGES/studip.po
*
* @author       André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
*/
// +---------------------------------------------------------------------------+
//
// Copyright (C) 2006 André Noack <noack@data-quest.de>,
// Suchi & Berg GmbH <info@data-quest.de>
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
if (@$_SERVER['argv'][1]) {
    $pot_file = $_SERVER['argv'][1];
} else {
    $pot_file = "studip.pot";
}
if (!is_file($pot_file)) {
    echo "File not found: {$pot_file} \n";
    exit(0);
}
$buffer = null;
$plural = false;
$lines = file($pot_file);
for($i=0;$i < count($lines);++$i){
  $line = $lines[$i];
    if(preg_match('/^msgid/', $line)) {
          $buffer[] = preg_replace('/^msgid[a-z_]*/','msgstr',$line);
      if (preg_match('/^msgid_plural/', $line)) {
        $plural = true;
        $c = 0;
        foreach($buffer as $k => $v) {
          if (strpos($v, 'msgstr') !== false) {
            $buffer[$k] = str_replace('msgstr', 'msgstr['.(int)$c.']', $v);
            ++$c;
          }
        }
      }

    } elseif(!is_null($buffer)){
        if(preg_match('/^msgstr/', $line)){
            echo join('',$buffer);
            $buffer = null;
      if ($plural) {
        $plural = false;
        ++$i;
      }
      continue;
        } else {
            $buffer[] = $line;
        }
    }
    echo $line;
}

