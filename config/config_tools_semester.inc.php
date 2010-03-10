<?
/**
* config_tools_semester.inc.php
*
* create some constants for semester data
*
* @access       public
* @package      studip_core
* @modulegroup  config
* @module       config_tools_semester.inc.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// config_tools_semester.inc.php
// hier werden ein paar Semester-Konstanten errechnet
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>,
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

global
  $semester,
  $all_semester,

  $SEM_BEGINN,
  $SEM_BEGINN_NEXT,
  $SEM_ENDE,
  $SEM_ENDE_NEXT,
  $SEM_ID,
  $SEM_ID_NEXT,
  $SEM_NAME,
  $SEM_NAME_NEXT,
  $VORLES_BEGINN,
  $VORLES_BEGINN_NEXT,
  $VORLES_ENDE,
  $VORLES_ENDE_NEXT;

//Checken ob es sich um vergangene Semester handelt + checken, welches das aktuelle Semester ist und Daten daraus verwenden
require_once("lib/classes/SemesterData.class.php");
$semester = new SemesterData;
$all_semester = $semester->getAllSemesterData();
for ($i=0; $i < sizeof($all_semester); $i++)
    {
    if (($all_semester[$i]["beginn"] < time()) && ($all_semester[$i]["ende"] >time()))
        {
        $VORLES_BEGINN=$all_semester[$i]["vorles_beginn"];
        $VORLES_ENDE=$all_semester[$i]["vorles_ende"];
        $SEM_BEGINN=$all_semester[$i]["beginn"];
        $SEM_ENDE=$all_semester[$i]["ende"];
        $SEM_NAME=$all_semester[$i]["name"];
        $SEM_ID=$i;
        if ($i<sizeof ($all_semester))
            {
            $VORLES_BEGINN_NEXT=$all_semester[$i+1]["vorles_beginn"];
            $VORLES_ENDE_NEXT=$all_semester[$i+1]["vorles_ende"];
            $SEM_BEGINN_NEXT=$all_semester[$i+1]["beginn"];
            $SEM_ENDE_NEXT=$all_semester[$i+1]["ende"];
            $SEM_NAME_NEXT=$all_semester[$i+1]["name"];
            $SEM_ID_NEXT=$i+1;
            }
        }
    }
?>
