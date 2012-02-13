<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Export-mainfile. Calls the submodules.
*
* This file checks the given parameters and calls the requested
* submodules for export in formats xml, rtf, html, pdf...
*
* @author       Arne Schroeder <schroeder@data.quest.de>
* @access       public
* @modulegroup      export_modules
* @module       export
* @package      Export
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// export.php
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

require '../lib/bootstrap.php';

ob_start();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

if (($o_mode != "direct") AND ($o_mode != "passthrough"))
{
    $perm->check("tutor");
    include ('lib/seminar_open.php'); // initialise Stud.IP-Session
}

//$i_page = "meine_seminare.php";
//$EXPORT_ENABLE = TRUE;
//$PATH_EXPORT = "export";
// -- here you have to put initialisations for the current page

require_once ('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once ('lib/msg.inc.php');
require_once ('lib/classes/DataFieldEntry.class.php');

require_once ('config.inc.php');

PageLayout::setHelpKeyword("Basis.Export");

if ($EXPORT_ENABLE)
{
    $ex_sem_class = Request::intArray('ex_sem_class');

    // Zurueckbutton benutzt?
    if (Request::submitted('back'))
    {
        if ($o_mode == "choose")
        {
            if ($page == 4)
            {
                if ($skip_page_3)
                    $page = 1;
                else
                    $page = 2;
            }
            elseif ($page>1)
                $page = $page-2;
            else
            {
                unset($xml_file_id);
                unset($page);
                $o_mode= "start";
            }
        }
    }

    if ((!isset($range_id) AND !isset($xml_file_id) AND !isset($o_mode) AND !isset($ex_type)) OR ($o_mode == "start"))
    {
        include($PATH_EXPORT . "/export_start.inc.php");
        $start_done = true;
    }

    if (($page==2) AND $XSLT_ENABLE AND $skip_page_3)
        $page=3;

    //Exportmodul einbinden
    if (/*($xml_file_id != "") AND */($page != 3) AND ($o_mode == "choose") AND ($export_error_num < 1))
    {
        include($PATH_EXPORT . "/export_choose_xslt.inc.php");
        if ($export_error_num < 1)
            $xslt_choose_done = true;
    }

    if (($range_id != "") AND ($xml_file_id == "") AND ($o_mode != "start") AND (($o_mode != "choose") OR ($page == 3)))
    {
        include($PATH_EXPORT . "/export_xml.inc.php");
        if ($export_error_num < 1)
            $xml_output_done = true;
    }

    if ( ($choose != "") AND ($format != "") AND ($format != "xml") AND ($XSLT_ENABLE) AND ($export_error_num==0) AND
        ( ($o_mode == "processor") OR ($o_mode == "passthrough") OR ($page == 3) ) )
    {
        include($PATH_EXPORT . "/export_run_xslt.inc.php");
        if ($export_error_num < 1)
            $xslt_process_done = true;
    }

    if (($export_error_num < 1) AND ($xslt_process_done) AND ($format == "fo"))
        include($PATH_EXPORT . "/export_run_fop.inc.php");

    if (($export_error_num < 1) AND (!$start_done) AND ((!$xml_output_done) OR ($o_mode != "file")) AND (!$xslt_choose_done) AND (!$xslt_process_done))
    {
        $export_pagename = "Exportmodul - Fehler!";
        $export_error = _("Fehlerhafter Seitenaufruf");
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

    include($PATH_EXPORT . "/export_view.inc.php");
}
else
{
    //TODO: Globales Fehlertemplate erzeugen und nur die Fehlermeldung übergeben
    // Start of Output
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   // Output of Stud.IP head
    parse_window ("error§" . _("Das Exportmodul ist nicht eingebunden. Damit Daten im XML-Format exportiert werden k&ouml;nnen, muss das Exportmodul in den Systemeinstellungen freigeschaltet werden. Wenden Sie sich bitte an die Administratoren."), "§",
                _("Exportmodul nicht eingebunden"));
    include ('lib/include/html_end.inc.php');
}
page_close();
?>
