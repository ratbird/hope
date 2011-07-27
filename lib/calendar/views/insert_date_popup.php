<?
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* popup calendar for studip
*
* popup calendar for studip
*
* @author           Peter Tienel <pthienel@web.de>, Till Glöggler <tgloeggl@uos.de>
* @access           public
* @module           insert_date_popup.ph
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// insert_date_popup.php
// Copyright (c) 2004 Peter Tienel <pthienel@web.de>, Jens Schmelzer <jens.schmelzer@fh-jena.de>
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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once ('config.inc.php');
require_once ('lib/visual.inc.php');
$atime = (int)$_REQUEST['atime'];
$element_switch = (isset($_REQUEST['element_switch']) ? $_REQUEST['element_switch'] : 0); // Wert fr Auswahl der Feldbezeichner
$element_depending = (isset($_REQUEST['element_depending'])
        && preg_match('!^[0-9a-z_-]{2,40}$!i', $_REQUEST['element_depending'])) ? $_REQUEST['element_depending'] : '';
$form_name = (isset($_REQUEST['form_name'])
     && preg_match('!^[0-9a-z_-]{2,40}$!i', $_REQUEST['form_name'])) ? $_REQUEST['form_name'] : '';
$submit = intval($_REQUEST['submit']);
$c = (isset($_REQUEST['c']))? $_REQUEST['c'] : 0;                   // Zï¿½ler wenn mehrere gleiche Eingabefelder im Zielformular
$mcount = (isset($_REQUEST['mcount']))? $_REQUEST['mcount'] : 1;    // Anzahl der anzuzeigenden Monate
$ss = (isset($_REQUEST['ss']))? sprintf('%02d',$_REQUEST['ss']):''; // Startstunde
$sm = (isset($_REQUEST['sm']))? sprintf('%02d',$_REQUEST['sm']):''; // Startminute
$es = (isset($_REQUEST['es']))? sprintf('%02d',$_REQUEST['es']):''; // Endstunde
$em = (isset($_REQUEST['em']))? sprintf('%02d',$_REQUEST['em']):''; // Endminute
$q = ($ss !== '')? "&ss=$ss&sm=$sm&es=$es&em=$em":'';

// Array mit Standardzeiten vorhanden?
if (isset($GLOBALS['TIME_PRESETS']) && is_array($GLOBALS['TIME_PRESETS']) && count($GLOBALS['TIME_PRESETS']) > 0) {
    $zz = $GLOBALS['TIME_PRESETS'];
    $preset_error = '';
} else {
    include_once('lib/msg.inc.php');
    $zz = array();
    $preset_error = _("Ihr Systemverwalter hat leider keine Standardzeiten vorgegeben.");
}

// Array für javascript aufbereiten
$jsarray = "zz = new Array();\n";
for($z = 0; $z < count($zz); $z++) {
    $jsarray .= "zz[$z] = new Array('".$zz[$z][0]."','".$zz[$z][1]."','".$zz[$z][2]."','".$zz[$z][3]."');\n";
}
$jsarray .= "zz[$z] = new Array('$ss','$sm','$es','$em');\n";

