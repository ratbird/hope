<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Export-subfile. Choses which XSL-Script to use.
*
* In this file there are several forms which help choosing the proper XSL-Script
* to transform the export-data into a specific file-format.
*
* @author       Arne Schroeder <schroeder@data.quest.de>
* @access       public
* @modulegroup      export_modules
* @module       export_choose_xslt
* @package      Export
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// export_choose_xslt.inc.php
// pages for choosing an xslt-script
//
// Copyright (c) 2002 Arne Schroeder <schroeder@data-quest.de>
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

use Studip\Button, Studip\LinkButton;

$perm->check("tutor");

require_once ($PATH_EXPORT.'/export_xslt_vars.inc.php');   // Liste der XSLT-Skripts
require_once ('lib/dates.inc.php');   // Datumsfunktionen

$cssSw = new cssClassSwitcher;                                  // Klasse für Zebra-Design
$cssSw->enableHover();


/**
* Checks given parameters
*
* This function checks the given parameters. If some are missing or refer to a XSL-file that
* doesn't exist it returns false and adds a warning to export_error.
*
* @access   public
* @return       boolean
*/
function CheckParamXSLT()
{
global $ex_type, $xml_file_id, $page, $o_mode, $format, $choose, $xslt_files, $export_o_modes, $export_ex_types, $export_error, $export_error_num;
    if ($page==1)
    {
        reset($xslt_files);
        while (list($key, $val) = each($xslt_files))
            if ($val[$ex_type] AND $val[$format])
                $mod_counter++;
        if (($mod_counter == 0) AND ($format != "xml"))
        {
            $export_error .= _("Für dieses Format sind keine Ausgabemodule installiert.<br>Bitte wählen Sie ein anderes Ausgabeformat.") . "<br>";
            $page = 0;
        }

        if ($format == "")
            $page = 0;
        reset($xslt_files);
    }

    if ( ($page==2) AND ($choose == "") )
        $page = 1;
    if ( /*($xml_file_id != "") AND */( in_array($ex_type, $export_ex_types) ) AND ( in_array($o_mode, $export_o_modes) ) )
        return true;

    $export_error .= "<b>" . _("Unzulässiger Seitenaufruf!") . "</b><br>";
    $export_error_num++;
    return false;
}

$export_pagename = _("Konvertierung der Daten: ");
$xslt_filename =  strlen($_REQUEST['xslt_filename']) ? basename(stripslashes($_REQUEST['xslt_filename'])) : $xslt_filename_default;

if (!CheckParamXSLT())
{
    $export_pagename .= _("Es ist ein Fehler aufgetreten ");
    $infobox = array(
    array ("kategorie"  => _("Information:"),
        "eintrag" => array  (
                        array ( "icon" => "icons/16/black/info.png",
                                "text"  => _("Die Parameter, mit denen diese Seite aufgerufen wurde, sind fehlerhaft oder unvollständig.")
                             )
                        )
        )
    );
}

// Die Seiten 2 und 3 ueberspringen, wenn als Dateiformat XML gewaehlt wurde
if (($format == "xml") AND ($page == 1))
{
    $xml_file_id = "";
    $o_mode = "file";
    $page = 3;
}

elseif (!isset($page) or ($page == 0)) // Seite 1 : Auswahl des Dateiformats
{
    $export_pagename .= _("Auswahl des Dateiformats");

    unset($export_msg);
    unset($xml_printimage);
    unset($xml_printlink);
    unset($xml_printdesc);
    unset($xml_printcontent);

    $export_info = _("Bitte wählen Sie, in welchem Format die Daten ausgegeben werden sollen!") . "<br>";

    $export_pagecontent .= "<form method=\"POST\" action=\"" . URLHelper::getLink() . "\">";
    $export_pagecontent .= CSRFProtection::tokenTag();

    $export_pagecontent .= "";
    $export_pagecontent .= "<b><font size=\"-1\">"._("Ausgabeformat:") .  "</font></b><br><select name=\"format\">";

    while (list($key, $val) = each($output_formats))
    {
        $export_pagecontent .= "<option value=\"" . $key . "\"";
        if ($format==$key) $export_pagecontent .= " selected";
        $export_pagecontent .= ">" . $val;
    }
    $export_pagecontent .= "</select><br>   <br><br>";
    $export_pagecontent .= "<b><font size=\"-1\">"._("Name der Datei (z.B. &raquo;Test&laquo;):")."</font></b><br>";
    $export_pagecontent .= "<input type=\"text\" name=\"xslt_filename\" value=\"" . htmlReady($xslt_filename) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"page\" value=\"1\"><br><br><br>";
    $export_pagecontent .= "<input type=\"hidden\" name=\"o_mode\" value=\"" . htmlReady($o_mode) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"range_id\" value=\"" . htmlReady($range_id) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"ex_sem\" value=\"" . htmlReady($ex_sem) . "\">";
    foreach(array_keys($ex_sem_class) as $semclassid){
        $export_pagecontent .= "<input type=\"hidden\" name=\"ex_sem_class[". htmlReady($semclassid) ."]\" value=\"1\">";
    }
    $export_pagecontent .= "<input type=\"hidden\" name=\"ex_type\" value=\"" . htmlReady($ex_type) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"choose\" value=\"" . htmlReady($choose) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"xml_file_id\" value=\"" . htmlReady($xml_file_id) . "\">";

    $export_weiter_button = '<br><center><div class="button-group">' . Button::create('<< ' . _('Zurück'), 'back' ) . "&nbsp;";
    $export_weiter_button .= Button::create(_('Weiter') . ' >>', 'next' ) . "</div>";

    $export_weiter_button .= "</center></form>";

    $infobox = array    (
    array ("kategorie"  => _("Information:"),
        "eintrag" => array  (
                        array ( "icon" => "icons/16/black/info.png",
                                "text"  => sprintf(_("Diese Seite bereitet die Datenausgabe vor. %s Schritt 1/3 %s"), "<br><i>", "</i>")
                             )
                        )
        )
    );
    $link = "<a href=\"./test.xml"."\">";
    $infobox[1]["kategorie"] = _("Aktionen:");
        $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/info.png" ,
                                    "text"  => _("Bitte w&auml;hlen Sie das Dateiformat, in dem Ihre Daten ausgegeben werden sollen. Klicken Sie anschließend auf 'weiter'.")
                                );
}


