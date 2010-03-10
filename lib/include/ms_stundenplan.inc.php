<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* ms_stundenplan.php
*
* edit the settings for the personal schedule
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  views
* @module       ms_stundenplan.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ms_stundenplan.inc.php
// Settings fuer den Stundenplan einstellen
// Copyright (c) 2003 Stefan Suchi <suchi@data-quest.de>
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

//Variable registrieren
//$user->register("my_schedule_settings");

require_once ('config.inc.php');
require_once ('config_tools_semester.inc.php');
require_once 'lib/functions.php';
require_once ('lib/language.inc.php');

//vorgenommene Anpassungen der Ansicht in Uservariablen schreiben
if ($schedule_cmd=="change_view_insert") {
    $beginn_zeit = Request::int('beginn_zeit', 8);
    $ende_zeit = Request::int('ende_zeit', 19);
    if ($ende_zeit <= $beginn_zeit) {
        $ende_zeit = $beginn_zeit + 1;
    }
    $my_schedule_settings=array(
        "glb_start_time" => $beginn_zeit,
        "glb_end_time" => $ende_zeit,
        "glb_days" => array(
            "mo" => Request::option('mo', 'TRUE'),
            "di" => Request::option('di', 'TRUE'),
            "mi" => Request::option('mi', 'TRUE'),
            "do" => Request::option('do', 'TRUE'),
            "fr" => Request::option('fr', 'TRUE'),
            "sa" => Request::option('sa', ''),
            "so" => Request::option('so', '')
            ),
        "glb_sem" => Request::get('sem', $my_schedule_settings['glb_sem']),
        "glb_inst_id" => Request::option('institut_id', $my_schedule_settings['glb_inst_id']),
        "changed" => "TRUE",
        'hidden'=> $my_schedule_settings['hidden']
        );
}


//Funktion zum ueberpruefen der Einstellungen
function check_schedule_settings() {
    global $my_schedule_settings,$_my_admin_inst_id, $perm,$auth, $user, $SEM_NAME_NEXT, $SEM_NAME, $VORLES_ENDE;

    $db=new DB_Seminar;
    $semester = new SemesterData;
    $all_semester = $semester->getAllSemesterData();

    //Check, ob Semester, das ich gespeichert habe, inzwischen abgelaufen ist. Dann das naechste (Semesterferien) oder aktuelle Semester setzen.
    $k=0;
    foreach ($all_semester as $a) {
        if ($a["name"] == $my_schedule_settings["glb_sem"])
            $tmp_sem_nr=$k;
        $k++;
    }

    if (time() >$all_semester[$tmp_sem_nr]["vorles_ende"])
        if (time() >$VORLES_ENDE)
            $my_schedule_settings["glb_sem"]=$SEM_NAME_NEXT;
        else
            $my_schedule_settings["glb_sem"]=$SEM_NAME;

    //Check, ob aktuelles Semester gespeichert ist. F&uuml;r einfacheres und eindeutiges Handling wird dieses Setting dann geloescht (dh. es wird IMMER das aktuelle Semester gewaehlt!)
    if ($my_schedule_settings["glb_sem"]==$SEM_NAME)
        $my_schedule_settings["glb_sem"]='';

    /*
    //Check, ob ich noch in dem Institut Admin wo ich es sein soll
    if (($my_schedule_settings["glb_inst_id"]) && (!$perm->have_perm("root"))) {
        $db->query("SELECT institut_id FROM user_inst  WHERE user_id = '".$user->id."' AND institut_id ='".$my_schedule_settings["glb_inst_id"]."' AND inst_perms = 'admin' ");
        if (!$db->num_rows())
            $my_schedule_settings["glb_inst_id"]='';
        }

    //Wenn ein Admin sich den Stundenplan anschaut und wird sein Institut eingetragen. Hat er mehrere, ist das erste im Alphabet default
    if (!$my_schedule_settings["glb_inst_id"]) {
        if ($perm->have_perm("admin")) {
            $db->query("SELECT Institute.Institut_id FROM Institute LEFT JOIN user_inst USING(Institut_id) WHERE user_id = '".$user->id."' AND inst_perms = 'admin' ORDER BY Name");
            $db->next_record();
            $my_schedule_settings["glb_inst_id"]=$db->f("Institut_id");
            }
        }
    */

    //Admins bekommen das Institut, dass sie auf meine_seminare ausgewählt haben
    if ($auth->auth['perm'] == 'admin'){
        $my_schedule_settings["glb_inst_id"] = $_my_admin_inst_id;
    }

    }