$function_addition = '';
if (strlen($element_switch) > 2 && $form_name != '') {
    $txt_day = $element_switch . '_day' . ($c ? "[$c]" : "");
    $txt_month = $element_switch . '_month' . ($c ? "[$c]" : "");
    $txt_year = $element_switch . '_year' . ($c ? "[$c]" : "");
    if ($element_depending != '') {
        $txt2_day = $element_depending . '_day';
        $txt2_month = $element_depending . '_month';
        $txt2_year = $element_depending . '_year';
        $depending_field_js = "
        if (opener.document.$form_name.elements['$txt2_month']
                && opener.document.$form_name.elements['$txt2_day']
                && opener.document.$form_name.elements['$txt2_year']) {
         opener.document.$form_name.elements['$txt2_month'].value = m;
     opener.document.$form_name.elements['$txt2_day'].value = (d < 10) ? '0' + d : d;
     opener.document.$form_name.elements['$txt2_year'].value = y;
         }
         ";
    } else {
        $depend_field_js = '';
    }
    $kalender = TRUE;
    $zeiten = FALSE;
    $studipform = TRUE;
}
else {
    if(!$form_name) $form_name = 'Formular';
switch ($element_switch){  // Auswahl der Zielparameter
    case 1: // raumzeit.php Einzeltermin bearbeiten, neuer Einzeltermin
        $txt_day   = 'day';
        $txt_month = 'month';
        $txt_year  = 'year';
        $txt_ss = 'start_stunde';
        $txt_sm = 'start_minute';
        $txt_es = 'end_stunde';
        $txt_em = 'end_minute';
        $zeiten = true;
        $kalender = true;
        $form_name = 'EditCycle';
        $function_addition = '_noform';
        break;

    case 2:  // raumzeit.php Metadate bearbeiten
        $txt_ss = "start_stunde";
        $txt_sm = "start_minute";
        $txt_es = "end_stunde";
        $txt_em = "end_minute";
        $zeiten = true;
        $kalender = false;
        $form_name = 'EditCycle';
        break;
    case 3: // raumzeit.php neues Metadate, Metadate bearbeiten
        $txt_ss = "start_stunde";
        $txt_sm = "start_minute";
        $txt_es = "end_stunde";
        $txt_em = "end_minute";
        $zeiten = true;
        $kalender = false;
        $function_addition = '_noform';
        break;
    case 4:  //admin_seminare_assi.php regelmäßige Veranstaltungen (kein Kalender)
        $txt_ss = "term_turnus_start_stunde[$c]";
        $txt_sm = "term_turnus_start_minute[$c]";
        $txt_es = "term_turnus_end_stunde[$c]";
        $txt_em = "term_turnus_end_minute[$c]";
        $zeiten = true;
        $kalender = false;
        break;
    case 5: // admin_seminare_assi.php unregelmäßige Veranstaltungen
        $txt_day   = "term_tag[$c]";
        $txt_month = "term_monat[$c]";
        $txt_year  = "term_jahr[$c]";
        $txt_ss = "term_start_stunde[$c]";
        $txt_sm = "term_start_minute[$c]";
        $txt_es = "term_end_stunde[$c]";
        $txt_em = "term_end_minute[$c]";
        $zeiten = true;
        $kalender = true;
        break;
    case 6: // admin_seminare_assi.php Vorbesprechung
        $txt_day   = 'vor_tag';
        $txt_month = 'vor_monat';
        $txt_year  = 'vor_jahr';
        $txt_ss = 'vor_stunde';
        $txt_sm = 'vor_minute';
        $txt_es = 'vor_end_stunde';;
        $txt_em = 'vor_end_minute';
        $zeiten = true;
        $kalender = true;
        break;
    case 7:  // admin_metadates.php Startdatum Veranstaltungsbeginn
        $txt_day   = 'tag';
        $txt_month = 'monat';
        $txt_year  = 'jahr';
        $zeiten = false;
        $kalender = true;
        break;
    case 8:  // resources.php&view=edit_object_assign
        $txt_day   = 'change_schedule_day';
        $txt_month = 'change_schedule_month';
        $txt_year  = 'change_schedule_year';
        $txt_ss = 'change_schedule_start_hour';
        $txt_sm = 'change_schedule_start_minute';
        $txt_es = 'change_schedule_end_hour';
        $txt_em = 'change_schedule_end_minute';
        $zeiten = true;
        $kalender = true;
        break;
    case 11: // calendar.php (edit.inc.php) End of recurrence
        $txt_month = 'exp_month';
        $txt_day   = 'exp_day';
        $txt_year  = 'exp_year';
        $zeiten = false;
        $kalender = true;
        break;
    case 12: // calendar.php (edit.inc.php) exceptions
        $txt_month = 'exc_month';
        $txt_day   = 'exc_day';
        $txt_year  = 'exc_year';
        $zeiten = false;
        $kalender = true;
        break;
    case 20:  // admin_admission
        $txt_day   = 'adm_s_tag';
        $txt_month = 'adm_s_monat';
        $txt_year  = 'adm_s_jahr';
        $zeiten = false;
        $kalender = true;
        break;
    case 21:  // admin_admission
        $txt_day   = 'adm_e_tag';
        $txt_month = 'adm_e_monat';
        $txt_year  = 'adm_e_jahr';
        $zeiten = false;
        $kalender = true;
        break;
    case 22:  // admin_admission
        $txt_day   = 'adm_tag';
        $txt_month = 'adm_monat';
        $txt_year  = 'adm_jahr';
        $zeiten = false;
        $kalender = true;
        break;
    case 51: // calendar.php (edit.inc.php) Enddate
        $txt_month = 'end_month';
        $txt_day   = 'end_day';
        $txt_year  = 'end_year';
        $zeiten = false;
        $kalender = true;
        break;
    case 0:
    case 50:
    default: // calendar.php (edit.inc.php) Startdate
        $txt_month = 'start_month';
        $txt_day   = 'start_day';
        $txt_year  = 'start_year';
        $zeiten = false;
        $kalender = true;

}
}

