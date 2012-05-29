<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
lit_overview_print_view.php 
Copyright (C) 2004 André Noack <noack@data-quest.de>
Suchi & Berg GmbH <info@data-quest.de>
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

require '../lib/bootstrap.php';
unregister_globals();
function my_session_open($id = false){
    if (!$id){
        $id = md5(basename($GLOBALS['PHP_SELF']));
    }
    if (!$GLOBALS['sess']->is_registered($id)){
        $GLOBALS['sess']->register($id);
    }
    if (isset($GLOBALS[$id])){
        $GLOBALS[$id] = unserialize($GLOBALS[$id]);
    }
}

function my_session_close($id = false){
    if (!$id){
        $id = md5(basename($GLOBALS['PHP_SELF']));
    }
    if (isset($GLOBALS[$id])){
        $GLOBALS[$id] = serialize($GLOBALS[$id]);
    }
}

function my_session_var($var, $id = false){
    if (!$id){
        $id = md5(basename($GLOBALS['PHP_SELF']));
    }
    if (is_array($var)){
        foreach ($var as $name){
            if (isset($_REQUEST[$name])){
                $GLOBALS[$id][$name] = $_REQUEST[$name];
            } else {
                $_REQUEST[$name] = $GLOBALS[$id][$name];
            }
            $GLOBALS[$name] =& $GLOBALS[$id][$name];
        }
    } else {
        if (isset($_REQUEST[$var])){
            $GLOBALS[$id][$var] = $_REQUEST[$var];
        } else {
            $_REQUEST[$var] = $GLOBALS[$id][$var];
        }
        $GLOBALS[$var] =& $GLOBALS[$id][$var];
    }
}

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
my_session_open(md5('admin_literatur_overview.php'));
$perm->check('admin');

require_once('lib/classes/StudipLitCatElement.class.php');

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

PageLayout::removeStylesheet('style.css');
PageLayout::addStylesheet('print.css'); // use special stylesheet for printing
// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head

$element = new StudipLitCatElement();

my_session_var(array('_semester_id','_inst_id','_lit_data','_lit_data_id'),md5('admin_literatur_overview.php'));
$header = get_object_name($_inst_id, 'inst');
?>
<h1>
<?=htmlReady(sprintf(_("Literaturliste %s"), $header['type'] . ": " . $header['name']))?>
</h1>
<?
if (is_array($_lit_data)){
    foreach ($_lit_data as $cid => $data){
        $element->setValues($data);
        if ($element->getValue('catalog_id')){
            $titel = htmlReady($element->getShortName());
            echo "\n<table width=\"99%\" cellpadding=\"0\" cellspacing=\"4\" border=\"0\" align=\"center\"><tr>";
            echo '<td><b>' . $titel . '</b></td>';
            echo "\n</tr></table>";
            $content = "";
            $estimated_p = 0;
            $participants = 0;
            echo "\n<table width=\"97%\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">";
            $content .= "<b>" . _("Titel:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("dc_title"),true,true) . "<br>";
            $content .= "<b>" . _("Autor; weitere Beteiligte:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("authors"),true,true) . "<br>";
            $content .= "<b>" . _("Erschienen:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("published"),true,true) . "<br>";
            $content .= "<b>" . _("Identifikation:") ."</b>&nbsp;&nbsp;" . htmlReady($element->getValue("dc_identifier")) . "<br>";
            
            $content .= "<b>" . _("Veranstaltungen:") . "</b>&nbsp;&nbsp;";
            foreach ($_lit_data[$cid]['sem_data'] as $sem_data){
                $content .= htmlReady(my_substr($sem_data["Name"],0,50)) .', ';
                $estimated_p += $sem_data['admission_turnout'];
                $participants += $sem_data['participants'];
            }
            $content = substr($content,0,-2);
            $content .= "<br>";
            $content .= "<b>" . _("Dozenten:") . "</b>&nbsp;&nbsp;";
            foreach ($_lit_data[$cid]['doz_data'] as $doz_data){
                $content .= htmlReady($doz_data["Nachname"]) . ", ";
            }
            $content = substr($content,0,-2);
            $content .= "<br>";
            $content .= "<b>" . _("Teilnehmeranzahl (erwartet/angemeldet):") . "</b>&nbsp;&nbsp;";
            $content .= ($estimated_p ? $estimated_p : _("unbekannt"));
            $content .= ' / ' . (int)$participants;
            $content .= "<br>";
            if (is_array($_lit_data[$cid]['check_accession'])){
                $content .= "<div style=\"margin-top: 10px;border: 1px solid black;padding: 5px; width:96%;\"<b>" ._("Verf&uuml;gbarkeit in externen Katalogen:") . "</b><br>";
                foreach ( $_lit_data[$cid]['check_accession'] as $plugin_name => $ret){
                    $content .= "<b>&nbsp;{$plugin_name}&nbsp;</b>";
                    if ($ret['found']){
                        $content .= _("gefunden") . "&nbsp;";
                        
                    } elseif (count($ret['error'])){
                        $content .= '<span style="color:red;">' . htmlReady($ret['error'][0]['msg']) . '</span>';
                    } else {
                        $content .= _("<u>nicht</u> gefunden") . "&nbsp;";
                    }
                    $content .= "<br>";
                }
                $content .= "</div>";
            }
            echo '<tr><td style="font-size:75%;">' . $content . '</td></tr>';
            echo "\n</table><br>";
        }
    }
}

include ('lib/include/html_end.inc.php');
// Save data back to database.
my_session_close(md5('admin_literatur_overview.php'));
page_close();
?>