elseif ($page == 1) // Seite 2 : Auswahl des XSLT-Scripts
{
    if (strpos($choose, $format) === false)
        unset($choose);
    $export_pagename .= _("Auswahl des Ausgabemoduls");

    $export_info = _("W&auml;hlen Sie bitte eine der folgenden XSLT-Dateien und klicken Sie auf 'weiter'");

    $export_pagecontent .= "<form method=\"POST\" action=\"" . URLHelper::getLink() . "\">";
    $export_pagecontent .= CSRFProtection::tokenTag();
    $export_pagecontent .= "";
    $export_pagecontent .= "<table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"100%\">";
    $export_pagecontent .= "<tr align=\"center\" valign=\"top\">";
    $export_pagecontent .= "<th width=\"5%\"><b>&nbsp;</b></th>";
    $export_pagecontent .= "<th width=\"15%\" align=\"left\">" . _("Ausgabemodul") . "</th>";
    $export_pagecontent .= "<th width=\"80%\"><b>" . _("Beschreibung") . "</b></th>";
    $export_pagecontent .= "</tr>";

    $opt_num = 0;
    while (list($key, $val) = each($xslt_files))
    {
        if ($val[$ex_type] AND $val[$format])
        {
            $cssSw->switchClass();
            $export_pagecontent .= "<tr " . $cssSw->getHover() . ">";
            $export_pagecontent .= "<td class=\"" . $cssSw->getClass() . "\">&nbsp;<input type=\"radio\" name=\"choose\" value=\"" . $key . "\"";
            if (($key == $choose) OR ( ($choose == "") AND ($opt_num == 0) ) ) $export_pagecontent .= " checked";
            $export_pagecontent .= ">&nbsp;</td>";
            $export_pagecontent .= "<td class=\"" . $cssSw->getClass() . "\">" . $val["name"] . "&nbsp;</td>";
            $export_pagecontent .= "<td class=\"" . $cssSw->getClass() . "\">" . $val["desc"] . "</td>";
            $export_pagecontent .= "</tr>";
            $opt_num++;
        }
    }

    $export_pagecontent .= "<br>";
    $export_pagecontent .= "</table>";
    $export_pagecontent .= "<input type=\"hidden\" name=\"page\" value=\"2\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"format\" value=\"" . htmlReady($format) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"o_mode\" value=\"" . htmlReady($o_mode) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"ex_type\" value=\"" . htmlReady($ex_type) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"ex_sem\" value=\"" . htmlReady($ex_sem) . "\">";
    foreach(array_keys($ex_sem_class) as $semclassid){
        $export_pagecontent .= "<input type=\"hidden\" name=\"ex_sem_class[". htmlReady($semclassid) ."]\" value=\"1\">";
    }   $export_pagecontent .= "<input type=\"hidden\" name=\"range_id\" value=\"" . htmlReady($range_id) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"xml_file_id\" value=\"" . htmlReady($xml_file_id) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"xslt_filename\" value=\"" . htmlReady($xslt_filename) . "\">";

    $export_weiter_button = '<br><center><div class="button-group">' . Button::create('<< ' . _('Zurück'), 'back' ) . "&nbsp;";

    $export_weiter_button .= Button::create(_('Weiter') . ' >>', 'next');
    $export_weiter_button .=  "</div></center></form>";


    $infobox = array    (
    array ("kategorie"  => _("Information:"),
        "eintrag" => array  (
                        array ( "icon" => "icons/16/black/info.png",
                                "text"  => sprintf(_("Diese Seite bereitet die Datenausgabe vor. %s Schritt 2/3 %s"), "<br><i>", "</i>")
                             )
                        )
        )
    );
    $link = "<a href=\"./test.xml"."\">";
    $infobox[1]["kategorie"] = _("Aktionen:");
        $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/info.png" ,
                                    "text"  => _("W&auml;hlen Sie bitte eines der zur Verf&uuml;gung stehenden Ausgabemodule. Klicken Sie dann auf 'weiter'.")
                                );
}