$title = _("Kalender");
$resize = '';
if ($zeiten && !$kalender) {  // popup Fenster verkleinern wenn kein Kalender
    $resize = 'window.resizeTo('.(($auth->auth["xres"] > 650)? 780 : 600).',160);'. "\n";
    $resize .= 'window.moveBy(0,330);'."\n";
}
if (intval($submit) == 1) {
    $do_submit = 'opener.document.'.$form_name.'.submit();';
    $submit = '1';
} else {
    $do_submit = '';
    $submit = '';
}
if ($preset_error != '') $zeiten = false;
echo <<<EOT
<!DOCTYPE html>
<html><head>
<title>$title</title>
<link rel="stylesheet" type="text/css" href="{$GLOBALS['ASSETS_URL']}stylesheets/style.css">
<script type="text/javascript">
<!--
window.setTimeout("window.close()", 120000); // Fenster automatisch wieder schließen :-)
$resize
function insert_time_noform() {
    if (opener) {
        $jsarray
        var t;
    var c = 999;
    for (i=0; i < document.forms['TimeForm'].elements.timei.length; i++){
        if (document.forms['TimeForm'].elements.timei[i].checked == true) c = i;
    }
    if(c != 999){
            t = opener.document.getElementById('$txt_ss');
            t.value = zz[c][0];
            t = opener.document.getElementById('$txt_sm');
            t.value = zz[c][1];
            t = opener.document.getElementById('$txt_es');
            t.value = zz[c][2];
            t = opener.document.getElementById('$txt_em');
            t.value = zz[c][3];
    }
    }
    window.close();
}

function insert_date_noform(m, d, y) {
    if (opener) {
        var t;
        t = opener.document.getElementById('$txt_month');
        t.value = m;
        t = opener.document.getElementById('$txt_day');
        t.value = d;
        t = opener.document.getElementById('$txt_year');
        t.value = y;
    }
    if (document.forms['TimeForm']) {
        insert_time_noform();
    } else {
        window.close();
    }
}