//Anpassen der Ansicht
function change_schedule_view() {
    global $my_schedule_settings, $PHP_SELF, $SEM_NAME, $SEM_NAME_NEXT, $VORLES_ENDE, $perm,$auth, $user;

    $db=new DB_Seminar;
    $cssSw=new cssClassSwitcher;
    $semester = new SemesterData;
    $all_semester = $semester->getAllSemesterData();

    ?>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
        <tr>
            <td class="blank" colspan=2>&nbsp;
            </td>
        </tr>
        <tr>

            <td class="blank" width="100%" colspan="2" align="center">
            <blockquote>
                <font size="-1"><b><?print _("Hier k&ouml;nnen Sie sie Ansicht ihres pers&ouml;nlichen Stundenplans nach Ihren Vorstellungen anpassen.")."<br>"._("Sie k&ouml;nnen den Zeitraum, den der Stundenplan umfasst, und die Tage, die der Stundenplan anzeigt, bestimmen.");?>
            </blockquote>
            <form method="POST" action="<? echo $PHP_SELF ?>?schedule_cmd=change_view_insert">
            <table width="70%" align="center" cellpadding=8 cellspacing=0 border=0>
                <tr>
                    <th width="50%" align="center"><?=_("Option")?></th>
                    <th align="center"><?=_("Auswahl")?></th>
                </tr>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><?print _("angezeigter Zeitraum");?></font>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <font size="-1">&nbsp;<?=_("Anfangszeit:");?>&nbsp; </font>
                        <?
                        echo "<select name=\"beginn_zeit\">";
                            for ($i=0; $i<=23; $i++)
                                {
                                if ($i==$my_schedule_settings["glb_start_time"])
                                    {
                                    echo "<option selected value=".$i.">";
                                    if ($i<10)  echo "0".$i.":00";
                                    else echo $i.":00";
                                    echo "</option>";
                                    }
                                    else
                                        {
                                    echo "<option value=".$i.">";
                                    if ($i<10)  echo "0".$i.":00";
                                    else echo $i.":00";
                                    echo "</option>";
                                    }
                                }
                            echo "</select>";
                        ?>
                        <font size="-1">&nbsp;<?=_("Uhr"). "<br><br>&nbsp;"._("Endzeit:")?>&nbsp;</font>
                        <?
                        echo "<select name=\"ende_zeit\">";
                            for ($i=0; $i<=23; $i++)
                                {
                                if ($i==$my_schedule_settings["glb_end_time"])
                                    {
                                    echo "<option selected value=".$i.">";
                                    if ($i<10)  echo "0".$i.":00";
                                    else echo $i.":00";
                                    echo "</option>";
                                    }
                                    else
                                        {
                                    echo "<option value=".$i.">";
                                    if ($i<10)  echo "0".$i.":00";
                                    else echo $i.":00";
                                    echo "</option>";
                                    }
                                }
                            echo "</select>";
                        ?>
                        <font size="-1">&nbsp;<?=_("Uhr")?></font>
                    </td>
                </tr>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><?print _("angezeigte Wochentage");?></font>
                    </td>
                    <td <?=$cssSw->getFullClass()?>><font size="-1">
                        &nbsp; <input type="CHECKBOX" name="mo" value="true" <?if ($my_schedule_settings ["glb_days"]["mo"]) echo "checked"?>>&nbsp; <?=_("Montag")?> <br>
                        &nbsp; <input type="CHECKBOX" name="di" value="true" <?if ($my_schedule_settings ["glb_days"]["di"]) echo "checked"?>>&nbsp; <?=_("Dienstag")?> <br>
                        &nbsp; <input type="CHECKBOX" name="mi" value="true" <?if ($my_schedule_settings ["glb_days"]["mi"]) echo "checked"?>>&nbsp; <?=_("Mittwoch")?> <br>
                        &nbsp; <input type="CHECKBOX" name="do" value="true" <?if ($my_schedule_settings ["glb_days"]["do"]) echo "checked"?>>&nbsp; <?=_("Donnerstag")?> <br>
                        &nbsp; <input type="CHECKBOX" name="fr" value="true" <?if ($my_schedule_settings ["glb_days"]["fr"]) echo "checked"?>>&nbsp; <?=_("Freitag")?> <br>
                        &nbsp; <input type="CHECKBOX" name="sa" value="true" <?if ($my_schedule_settings ["glb_days"]["sa"]) echo "checked"?>>&nbsp; <?=_("Samstag")?> <br>
                        &nbsp; <input type="CHECKBOX" name="so" value="true" <?if ($my_schedule_settings ["glb_days"]["so"]) echo "checked"?>>&nbsp; <?=_("Sonntag")?> <br></font>
                    </td>
                </tr>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><?print _("angezeigtes Semester");?></font>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <?
                        echo "&nbsp; <select name=\"sem\">";
                        if (!$my_schedule_settings ["glb_sem"]) {
                            if (time() > $VORLES_ENDE) {
                                echo "<option>$SEM_NAME</option>";
                                echo "<option selected value=$SEM_NAME_NEXT>"._("aktuelles Semester")." ($SEM_NAME_NEXT)</option>";
                                $tmp_name=$SEM_NAME_NEXT;
                                }
                            else {
                                echo "<option selected value=$SEM_NAME>"._("aktuelles Semester")." ($SEM_NAME)</option>";
                                $tmp_name=$SEM_NAME;
                                }
                            }

                        foreach ($all_semester as $a) {
                            if ((time() < $a["vorles_ende"]) && ($a["name"] != $tmp_name)){
                                if ($my_schedule_settings ["glb_sem"] == $a["name"])
                                    echo "<option selected>".$a["name"]."</option>";
                                else
                                    echo "<option>".$a["name"]."</option>";
                                }
                            }
                        echo "</select>";
                        ?>
                    </td>
                </tr>
                <?
                if ($perm->have_perm("root")) {
                    $db->query("SELECT Institut_id, Name FROM Institute  ORDER BY Name");
                    if ($db->num_rows()>1) {
                    ?>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><?print _("angezeigte Einrichtung");?></font>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                    <?
                    echo "&nbsp; <select name=\"institut_id\">";
                    while ($db->next_record()) {
                        if ($my_schedule_settings ["glb_inst_id"] == $db->f("Institut_id"))
                            echo "<option selected value=\"".$db->f("Institut_id")."\">".my_substr($db->f("Name"), 0, 60)."</option>";
                        else
                            echo "<option value=\"".$db->f("Institut_id")."\">".my_substr($db->f("Name"), 0, 60)."</option>";
                        }
                    echo "</select>";
                    ?>
                    </td>
                </tr>
                    <?
                        }
                    }
                    ?>
                <tr <? $cssSw->switchClass() ?>>
                    <td  <?=$cssSw->getFullClass()?> colspan=2 align="middle">
                    <input type="IMAGE" <?=makeButton("uebernehmen", "src") ?> border=0 value="<?=_("&Auml;nderungen &uuml;bernehmen")?>"></font>&nbsp;
                    <input type="HIDDEN" name="view" value="Stundenplan">
                    </td>
                </tr>
                </form>
            </table>
            <br>
            <br>
            </td>
        </tr>
    </table>
    <?
    }

check_schedule_settings();
?>
