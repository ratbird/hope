<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Export-Subfile that calls the XSLT-Process.
*
* This file calls the XSLT-Process to convert XML-Files into other file-formats.
*
* @author       Arne Schroeder <schroeder@data.quest.de>
* @access       public
* @modulegroup      export_modules
* @module       export_run_xslt
* @package      Export
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// export_run_xslt.inc.php
// Integration of xslt-processor
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

if (version_compare(PHP_VERSION,'5','>=') && extension_loaded('xsl')) require_once('vendor/php4-to-php5/xslt-php4-to-php5.php');

if (($o_mode != "direct") AND ($o_mode != "passthrough"))
    $perm->check("tutor");

require_once ($GLOBALS['PATH_EXPORT'] . '/export_xslt_vars.inc.php');   // Liste der XSLT-Skripts
require_once('lib/datei.inc.php');

/**
* Checks given parameters
*
* This function checks the given parameters. If some are missing
* it returns false and adds a warning to $export_error.
*
* @access   public
* @return       boolean
*/
function CheckParamRUN()
{
global $XSLT_ENABLE, $ex_type, $o_mode, $xml_file_id, $page, $format, $output_formats, $choose, $xslt_files, $export_error, $export_error_num, $export_o_modes, $export_ex_types;

    if (($xml_file_id == "")
            OR ($xslt_files[$choose]["file"] == "")
            OR ($XSLT_ENABLE != true))
    {
        $export_error .= "<b>" . _("Fehlende Parameter!") . "</b><br>";
        $export_error_num++;
        return false;
    }

    if (!in_array($ex_type, $export_ex_types)
            OR (!in_array($o_mode,  $export_o_modes))
            OR (!$xslt_files[$choose][$format]))
    {
        $export_error .= "<b>" . _("Unzulässiger Seitenaufruf!") . "</b><br>";
        $export_error_num++;
        return false;
    }

    return true;
}


$export_pagename = _("Download der Ausgabedatei");
$xslt_process = false;
$xslt_filename = strlen($_REQUEST['xslt_filename']) ? basename(stripslashes($_REQUEST['xslt_filename'])) : $xslt_filename_default;