function insert_time () {
   if (opener && document.forms['TimeForm']) {
     $jsarray
     var c = 999;
     for (i=0; i < document.forms['TimeForm'].elements.timei.length; i++){
        if (document.forms['TimeForm'].elements.timei[i].checked == true) c = i;
     }
     if(c != 999){
        opener.document.$form_name.elements['$txt_ss'].value = zz[c][0];
        opener.document.$form_name.elements['$txt_sm'].value = zz[c][1];
        opener.document.$form_name.elements['$txt_es'].value = zz[c][2];
        opener.document.$form_name.elements['$txt_em'].value = zz[c][3];
     }
   }
   window.close();
}
function insert_date (m, d, y) {
   if (opener) {
     opener.document.$form_name.elements['$txt_month'].value = m;
     opener.document.$form_name.elements['$txt_day'].value = (d < 10) ? '0' + d : d;
     opener.document.$form_name.elements['$txt_year'].value = y;
         $depending_field_js;
         $do_submit;
   }
   if (document.forms['TimeForm']) {
      insert_time();
   } else {
      window.close();
   }
}
-->
</script>
</head>
<body{$onunload}>
EOT;

require_once($RELATIVE_PATH_CALENDAR . '/calendar_visual.inc.php');

$imt =  (isset($_REQUEST['imt']) && $_REQUEST['imt']) ? intval($_REQUEST['imt']) : time();

$js['function'] = 'insert_date'.$function_addition;

