<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
stm_details.php - Detail-Uebersicht eines konkreten Studienmoduls
Copyright (C) 2006 André Noack <noack@data-quest.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA   02111-1307, USA.
*/

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($again && ($auth->auth["uid"] == "nobody"));

include ("lib/seminar_open.php"); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

require_once ("lib/msg.inc.php");
require_once ("lib/visual.inc.php"); // wir brauchen htmlReady
require_once ("lib/functions.php");
require_once ("lib/classes/StudipStmInstance.class.php");


include ("lib/include/html_head.inc.php"); // Output of html head

//Inits
$cssSw=new cssClassSwitcher;
$msg = array();
$send_from_search = (int)isset($send_from_search);
if (!preg_match('/^('.preg_quote($CANONICAL_RELATIVE_PATH_STUDIP,'/').')?([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9_?&=-]*)$/', $send_from_search_page)) $send_from_search_page = '';

if ($send_from_search) $back_msg =_("Zur&uuml;ck zur letzten Auswahl");

$stm_obj = new StudipStmInstance($_REQUEST['stm_instance_id']);
if (!$stm_obj->isNew()){
    if ($_REQUEST['cmd'] == 'do_enter'
        && $stm_obj->isAllowedToEnter($GLOBALS['user']->id)
        && !$stm_obj->isParticipant($GLOBALS['user']->id)
        && Request::submitted('ok')) {

        if (isset($_REQUEST['elgroup']) && is_array($stm_obj->el_struct[$_REQUEST['elgroup']])){
            $added = $stm_obj->addParticipant($GLOBALS['user']->id, $_REQUEST['elgroup'], $_REQUEST['sem_el']);
        }
        if ($added) $msg[] = array('msg', _("Ihre gewünschte Belegung wurde eingetragen."));
        else $msg[] = array('error', _("Ihre gewünschte Belegung konnte nicht eingetragen werden."));
    }

// Start of Output
    PageLayout::setTitle(_("Studienmodul:") . ' ' . $stm_obj->getValue('title') . " - " . _("Details"));
    include ("lib/include/header.php");  // Output of Stud.IP head
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
    <?
    if (count($msg)){
        echo '<tr><td class="blank" colspan="2">&nbsp;</td></tr>';
        parse_msg_array($msg, "blank", 2, false, false);
    }
    if ($_REQUEST['cmd'] == 'enter' && $stm_obj->isAllowedToEnter($GLOBALS['user']->id, true) && !$stm_obj->isParticipant($GLOBALS['user']->id)){
        $out = _("Sie haben sich entschieden dieses Modul zu belegen.");
        if ($stm_obj->getGroupCount() > 1) $out .= '<br>' . _("Für dieses Modul existieren verschiedene Ausprägungen. Bitte wählen Sie eine davon aus:");
        ?>
        <tr><td class="blank">
        <div style="margin:20px;font-size:10pt;">
        <form action="<? echo $PHP_SELF.'?cmd=do_enter&stm_instance_id='.$stm_obj->getId().'&send_from_search=1&send_from_search_page='.urlencode($send_from_search_page)?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <?=$out?>
        <br><br>
        <table style="font-size:10pt;"  border="0" cellpadding="2" cellspacing="0">
        <?foreach ($stm_obj->el_struct as $elgroup => $elements){
            $cssSw->switchClass();
            echo '<tr><td width="1%" class="'.$cssSw->getClass().'"><input type="radio" name="elgroup" value="'.$elgroup.'" ';
            if ($elgroup == 0) echo 'checked';
            echo '></td><td class="'.$cssSw->getClass().'" colspan="2"><b>' . ($elgroup + 1) .'. '._("Modulausprägung").'</b></td><td  class="'.$cssSw->getClass().'" >&nbsp;</td></tr>';
            foreach($elements as $e_id => $sem_ids){
                $c = 0;
                foreach ($sem_ids as $sem_id){
                    ++$c;
                    echo '<tr><td class="'.$cssSw->getClass().'" >&nbsp;</td>';
                    $style="";
                    if (count($sem_ids) > 1){
                        if ($c == 1) $style = "border-top: 1px solid;";
                        if ($c == count($sem_ids)) $style = "border-bottom: 1px solid;";
                        echo '<td width="1%" class="'.$cssSw->getClass().'" style="'.$style.'border-left: 1px solid;"><input type="radio" name="sem_el['.$e_id.']" value="'.$sem_id.'" ';
                        if ($c == 1) echo 'checked';
                        echo '></td>';
                        $style = 'style="'.$style.'border-right: 1px solid;"';
                    } else {
                        echo '<td  class="'.$cssSw->getClass().'" >&nbsp;</td>';
                    }
                        $element_id = $e_id.'-'.$stm_obj->getId().'-'.$sem_id;
                        echo '<td '.$style.' class="'.$cssSw->getClass().'" >'. htmlReady($stm_obj->elements[$element_id]->getValue('type_abbrev').': '. $stm_obj->elements[$element_id]->getValue('seminar_name')) .'</td><td  class="'.$cssSw->getClass().'" >&nbsp;</td></tr>';
                    }

            }
        }?>
        </table>
        <br>
        <div class="button-group">
        <?= Button::createAccept(_('Übernehmen'), 'ok')?>
        <?= Button::createCancel(_('Abbrechen'), 'cancel')?>
        </div>
        </form>
        </div></td></tr>
        <?
    }
    ?>
    <tr><td class="blank">
        &nbsp; <br>
        <table align="center" width="99%" border="0" cellpadding="2" cellspacing="0">
        <tr>
            <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp; <img src="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" width="25" height="10" border="0">
            </td>
            <td class="<? echo $cssSw->getClass() ?>" valign="top" colspan=2 valign="top" width="70%">
                <?
                printf ("<b>%s</b><br> ",( $stm_obj->getValue('id_number') ? htmlReady($stm_obj->getValue('id_number')).': ' : '' ) .htmlReady($stm_obj->getValue('title')));
                printf ("<font size=-1>%s</font>",htmlReady($stm_obj->getValue('sub_title')));
                ?>
            </td>
            <td class="steel1" width="26%" rowspan="7" valign="top">

            <? // Infobox


    $picture_tmp = "icons/16/black/info.png";

    $enter = false;
    /*if (!$perm->have_perm('dozent')){
        if ($stm_obj->isParticipant($GLOBALS['user']->id)){
            $picture_tmp = "pictures/haken.gif";
            $status_text = _("Sie belegen dieses Modul bereits.");
        } else if($stm_obj->isAllowedToEnter($GLOBALS['user']->id)){
            $enter = true;
            $status_text = _("Sie können dieses Modul belegen.");
        } else {
            $picture_tmp = "images/icons/16/red/decline.png";
            $status_text =  _("Sie können dieses Modul nicht belegen.")
                        . '<br>' . _("Sie erfüllen die Voraussetzungen nicht.");
        }

    } else {
        $status_text = _("Sie können dieses Modul nicht belegen.");
    }

    $infobox = array    (
        array   ("kategorie"    => _("Pers&ouml;nlicher Status:"),
            "eintrag" => array  (
                array ( "icon" => $picture_tmp,
                    "text"  => $status_text
                )
            )
        ),
    );
    */


if (1 || $back_msg || $info_msg || $enter) {
    $infobox[0]["kategorie"] = _("Aktionen:");
    if ($enter) {
        $infobox[0]["eintrag"][] = array (  "icon" => "icons/16/black/schedule.png" ,
                                    "text"  => "<a href=\"$PHP_SELF?cmd=enter&stm_instance_id=".$stm_obj->getId()."&send_from_search=1&send_from_search_page=".urlencode($send_from_search_page)."\">"._("Tragen Sie sich hier in das Modul ein"). "</a>"
                                );
    }
    if ($back_msg) {
        $infobox[0]["eintrag"][] = array (  "icon" => "icons/16/black/schedule.png" ,
                                    "text"  => "<a href=\"$send_from_search_page\">".$back_msg. "</a>"
                                );
    }
    if ($info_msg) {
        $infobox[0]["eintrag"][] = array (  "icon" => "icons/16/black/info.png" ,
                                    "text"  => $info_msg
                                );
    }
    if($stm_obj->isAllowedToEdit($user->id)){
        $infobox[0]["eintrag"][] = array (  "icon" => "icons/16/black/schedule.png" ,
                                    "text"  => "<a href=\"stm_instance_assi.php?sel_stm_form_1508068a50572e5faff81c27f7b3a72f=1&sel_stm_form_sel_".$stm_obj->getId()."_x=1\">"._("Bearbeiten Sie dieses Modul"). "</a>"
                                );
    }
}


if (!$stm_obj->getValue('complete')) {
    $infobox[count($infobox)]["kategorie"] = _("Information:");
    $infobox[count($infobox)-1]["eintrag"][] = array (  "icon" => "icons/16/grey/info-circle.png" ,
                                "text"  => _("Dieses Studienmodul ist noch unvollständig!")
                            );
}

// print the info_box

print_infobox ($infobox, "infobox/contract.jpg");

// ende Infobox

?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="45%">
                <?
                 printf ("<font size=-1><b>" . _("Modulverantwortlicher:") . "</b></font><br><font size=-1><a href=\"about.php?username=%s\">%s</a></font>",get_username($stm_obj->getValue('responsible')), htmlReady(get_fullname($stm_obj->getValue('responsible'))));
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="25%">
                <?
                printf ("<font size=-1><b>" . _("Semester:") . "</b></font><br><font size=-1>%s</font>",htmlReady($stm_obj->getValue('sem_name')));
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="45%">
                <?
                printf ("<font size=-1><b>" . _("Leistungspunkte:") . "</b></font><br><font size=-1>%s</font>",htmlReady($stm_obj->getValue('credits')));
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="25%">
                <?
                printf ("<font size=-1><b>" . _("Stud. Arbeitsaufwand:") . "</b></font><br><font size=-1>%s</font>", htmlReady($stm_obj->getValue('workload')));
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="45%">
                <?
                $homes = get_range_tree_path($stm_obj->getValue("homeinst"));
                if(is_array($homes)){
                    $home = join("\n", $homes);
                } else $home = $stm_obj->getValue('homeinst_name');
                printf ("<font size=-1><b>" . _("Anbietende Einrichtung:") . "</b></font><br><font size=-1><a href=\"institut_main.php?auswahl=%s\">%s</a></font>",$stm_obj->getValue("homeinst"), htmlReady($home,1,1));
                ?>
                </td>
                <td class="<? echo $cssSw->getClass() ?>" valign="top" width="25%">
                <?
                printf ("<font size=-1><b>" . _("Dauer:") . "</b></font><br><font size=-1>%s</font>", htmlReady($stm_obj->getValue('duration')));
                ?>
                </td>
            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan="2" valign="top">
                <?
                 printf ("<font size=-1><b>" . _("Lernziele:") . "</b></font><br><font size=-1>%s</font>", ($stm_obj->getValue('aims') ? formatReady($stm_obj->getValue('aims')) : _("n.a.")));
                ?>
                </td>

            </tr>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan="2" valign="top">
                <?
                 printf ("<font size=-1><b>" . _("Inhalte:") . "</b></font><br><font size=-1>%s</font>", ($stm_obj->getValue('topics') ? formatReady($stm_obj->getValue('topics')) : _("n.a.")));
                ?>
                </td>
            </tr>
        </table>
        <table align="center" width="99%" border=0 cellpadding=2 cellspacing=0>
            <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp; <img src="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" width="25" height="10" border="0">
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2  valign="top">
                <br>
                <table width="100%" border=0 cellpadding=2 cellspacing=2>
                <tr>
                <th><font size=-1><?=_("Studiengang")?></font></th>
                <th><font size=-1><?=_("Studienprogramm")?></font></th>
                <th><font size=-1><?=_("Modulart")?></font></th>
                <th><font size=-1><?=_("Version der Prüfungsordnung")?></font></th>
                <th><font size=-1><?=_("Empfohlenes Studiensemester")?></font></th>
                </tr>
                <?foreach($stm_obj->assigns as $assign){?>
                <tr>
                <td class="blank" align="center"><font size=-1><?=htmlReady($assign['abschl_name'])?></font></td>
                <td class="blank" align="center"><font size=-1><?=htmlReady($assign['stg_name'])?></font></td>
                <td class="blank" align="center"><font size=-1><?=htmlReady($assign['type_name'])?></font></td>
                <td class="blank" align="center"><font size=-1><?=htmlReady($assign['p_version_name'])?></font></td>
                <td class="blank" align="center"><font size=-1><?=htmlReady($assign['recommed'])?></font></td>
                </tr>
                <?}?>
                </table>
                <br>
                </td>
        </tr>
        <tr>
                <td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="1%">&nbsp; <img src="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" width="25" height="10" border="0">
                </td>
                <td class="<? echo $cssSw->getClass() ?>" colspan=2  valign="top">
                <br>
                <table  width="100%" border=0 cellpadding=2 cellspacing=2>
                <tr>
                <th colspan="2"><font size=-1><?=_("Lehr- und Lernform")?></font></th>
                <th><font size=-1><?=_("Name der Veranstaltung")?></font></th>
                <th><font size=-1><?=_("Dozenten")?></font></th>
                <th><font size=-1><?=_("SWS")?></font></th>
                <th><font size=-1><?=_("Studentische Arbeitszeit")?></font></th>
                <th><font size=-1><?=_("Semester")?></font></th>
                </tr>
                <?
                $el_group = false;
                $el_id = false;
                $bgcolor = 0;
                foreach(array_keys($stm_obj->elements) as $element_id){
                    if (strcmp($el_group, ($el_group = $stm_obj->elements[$element_id]->getValue('elementgroup')))){
                        echo "<tr><td class=\"blank\" colspan=\"7\"><font size=-1><b>" . ($el_group + 1) .". "._("Modulausprägung")."</b></font></td></tr>";
                    }
                    $dozenten = '';
                    foreach($stm_obj->elements[$element_id]->getValue('dozenten') as $dozent){
                        if ($dozenten) $dozenten .= ', ';
                        $dozenten .= sprintf("<a href=\"about.php?username=%s\">%s</a>", $dozent['username'], htmlReady($dozent['Nachname'] . ', ' . $dozent['Vorname']{0} . '.'));
                    }
                    ?>
                    <tr>
                    <?
                    if ( strcmp($el_id, ($el_id = $stm_obj->elements[$element_id]->getValue('element_id')))
                    && ( ($row_span = $stm_obj->getGroupedElementSemCount($el_group, $el_id)) > 1)){
                        echo '<td style="background-color:yellow;border:1px solid" width="1%" align="center" valign="middle" rowspan="'.$row_span.'">';
                        echo "\n<img src=\"{$GLOBALS['ASSETS_URL']}images/icons/16/grey/info-circle.png\""
                        . tooltip(_("Aus diesen Veranstaltungen muss alternativ gewählt werden."), TRUE, TRUE) . " align=\"absmiddle\">";
                        echo '</td>';
                    }
                    $colspan = ($row_span > 1 ? 1 : 2);
                    ?>
                    <td class="blank" colspan="<?=$colspan?>" align="center"><font size=-1><?=htmlReady($stm_obj->elements[$element_id]->getValue('type_name'));?></font></td>
                    <td class="blank" align="center"><font size=-1>
                    <?if($stm_obj->elements[$element_id]->getValue('sem_id')){
                        ?>
                        <a href="details.php?sem_id=<?=$stm_obj->elements[$element_id]->getValue('sem_id')?>">
                        <?=htmlReady($stm_obj->elements[$element_id]->getValue('seminar_name'))?>
                        </a>
                    <?} else {?>
                        n.a.
                    <?}?>
                    </font></td>
                    <td class="blank" align="center"><font size=-1><?=$dozenten?></font></td>
                    <td class="blank" align="center"><font size=-1><?=htmlReady($stm_obj->elements[$element_id]->getValue('sws'))?></font></td>
                    <td class="blank" align="center"><font size=-1><?=htmlReady($stm_obj->elements[$element_id]->getValue('workload'))?></font></td>
                    <td class="blank" align="center"><font size=-1><?=htmlReady($stm_obj->elements[$element_id]->getValue('semester_txt'))?></font></td>
                    </tr>
                <?}?>
                </table>
                <br>
                </td>
        </tr>
    </table>
    <br>&nbsp;
</td>
</tr>
</table>
</td>
</tr>
</table>
<?php
}
include "lib/include/html_end.inc.php";
// Save data back to database.
page_close();
?>