if (!CheckParamRUN())
{
    $infobox = array(
    array ("kategorie"  => _("Information:"),
        "eintrag" => array  (
                        array ( "icon" => "icons/16/black/info.png",
                                "text"  => _("Die Parameter, mit denen diese Seite aufgerufen wurde, sind fehlerhaft.")
                             )
                        )
        )
    );
}
else
{
    // Allocate a new XSLT processor
    $xh = xslt_create();

    // Process the document
    $result_file = md5(uniqid(rand())) . "." . $format;
    $result = "" . $TMP_PATH . "/export/" . $result_file;
    $xml_process_file = "" . $TMP_PATH . "/export/" . $xml_file_id;
    $xslt_process_file = $GLOBALS['STUDIP_BASE_PATH'] . '/' . $PATH_EXPORT . "/" . $xslt_files[$choose]["file"];

    if (xslt_process($xh, "file://$xml_process_file" , "file://$xslt_process_file", "file://$result") && ($o_mode != "passthrough")) {
        $export_msg .= sprintf(_("Die Daten wurden erfolgreich konvertiert. %s Sie k&ouml;nnen die Ausgabedatei jetzt herunterladen. %s"), "<br>", "<br>");
        $xslt_info = _("Die Daten sind nun im gew&auml;hlten Format verf&uuml;gbar.");
        $xslt_process = true;
        $link1 = "<a href=\"" . $TMP_PATH . "/" . $result_file . "\">";
        $link2 = '<a href="'. GetDownloadLink($result_file, $xslt_filename .'.'. $format, 2) . '">';

    } elseif ($o_mode != "passthrough") {

        $export_error .= sprintf(_("Bei der Konvertierung ist ein Fehler aufgetreten. %sDer XSLT-Prozessor meldet den Fehler Nr. %s: %s %s"), "<br>", xslt_errno($xh), xslt_error($xh), "<br>");
        $xslt_info = _("Bei der Konvertierung ist ein Fehler aufgetreten.");
        $xslt_process = false;
        $export_error_num++;
    }

    xslt_free($xh);


    if ($o_mode == "passthrough")
    {
        header("Location: " . GetDownloadLink($result_file, $xslt_filename .'.'. $format, 2));
        unlink( $TMP_PATH . "/export/" . $xml_file_id);
    } else {

        $export_weiter_button = "<form method=\"POST\" action=\"" . URLHelper::getURL() . "\">";
        $export_weiter_button .= CSRFProtection::tokenTag();
        $export_weiter_button .= "<input type=\"hidden\" name=\"page\" value=\"4\">";
        $export_weiter_button .= "<input type=\"hidden\" name=\"choose\" value=\"" . htmlReady($choose) . "\">";
        $export_weiter_button .= "<input type=\"hidden\" name=\"format\" value=\"" . htmlReady($format) . "\">";
        $export_weiter_button .= "<input type=\"hidden\" name=\"o_mode\" value=\"" . htmlReady($o_mode) . "\">";
        $export_weiter_button .= "<input type=\"hidden\" name=\"ex_type\" value=\"" . htmlReady($ex_type) . "\">";
        $export_pagecontent .= "<input type=\"hidden\" name=\"ex_sem\" value=\"" . htmlReady($ex_sem) . "\">";
        foreach(array_keys($ex_sem_class) as $semclassid){
            $export_pagecontent .= "<input type=\"hidden\" name=\"ex_sem_class[". htmlReady($semclassid) ."]\" value=\"1\">";
        }
        $export_weiter_button .= "<input type=\"hidden\" name=\"range_id\" value=\"" . htmlReady($range_id) . "\">";
        $export_weiter_button .= "<input type=\"hidden\" name=\"xml_file_id\" value=\"" . htmlReady($xml_file_id) . "\">";
        $export_weiter_button .= "<input type=\"hidden\" name=\"xslt_filename\" value=\"" . htmlReady($xslt_filename) . "\">";
        if (Request::option('jump'))
            $export_weiter_button .= '<center>' . LinkButton::create('<< ' . _('Zurück'), URLHelper::getURL('seminar_main.php', array('auswahl' => $range_id, 'redirect_to' => $jump))) . "<br>";
        else
            $export_weiter_button .= "<center>" . Button::create('<< ' . _('Zurück'), 'back') . "<br>";
        $export_weiter_button .= "</center></form>";

        if ($xslt_process) {
            $export_pagecontent .= "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td width=\"40%\">";
            $export_pagecontent .= "&nbsp; <b>" . _("Ausgabe-Datei: ") . "</b>";
            $export_pagecontent .= "</td><td>" . $link2 . htmlReady($xslt_filename) . "." . htmlReady($format) . "</a>";
            $export_pagecontent .= "</td></tr></table></center><br>";
        }


        $xml_printimage = ' <a href="' . GetDownloadLink($xml_file_id, $xml_filename, 2) . '" target="_blank"><img class="text-top" src="'.$GLOBALS['ASSETS_URL'].'images/' . $export_icon['xml'] . '"></a>';
        $xml_printlink = ' <a href="'. GetDownloadLink($xml_file_id, $xml_filename, 2) . '" class="tree">' . htmlReady($xml_filename) . '</a>';
        $xml_printdesc = _("XML-Daten");
        $xml_printcontent = _("In dieser Datei sind die Daten als XML-Tags gespeichert. Diese Tags können mit einem XSLT-Script verarbeitet werden.") . '<br>';

        $xslt_printimage = ' <a href="'. GetDownloadLink($xslt_files[$choose]['file'], $xslt_files[$choose]['name'].'.xsl', 3) . '"> <img class="text-top" src="'.$GLOBALS['ASSETS_URL'].'images/' . $export_icon['xslt'] . '"></a>';
        $xslt_printlink = ' <a href="' . GetDownloadLink($xslt_files[$choose]['file'], $xslt_files[$choose]['name'].'.xsl', 3) .  '" class="tree"> ' . $xslt_files[$choose]['name'] . '.xsl</a>';
        $xslt_printdesc = _("XSLT-Datei");
        $xslt_printcontent = _("Dies ist das XSLT-Script zur Konvertierung der Daten. Klicken Sie auf den Dateinamen, um die Datei zu öffnen.") . '<br>';

        if ($xslt_process) {
            $result_printimage = '<a href="'. GetDownloadLink($result_file, $xslt_filename .'.'. $format, 2) . '"> <img class="text-top" src="'.$GLOBALS['ASSETS_URL'].'images/' . $export_icon[$format] . '"></a>';
            $result_printlink = '<a href="'. GetDownloadLink($result_file, $xslt_filename .'.'. $format, 2) . '" class="tree"> ' . htmlReady($xslt_filename) . '.' . htmlReady($format) . '</a>';
            $result_printdesc = _("Ausgabe-Datei");
            $result_printcontent = _("Dies ist die fertige Ausgabedatei.") . "<br>";
        }


        $infobox = array(
            array(
                'kategorie'  => _("Information:"),
                'eintrag'    => array(
                    array(
                        'icon' => 'icons/16/black/info.png',
                        'text' => $xslt_info
                     )
                )
            )
        );

        if ($xslt_process) {
            $infobox[1]["kategorie"] = _("Aktionen:");
            $infobox[1]["eintrag"][] = array(
                'icon' => 'icons/16/black/download.png' ,
                'text' => sprintf(_("Um die Ausgabe-Datei herunterzuladen, klicken Sie %shier%s."), $link2, "</a>")
            );
        }

        include_once ("$PATH_EXPORT/oscar.php");
    }

}