elseif ($page == 2)  // Seite 3 : Download der Dateien
{
    $export_pagename .= _("Download der Dateien");

    $export_info = _("Die ben&ouml;tigten Dateien liegen nun zum Download bereit.");
    $export_pagecontent .= "<form method=\"POST\" action=\"" . URLHelper::getLink() . "\">";
    $export_pagecontent .= CSRFProtection::tokenTag();

    $xml_printimage = '<a href="'. GetDownloadLink($xml_file_id, $xml_filename, 2) . '"><img src="'.$GLOBALS['ASSETS_URL'].'images/' . $export_icon['xml'] . '" border=0></a>';
    $xml_printlink = '<a href="' . GetDownloadLink($xml_file_id, $xml_filename, 2) . '">' . htmlReady($xml_filename) . '</a>';
    $xml_printdesc = _("XML-Daten");
    $xml_printcontent = _("In dieser Datei sind die Daten als XML-Tags gespeichert. Diese Tags können mit einem XSLT-Script verarbeitet werden.") . "<br>";

    $xslt_printimage = '<a href="' . GetDownloadLink( $xslt_files[$choose]['file'], $xslt_files[$choose]['name'] . '.xsl', 3). '"><img src="'.$GLOBALS['ASSETS_URL'].'images/' . $export_icon['xslt'] . '" border=0></a>';
    $xslt_printlink = '<a href="'.GetDownloadLink( $xslt_files[$choose]['file'], $xslt_files[$choose]['name'] . '.xsl', 3).'">' . $xslt_files[$choose]['name'] . '.xsl</a>';
    $xslt_printdesc = _("XSLT-Datei");
    $xslt_printcontent = _("Dies ist das XSLT-Script zur Konvertierung der Daten. Klicken Sie auf den Dateinamen, um die Datei zu &ouml;ffnen.") . "<br>";

    $export_pagecontent .= "";
    $export_pagecontent .= "<input type=\"hidden\" name=\"page\" value=\"3\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"choose\" value=\"" . htmlReady($choose) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"format\" value=\"" . htmlReady($format) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"o_mode\" value=\"" . htmlReady($o_mode) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"ex_type\" value=\"" . htmlReady($ex_type) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"ex_sem\" value=\"" . htmlReady($ex_sem) . "\">";
    foreach(array_keys($ex_sem_class) as $semclassid){
        $export_pagecontent .= "<input type=\"hidden\" name=\"ex_sem_class[". htmlReady($semclassid) ."]\" value=\"1\">";
    }
    $export_pagecontent .= "<input type=\"hidden\" name=\"range_id\" value=\"" . htmlReady($range_id) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"xml_file_id\" value=\"" . htmlReady($xml_file_id) . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"xslt_filename\" value=\"" . htmlReady($xslt_filename) . "\">";

    $export_weiter_button = "<center>" . Button::create('<< ' . _('Zurück'), 'back') . "&nbsp;";
    if ($XSLT_ENABLE)
    {
        $export_pagecontent .= _("Um die Daten mit dem installierten XSLT-Prozessor in das gew&uuml;nschte Format zu bringen, klicken Sie bitte auf 'weiter'") . "<br><br>";
        $export_weiter_button .= LinkButton::create(_('Weiter') . ' >>', '#', array('name' => 'next'));
    } else {
        $export_pagecontent .= "<br><br><br>";
    }

    $export_weiter_button .= "</center></form>";

    $infobox = array    (
    array ("kategorie"  => _("Information:"),
        "eintrag" => array  (
                        array ( "icon" => "icons/16/black/info.png",
                                "text"  => sprintf(_("Diese Seite bereitet die Datenausgabe vor. %s Schritt 3/3 %s"), "<br><i>", "</i>")
                             )
                        )
        )
    );
    $link = "<a href=\"./test.xml"."\">";
    $infobox[1]["kategorie"] = _("Aktionen:");

    $infobox[1]["eintrag"][] = array (
        "icon" => "icons/16/black/download.png" ,
        "text"  => _("Sie können sich die XML-Daten und das XSLT-Skript herunterladen.")
    );

    if ($XSLT_ENABLE) {
        $infobox[1]["eintrag"][] = array (  "icon" => "icons/16/black/info.png" ,
                                    "text"  => _("Wenn Sie auf 'weiter' klicken, wird mit dem installierten XSLT-Prozessor die Ausgabedatei erzeugt.")
                                );
    }
}
