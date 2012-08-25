<?php
# Lifter007: TEST
# Lifter010: TODO
/**
 * msg.inc.php
 *
 * Modul zur Ausgabe von Nachrichten auf Administrationsseiten von Stud.IP.
 *
 * Diese Funktion zeigt Messages mit zugehoerigenm Symbol.
 * ACHTUNG: Die Funktion wird innerhalb einer Tabelle aufgerufen, daher
 * wird eine eigene Tabellenzelle geoeffnet
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2000-2009 Stud.IP
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL Licence 2
 * @category    Stud.IP
 * @package     layout
 *
 * @deprecated  since Stud.IP version 1.10. please use the new MessageBox instead.
 *
 */

//Imports
require_once 'lib/classes/MessageBox.class.php';


//Displays Errormessages (kritischer Abbruch, Symbol "X")
function my_error($msg, $class="blank", $colspan=2, $add_row='', $small='')
{
    echo '<tr><td class="'.$class.'" colspan="'.$colspan.'">';
    echo MessageBox::error($msg);
    echo '</td></tr>';
}

//Displays Successmessages (Information ueber erfolgreiche Aktion, Symbol Haken)
function my_msg($msg, $class="blank", $colspan=2, $add_row='', $small='')
{
    echo '<tr><td class="'.$class.'" colspan="'.$colspan.'">';
    echo MessageBox::success($msg);
    echo '</td></tr>';
}

//Displays Informationmessages  (Hinweisnachrichten, Symbol Ausrufungszeichen)
function my_info($msg, $class="blank", $colspan='', $add_row='', $small='')
{
    echo '<tr><td class="'.$class.'" colspan="'.$colspan.'">';
    echo MessageBox::info($msg);
    echo '</td></tr>';
}

//Kombinierte Nachrichten zerlegen
function parse_msg($long_msg,$separator="§", $class="blank", $colspan=2, $add_row='', $small='')
{
    $msg = explode ($separator,$long_msg);
    for ($i=0; $i < count($msg); $i=$i+2) {
        switch ($msg[$i]) {
            case "error" : my_error($msg[$i+1], $class, $colspan); break;
            case "info" : my_info($msg[$i+1], $class, $colspan); break;
            case "msg" : my_msg($msg[$i+1], $class, $colspan); break;
        }
    }
    return;
}

function parse_msg_array($msg, $class = "blank", $colspan = 2, $add_row='', $small='')
{
    if (is_array($msg)) {
        foreach($msg as $one_msg) {
            list($type, $content) = $one_msg;
            call_user_func('my_' . $type, $content, $class, $colspan);
        }
    }
}

//Kombinierte Nachrichten zerlegen und in eigenem Fenster anzeigen
function parse_window($long_msg, $separator="§", $titel, $add_msg="")
{
    if ($titel == "")
        $titel= _("Fehler");
    if ($add_msg == "")
        $add_msg= sprintf(_("%sHier%s geht es zurück zur Startseite."), "<a href=\"index.php\"><em>", "</em></a>") . "<br>";
    ?>
    <table border="0" bgcolor="#000000" align="center" cellspacing="0" cellpadding="2" width="70%">
        <tr>
            <td class="table_header_bold"><b><? echo $titel?></b></td>
        </tr>
       <tr>
           <td class="blank">&nbsp;</td>
       </tr>
    <?php
      $msg = explode ($separator,$long_msg);
        for ($i=0; $i < count($msg); $i=$i+2) {
            switch ($msg[$i]) {
                case "error" : my_error($msg[$i+1], "blank", 1); break;
                case "info" : my_info($msg[$i+1], "blank", 1); break;
                case "msg" : my_msg($msg[$i+1], "blank", 1); break;
            }
        }
    ?>
        <tr>
            <td class="blank"><?= $add_msg ?></td>
        </tr>
    </table>
    <?
}
