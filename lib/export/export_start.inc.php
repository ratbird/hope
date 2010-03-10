<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* Export-Subfile that contains the first page of the export-module.
*
* This file shows the first page of the export-module where the type of export and the institute can be chosen.
*
* @author       Arne Schroeder <schroeder@data.quest.de>
* @access       public
* @modulegroup      export_modules
* @module       export_start
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


require_once ("config.inc.php");   // Stud.IP - Konfigurationsdatei
require_once ("$PATH_EXPORT/export_xslt_vars.inc.php");   // XSLT-Variablen
require_once ("config_tools_semester.inc.php");   // Checken des aktuellen Semesters
require_once ("lib/classes/SemesterData.class.php");   // Checken des aktuellen Semesters

    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $semester = new SemesterData;

$export_pagename = _("Datenexport - Startseite");

    $export_info = _("Bitte wählen Sie Datenart und Einrichtung.") . "<br>";

    $export_pagecontent .= "<form method=\"POST\" action=\"" . $PHP_SELF . "\">";

    $export_pagecontent .="<br><b><font size=\"-1\">". _("Bitte w&auml;hlen Sie eine Einrichtung: ") .  "</font></b><br /><select name=\"range_id\">";

/*  if ($auth->auth['perm'] == "root")
    {
        $db->query("SELECT Institut_id, Name, 1 AS is_fak  FROM Institute WHERE Institut_id=fakultaets_id ORDER BY Name");
    }
    elseif ($auth->auth['perm'] == "admin")
    {
        $db->query("SELECT a.Institut_id,Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
                    WHERE a.user_id='$user->id' AND a.inst_perms='admin' ORDER BY is_fak,Name");
    }
    else
    {
        $db->query("SELECT a.Institut_id,Name FROM user_inst a LEFT JOIN Institute b USING (Institut_id) WHERE inst_perms IN('tutor','dozent') AND user_id='$user->id'");
    }

    $export_pagecontent .= sprintf ("<option value=\"NULL\">-- " . _("bitte Einrichtung ausw&auml;hlen") . " --</option>\n");
    while ($db->next_record())
    {
        $export_pagecontent .= sprintf ("<option value=\"%s\" style=\"%s\">%s </option>\n", $db->f("Institut_id"),($db->f("is_fak") ? "font-weight:bold;" : ""), htmlReady(my_substr($db->f("Name"), 0, 60)));
        if ($db->f("is_fak"))
        {
            $db2->query("SELECT Institut_id, Name FROM Institute WHERE fakultaets_id='" .$db->f("Institut_id") . "' AND institut_id!='" .$db->f("Institut_id") . "'");
            while ($db2->next_record())
            {
                $export_pagecontent .= sprintf("<option value=\"%s\">&nbsp;&nbsp;&nbsp;&nbsp;%s </option>\n", $db2->f("Institut_id"), htmlReady(my_substr($db2->f("Name"), 0, 60)));
            }
        }
    } /**/
    $db->query("SELECT Institut_id, Name, fakultaets_id FROM Institute WHERE fakultaets_id = Institut_id ORDER BY Name");

    while ($db->next_record())
    {
        $export_pagecontent .= "<option style=\"font-weight:bold;\" ";
        if ($range_id == $db->f("fakultaets_id"))
            $export_pagecontent .= " selected";
        $export_pagecontent .= " value=\"" . $db->f("Institut_id") . "\">" . htmlReady(my_substr($db->f("Name"), 0, 60)) . "</option>";
        if ($db->f("fakultaets_id") == $db->f("Institut_id"))
        {
            $db2->query("SELECT Institut_id, Name FROM Institute WHERE fakultaets_id='" .$db->f("Institut_id") . "' AND institut_id!='" .$db->f("Institut_id") . "' ORDER BY Name");
            while ($db2->next_record())
            {
                $export_pagecontent .= sprintf("<option value=\"%s\"", $db2->f("Institut_id"));
                if ( ( $range_id == $db2->f("Institut_id") ) AND( $range_id != $db->f("Institut_id")))
                    $export_pagecontent .= " selected";
                $export_pagecontent .= sprintf(">&nbsp;&nbsp;&nbsp;&nbsp;%s </option>\n", htmlReady(my_substr($db2->f("Name"), 0, 60)));
            }
        }
    } /**/
    if ($perm->have_perm("root"))
        $export_pagecontent .= "<option style=\"font-weight:bold;\" value=\"root\">Alle Einrichtungen";
    $export_pagecontent .= "</select><br><br>";

    $export_pagecontent .= "<b><font size=\"-1\">"._("Art der auszugebenden Daten: ") .  "</font></b><br /><select name=\"ex_type\">";

    $export_pagecontent .= "<option";
    if ($ex_type=="veranstaltung")
        $export_pagecontent .= " selected";
    $export_pagecontent .= " value=\"veranstaltung\">" . _("Veranstaltungsdaten") .  "</option>";

    $export_pagecontent .= "<option";
    if ($ex_type=="person")
        $export_pagecontent .= " selected";
    $export_pagecontent .= " value=\"person\">" . _("MitarbeiterInnendaten") .  "</option>";