// mehr als einen Monat anzeigen?
if ($mcount > 3) {
    if ($mcount > 12) $mcount = 12;
    if ($mcount % 2 == 1) $mcount++; // nur gerade Werte erlaubt
    $mcounth = $mcount / 2;
    $atimex = getdate($imt);
    $i = 0;
    if (!$kalender && $preset_error != '') {
        parse_window ('info§' .$preset_error,'§', '', '<div align="center"><a href="javascript:window.close();">' . makeButton('schliessen', 'img').'</a></div>');
    } else {
        echo '<table class="blank" border=0 align="center"><tr valign=top>', "\n";
        while ($kalender && ($i < $mcount)) {
            if (($i % $mcounth == 0) && $i > 0) echo '</tr><tr valign=top>', "\n";
            echo '<td class="blank">';
            echo includeMonth(mktime(12,0,0,$atimex['mon'] + $i++,10,$atimex['year']), 'javascript:void(0);//', 'NONAV', $js, $atime);
            echo '</td>';
        }
        if (!$kalender) echo '<td class="blank" colspan="',$mcounth,'">&nbsp;</td>';
        echo '</tr>', "\n";
        // time row
        if ($zeiten) {
            echo '<tr><td class="blank" colspan="',$mcounth,'" align=center><form name="TimeForm" action="javascript:void(0);">';
            echo '<table class="tabdaterow" cellspacing="0" cellpadding="0" align="center"><tr>', "\n";
            $sel = 0;
            for($z = 0; $z < count($zz); $z++) {
                if ($zz[$z][0] == $ss && $zz[$z][1] == $sm && $zz[$z][2] == $es && $zz[$z][3] == $em ){
                    $selx = 'tddaterowpx';
                    $check = ' checked';
                    $sel = 1;
                } else {
                    $selx = 'tddaterowp';
                    $check = '';
                }
                $txtzeit =  $zz[$z][0].':'.$zz[$z][1].'&nbsp;-&nbsp;'.$zz[$z][2].':'.$zz[$z][3];
                $txtzeitc =  $zz[$z][0].':'.$zz[$z][1].' - '.$zz[$z][2].':'.$zz[$z][3] .' ' . _("Uhr") . ' ' . _("eintragen");
                echo '<td class="', $selx, '" ', tooltip($txtzeitc), '><input type="radio" name="timei" value="',$z,'"',$check,'>', $txtzeit, '</td>', "\n";
            }
            if ($sel == 0 && $ss != '' && $sm != '' && $es != '' && $em != '') {
                $txtzeit =  $ss.':'.$sm.'&nbsp;-&nbsp;'.$es.':'.$em;
                $txtzeitc =  _("zurücksetzen auf") .' '. $ss.':'.$sm.' - '.$es.':'.$em .' ' . _("Uhr");
                echo '<td class="tddaterowpx" ', tooltip($txtzeitc), '><input type="radio" name="timei" value="',$z,'" checked>', $txtzeit, '&nbsp;</td>', "\n";
            }
            echo '</tr></table></form>';
            echo '</td></tr>', "\n";
        } elseif($preset_error != '') {
            my_info($preset_error,'blank',$mcounth, FALSE);
        }

        // navigation arrows

        echo '<tr>';
        $zeiten_buttons = '<a href="javascript:insert_time'.$function_addition.'();">'. makeButton('uebernehmen', 'img') . '</a> &nbsp; <a href="javascript:window.close();">' . makeButton('abbrechen', 'img').'</a>';
        if ($kalender) {
            echo '<td class="blank">&nbsp;<a href="',$PHP_SELF,'?imt=',mktime(0,0,0,$atimex['mon'] - $mcount,10,$atimex['year']);
            echo ($form_name ? "&form_name=$form_name" : '');
            echo ($submit ? '&submit=1' : '');
            echo '&mcount=',$mcount,'&element_switch=',$element_switch,'&c=',$c,'&atime=',$atime,$q,'">';
            echo '<img border="0" src="' . Assets::image_path('icons/16/blue/arr_eol-left.png') . '"';
            echo tooltip($mcount . ' ' . _('Monate zurück')),' border="0"></a>';
            echo '&nbsp;<a href="',$PHP_SELF,'?imt=',mktime(0,0,0,$atimex['mon'] - $mcounth,10,$atimex['year']);
            echo ($form_name ? "&form_name=$form_name" : '');
            echo ($submit ? '&submit=1' : '');
            echo '&mcount=',$mcount,'&element_switch=',$element_switch,'&c=',$c,'&atime=',$atime,$q,'">';
            echo '<img border="0" src="' . Assets::image_path('icons/16/blue/arr_2left.png') . '"';
            echo tooltip($mcounth .' ' . _('Monate zurück')),' border="0"></a></td>', "\n";
            if ($mcounth - 2 > 0) {
                echo '<td class="blank" colspan="' , ($mcounth - 2) , '" align=center>';
                if ($zeiten) echo $zeiten_buttons;
                echo '&nbsp;</td>';
            }
            echo '<td class="blank" align="right"><a href="',$PHP_SELF,'?imt=';
            echo mktime(0,0,0,$atimex['mon'] + $mcounth,10,$atimex['year']),'&mcount=',$mcount;
            echo ($form_name ? "&form_name=$form_name" : '');
            echo ($submit ? '&submit=1' : '');
            echo '&element_switch=',$element_switch,'&c=',$c,'&atime=',$atime,$q,'">';
            echo '<img border="0" src="' . Assets::image_path('icons/16/blue/arr_2right.png') . '"';
            echo tooltip($mcounth . ' ' . _('Monate vor')),' border="0"></a>&nbsp;', "\n";
            echo '<a href="',$PHP_SELF,'?imt=',mktime(0,0,0,$atimex['mon'] + $mcount,10,$atimex['year']);
            echo ($form_name ? "&form_name=$form_name" : '');
            echo ($submit ? '&submit=1' : '');
            echo '&mcount=',$mcount,'&element_switch=',$element_switch,'&c=',$c,'&atime=',$atime,$q,'">';
            echo '<img border="0" src="' . Assets::image_path('icons/16/blue/arr_eol-right.png') . '"';
            echo tooltip($mcount .' ' . _('Monate vor')),' border="0"></a>&nbsp;</td>';
        } elseif ($zeiten) {
            echo '<td class="blank" colspan="',$mcounth,'" align="center">', $zeiten_buttons, "</td>\n";
        }
        echo '</tr></table>', "\n";
    }
} else { // nur einen Monat anzeigen
    if ($studipform) {
        echo includeMonth($imt, "?form_name=$form_name&submit=$submit&element_switch=$element_switch&c=$c", 'NOKW', $js, $atime);
    } else {
        echo includeMonth($imt, "?element_switch=$element_switch&c=$c", 'NOKW', $js, $atime);
    }
}
echo "</body>\n</html>";

page_close();
?>
