<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Export-Subfile that calls the FOP.
*
* This file calls the Formatting-Objects-Processor to generate PDF-Files.
*
* @author       Arne Schroeder <schroeder@data.quest.de>
* @access       public
* @modulegroup      export_modules
* @module       export_run_fop
* @package      Export
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// export_run_fop.inc.php
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

$FOP_ENABLE = true;

if (($o_mode != "direct") AND ($o_mode != "passthrough"))
    $perm->check("tutor");

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
function CheckParamRUN_FOP()
{
global $XSLT_ENABLE, $ex_type, $o_mode, $xml_file_id, $page, $format, $output_formats, $choose, $xslt_files, $export_error, $export_error_num, $export_o_modes, $export_ex_types, $result_file;

    if ($result_file == "")
    {
        $export_error .= "<b>" . _("Fehlende Parameter!") . "</b><br>";
        $export_error_num++;
        return false;
    }


    return true;
}


if (!CheckParamRUN_FOP())
{
    $infobox = array(
    array ("kategorie"  => _("Information:"),
        "eintrag" => array  (
                        array ( "icon" => "icons/16/black/info.png",
                                "text"  => _("Die Parametern, mit denen diese Seite aufgerufen wurde, sind fehlerhaft.")
                             )
                        )
        )
    );
}
elseif ($FOP_ENABLE != true)
{
    $infobox[1]["eintrag"][] = array (  'icon' => "icons/16/black/admin.png",
                                "text"  => sprintf(_("Die Erweiterung zum Erzeugen von PDF-Dateien ist nicht aktiviert, es konnten daher nur Formatting Objects erzeugt werden."))
                            );
}
else
{
    $export_pagename = _("Download der PDF-Datei");

    // Process the document
    escapeshellcmd ( $result_file );
    escapeshellcmd ( $TMP_PATH );
    $pdf_file = md5(uniqid(rand())) .".pdf";

    $str = "$FOP_SH_CALL $TMP_PATH/export/$result_file $TMP_PATH/export/$pdf_file ";

    $out = exec( $str );
    if ($out == '')
        $out = $str;
        if (file_exists($TMP_PATH.'/export/'.$pdf_file))
        {
            $link2 = '<a href="'. GetDownloadLink($pdf_file, $xslt_filename . '.pdf', 2).'">';
            $export_pagecontent = '<table cellspacing="0" cellpadding="0" border="0" width="40%"><tr align="center"><td>';
            $export_pagecontent .= '<b>' . _("Ausgabe-Datei: ") . '</b>';
            $export_pagecontent .= '</td><td>' . $link2 . $xslt_filename . '.pdf</a>';
//          $export_pagecontent .= "</td><td>" . $result_file . "</td></tr><tr><td colspan=\"2\">";
//          $export_pagecontent .= "&nbsp;&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;" . $link1 . _("Datei &ouml;ffnen") . "</a></td></tr><tr><td colspan=\"2\">";
//          $export_pagecontent .= "&nbsp;&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;" . $link2 . _("Datei herunterladen") . "</a></td></tr>";
            $export_pagecontent .= '</td></tr></table><br>';

            $result_printimage = ' <a href="' . GetDownloadLink($pdf_file, $xslt_filename . '.pdf', 2). '"> <img class="text-top" src="'.$GLOBALS['ASSETS_URL'].'images/' . $export_icon['pdf'] . '"></a>';
            $result_printlink = ' <a href="'. GetDownloadLink($pdf_file, $xslt_filename . '.pdf', 2).   '" class="tree"> ' . $xslt_filename . '.pdf</a>';
            $result_printdesc = _("PDF-Datei");
            $result_printcontent = _("Dies ist die fertige PDF-Datei.") . '<br>';
        }
        else
        {
            unset($result_printimage);
            unset($result_printlink);
            unset($result_printdesc);
            unset($result_printcontent);
            unset($export_info);
            unset($export_msg);
            $export_pagecontent = "";
            $export_error = "<b>" . sprintf(_("Bei der Erzeugung der PDF-Datei ist ein Fehler aufgetreten. <br>Fehlermeldung: %s <br>Datei: %s"), $out, $pdf_file) . "</b><br>";
            $export_error_num ++;
        }

        $xml_printimage = ' <a href="'. GetDownloadLink($xml_file_id, $xml_filename, 2). '"><img class="text-top" src="'.$GLOBALS['ASSETS_URL'].'images/' . $export_icon['xml'] . '"></a>';
        $xml_printlink = ' <a href="'. GetDownloadLink($xml_file_id, $xml_filename, 2) .  '" class="tree"> ' . $xml_filename . '</a>';
        $xml_printdesc = _("XML-Daten");
        $xml_printcontent = _("In dieser Datei sind die Daten als XML-Tags gespeichert. Diese Tags k&ouml;nnen mit einem XSLT-Script verarbeitet werden.") . "<br>";

        $xslt_printimage = '<a href="'. GetDownloadLink($result_file,$xslt_filename .'.'. $format,2) . '"> <img class="text-top" src="'.$GLOBALS['ASSETS_URL'].'images/' . $export_icon[$format] . '"></a>';
        $xslt_printlink = '<a href="'. GetDownloadLink($result_file,$xslt_filename .'.'. $format,2) . '" class="tree">' . $xslt_filename .'.'. $format . '</a>';
        $xslt_printdesc = _("Formatting-Objects-Datei");
        $xslt_printcontent = _("In dieser Datei sind die Formatting Objects zur Erzeugung der PDF-Datei gespeichert.") . "<br>";


        $infobox = array    (
        array ("kategorie"  => _("Information:"),
            "eintrag" => array  (
                            array ( "icon" => "icons/16/black/info.png",
                                    "text"  => $xslt_info
                                 )
                            )
            )
        );
        {
            $infobox[1]["kategorie"] = _("Aktionen:");
                $infobox[1]["eintrag"][] = array (  'icon' => "icons/16/black/download.png" ,
                                            "text"  => sprintf(_("Um die Ausgabe-Datei herunterzuladen, klicken Sie %s hier %s."), $link2, "</a>")
                                        );
        }


}
?>