/*  $export_pagecontent .= "<option";
    if ($ex_type=="forschung")
        $export_pagecontent .= " selected";
    $export_pagecontent .= " value=\"forschung\">" . _("Forschungsberichte") .  "</option>";/**/

    $export_pagecontent .= "</select><br><br><br><br>";

    $export_pagecontent .="<b><font size=\"-1\">". _("Aus welchem Semester sollen die Daten exportiert werden (f&uuml;r Veranstaltungsexport): ") .  "</font></b><br /><select name=\"ex_sem\">";
    $export_pagecontent .= "<option value=\"all\">" . _("Alle Semester") . "</option>";
    $all_semester = $semester->getAllSemesterData();
    reset($all_semester);
    while (list($key, $val) = each($all_semester))
    {
        $export_pagecontent .= "<option";
        if (($ex_sem == $key) OR
            (($ex_sem == "") AND ($key == $SEM_ID)))
            $export_pagecontent .= " selected";
        $export_pagecontent .= " value=\"" . $key . "\">" . $val["name"] . "</option>";
    }
    $export_pagecontent .= "</select><br><br>";

    $export_pagecontent .="<b><font size=\"-1\">". _("Welche Arten von Veranstaltungen sollen exportiert werden? ") .  "</font></b><br />";

    if(!count($ex_sem_class)){
        $ex_sem_class[1] = 1;
    }
    foreach (SeminarCategories::getAll() as $sem_class)
    {
        if(!$sem_class->studygroup_mode){
            $export_pagecontent .= "<input type=\"checkbox\" name=\"ex_sem_class[$sem_class->id]\" value=\"1\"";
            if (isset($ex_sem_class[$sem_class->id])) $export_pagecontent .= " checked";
            $export_pagecontent .= ">&nbsp;" . htmlready($sem_class->name) . "&nbsp;&nbsp;";
        }
    }

    $export_pagecontent .= "<input type=\"hidden\" name=\"o_mode\" value=\"choose\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"xslt_filename\" value=\"" . $xslt_filename . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"choose\" value=\"" . $choose . "\">";
    $export_pagecontent .= "<input type=\"hidden\" name=\"format\" value=\"" . $format . "\">";

    $export_weiter_button = "<center><input type=\"IMAGE\"" . makeButton("weiter", "src") . " name=\"\"></center></form>";
        $infobox = array    (
        array ("kategorie"  => _("Information:"),
            "eintrag" => array  (
                            array ( "icon" => "ausruf_small.gif",
                                    "text"  => sprintf(_("Dies ist das Stud.IP-Exportmodul. Mit diesem Modul k&ouml;nnen Sie Daten in den folgenden Formaten ausgeben: %s."), implode($output_formats, ", "))
                                 )
                            )
            )
        );
        {
            $infobox[1]["kategorie"] = _("Aktionen:");
                $infobox[1]["eintrag"][] = array (  "icon" => "forumrot.gif" ,
                                            "text"  => sprintf(_("W&auml;hlen Sie die Art der Daten, die Sie exportieren wollen, und die Einrichtung, aus der die Daten gelesen werden sollen. Klicken Sie dann auf 'weiter.'"), $link2, "</a>")
                                        );
        }
?>
